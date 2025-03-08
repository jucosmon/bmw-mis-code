<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref } from 'vue';

const page = usePage(); // Ensure page is initialized

const props = defineProps({
    strandedIncident:  {
        type: Object,
        required: true
    }
});

Head

// Add defensive check
if (!page || !page.props) {
    console.error('Page object is null or undefined');
}

const deletedImages = ref([]);
const previewNewImages = ref([]);
const municipalities = ref([]);
const barangays = ref([]);

const fetchBarangays = async (municipalityId = props.strandedIncident.municipality_id) => {
    if (!municipalityId) return;
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
};

onMounted(async () => {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    if (props.strandedIncident.municipality_id) {
        await fetchBarangays();
    }
});


const existingImages = ref(props.strandedIncident.mediaFiles ? props.strandedIncident.mediaFiles : []);

const backRoute = computed(() => {
    return route('stranded.incident.view', { id: props.strandedIncident.id });
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
  municipality_id: props.strandedIncident.municipality_id || '',
  barangay_id: props.strandedIncident.barangay_id || '',
  report_status: props.strandedIncident.report_status || 'pending',
  mediaFiles: [],
  deletedImages: [],
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

    // Check for basic field changes
    const dataChanged = Object.keys(currentData).some((key) => {
        if (key === 'mediaFiles' || key === 'deletedImages') return false;
        return normalizeValue(currentData[key]) !== normalizeValue(props.strandedIncident[key]);
    });

    // Check if media files or deleted images changed
    const mediaFilesChanged = form.mediaFiles.length > 0;
    const deletedImagesChanged = deletedImages.value.length > 0;

    return dataChanged || mediaFilesChanged || deletedImagesChanged;
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
const submit = () => {
    if (hasChanges.value) {
        form.deletedImages = deletedImages.value;

        form.post(updateRoute.value, {
            onSuccess: () => {
                formErrors.value = null;
            },
            onError: (errors) => {
                formErrors.value = errors;
            },
        });
    } else {
        alert('No changes detected in the form.');
    }
};

// location
// Map references
const map = ref(null);
const marker = ref(null);
const locationSource = ref('original'); // 'original', 'gps', or 'manual'
const isGeocodingInProgress = ref(false);
const showMap = ref(true);
const originalLocation = ref({
    latitude: props.strandedIncident.latitude,
    longitude: props.strandedIncident.longitude,
    municipality_id: props.strandedIncident.municipality_id,
    barangay_id: props.strandedIncident.barangay_id,
    detailed_location: props.strandedIncident.detailed_location
});

// Initialize Leaflet map
onMounted(() => {
  nextTick(() => {
    if (!form) {
      console.error('Form object is null');
      return;
    }
    console.log('Form object:', form); // Debugging line
    map.value = L.map('map').setView([form.latitude, form.longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    marker.value = L.marker([form.latitude, form.longitude], {
      draggable: true,
    }).addTo(map.value);

    marker.value.on('dragend', (e) => {
      const { lat, lng } = e.target.getLatLng();
      form.latitude = lat;
      form.longitude = lng;
    });
  });
});


// Use current location
const setLocationFromMap = async () => {
  locationSource.value = 'gps';
  showMap.value = true;

  await nextTick();

  if (!map.value) {
    initializeMap();
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude } = position.coords;
        form.latitude = latitude;
        form.longitude = longitude;
        map.value.setView([latitude, longitude], 13);
        marker.value.setLatLng([latitude, longitude]);
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
        return;
    }

    cleanupMap();

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
};

// Add remove GPS location function
const removeGpsLocation = () => {
    locationSource.value = 'manual';
    showMap.value = false;  // Hide map
    form.latitude = '';
    form.longitude = '';
    form.municipality_id = '';
    form.barangay_id = '';
    form.detailed_location = ''; // Ensure detailed location is emptied
    cleanupMap();
};

// Initialize map on mount
onMounted(() => {
    nextTick(() => {
        if (originalLocation.value.latitude && originalLocation.value.longitude) {
            initializeMap();
        }
    });
});

// Add reset to original location function
const resetToOriginal = () => {
    locationSource.value = 'original';
    showMap.value = true; // Show map when resetting to original

    // Wait for DOM update before initializing map
    nextTick(() => {
        form.latitude = originalLocation.value.latitude;
        form.longitude = originalLocation.value.longitude;
        form.municipality_id = originalLocation.value.municipality_id;
        form.barangay_id = originalLocation.value.barangay_id;
        form.detailed_location = originalLocation.value.detailed_location;

        // Fetch barangays for the original municipality
        if (originalLocation.value.municipality_id) {
            fetchBarangays(originalLocation.value.municipality_id);
        }

        // Initialize map if it doesn't exist
        if (!map.value) {
            initializeMap();
        } else {
            map.value.setView([form.latitude, form.longitude], 13);
            marker.value.setLatLng([form.latitude, form.longitude]);
        }
    });
};

// Update cleanup function
const cleanupMap = () => {
  if (map.value) {
    map.value.remove();
    map.value = null;
    marker.value = null;
  }
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
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Stranded Incident</h2>

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
                        <select v-model="form.condition" class="w-full" required>
                            <option value="" disabled>Select an option</option>
                            <option value="alive">Alive</option>
                            <option value="dead">Dead</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.condition" />
                        </div>
                        <div>
                        <InputLabel for="sea_state" value="Sea State" />
                        <select v-model="form.sea_state" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="calm">Calm</option>
                            <option value="moderate">Moderate</option>
                            <option value="rough">Rough</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sea_state" />
                        </div>  <div>
                        <InputLabel for="weather" value="Weather" />
                        <select v-model="form.weather" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="sunny">Sunny</option>
                            <option value="cloudy">Cloudy</option>
                            <option value="rainy">Rainy</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.weather" />
                        </div>  <div>
                        <InputLabel for="beach_type" value="Beach type" />
                        <select v-model="form.beach_type" class="w-full">
                            <option value="" disabled>Select an option</option>
                            <option value="mangrove">Mangrove</option>
                            <option value="rocky">Rocky</option>
                            <option value="sandy">Sandy</option>
                            <option value="reef">Reef</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.beach_type" />
                        </div>

                        <!--Map-->
                        <div class="mt-4 sm:col-span-2 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900">Location Details</h3>
                                <div class="flex space-x-2">
                                    <!-- Always show Use Current Location -->
                                    <button
                                        @click.prevent="setLocationFromMap"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center space-x-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <span>Use Current Location</span>
                                    </button>
                                    <!-- Show Reset to Original if map is not visible or location is not original -->
                                    <button
                                        v-if="!showMap || locationSource !== 'original'"
                                        @click="resetToOriginal"
                                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
                                    >
                                        Reset to Original
                                    </button>
                                </div>
                            </div>

                            <div v-if="showMap" class="relative rounded-xl overflow-hidden shadow-lg">
                                <div id="map" class="h-[400px] w-full z-0"></div>
                                <!-- Move Remove GPS button to top-right corner of map -->
                                <div class="absolute top-4 right-4 z-10">
                                    <button
                                        @click="removeGpsLocation"
                                        class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors shadow-lg"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Remove GPS
                                    </button>
                                </div>
                                <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm p-3 rounded-lg shadow-md">
                                    <p class="text-sm font-medium text-gray-700">
                                        Latitude: {{ form.latitude || 'Not Set' }}<br>
                                        Longitude: {{ form.longitude || 'Not Set' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Update municipality and barangay sections with loading states -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="municipality_id" value="Municipality" />
                                    <select
                                        v-model="form.municipality_id"
                                        @change="fetchBarangays(form.municipality_id)"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="locationSource === 'gps' && isGeocodingInProgress"
                                    >
                                        <option value="" disabled>Select a municipality</option>
                                        <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                        {{ municipality.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel for="barangay_id" value="Barangay" />
                                    <select
                                        v-model="form.barangay_id"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="locationSource === 'gps' && isGeocodingInProgress"
                                    >
                                        <option value="" disabled>Select a barangay</option>
                                        <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
                                        {{ barangay.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
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

                    <!-- Submit and Cancel Buttons -->
                    <div class="flex items-center justify-between mt-6">
                        <Link :href="backRoute" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900">
                            Update Stranded Incident
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
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

/* Add new map-related styles */
.map-container {
  @apply relative rounded-xl overflow-hidden shadow-lg transition-all duration-300;
}

.map-controls {
  @apply absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm p-3 rounded-lg shadow-md
         text-sm font-medium text-gray-700;
}

.location-button {
  @apply px-4 py-2 rounded-lg transition-all duration-200
         focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.gps-button {
  @apply bg-indigo-600 text-white hover:bg-indigo-700
         focus:ring-indigo-500;
}

.remove-gps-button {
  @apply bg-red-600 text-white hover:bg-red-700
         focus:ring-red-500;
}

/* Enhance map interaction styles */
#map {
  @apply rounded-lg border shadow-lg transition-all duration-300;
}

.leaflet-control-zoom {
  @apply shadow-lg rounded-lg overflow-hidden;
}

.leaflet-control-zoom a {
  @apply bg-white text-gray-700 hover:bg-gray-50 transition-colors;
}
</style>
