<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, watch } from 'vue';

const page = usePage();
const formErrors = ref(null);
const previewImages = ref([]);
const locationSource = ref('manual');
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
    return {
      url: URL.createObjectURL(file),
      type: file.type.startsWith('video/') ? 'video' : 'image'
    };
  });
};

const removeImage = (index) => {
  URL.revokeObjectURL(previewImages.value[index].url); // Clean up the object URL
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
  // Fix for Leaflet default icon
  delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

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

// Add getFallbackLocation helper function
const getFallbackLocation = async () => {
    try {
        const response = await fetch('https://ipapi.co/json/');
        const data = await response.json();
        return {
            latitude: data.latitude,
            longitude: data.longitude
        };
    } catch (error) {
        console.error('Fallback location fetch failed:', error);
        throw new Error('Could not retrieve fallback location');
    }
};

// Update setLocationFromMap function
const setLocationFromMap = async () => {
    // Check if geolocation is supported
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    // Add detailed options for geolocation
    const options = {
        enableHighAccuracy: true, // Request most accurate location
        timeout: 10000, // 10 seconds timeout
        maximumAge: 0 // Don't use cached location
    };

    // Wrap geolocation in a promise for better async handling
    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, options);
        });

        const { latitude, longitude } = position.coords;

        // Update location source and form data
        locationSource.value = 'manual';
        form.latitude = latitude;
        form.longitude = longitude;

        // Show map
        showMap.value = true;

        // Ensure DOM is updated before manipulating map
        await nextTick();

        // Initialize or update map
        if (!map.value) {
            await initializeMap();
        } else {
            map.value.setView([latitude, longitude], 13);
            marker.value.setLatLng([latitude, longitude]);
        }

        // Reverse geocode to get address details
        await reverseGeocode(latitude, longitude);

        // Success notification
        alert(`Location found: ${latitude}, ${longitude}`);

    } catch (error) {
        // Detailed error handling
        let errorMessage = 'Failed to fetch current location.';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'Location access was denied. Please enable location permissions in your browser settings.';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'Location information is currently unavailable. Please try again later.';
                break;
            case error.TIMEOUT:
                errorMessage = 'Location request timed out. Please check your internet connection and try again.';
                break;
        }

        alert(errorMessage);

        // Fallback location method
        try {
            const fallbackLocation = await getFallbackLocation();
            // Use fallback location
            form.latitude = fallbackLocation.latitude;
            form.longitude = fallbackLocation.longitude;
            showMap.value = true;
            await nextTick();
            await initializeMap();
            await reverseGeocode(fallbackLocation.latitude, fallbackLocation.longitude);
            alert(`Using approximate location: ${fallbackLocation.latitude}, ${fallbackLocation.longitude}`);
        } catch (fallbackError) {
            console.error('Fallback location failed', fallbackError);
        }
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
  <Head title="Create Stranded Incident">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
  </Head>

  <Sidebar>

    <div class="min-h-screen relative">
      <!-- Background image with overlay -->
      <div class="absolute inset-0 z-0">
        <img src="/images/landing.jpg" class="w-full h-full object-cover" alt="Background" />
        <div class="absolute inset-0 bg-gradient-to-br from-[rgba(0,40,80,0.85)] to-[rgba(0,96,128,0.8)]"></div>
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 grid-pattern"></div>
      </div>

      <!-- Content container with higher z-index -->
      <div class="container mx-auto px-4 py-16 relative z-10">
        <h2 class="title-gradient mb-6">Report Stranded Incident</h2>

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
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-200 text-gray-500'
                        ]">
                  {{ step.number }}
                </button>
                <div class="flex-1 h-1 mx-2"
                     :class="currentStep >= step.number ? 'bg-blue-500' : 'bg-gray-200'"></div>
              </div>
              <div class="text-xs text-center mt-2 text-white">{{ step.title }}</div>
            </div>
          </div>
          <!-- Mobile progress indicator -->
          <div class="sm:hidden text-center">
            <p class="text-lg font-medium text-white">Step {{ currentStep }} of {{ totalSteps }}</p>
            <p class="text-sm text-blue-300">{{ steps[currentStep - 1].title }}</p>
          </div>
        </div>

        <!-- Main Form Container -->
        <div class="max-w-4xl mx-auto">
          <!-- Info Card -->
          <div class="info-card mb-6">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-white">
                  Please provide details about the stranded marine wildlife incident. Your report helps conservation efforts.
                </p>
              </div>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <!-- Error Messages -->
            <div v-if="formErrors" class="error-container">
              <ul class="list-disc ml-4">
                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
              </ul>
            </div>

            <!-- Step 1: Basic Information -->
            <div v-show="currentStep === 1" class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Basic Information</h3>
              </div>

              <div class="item-content">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="date" value="Date of Incident" />
                    <TextInput
                      required
                      id="date"
                      type="date"
                      v-model="form.date"
                      @blur="markFieldAsTouched('date')"
                      :class="{
                        'input-required': getFieldState('date').isTouched && getFieldState('date').isEmpty,
                        'input-valid': form.date
                      }"
                      class="w-full form-input"
                    />
                    <div v-if="getFieldState('date').isTouched && getFieldState('date').isEmpty"
                         class="input-helper-text input-error-text">
                      This field is required
                    </div>
                    <InputError :message="form.errors.date" />
                  </div>

                  <div>
                    <InputLabel for="time" value="Time of the Incident" />
                    <TextInput
                      required
                      id="time"
                      type="time"
                      v-model="form.time"
                      @blur="markFieldAsTouched('time')"
                      :class="{
                        'input-required': getFieldState('time').isTouched && getFieldState('time').isEmpty,
                        'input-valid': form.time
                      }"
                      class="w-full form-input"
                      step="1"
                    />
                    <div v-if="getFieldState('time').isTouched && getFieldState('time').isEmpty"
                         class="input-helper-text input-error-text">
                      This field is required
                    </div>
                    <InputError class="mt-2" :message="form.errors.time" />
                  </div>

                  <div class="sm:col-span-2">
                    <InputLabel for="species_involved" value="Describe what species are involved" />
                    <TextInput
                      id="species_involved"
                      v-model="form.species_involved"
                      required
                      :class="{
                        'input-required': getFieldState('species_involved').isTouched &&
                                         getFieldState('species_involved').isEmpty,
                        'input-valid': form.species_involved
                      }"
                      @blur="markFieldAsTouched('species_involved')"
                      class="w-full form-input"
                      placeholder="e.g. Dolphins, Large Whales, Sharks"
                    />
                    <div v-if="getFieldState('species_involved').isTouched && getFieldState('species_involved').isEmpty"
                         class="input-helper-text input-error-text">
                      This field is required
                    </div>
                    <InputError class="mt-2" :message="form.errors.species_involved" />
                  </div>

                  <div>
                    <InputLabel for="quantity" value="Species Quantity" />
                    <input
                      required
                      id="quantity"
                      type="number"
                      min="1"
                      v-model="form.quantity"
                      @blur="markFieldAsTouched('quantity')"
                      :class="{
                        'input-required': getFieldState('quantity').isTouched && (!form.quantity || form.quantity < 1),
                        'input-valid': form.quantity && form.quantity >= 1
                      }"
                      class="w-full form-input"
                    />
                    <div v-if="getFieldState('quantity').isTouched && (!form.quantity || form.quantity < 1)"
                         class="input-helper-text input-error-text">
                      Please enter a valid quantity (minimum 1)
                    </div>
                    <InputError class="mt-2" :message="form.errors.quantity" />
                  </div>

                  <div>
                    <InputLabel for="condition" value="Condition" />
                    <select
                      v-model="form.condition"
                      @blur="markFieldAsTouched('condition')"
                      :class="{
                        'input-required': getFieldState('condition').isTouched &&
                                         getFieldState('condition').isEmpty,
                        'input-valid': form.condition
                      }"
                      class="form-select"
                      required
                    >
                      <option value="" disabled>Select an option</option>
                      <option value="alive">Alive</option>
                      <option value="dead">Dead</option>
                    </select>
                    <div v-if="getFieldState('condition').isTouched && getFieldState('condition').isEmpty"
                         class="input-helper-text input-error-text">
                      Please select a condition
                    </div>
                    <InputError class="mt-2" :message="form.errors.condition" />
                  </div>

                  <div class="sm:col-span-2">
                    <InputLabel for="certainty_level" value="Certainty Level (1-10)" />
                    <div class="relative">
                      <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full h-2 bg-blue-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                      <div class="flex justify-between px-2 text-xs text-white">
                        <span>Not Sure</span>
                        <span>Very Sure</span>
                      </div>
                      <div class="text-center text-lg font-medium text-blue-400 mt-2">
                        {{ form.certainty_level }}
                      </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.certainty_level" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Location Details -->
            <div v-show="currentStep === 2" class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Location Information</h3>
              </div>

              <div class="item-content">
                <!-- GPS Location Button -->
                <div class="flex justify-center space-x-4 mb-4">
                  <button
                    @click.prevent="setLocationFromMap"
                    class="location-button"
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
                <div v-if="showMap" class="relative rounded-xl overflow-hidden shadow-lg mb-6">
                  <div id="map" class="h-[500px] w-full z-0"></div>
                  <div class="absolute top-4 right-4 z-[9999]">
                    <button
                      @click="removeGpsLocation"
                      type="button"
                      class="big-delete-button"
                      title="Remove Location"
                    >
                      <span class="material-icons">location_off</span>
                    </button>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="municipality_id" value="Municipality" />
                    <select v-model="form.municipality_id" class="form-select" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                      <option value="" disabled>Select a municipality</option>
                      <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
                        {{ municipality.name }}
                      </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.municipality_id" />
                  </div>

                  <div>
                    <InputLabel for="barangay_id" value="Barangay" />
                    <select v-model="form.barangay_id" class="form-select" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                      <option value="" disabled>Select a barangay</option>
                      <option v-for="barangay in filteredBarangays" :key="barangay.id" :value="barangay.id">
                        {{ barangay.name }}
                      </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.barangay_id" />
                  </div>

                  <div class="sm:col-span-2">
                    <InputLabel for="detailed_location" value="Detailed Location" />
                    <textarea
                      required
                      id="detailed_location"
                      v-model="form.detailed_location"
                      @blur="markFieldAsTouched('detailed_location')"
                      :class="{
                        'input-required': getFieldState('detailed_location').isTouched &&
                                        getFieldState('detailed_location').isEmpty,
                        'input-valid': form.detailed_location
                      }"
                      class="form-textarea"
                      placeholder="Please add more details of the exact location"
                    ></textarea>
                    <div v-if="getFieldState('detailed_location').isTouched && getFieldState('detailed_location').isEmpty"
                         class="input-helper-text input-error-text">
                      This field is required
                    </div>
                    <InputError class="mt-2" :message="form.errors.detailed_location" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 3: Environmental Data -->
            <div v-show="currentStep === 3" class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Environmental Data</h3>
              </div>

              <div class="item-content">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                  <div>
                    <InputLabel for="sea_state" value="Sea State" />
                    <select v-model="form.sea_state" class="form-select">
                      <option value="">Select an option (optional)</option>
                      <option value="calm">Calm</option>
                      <option value="moderate">Moderate</option>
                      <option value="rough">Rough</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.sea_state" />
                  </div>

                  <div>
                    <InputLabel for="weather" value="Weather" />
                    <select v-model="form.weather" class="form-select">
                      <option value="">Select an option (optional)</option>
                      <option value="sunny">Sunny</option>
                      <option value="cloudy">Cloudy</option>
                      <option value="rainy">Rainy</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.weather" />
                  </div>

                  <div>
                    <InputLabel for="beach_type" value="Beach type" />
                    <select v-model="form.beach_type" class="form-select">
                      <option value="">Select an option (optional)</option>
                      <option value="mangrove">Mangrove</option>
                      <option value="rocky">Rocky</option>
                      <option value="sandy">Sandy</option>
                      <option value="reef">Reef</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.beach_type" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Media & Additional Info -->
            <div v-show="currentStep === 4" class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Media & Additional Information</h3>
              </div>

              <div class="item-content space-y-6">
                <div>
                  <InputLabel for="more_information" value="More Information of the Incident" />
                  <textarea
                    id="more_information"
                    v-model="form.more_information"
                    autocomplete="more_information"
                    class="form-textarea"
                    placeholder="Please share more information of the incident"
                  ></textarea>
                  <InputError class="mt-2" :message="form.errors.more_information" />
                </div>

                <div class="media-section">
                  <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
                  <label for="mediaFiles" class="browse-button" tabindex="0" role="button" @keypress.enter="$event.target.click()">
                    Browse Files
                  </label>
                  <input type="file" accept="image/*,video/*" id="mediaFiles" @change="handleFileChange" multiple class="hidden" />

                  <!-- Preview Section -->
                  <div v-if="previewImages.length" class="preview-section mt-4">
                    <h4 class="preview-title">Media Preview</h4>
                    <div class="preview-grid">
                      <div v-for="(media, index) in previewImages" :key="index" class="preview-item">
                        <template v-if="media.type === 'video'">
                          <video
                            :src="media.url"
                            class="preview-video"
                            controls
                            preload="metadata">
                            Your browser does not support the video tag.
                          </video>
                        </template>
                        <template v-else>
                          <img :src="media.url" alt="Preview" class="preview-image" />
                        </template>
                        <button @click="removeImage(index)" type="button" class="remove-button" title="Remove">
                          <span class="material-icons">close</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <InputError class="mt-2" :message="form.errors.mediaFiles" />
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between items-center mt-6">
              <button
                type="button"
                @click="previousStep"
                v-show="currentStep > 1"
                class="cancel-button">
                Previous
              </button>
              <div class="flex space-x-4">
                <button
                  v-if="currentStep < totalSteps"
                  type="button"
                  @click="nextStep"
                  :disabled="!isStepValid"
                  class="create-button"
                  :class="{ 'opacity-50 cursor-not-allowed': !isStepValid }">
                  Next
                </button>
                <button
                  v-else
                  type="submit"
                  :disabled="isSubmitting"
                  class="create-button"
                  :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }">
                  <svg v-if="isSubmitting" class="animate-spin h-5 w-5 mr-2 inline-block" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  {{ isSubmitting ? 'Submitting...' : 'Submit Report' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
/* Grid pattern overlay */
.grid-pattern {
    background:
        linear-gradient(
            rgba(0, 64, 128, 0.2) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(0, 64, 128, 0.2) 1px,
            transparent 1px
        );
    background-size: 32px 32px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}

/* Title styling with gradient */
.title-gradient {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
    margin-bottom: 1.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Info card */
.info-card {
    background: rgba(13, 71, 161, 0.5);
    border-left: 4px solid #2196f3;
    padding: 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    backdrop-filter: blur(10px);
}

/* Error container */
.error-container {
    @apply p-4 rounded-lg;
    background: rgba(255, 59, 48, 0.2);
    border: 1px solid rgba(255, 59, 48, 0.3);
    color: #ffcccc;
}

/* Form containers */
.item-card {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    padding: 2rem;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

.item-card:hover {
    background: rgba(0, 51, 102, 0.4);
    box-shadow: 0 6px 28px rgba(0, 0, 0, 0.2);
    transform: translateY(-2px);
}

/* Section headers */
.item-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 1rem;
}

.item-header h3 {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Form controls */
.form-input, .form-select, .form-textarea {
    @apply bg-white/10 border-white/10 text-white text-sm;
    backdrop-filter: blur(4px);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
    width: 100%;
}

/* Label styles */
label {
    @apply block text-white text-sm font-medium mb-2;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.form-select {
    /* Specific styles for select elements */
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.form-select option {
    background-color: #1a365d;
    color: white;
    padding: 0.5rem;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    @apply ring-1 ring-blue-400;
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
    border-color: rgba(147, 197, 253, 0.5);
}

.form-textarea {
    min-height: 6rem;
    height: auto;
    resize: vertical;
}

/* Button styles */
.location-button {
    display: inline-block;
    background: #4a90e2;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
}

.location-button:hover,
.location-button:focus {
    background: #3a80d2;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
    outline: none;
}

.create-button {
    @apply px-6 py-2.5 text-sm font-medium;
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border-radius: 50px;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
    transition: all 0.3s ease;
    min-width: 140px;
    text-align: center;
}

.create-button:hover:not(:disabled) {
    background: linear-gradient(135deg, #00b3e6, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.cancel-button {
    @apply px-6 py-2.5 text-sm font-medium inline-flex items-center justify-center;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 50px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
    min-width: 140px;
    text-align: center;
}

.cancel-button:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Map styles */
#map {
    @apply h-[300px] sm:h-[500px] w-full rounded-lg shadow-lg;
    z-index: 1;
    background: white !important;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

/* Media preview section */
.preview-section {
    @apply mt-6 p-4 rounded-lg;
    background: rgba(0, 51, 102, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-title {
    @apply text-sm font-medium text-white/90 mb-3;
    letter-spacing: 0.01em;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    @apply p-2;
    justify-content: start;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    @apply rounded-lg overflow-hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: rgba(0, 0, 0, 0.3);
}

.preview-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: rgba(0, 0, 0, 0.3);
}

.remove-button {
    position: absolute;
    top: 4px;
    right: 4px;
    @apply p-1 rounded-full bg-black/50 text-white/90
           hover:bg-black/70 transition-colors duration-200;
}

.remove-button .material-icons {
    @apply text-sm;
}

/* Browse button styles */
.browse-button {
    display: inline-block;
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.browse-button:hover,
.browse-button:focus {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
    outline: none;
}

/* The big delete button for map */
.big-delete-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    background-color: #247990;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
    border: 2px solid white;
    transition: all 0.2s ease;
    font-size: 20px;
    z-index: 9999;
    position: relative;
    font-weight: 500;
    min-width: 50px;
    min-height: 50px;
}

.big-delete-button:hover {
    background-color: #ff0000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}

/* Form validation */
.input-required {
    @apply border-red-300 bg-red-50/10;
}

.input-valid {
    @apply border-green-300 bg-green-50/10;
}

.input-helper-text {
    @apply text-sm mt-1;
}

.input-error-text {
    @apply text-red-400;
}

.input-success-text {
    @apply text-green-400;
}

/* Enhanced focus states for validation */
.input-required:focus {
    @apply ring-red-200 border-red-400;
}

.input-valid:focus {
    @apply ring-green-200 border-green-400;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .preview-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 100px));
        gap: 0.5rem;
    }

    .preview-item {
        max-width: 100px;
    }

    .title-gradient {
        font-size: 1.8rem;
    }
}

@media (max-width: 768px) {
    .title-gradient {
        font-size: 2rem;
    }
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
</style>
