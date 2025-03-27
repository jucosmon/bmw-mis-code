<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

import { computed, nextTick, onMounted, ref, watch } from 'vue';

const page = usePage();

const props = defineProps({
    strandedIncident:  {
        type: Object,
        required: true
    },
    userRespondStatus: {
        type: String,
        default: '',

    },
    municipalities: {
        type: Array,
        required: true
    },
    barangays: {
        type: Array,
        required: true
    }
});

const currentUserRole = page.props.auth.user.user_role;
const deletedImages = ref([]);
const previewNewImages = ref([]);
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
      const matchedMunicipality = props.municipalities.find(m =>
        m.name.toLowerCase() === municipalityName.toLowerCase()
      );

      if (matchedMunicipality) {
        form.municipality_id = matchedMunicipality.id;

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
            // Fix for Leaflet default icon
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: markerIcon,
                iconUrl: markerIcon,
                shadowUrl: markerShadow,
            });
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

// Add remove GPS location function
const removeGpsLocation = () => {
    locationSource.value = 'manual';
    form.latitude = '';
    form.longitude = '';
    form.municipality_id = '';
    form.barangay_id = '';
    form.detailed_location = '';

    // First cleanup the map
    cleanupMap();
    // Then hide the map container
    showMap.value = false;
};

// Add cleanup function
const cleanupMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (marker.value) {
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

// Add this computed property for filtered barangays
const filteredBarangays = computed(() => {
  if (!form.municipality_id) return [];
  return props.barangays.filter(barangay =>
    barangay.municipality_id === form.municipality_id
  );
});

// Add geocodeLocation function before the watchers
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

// Update the watch section for municipality and barangay
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

// Add watch for map visibility
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
    <Head title="Update Stranded Incident" />

    <Sidebar>
        <template #header>
            <div>
                <button class="oceanic-button">
                    <Link :href="backRoute" class="flex items-center">
                        <span class="material-icons text-sm mr-2">arrow_back</span>
                        Back
                    </Link>
                </button>
            </div>
        </template>

        <div class="relative min-h-screen">
            <!-- Background image with oceanic overlay -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main content -->
            <div class="relative z-10">
                <div class="container mx-auto px-4 py-8">
                    <h2 class="title-gradient mb-6">Update Stranded Incident for Responders</h2>

                    <form @submit.prevent="submit" class="space-y-8 max-w-4xl mx-auto">
                        <!-- Error Messages -->
                        <div v-if="formErrors" class="error-container">
                            <ul class="list-disc ml-4">
                                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Incident Details Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons mr-2">event</span>
                                Incident Details
                            </h3>
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
                            </div>
                        </div>

                        <!-- Species Information Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons mr-2">pets</span>
                                Species Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="species_involved" value="Describe what species are involved" />
                                    <TextInput id="species_involved" required v-model="form.species_involved" class="w-full" placeholder="e.g. Dolphins, Large Whales, Sharks" />
                                    <InputError class="mt-2" :message="form.errors.species_involved" />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="quantity" value="How many species are involved in the incident?" />
                                        <input id="quantity" type="number" min="1" v-model="form.quantity" class="mt-1 block w-full rounded-md" required/>
                                        <InputError class="mt-2" :message="form.errors.quantity" />
                                    </div>
                                    <div>
                                        <InputLabel for="certainty_level" value="Certainty Level (1-10)" />
                                        <div class="mt-1">
                                            <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full accent-cyan" />
                                            <p class="text-center font-medium text-white">{{ form.certainty_level }}</p>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.certainty_level" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Environmental Conditions -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons mr-2">water</span>
                                Environmental Conditions
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
                                </div>
                                <div>
                                    <InputLabel for="weather" value="Weather" />
                                    <select v-model="form.weather" name="weather" class="w-full">
                                        <option value="" disabled>Select an option</option>
                                        <option value="sunny">Sunny</option>
                                        <option value="cloudy">Cloudy</option>
                                        <option value="rainy">Rainy</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.weather" />
                                </div>
                                <div>
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
                            </div>
                        </div>

                        <!--Location Section-->
                        <div class="form-section">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                                <h3 class="section-title mb-0 pb-0 border-b-0">
                                    <span class="material-icons mr-2">location_on</span>
                                    Location Details
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click.prevent="setLocationFromMap"
                                        class="action-button location-button"
                                    >
                                        <span class="material-icons">my_location</span>
                                        <span>Use Current Location</span>
                                    </button>

                                    <button
                                        v-if="!showMap || locationSource !== 'original'"
                                        type="button"
                                        @click="resetToOriginal"
                                        class="action-button reset-button"
                                    >
                                        <span class="material-icons">restart_alt</span>
                                        <span>Reset Location</span>
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-blue-300/20 pt-4 mb-4"></div>

                            <div v-if="showMap" class="map-container">
                                <div id="map" class="h-[450px] w-full z-0"></div>
                                <div class="absolute top-4 right-4 z-10">
                                    <button
                                        type="button"
                                        @click="removeGpsLocation"
                                        class="remove-location-button"
                                    >
                                        <span class="material-icons">location_off</span>
                                        <span>Remove Location</span>
                                    </button>
                                </div>
                                <div class="map-coordinates">
                                    <p class="text-sm font-medium">
                                        <span class="coordinate-label">Latitude:</span> {{ form.latitude || 'Not Set' }}<br>
                                        <span class="coordinate-label">Longitude:</span> {{ form.longitude || 'Not Set' }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <InputLabel for="municipality_id" value="Municipality" />
                                    <select
                                        id="municipality_id"
                                        name="municipality_id"
                                        v-model="form.municipality_id"
                                        class="w-full"
                                        :class="{ 'border-red-500': form.errors.municipality_id }"
                                        required
                                    >
                                        <option value="">Select a municipality</option>
                                        <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
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
                                        class="w-full"
                                        :class="{ 'border-red-500': form.errors.barangay_id }"
                                        required
                                    >
                                        <option value="" disabled>Select a barangay</option>
                                        <option v-for="barangay in filteredBarangays"
                                                :key="barangay.id"
                                                :value="barangay.id"
                                        >
                                            {{ barangay.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.barangay_id" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <InputLabel for="detailed_location" value="Detailed Location" />
                                <textarea
                                    required
                                    id="detailed_location"
                                    v-model="form.detailed_location"
                                    autocomplete="detailed_location"
                                    class="w-full"
                                    placeholder="Please add more details of the exact location"
                                    rows="3"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.detailed_location" />
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons mr-2">info</span>
                                Additional Information
                            </h3>
                            <div>
                                <InputLabel for="more_information" value="More Information of the Incident" />
                                <textarea
                                    id="more_information"
                                    v-model="form.more_information"
                                    autocomplete="more_information"
                                    class="w-full"
                                    placeholder="Please share more information of the incident"
                                    rows="4"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.more_information" />
                            </div>
                        </div>

                        <!-- Images Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons mr-2">photo_library</span>
                                Images
                            </h3>

                            <!-- Existing Images -->
                            <div class="preview-section">
                                <h4 class="preview-title">Existing Images</h4>
                                <div class="mt-3">
                                    <div v-if="existingImages.length===0" class="text-gray-400 italic">No existing images</div>
                                    <div v-else class="preview-grid">
                                        <div v-for="(image, index) in existingImages" :key="index" class="preview-item group">
                                            <img :src="image.url" alt="Image Preview" class="preview-image"/>
                                            <button
                                                type="button"
                                                @click.prevent="removeExistingImage(index)"
                                                class="remove-button"
                                            >
                                                <span class="material-icons">close</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Images -->
                            <div class="preview-section">
                                <h4 class="preview-title">Upload New Images</h4>
                                <label for="mediaFiles" class="browse-button" tabindex="0" role="button" @keypress.enter="$event.target.click()">
                                    <span class="material-icons mr-2">cloud_upload</span>
                                    Browse Files
                                </label>
                                <input
                                    id="mediaFiles"
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    @change="handleNewFileChange"
                                    class="hidden"
                                />
                                <InputError class="mt-2" :message="form.errors.mediaFiles" />

                                <!-- Preview New Images -->
                                <div v-if="previewNewImages.length" class="mt-4">
                                    <div class="preview-grid">
                                        <div v-for="(image, index) in previewNewImages" :key="index" class="preview-item group">
                                            <img :src="image" alt="Image Preview" class="preview-image"/>
                                            <button
                                                type="button"
                                                @click="removeNewImage(index)"
                                                class="remove-button"
                                            >
                                                <span class="material-icons">close</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="mt-6 pt-4 border-t border-opacity-20 border-cyan-200">
                            <!-- Submit and Cancel Buttons for regular update -->
                            <div v-if="!buttonStatus" class="flex items-center justify-between w-full">
                                <Link :href="backRoute" class="cancel-button">Cancel</Link>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="create-button"
                                    :class="{ 'opacity-50': form.processing }"
                                >
                                    Update Incident
                                </button>
                            </div>

                            <!-- False and Verify buttons for pending cases -->
                            <div v-else class="flex items-center justify-end gap-4 w-full">
                                <Link :href="backRoute" class="cancel-button">Cancel</Link>

                                <div class="flex flex-wrap sm:flex-nowrap gap-3">
                                    <button
                                        :disabled="form.processing"
                                        :class="{ 'opacity-50': form.processing }"
                                        class="danger-button"
                                        @click="falseIncident"
                                        type="button"
                                        formnovalidate
                                    >
                                        <span class="flex items-center justify-center">
                                            <span class="material-icons mr-1">cancel</span>
                                            <span class="hidden sm:inline">Mark as False</span>
                                        </span>
                                    </button>
                                    <button
                                        :disabled="form.processing"
                                        :class="{ 'opacity-50': form.processing }"
                                        class="verify-button"
                                        type="button"
                                        @click="verifyIncident"
                                    >
                                        <span class="flex items-center justify-center">
                                            <span class="material-icons mr-1">check_circle</span>
                                            <span class="hidden sm:inline">Verify as True</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal components with improved styling -->
        <Modal :show="showVerifyModal" @close="showVerifyModal = false" max-width="md">
            <div class="verify-modal">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-green-500"></div>
                <div class="flex items-center justify-center mt-8 mb-4">
                    <span class="material-icons text-emerald-500 text-5xl">check_circle</span>
                </div>
                <h2 class="text-2xl font-bold text-white text-center mb-6">Confirm Verification</h2>
                <p class="text-white/90 text-center text-lg mb-8">
                    Are you sure that the report is true and accurate?
                </p>
                <div class="flex justify-center gap-4 mb-6">
                    <button
                        type="button"
                        class="px-6 py-3 bg-white/10 hover:bg-white/15 text-white border border-white/20 rounded-full min-w-[120px] transition-all duration-300 hover:-translate-y-1"
                        @click="showVerifyModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmVerify"
                        class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-full min-w-[120px] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-green-500/30"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showFalseModal" @close="showFalseModal = false" max-width="md">
            <div class="false-modal">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-400 to-red-600"></div>
                <div class="flex items-center justify-center mt-8 mb-4">
                    <span class="material-icons text-red-500 text-5xl">cancel</span>
                </div>
                <h2 class="text-2xl font-bold text-white text-center mb-6">Confirm False Report</h2>
                <p class="text-white/90 text-center text-lg mb-8">
                    Are you sure the report is false?
                </p>
                <div class="flex justify-center gap-4 mb-6">
                    <button
                        type="button"
                        class="px-6 py-3 bg-white/10 hover:bg-white/15 text-white border border-white/20 rounded-full min-w-[120px] transition-all duration-300 hover:-translate-y-1"
                        @click="showFalseModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmFalse"
                        class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-full min-w-[120px] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-red-500/30"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>
<style scoped>
/* Oceanic Theme Base */
.title-gradient {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
    margin-bottom: 2.5rem;
}

.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.92) 0%,
        rgba(0, 75, 150, 0.9) 50%,
        rgba(0, 51, 102, 0.92) 100%
    );
}

.form-section {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    padding: 2.5rem;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.9);
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.01em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 0.75rem;
}

/* Form Input Styles */
input[type="text"],
input[type="date"],
input[type="time"],
input[type="number"],
textarea,
select {
    background: rgba(255, 255, 255, 0.08) !important;
    backdrop-filter: blur(2px);
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 8px !important;
    transition: all 0.3s ease;
    width: 100% !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    padding: 0.75rem 1rem !important;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="time"]:focus,
input[type="number"]:focus,
textarea:focus,
select:focus {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(0, 204, 255, 0.5) !important;
    box-shadow: 0 0 0 2px rgba(0, 204, 255, 0.25) !important;
    outline: none !important;
}

textarea {
    min-height: 8rem !important;
    resize: vertical;
}

select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' height='24' viewBox='0 0 24 24' width='24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem !important;
    border: 1px solid rgba(0, 204, 255, 0.4) !important;
}

/* Fix dropdown options contrast */
select option {
    background-color: #003366 !important;
    color: white !important;
}

input[type="range"] {
    accent-color: #00ccff;
    height: 1.5rem;
}

/* Preview Styles */
.preview-section {
    background: rgba(0, 51, 102, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.preview-title {
    font-size: 1rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9) !important;
    margin-bottom: 1rem;
    letter-spacing: 0.01em;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    padding: 0.5rem;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease;
}

.preview-item:hover .preview-image {
    transform: scale(1.05);
}

.remove-button {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 4px;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.2s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
}

.preview-item:hover .remove-button {
    opacity: 1;
}

.remove-button:hover {
    background: rgba(0, 0, 0, 0.7);
    transform: scale(1.1);
}

/* Map Styles */
.map-container {
    position: relative;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    margin: 1.5rem 0;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.map-coordinates {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    background: rgba(0, 51, 102, 0.8);
    backdrop-filter: blur(4px);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.coordinate-label {
    color: #00ccff;
    font-weight: 600;
}

/* Button Styles */
.action-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.location-button {
    background: rgba(0, 204, 255, 0.15);
    color: #00ccff;
    border: 1px solid rgba(0, 204, 255, 0.3);
}

.location-button:hover {
    background: rgba(0, 204, 255, 0.25);
    transform: translateY(-1px);
}

.reset-button {
    background: rgba(255, 177, 66, 0.15);
    color: #ffb142;
    border: 1px solid rgba(255, 177, 66, 0.3);
}

.reset-button:hover {
    background: rgba(255, 177, 66, 0.25);
    transform: translateY(-1px);
}

.remove-location-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    background: rgba(255, 68, 68, 0.15);
    color: #ff4444;
    border: 1px solid rgba(255, 68, 68, 0.3);
    transition: all 0.3s ease;
}

.remove-location-button:hover {
    background: rgba(255, 68, 68, 0.25);
    transform: translateY(-1px);
}

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
    display: flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
}

.browse-button:hover,
.browse-button:focus {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
    outline: none;
}

.create-button,
.cancel-button,
.danger-button,
.verify-button {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 50px;
    min-width: 140px;
    text-align: center;
    transition: all 0.3s ease;
}

.create-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.cancel-button {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
}

.danger-button {
    background: linear-gradient(135deg, #e53935, #ff5252);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(255, 82, 82, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.verify-button {
    background: linear-gradient(135deg, #2e7d32, #4caf50);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.create-button:hover,
.cancel-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.danger-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(255, 82, 82, 0.4);
}

.verify-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

.create-button:active,
.cancel-button:active,
.danger-button:active,
.verify-button:active {
    transform: translateY(0);
}

/* Error Container */
.error-container {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid rgba(255, 68, 68, 0.2);
    border-radius: 8px;
    padding: 1.25rem;
    color: #ff4444;
    margin-bottom: 1.5rem;
}

/* Labels */
label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

/* Back Button */
.oceanic-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.oceanic-button:hover {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.oceanic-button a {
    color: white !important;
    text-decoration: none;
}

/* Modal Styling */
.verify-modal {
    position: relative;
    padding: 2rem;
    text-align: center;
    overflow: hidden;
}

.false-modal {
    position: relative;
    padding: 2rem;
    text-align: center;
    overflow: hidden;
}

.verify-modal .p-dialog-content,
.false-modal .p-dialog-content {
    background: #002147;
    border-radius: 16px;
    padding: 0;
    overflow: hidden;
}

.verify-modal .modal-title {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: white;
    text-align: center;
}

.verify-modal .modal-content {
    margin: 1.5rem 0;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    text-align: center;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

.modal-cancel-button {
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 120px;
}

.modal-confirm-button {
    padding: 0.75rem 1.5rem;
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 120px;
}

.verify-confirm-button {
    background: #4caf50;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.verify-confirm-button:hover {
    background: #43a047;
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    transform: translateY(-1px);
}

.false-confirm-button {
    background: #f44336;
    box-shadow: 0 4px 15px rgba(255, 82, 82, 0.3);
}

.false-confirm-button:hover {
    background: #e53935;
    box-shadow: 0 6px 20px rgba(255, 82, 82, 0.4);
    transform: translateY(-1px);
}

.modal-cancel-button:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

/* Custom Modal Styling for Inertia Modal Component */
:deep(.p-dialog) {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

:deep(.p-dialog-content) {
    border-radius: inherit;
}

@media (max-width: 640px) {
    .modal-content-direct {
        padding: 1.5rem;
    }

    .modal-title {
        font-size: 1.5rem;
    }

    .modal-content {
        font-size: 1rem;
    }

    .modal-cancel-button,
    .modal-confirm-button {
        padding: 0.75rem 1rem;
        min-width: 100px;
    }
}
</style>
