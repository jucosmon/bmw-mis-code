<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, watch } from 'vue';

const page = usePage();
const formErrors = ref(null);
const previewImages = ref([]);
const locationSource = ref('manual'); // 'manual' or 'gps'
const isGeocodingInProgress = ref(false);
const showMap = ref(false);
const props = defineProps({
    municipalities: {
        type: Array,
        required: true
    },
    barangays: {
        type: Array,
        required: true
    }
});

const form = useForm({
  certainty_level: 5,
  date: new Date().toISOString().split('T')[0],
  time: new Date().toTimeString().split(' ')[0],
  species_involved: '',
  quantity: 1,
  condition: '',
  latitude: '',
  longitude: '',
  sea_state: '',
  weather: '',
  beach_type: '',
  detailed_location: '',
  more_information: '',
  municipality_id: '',
  barangay_id: '',
  mediaFiles: [],
});

const backRoute = computed(() => route('stranded.incident.index'));
const createRoute = computed(() => route('stranded.incident.create'));

// Watch for changes in municipality_id after form is initialized
watch(() => form.municipality_id, (newValue) => {
  if (newValue) {
    form.barangay_id = ''; // Reset barangay selection when municipality changes
  }
});

const handleFileChange = (event) => {
  const files = event.target.files;
  form.mediaFiles = Array.from(files);

  previewImages.value = Array.from(files).map((file) => {
    return URL.createObjectURL(file);
  });
};

const removeImage = (index) => {
  previewImages.value.splice(index, 1);
  form.mediaFiles.splice(index, 1);
};

// Nominatim reverse geocoding function
const reverseGeocode = async (latitude, longitude) => {
  isGeocodingInProgress.value = true;

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`);
    const data = await response.json();

    console.log("Nominatim response:", data);

    // Extract address components
    const address = data.address;

    // Update form with location details
    // Note: You'll need to map Nominatim's address components to your database fields
    // This is an example and might need adjustments based on your location data

    // Try to find the most relevant administrative level for municipality
    // In the Philippines, this could be city, town, or municipality
    let municipalityName = address.city || address.town || address.municipality;

    // For barangay (which is specific to the Philippines)
    // It might be stored under different keys depending on the region
    let barangayName = address.quarter || address.village || address.suburb || address.neighbourhood || address.hamlet;

    console.log("Found municipality:", municipalityName);
    console.log("Found barangay:", barangayName);

    // Clear existing selections first
    form.municipality_id = '';
    form.barangay_id = '';

    // Find matching municipality in our database
    if (municipalityName) {
      const matchedMunicipality = props.municipalities.find(m =>
        m.name.toLowerCase() === municipalityName.toLowerCase()
      );

      if (matchedMunicipality) {
        form.municipality_id = matchedMunicipality.id;

        // Find matching barangay in our database
        if (barangayName) {
          const matchedBarangay = filteredBarangays.value.find(b =>
            b.name.toLowerCase() === barangayName.toLowerCase()
          );

          if (matchedBarangay) {
            form.barangay_id = matchedBarangay.id;
          }
        }
      }
    }

    // Set detailed location from Nominatim
    form.detailed_location = data.display_name || '';

  } catch (error) {
    console.error("Error with reverse geocoding:", error);
    form.municipality_id = '';
    form.barangay_id = '';
  } finally {
    isGeocodingInProgress.value = false;
  }
};

// Add new refs for form steps
const currentStep = ref(1);
const totalSteps = 4;
const isSubmitting = ref(false);

const steps = [
  { number: 1, title: 'Basic Information' },
  { number: 2, title: 'Location Details' },
  { number: 3, title: 'Environmental Data' },
  { number: 4, title: 'Media & Additional Info' },
];

const goToStep = (step) => {
  if (step >= 1 && step <= totalSteps) {
    currentStep.value = step;
  }
};

// Add new refs for step validation
const isStepValid = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.date &&
             form.time &&
             form.species_involved &&
             form.quantity > 0 &&
             form.condition &&
             form.certainty_level;
    case 2:
      return (form.municipality_id && form.barangay_id && form.detailed_location) ||
             (locationSource.value === 'gps' && form.latitude && form.longitude);
    case 3:
      return true; // Make step 3 always valid since environmental fields are optional
    case 4:
      return true; // Optional fields
    default:
      return false;
  }
});

// Modify the isFieldRequired function in the script section
const isFieldRequired = (fieldName) => {
  const requiredFields = {
    date: true,
    time: true,
    species_involved: true,
    quantity: true,
    condition: true,
    detailed_location: true,
    certainty_level: true,
    // Make environmental fields not required
    sea_state: false,
    weather: false,
    beach_type: false
  };
  return requiredFields[fieldName] || false;
};

const nextStep = () => {
  if (currentStep.value < totalSteps && isStepValid.value) {
    currentStep.value++;
  }
};

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

// Enhance submit function with loading state
const submit = () => {
  if (createRoute.value) {
    isSubmitting.value = true;
    form.post(createRoute.value, {
      onSuccess: () => {
        formErrors.value = null;
        isSubmitting.value = false;
      },
      onError: (errors) => {
        formErrors.value = errors;
        isSubmitting.value = false;
      },
    });
  }
};

// Map references
const map = ref(null);
const marker = ref(null);

const initializeMap = () => {
  // Default to Philippines if no coordinates are set
  const defaultLat = 12.8797;
  const defaultLng = 121.7740;

  map.value = L.map('map').setView([form.latitude || defaultLat, form.longitude || defaultLng], 6);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map.value);

  marker.value = L.marker([form.latitude || defaultLat, form.longitude || defaultLng], {
    draggable: true,
  }).addTo(map.value);

  marker.value.on('dragend', async (e) => {
    const { lat, lng } = e.target.getLatLng();
    form.latitude = lat;
    form.longitude = lng;

    if (locationSource.value === 'gps') {
      await reverseGeocode(lat, lng);
    }
  });

  map.value.on('click', async (e) => {
    const { lat, lng } = e.latlng;
    form.latitude = lat;
    form.longitude = lng;
    marker.value.setLatLng([lat, lng]);

    if (locationSource.value === 'gps') {
      await reverseGeocode(lat, lng);
    }
  });
};

// Use current location
const setLocationFromMap = async () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude } = position.coords;
                locationSource.value = 'manual'; // Changed from 'gps' to 'manual'

                form.latitude = latitude;
                form.longitude = longitude;
                showMap.value = true;

                await nextTick();
                if (!map.value) {
                    await initializeMap();
                } else {
                    map.value.setView([latitude, longitude], 13);
                    marker.value.setLatLng([latitude, longitude]);
                }
                await reverseGeocode(latitude, longitude);
            },
            () => {
                alert('Failed to fetch current location. Please allow location access.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
    }
};

// Add function to remove GPS location
const removeGpsLocation = () => {
  showMap.value = false;
  locationSource.value = 'manual';
  form.latitude = '';
  form.longitude = '';
  form.municipality_id = '';
  form.barangay_id = '';
  form.detailed_location = '';
  cleanupMap();
};

// Switch to manual selection mode
const useManualSelection = () => {
  locationSource.value = 'manual';
};

// Add cleanup function for map
const cleanupMap = () => {
  if (map.value) {
    map.value.remove();
    map.value = null;
    marker.value = null;
  }
};

// Add watch for showMap to ensure proper cleanup
watch(showMap, (newValue) => {
  if (!newValue) {
    cleanupMap();
  }
});

// Add new computed properties for field validation
const getFieldState = (fieldName) => {
  return {
    isRequired: isFieldRequired(fieldName),
    isEmpty: isFieldEmpty(fieldName),
    isTouched: isFieldTouched.value[fieldName]
  };
};

const isFieldEmpty = (fieldName) => {
  return !form[fieldName] || form[fieldName] === '';
};

// Track touched fields
const isFieldTouched = ref({});

const markFieldAsTouched = (fieldName) => {
  isFieldTouched.value[fieldName] = true;
};

// Add this computed property for filtered barangays
const filteredBarangays = computed(() => {
  if (!form.municipality_id) return [];
  return props.barangays.filter(barangay =>
    barangay.municipality_id === form.municipality_id
  );
});

// Update resetToOriginal function
const resetToOriginal = (e) => {
    e.preventDefault();
    locationSource.value = 'original';

    if (originalLocation.value.latitude && originalLocation.value.longitude) {
        showMap.value = true;
        form.latitude = originalLocation.value.latitude;
        form.longitude = originalLocation.value.longitude;
        form.municipality_id = originalLocation.value.municipality_id;
        form.barangay_id = originalLocation.value.barangay_id;
        form.detailed_location = originalLocation.value.detailed_location;

        // Ensure map is initialized after setting coordinates
        nextTick(() => {
            if (!map.value) {
                initializeMap();
            } else {
                map.value.setView([form.latitude, form.longitude], 13);
                marker.value.setLatLng([form.latitude, form.longitude]);
                map.value.invalidateSize();
            }
        });
    }
};

// Add geocodeLocation function
const geocodeLocation = async (municipality, barangay) => {
    try {
        const query = `${barangay ? barangay + ', ' : ''}${municipality}, Bohol, Philippines`;
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data && data.length > 0) {
            const { lat, lon } = data[0];
            form.latitude = parseFloat(lat);
            form.longitude = parseFloat(lon);
            form.detailed_location = data[0].display_name || '';

            cleanupMap();
            showMap.value = true;

            await nextTick();
            await initializeMap();
        } else {
            showMap.value = false;
            form.latitude = '';
            form.longitude = '';
            form.detailed_location = '';
            cleanupMap();
        }
    } catch (error) {
        console.error('Geocoding error:', error);
        showMap.value = false;
        form.latitude = '';
        form.longitude = '';
        form.detailed_location = '';
        cleanupMap();
    }
};

// Update municipality and barangay watcher
watch([() => form.municipality_id, () => form.barangay_id],
    async ([newMunicipality, newBarangay]) => {
        if (newMunicipality) {
            const municipality = props.municipalities.find(m => m.id === newMunicipality);
            const barangay = newBarangay ? props.barangays.find(b => b.id === newBarangay) : null;

            if (municipality) {
                locationSource.value = 'manual';
                await geocodeLocation(municipality.name, barangay?.name);
            } else {
                showMap.value = false;
                form.latitude = '';
                form.longitude = '';
                form.detailed_location = '';
                cleanupMap();
            }
        } else {
            showMap.value = false;
            form.latitude = '';
            form.longitude = '';
            form.detailed_location = '';
            cleanupMap();
        }
    },
    { immediate: false }
);

// Add map visibility watcher
watch(showMap, async (newValue) => {
    if (!newValue) {
        cleanupMap();
    } else {
        await nextTick();
        if (form.latitude && form.longitude) {
            await initializeMap();
        }
    }
});
</script>

<template>
  <Head title="Create Stranded Incident" />

  <Sidebar>
    <template #header>
        <h1 class="text-xl font-semibold text-gray-900">Report Stranded Incident</h1>
    </template>

    <div class="container mx-auto px-4 py-8">
      <!-- Progress Steps -->
      <div class="max-w-4xl mx-auto mb-8 px-4">
        <div class="hidden sm:flex justify-between items-center">
          <!-- Existing progress steps for desktop -->
          <div v-for="step in steps" :key="step.number"
               class="flex-1 relative">
            <div class="flex items-center">
              <button @click="goToStep(step.number)"
                      :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center transition-all',
                        currentStep >= step.number
                          ? 'bg-indigo-600 text-white'
                          : 'bg-gray-200 text-gray-500'
                      ]">
                {{ step.number }}
              </button>
              <div class="flex-1 h-1 mx-2"
                   :class="currentStep >= step.number ? 'bg-indigo-600' : 'bg-gray-200'"></div>
            </div>
            <div class="text-xs text-center mt-2">{{ step.title }}</div>
          </div>
        </div>
        <!-- Mobile progress indicator -->
        <div class="sm:hidden text-center">
          <p class="text-lg font-medium text-gray-900">Step {{ currentStep }} of {{ totalSteps }}</p>
          <p class="text-sm text-gray-500">{{ steps[currentStep - 1].title }}</p>
        </div>
      </div>

      <!-- Main Form Container -->
      <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <form @submit.prevent="submit" class="divide-y divide-gray-200">
          <!-- Error Messages -->
          <div v-if="formErrors"
               class="p-4 bg-red-50 border-l-4 border-red-400">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                <div class="mt-2 text-sm text-red-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 1: Basic Information -->
          <div v-show="currentStep === 1" class="p-8 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div class="space-y-2">
                <InputLabel for="date" class="flex items-center">
                  <span>Date of Incident</span>
                  <span v-if="getFieldState('date').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <TextInput
                  required
                  id="date"
                  type="date"
                  v-model="form.date"
                  @blur="markFieldAsTouched('date')"
                  :class="{
                    'border-red-300 bg-red-50': getFieldState('date').isTouched && getFieldState('date').isEmpty,
                    'border-green-300 bg-green-50': form.date
                  }"
                  class="w-full transition-all"
                />
                <div v-if="getFieldState('date').isTouched && getFieldState('date').isEmpty"
                     class="text-sm text-red-600">
                  This field is required
                </div>
                <InputError :message="form.errors.date" />
              </div>

              <div class="space-y-2">
                <InputLabel for="time" class="flex items-center">
                  <span>Time of the Incident</span>
                  <span v-if="getFieldState('time').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <TextInput
                  required
                  id="time"
                  type="time"
                  v-model="form.time"
                  @blur="markFieldAsTouched('time')"
                  :class="{
                    'border-red-300 bg-red-50': getFieldState('time').isTouched && getFieldState('time').isEmpty,
                    'border-green-300 bg-green-50': form.time
                  }"
                  class="w-full transition-all"
                  step="1"
                />
                <div v-if="getFieldState('time').isTouched && getFieldState('time').isEmpty"
                     class="text-sm text-red-600">
                  This field is required
                </div>
                <InputError class="mt-2" :message="form.errors.time" />
              </div>

              <div class="sm:col-span-2 space-y-2">
                <InputLabel for="species_involved" class="flex items-center">
                  <span>Describe what species are involved</span>
                  <span v-if="getFieldState('species_involved').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <TextInput
                  id="species_involved"
                  v-model="form.species_involved"
                  required
                  :class="{
                    'border-red-300 bg-red-50': getFieldState('species_involved').isTouched &&
                                               getFieldState('species_involved').isEmpty,
                    'border-green-300 bg-green-50': form.species_involved
                  }"
                  @blur="markFieldAsTouched('species_involved')"
                  class="w-full transition-all duration-200"
                  placeholder="e.g. Dolphins, Large Whales, Sharks"
                />
                <div v-if="getFieldState('species_involved').isTouched && getFieldState('species_involved').isEmpty"
                     class="text-sm text-red-600">
                  This field is required
                </div>
                <InputError class="mt-2" :message="form.errors.species_involved" />
              </div>

              <div class="space-y-2">
                <InputLabel for="quantity" class="flex items-center">
                  <span>Species Quantity</span>
                  <span v-if="getFieldState('quantity').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <input
                  required
                  id="quantity"
                  type="number"
                  min="1"
                  v-model="form.quantity"
                  @blur="markFieldAsTouched('quantity')"
                  :class="{
                    'border-red-300 bg-red-50': getFieldState('quantity').isTouched && (!form.quantity || form.quantity < 1),
                    'border-green-300 bg-green-50': form.quantity && form.quantity >= 1
                  }"
                  class="w-full transition-all"
                />
                <div v-if="getFieldState('quantity').isTouched && (!form.quantity || form.quantity < 1)"
                     class="text-sm text-red-600">
                  Please enter a valid quantity (minimum 1)
                </div>
                <InputError class="mt-2" :message="form.errors.quantity" />
              </div>
              <div class="space-y-2">
                <InputLabel for="condition" class="flex items-center">
                  <span>Condition</span>
                  <span v-if="getFieldState('condition').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <select
                  v-model="form.condition"
                  @blur="markFieldAsTouched('condition')"
                  :class="{
                    'border-red-300 bg-red-50': getFieldState('condition').isTouched &&
                                               getFieldState('condition').isEmpty,
                    'border-green-300 bg-green-50': form.condition
                  }"
                  class="w-full px-4 py-2 border rounded-lg transition-all duration-200"
                  required
                >
                  <option value="" disabled>Select an option</option>
                  <option value="alive">Alive</option>
                  <option value="dead">Dead</option>
                </select>
                <div v-if="getFieldState('condition').isTouched && getFieldState('condition').isEmpty"
                     class="text-sm text-red-600">
                  Please select a condition
                </div>
                <InputError class="mt-2" :message="form.errors.condition" />
              </div>
              <div class="sm:col-span-2 space-y-2">
                <InputLabel for="certainty_level" class="flex items-center">
                  <span>Certainty Level (1-10)</span>
                  <span v-if="getFieldState('certainty_level').isRequired" class="text-red-500 ml-1">*</span>
                </InputLabel>
                <div class="relative">
                  <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600" />
                  <div class="flex justify-between px-2 text-xs text-gray-600">
                    <span>Not Sure</span>
                    <span>Very Sure</span>
                  </div>
                  <div class="text-center text-lg font-medium text-indigo-600 mt-2">
                    {{ form.certainty_level }}
                  </div>
                </div>
                <InputError class="mt-2" :message="form.errors.certainty_level" />
              </div>

            </div>
          </div>

          <!-- Step 2: Location Details -->
          <div v-show="currentStep === 2" class="p-8 space-y-6">
            <!-- GPS Location Button -->
            <div class="flex justify-center space-x-4 mb-4">
              <button
                @click.prevent="setLocationFromMap"
                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors duration-200"
                :class="{ 'bg-indigo-800': showMap }"
              >
                <div class="flex items-center justify-center space-x-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Use Current Location</span>
                </div>
              </button>
            </div>

            <!-- Enhanced map container -->
            <div v-if="showMap" class="relative rounded-xl overflow-hidden shadow-lg">
              <div id="map" class="h-[500px] w-full z-0"></div>
              <div class="absolute top-4 right-4 space-y-2">
                <button
                    @click="removeGpsLocation"
                    class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors shadow-lg"
                >
                <span class="material-icons material-symbols-outlined">
                    location_off
                </span>
                </button>
              </div>
            </div>

            <div class="space-y-2">
              <InputLabel for="municipality_id" value="Municipality" />
              <select v-model="form.municipality_id" @change="fetchBarangays(form.municipality_id)" class="w-full" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                <option value="" disabled>Select a municipality</option>
                <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
                  {{ municipality.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="form.errors.municipality_id" />
            </div>

            <div class="space-y-2">
              <InputLabel for="barangay_id" value="Barangay" />
              <select v-model="form.barangay_id" class="w-full" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                <option value="" disabled>Select a barangay</option>
                <option v-for="barangay in filteredBarangays" :key="barangay.id" :value="barangay.id">
                  {{ barangay.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="form.errors.barangay_id" />
            </div>

            <div class="sm:col-span-2 space-y-2">
              <InputLabel for="detailed_location" class="flex items-center">
                <span>Detailed Location</span>
                <span v-if="getFieldState('detailed_location').isRequired" class="text-red-500 ml-1">*</span>
              </InputLabel>
              <textarea
                required
                id="detailed_location"
                v-model="form.detailed_location"
                @blur="markFieldAsTouched('detailed_location')"
                :class="{
                  'border-red-300 bg-red-50': getFieldState('detailed_location').isTouched &&
                                             getFieldState('detailed_location').isEmpty,
                  'border-green-300 bg-green-50': form.detailed_location
                }"
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y transition-all"
                placeholder="Please add more details of the exact location"
              ></textarea>
              <div v-if="getFieldState('detailed_location').isTouched && getFieldState('detailed_location').isEmpty"
                   class="text-sm text-red-600">
                This field is required
              </div>
              <InputError class="mt-2" :message="form.errors.detailed_location" />
            </div>
          </div>

          <!-- Step 3: Environmental Data -->
          <div v-show="currentStep === 3" class="p-8 space-y-6">
            <div class="space-y-2">
              <InputLabel for="sea_state" value="Sea State" />
              <select v-model="form.sea_state" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-white shadow-sm hover:border-indigo-300">
                <option value="">Select an option (optional)</option>
                <option value="calm">Calm</option>
                <option value="moderate">Moderate</option>
                <option value="rough">Rough</option>
              </select>
              <InputError class="mt-2" :message="form.errors.sea_state" />
            </div>
            <div class="space-y-2">
              <InputLabel for="weather" value="Weather" />
              <select v-model="form.weather" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-white shadow-sm hover:border-indigo-300">
                <option value="">Select an option (optional)</option>
                <option value="sunny">Sunny</option>
                <option value="cloudy">Cloudy</option>
                <option value="rainy">Rainy</option>
              </select>
              <InputError class="mt-2" :message="form.errors.weather" />
            </div>
            <div class="space-y-2">
              <InputLabel for="beach_type" value="Beach type" />
              <select v-model="form.beach_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-white shadow-sm hover:border-indigo-300">
                <option value="">Select an option (optional)</option>
                <option value="mangrove">Mangrove</option>
                <option value="rocky">Rocky</option>
                <option value="sandy">Sandy</option>
                <option value="reef">Reef</option>
              </select>
              <InputError class="mt-2" :message="form.errors.beach_type" />
            </div>
          </div>

          <!-- Step 4: Media & Additional Info -->
          <div v-show="currentStep === 4" class="p-8 space-y-6">
            <div class="sm:col-span-2 space-y-2">
              <InputLabel for="more_information" value="More Information of the Incident" />
              <textarea
                id="more_information"
                v-model="form.more_information"
                autocomplete="more_information"
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                placeholder="Please share more information of the incident"
              ></textarea>
              <InputError class="mt-2" :message="form.errors.more_information" />
            </div>

            <div class="sm:col-span-2 space-y-2">
              <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
              <input type="file" accept="image/*,video/*" id="mediaFiles" @change="handleFileChange" multiple class="file-input w-full"/>
              <div v-if="previewImages.length" class="mt-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <div v-for="(img, index) in previewImages" :key="index" class="relative aspect-square">
                  <img :src="img" alt="Preview" class="w-full h-full object-cover rounded-lg"/>
                  <button @click="removeImage(index)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-lg hover:bg-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
              <InputError class="mt-2" :message="form.errors.mediaFiles" />
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="px-4 sm:px-8 py-4 bg-gray-50 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <button
              type="button"
              @click="previousStep"
              v-show="currentStep > 1"
              class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
              Previous
            </button>
            <div class="flex space-x-4 w-full sm:w-auto">
              <button
                v-if="currentStep < totalSteps"
                type="button"
                @click="nextStep"
                :disabled="!isStepValid"
                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Next
              </button>
              <button
                v-else
                type="submit"
                :disabled="isSubmitting"
                class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg v-if="isSubmitting" class="animate-spin h-5 w-5 mr-2 inline-block" viewBox="0 0 24 24">
                  <!-- Loading spinner SVG -->
                </svg>
                {{ isSubmitting ? 'Submitting...' : 'Submit Report' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
/* Base styles */
.file-input {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 40px;
  color: white;
}

/* Hide the file name text after file is selected */
.file-input::-webkit-file-upload-button {
  visibility: hidden;
}

/* Optional: custom styling for the file input button */
.file-input::before {
  content: "Choose Files";
  display: inline-block;
  background-color: indigo;
  color: white;
  padding: 10px;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
}

/* Form elements base styling */
.form-base {
  @apply w-full px-4 py-2 border border-gray-300 rounded-lg
         focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
         transition-all duration-200 bg-white shadow-sm
         hover:border-indigo-300;
}

/* Range input custom styling */
input[type="range"] {
  @apply appearance-none bg-gray-200 h-2 rounded-lg;
}

input[type="range"]::-webkit-slider-thumb {
  @apply appearance-none w-6 h-6 bg-indigo-600
         rounded-full border-none cursor-pointer
         transition-all duration-200
         hover:bg-indigo-700
         active:ring-4 active:ring-indigo-200;
}

/* Map styling */
#map {
  @apply h-[300px] sm:h-[500px] w-full rounded-lg shadow-lg;
}

/* Required field indicator */
.required::after {
  content: "*";
  @apply text-red-500 ml-1;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Map controls */
.leaflet-control-zoom {
  @apply shadow-lg rounded-lg overflow-hidden;
}

.leaflet-control-zoom a {
  @apply bg-white text-gray-700 hover:bg-gray-50 transition-colors;
}

/* Add new styles for form validation */
.input-required {
  @apply border-red-300 bg-red-50;
}

.input-valid {
  @apply border-green-300 bg-green-50;
}

.input-helper-text {
  @apply text-sm mt-1;
}

.input-error-text {
  @apply text-red-600;
}

.input-success-text {
  @apply text-green-600;
}

/* Enhanced focus states for validation */
.input-required:focus {
  @apply ring-red-200 border-red-400;
}

.input-valid:focus {
  @apply ring-green-200 border-green-400;
}

/* Required field indicator animation */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.required-indicator {
  @apply text-red-500 ml-1;
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
