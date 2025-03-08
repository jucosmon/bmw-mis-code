<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref } from 'vue';

const page = usePage();

const props = defineProps({
    strandedIncident:  {
        type: Object,
        required: true
    },
    userRespondStatus: {
        type: String,
        default: '',

    }
});

const currentUserRole = page.props.auth.user.user_role;
const deletedImages = ref([]);
const previewNewImages = ref([]);
const municipalities = ref([]);
const barangays = ref([]);

onMounted(async () => {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    if (props.strandedIncident.municipality_id) {
        await fetchBarangays();
    }
});

const fetchBarangays = async (municipalityId = props.strandedIncident.municipality_id) => {
    if (!municipalityId) return;
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
};

const existingImages = ref(props.strandedIncident.mediaFiles ? props.strandedIncident.mediaFiles : []);

const backRoute = computed(() => {
    return route('stranded.incident.view', {id: props.strandedIncident.id});
});

const updateRoute = computed(() => {
    return route('stranded.incident.update', {
        id: props.strandedIncident.id,
    });
});

const form = useForm({
  certainty_level: props.strandedIncident.certainty_level || null,
  date: props.strandedIncident.date || new Date().toISOString().split('T')[0],
  time: props.strandedIncident.time || new Date().toTimeString().split(' ')[0],
  species_involved: props.strandedIncident.species_involved || '',
  quantity: props.strandedIncident.quantity || null,
  condition: props.strandedIncident.condition || '',
  latitude: props.strandedIncident.latitude || '',
  longitude: props.strandedIncident.longitude || '',
  sea_state: props.strandedIncident.sea_state || '',
  weather: props.strandedIncident.weather || '',
  beach_type: props.strandedIncident.beach_type || '',
  detailed_location: props.strandedIncident.detailed_location || '',
  more_information: props.strandedIncident.more_information || '',
  municipality_id: props.strandedIncident.municipality_id || null,
  barangay_id: props.strandedIncident.barangay_id || null,
  report_status: props.strandedIncident.report_status || '',
  mediaFiles: [],
  deletedImages: [],
  clearErrors() {
    this.errors = {};
  },
  setError(errors) {
    this.errors = errors;
  }
});

const formErrors = ref(null);

const normalizeValue = (value) => {
    if (value instanceof Date) return value.toISOString().split('T')[0];
    if (typeof value === 'number') return String(value);
    if (value === null || value === undefined) return '';
    return value;
};
const hasChanges = computed(() => {
    const currentData = form.data();

    // Check if basic fields are different
    const dataChanged = Object.keys(currentData).some((key) => {
        if (key === 'mediaFiles' || key === 'deletedImages') return false;
        return normalizeValue(currentData[key]) !== normalizeValue(props.strandedIncident[key]);
    });

    const mediaFilesChanged = form.mediaFiles.length > 0;
    const deletedImagesChanged = deletedImages.value.length > 0;
    const reportStatusChanged = form.report_status !== props.strandedIncident.report_status;

    return dataChanged || mediaFilesChanged || deletedImagesChanged || reportStatusChanged;
});

const handleNewFileChange = (event) => {
    const files = event.target.files;
    // Append new files to the mediaFiles array without resetting the form
    form.mediaFiles.push(...Array.from(files));

    // Generate previews for each selected image
    previewNewImages.value = Array.from(files).map(file => {
        return URL.createObjectURL(file);
    });
};

// Remove selected preview image
const removeNewImage = (index) => {
    previewNewImages.value.splice(index, 1);
    form.mediaFiles.splice(index, 1);
};


const removeExistingImage = (index) => {
    const imageToDelete = existingImages.value[index];
    deletedImages.value.push(imageToDelete.id); // Assuming each image has an `id`
    existingImages.value.splice(index, 1);
};


// Map references
const map = ref(null);
const marker = ref(null);
const locationSource = ref('original');
const isGeocodingInProgress = ref(false);
const showMap = ref(false); // Change initial value to false
const originalLocation = ref({
    latitude: props.strandedIncident.latitude || null,
    longitude: props.strandedIncident.longitude || null,
    municipality_id: props.strandedIncident.municipality_id || '',
    barangay_id: props.strandedIncident.barangay_id || '',
    detailed_location: props.strandedIncident.detailed_location || ''
});

// Add helper function for location comparison
const isSameLocation = (lat1, lng1, lat2, lng2, tolerance = 0.0001) => {
    if (!lat1 || !lng1 || !lat2 || !lng2) return false;
    // Compare with form's current values instead of original location
    if (lat1 === form.latitude && lng1 === form.longitude) {
        return true;
    }
    return false;
};

// Update setLocationFromMap function
const setLocationFromMap = async () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude } = position.coords;

                // Only check if coordinates match current form values
                if (isSameLocation(latitude, longitude, form.latitude, form.longitude)) {
                    alert("You're already using these coordinates!");
                    return;
                }

                form.latitude = latitude;
                form.longitude = longitude;
                locationSource.value = 'gps';
                showMap.value = true;

                await nextTick();
                initializeMap();
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

// Add Nominatim reverse geocoding function
const reverseGeocode = async (latitude, longitude) => {
  isGeocodingInProgress.value = true;

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`);
    const data = await response.json();
    const address = data.address;

    let municipalityName = address.city || address.town || address.municipality;
    let barangayName = address.quarter || address.village || address.suburb || address.neighbourhood || address.hamlet;

    // Clear existing selections
    form.municipality_id = '';
    form.barangay_id = '';

    if (municipalityName) {
      const matchedMunicipality = municipalities.value.find(m =>
        m.name.toLowerCase() === municipalityName.toLowerCase()
      );

      if (matchedMunicipality) {
        form.municipality_id = matchedMunicipality.id;
        await fetchBarangays(matchedMunicipality.id);

        if (barangayName && barangays.value.length > 0) {
          const matchedBarangay = barangays.value.find(b =>
            b.name.toLowerCase() === barangayName.toLowerCase()
          );

          if (matchedBarangay) {
            form.barangay_id = matchedBarangay.id;
          }
        }
      }
    }

    form.detailed_location = data.display_name || '';

  } catch (error) {
    console.error("Error with reverse geocoding:", error);
    form.municipality_id = '';
    form.barangay_id = '';
  } finally {
    isGeocodingInProgress.value = false;
  }
};

// Update map initialization
const initializeMap = () => {
    const defaultLat = form.latitude || originalLocation.value.latitude;
    const defaultLng = form.longitude || originalLocation.value.longitude;

    if (!defaultLat || !defaultLng) {
        console.error('No valid coordinates available');
        showMap.value = false;
        return;
    }

    // Wait for DOM to be ready
    nextTick(() => {
        // Check if map container exists
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('Map container not found');
            return;
        }

        cleanupMap();

        try {
            map.value = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map.value);

            marker.value = L.marker([defaultLat, defaultLng], {
                draggable: true,
            }).addTo(map.value);

            marker.value.on('dragend', async (e) => {
                const { lat, lng } = e.target.getLatLng();
                form.latitude = lat;
                form.longitude = lng;
                locationSource.value = 'manual';
                await reverseGeocode(lat, lng);
            });

            map.value.on('click', async (e) => {
                const { lat, lng } = e.latlng;
                form.latitude = lat;
                form.longitude = lng;
                marker.value.setLatLng([lat, lng]);
                locationSource.value = 'manual';
                await reverseGeocode(lat, lng);
            });

            showMap.value = true;
            // Invalidate map size after rendering
            map.value.invalidateSize();
        } catch (error) {
            console.error('Error initializing map:', error);
            showMap.value = false;
        }
    });
};

// Add reset to original location function
const resetToOriginal = (e) => {
    e.preventDefault(); // Add this line
    locationSource.value = 'original';

    // Only show map if original coordinates exist
    if (originalLocation.value.latitude && originalLocation.value.longitude) {
        showMap.value = true;
        nextTick(() => {
            form.latitude = originalLocation.value.latitude;
            form.longitude = originalLocation.value.longitude;
            form.municipality_id = originalLocation.value.municipality_id;
            form.barangay_id = originalLocation.value.barangay_id;
            form.detailed_location = originalLocation.value.detailed_location;

            if (originalLocation.value.municipality_id) {
                fetchBarangays(originalLocation.value.municipality_id);
            }

            if (!map.value) {
                initializeMap();
            } else {
                map.value.setView([form.latitude, form.longitude], 13);
                marker.value.setLatLng([form.latitude, form.longitude]);
            }
        });
    } else {
        showMap.value = false;
        form.latitude = '';
        form.longitude = '';
        form.municipality_id = originalLocation.value.municipality_id;
        form.barangay_id = originalLocation.value.barangay_id;
        form.detailed_location = originalLocation.value.detailed_location;
        cleanupMap();
    }
};

// Add remove GPS location function
const removeGpsLocation = () => {
    locationSource.value = 'manual';
    showMap.value = false;
    form.latitude = '';
    form.longitude = '';
    form.municipality_id = '';
    form.barangay_id = '';
    form.detailed_location = '';
    cleanupMap();
};

// Add cleanup function
const cleanupMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
        marker.value = null;
    }
};

// Fix map initialization
onMounted(() => {
    // Only show and initialize map if coordinates exist
    if (originalLocation.value.latitude && originalLocation.value.longitude) {
        showMap.value = true;
        initializeMap();
    } else {
        showMap.value = false;
    }

    // Fetch municipalities after map initialization
    fetch('/municipalities')
        .then(response => response.json())
        .then(data => {
            municipalities.value = data;
            if (props.strandedIncident.municipality_id) {
                return fetchBarangays();
            }
        })
        .catch(error => console.error("Error fetching data:", error));
});

// button status
const buttonStatus = computed(() => {
    return (props.strandedIncident.report_status === 'pending' ||
            props.strandedIncident.report_status === 'false') &&
            currentUserRole !== 'public_user';
});

const showVerifyModal = ref(false);
const showFalseModal = ref(false);

const falseIncident = () => {
    showFalseModal.value = true;
};

const verifyIncident = () => {
    if (!validateForm()) {
        alert('Please fill in all required fields before verifying the incident.');
        return;
    }
    showVerifyModal.value = true;
};

// Update validateForm function
const validateForm = () => {
    const requiredFields = {
        date: 'Date',
        time: 'Time',
        species_involved: 'Species involved',
        quantity: 'Quantity',
        certainty_level: 'Certainty level',
        condition: 'Condition',
        municipality_id: 'Municipality',
        barangay_id: 'Barangay',
        detailed_location: 'Detailed location'
    };

    let hasErrors = false;
    const errors = {};

    Object.entries(requiredFields).forEach(([field, label]) => {
        if (!form[field]) {
            errors[field] = `${label} is required`;
            hasErrors = true;
        }
    });

    if (hasErrors) {
        form.setError(errors);
        return false;
    }

    return true;
};

// Update submit handler
const submit = (e) => {
    e.preventDefault();
    form.clearErrors();

    if (buttonStatus.value) {
        return;
    }

    if (!validateForm()) {
        return;
    }

    if (hasChanges.value) {
        submitForm();
    } else {
        alert('No changes detected in the form.');
    }
};

// Add separate submitForm function
const submitForm = () => {
    form.deletedImages = deletedImages.value;
    form.post(updateRoute.value, {
        onSuccess: () => {
            formErrors.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};

// Update verify/false functions to use new submitForm
const confirmFalse = () => {

    router.patch(route('stranded.incident.false', props.strandedIncident.id), {}, {
        onSuccess: () => {
            showFalseModal.value = false;
            // Optionally redirect or show success message
        },
    });
};


const confirmVerify = () => {
    if (!validateForm()) {
        showVerifyModal.value = false;
        return;
    }

    form.report_status = 'verified';
    submitForm();
    showVerifyModal.value = false;
};

</script>

<template>
    <Head title="Update Stranded Incident" />

    <Sidebar>
        <template #header>
            <div>
                <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-900 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                    <Link :href="backRoute" class="flex items-center">
                        Back
                    </Link>
                </button>
            </div>
        </template>

        <div class="container mx-auto px-4 py-8">
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Stranded Incident for Responders</h2>

            <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Error Messages -->
                    <div v-if="formErrors" class="p-4 bg-red-100 border border-red-400 rounded-lg text-red-600">
                        <ul class="list-disc ml-4">
                            <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>
                    <!-- copy -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                        <InputLabel for="date" value="Date of the Incident" />
                        <TextInput required id="date" type="date" v-model="form.date" autocomplete="date" class="w-full" />
                        <InputError class="mt-2" :message="form.errors.date" />
                        </div>
                        <div>
                        <InputLabel for="time" value="Time of the Incident" />
                        <TextInput required id="time" type="time" v-model="form.time" autocomplete="time" class="w-full" step="1" />
                        <InputError class="mt-2" :message="form.errors.time" />
                        </div>

                        <div class="sm:col-span-2">
                        <InputLabel for="species_involved" value="Describe what species are involved" />
                        <TextInput id="species_involved" required v-model="form.species_involved" class="w-full" placeholder="e.g. Dolphins, Large Whales, Sharks" />
                        <InputError class="mt-2" :message="form.errors.species_involved" />
                        </div>

                        <div>
                        <InputLabel for="quantity" value="How many species are involved in the incident?" />
                        <input id="quantity" type="number" min="1" v-model="form.quantity" class="w-full" required/>
                        <InputError class="mt-2" :message="form.errors.quantity" />
                        </div>
                        <div>
                        <InputLabel for="certainty_level" value="Certainty Level (1-10)" />
                        <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full" />
                        <p class="text-center">{{ form.certainty_level }}</p>
                        <InputError class="mt-2" :message="form.errors.certainty_level" />
                        </div>
                        <div>
                        <InputLabel for="condition" value="Condition" />
                        <select v-model="form.condition" name="condition" class="w-full" required>
                            <option value="" disabled>Select an option</option>
                            <option value="alive">Alive</option>
                            <option value="dead">Dead</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.condition" />
                        </div>
                        <div>
                        <InputLabel for="sea_state" value="Sea State" />
                        <select v-model="form.sea_state" name="sea_state" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="calm">Calm</option>
                            <option value="moderate">Moderate</option>
                            <option value="rough">Rough</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sea_state" />
                        </div>  <div>
                        <InputLabel for="weather" value="Weather" />
                        <select v-model="form.weather" name="weather" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="sunny">Sunny</option>
                            <option value="cloudy">Cloudy</option>
                            <option value="rainy">Rainy</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.weather" />
                        </div>  <div>
                        <InputLabel for="beach_type" value="Beach type" />
                        <select v-model="form.beach_type" name="beach_type" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="mangrove">Mangrove</option>
                            <option value="rocky">Rocky</option>
                            <option value="sandy">Sandy</option>
                            <option value="reef">Reef</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.beach_type" />
                        </div>

                        <!--Map Section-->
                        <div class="mt-4 sm:col-span-2 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900">Location Details</h3>
                                <div class="flex space-x-2">
                                    <button
                                        type="button"
                                        @click.prevent="setLocationFromMap"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center space-x-2"
                                    >
                                    <span class="material-icons material-symbols-outlined">
                                        my_location
                                    </span>
                                    </button>
                                    <button
                                        v-if="!showMap || locationSource !== 'original'"
                                        type="button"
                                        @click="resetToOriginal"
                                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
                                    >
                                    <span class="material-icons material-symbols-outlined">
                                        restart_alt
                                    </span>
                                    </button>
                                </div>
                            </div>

                            <div v-if="showMap" class="relative rounded-xl overflow-hidden shadow-lg">
                                <div id="map" class="h-[400px] w-full z-0"></div>
                                <div class="absolute top-4 right-4 z-10">
                                    <button
                                        type="button"
                                        @click="removeGpsLocation"
                                        class="px-5 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors shadow-lg"
                                    >
                                    <span class="material-icons material-symbols-outlined">
                                        location_off
                                    </span>

                                    </button>
                                </div>
                                <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm p-3 rounded-lg shadow-md">
                                    <p class="text-sm font-medium text-gray-700">
                                        Latitude: {{ form.latitude || 'Not Set' }}<br>
                                        Longitude: {{ form.longitude || 'Not Set' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                        <InputLabel for="municipality_id" value="Municipality" />
                        <select
                            id="municipality_id"
                            name="municipality_id"
                            v-model="form.municipality_id"
                            @change="fetchBarangays(form.municipality_id)"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors.municipality_id }"
                            required
                        >
                            <option value="">Select a municipality</option>
                            <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                {{ municipality.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.municipality_id" />
                        </div>

                        <div>
                        <InputLabel for="barangay_id" value="Barangay" />
                        <select
                            id="barangay_id"
                            name="barangay_id"
                            v-model="form.barangay_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors.barangay_id }"
                            required
                        >
                            <option :value="null">Select a barangay</option>
                            <option v-for="barangay in barangays"
                                    :key="barangay.id"
                                    :value="barangay.id"
                                    :selected="form.barangay_id === barangay.id"
                            >
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
                            autocomplete="detailed_location"
                            class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                            placeholder="Please add more details of the exact location"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.detailed_location" />
                        </div>
                        <div class="sm:col-span-2">
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

                                           <!-- Existing Image Previews -->
                                           <div class="mt-4 sm:col-span-2 col-span-1">
                            <InputLabel value="Existing Images" />
                            <div>
                                <div v-if="existingImages.length===0" class="flex flex-wrap gap-2">No existing images</div>
                                <div v-if="existingImages.length" class="flex flex-wrap gap-2">
                                <div v-for="(image, index) in existingImages" :key="index" class="relative">
                                    <img :src="image.url" alt="Image Preview" class="h-32 w-32 object-cover rounded-md"/>
                                    <button
                                        type="button"
                                        @click.prevent="removeExistingImage(index)"
                                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                    >
                                        &times;
                                    </button>
                                </div>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload Field -->
                        <div class="sm:col-span-2 col-span-1">
                            <InputLabel for="mediaFiles" value="Upload New Images" />
                            <input
                                id="mediaFiles"
                                type="file"
                                accept="image/*"
                                multiple
                                @change="handleNewFileChange"
                                class="file-input w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.mediaFiles" />
                        </div>

                        <!-- Image Previews -->
                        <div class="mt-4 sm:col-span-2 col-span-1">
                            <div>
                                <div v-if="previewNewImages.length" class="flex flex-wrap gap-2">
                                    <div v-for="(image, index) in previewNewImages" :key="index" class="relative">
                                        <img :src="image" alt="Image Preview" class="h-32 w-32 object-cover rounded-md"/>
                                        <button
                                            type="button"
                                            @click="removeNewImage(index)"
                                            class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit and Cancel Buttons for regular update -->
                    <div v-if="!buttonStatus" class="flex items-center justify-between mt-6">
                        <Link :href="backRoute" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>

                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }"
                        class="bg-indigo-900">
                            Update Incident
                        </PrimaryButton>
                    </div>

                    <!-- False and Verify button for pending cases-->
                    <div v-else class="flex items-center justify-end gap-5 mt-6">
                        <DangerButton
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                            class="bg-indigo-900"
                            @click="falseIncident"
                            type="button"
                            formnovalidate
                        >
                            Mark as False
                        </DangerButton>
                        <PrimaryButton
                            :disabled="form.processing"
                            :class="{ 'opacity-25': form.processing }"
                            class="bg-indigo-900"
                            type="button"
                            @click="verifyIncident"
                        >
                            Verify as True
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>

        <Modal :show="showVerifyModal" @close="showVerifyModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Confirm Verification
                </h2>
                <p class="mt-3 text-sm text-gray-600">
                    Are you sure that the report is true and accurate?
                </p>
                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="showVerifyModal = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton @click="confirmVerify">
                        Confirm
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showFalseModal" @close="showFalseModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Confirm False Report
                </h2>
                <p class="mt-3 text-sm text-gray-600">
                    Are you sure the report is false?
                </p>
                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="showFalseModal = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton @click="confirmFalse">
                        Confirm
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>
<style scoped>
/* Hide the file name (text) but keep the button */
.file-input {
  position: relative;
  overflow: hidden;
  width: 100%; /* Adjust as needed */
  height: 40px; /* Adjust height as needed */
  color: white;


}

/* Hide the file name text after file is selected */
.file-input::-webkit-file-upload-button {
  visibility: hidden; /* Hides the file name */
}

/* Optional: custom styling for the file input button */
.file-input::before {
  content: "Choose Files"; /* Text for the button */
  display: inline-block;
  background-color: indigo; /* Change to your preferred color */
  color: white;
  padding: 10px;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
}

</style>
