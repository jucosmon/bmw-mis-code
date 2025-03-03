<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const page = usePage();
const formErrors = ref(null);
const previewImages = ref([]);
const municipalities = ref([]);
const barangays = ref([]);
const locationSource = ref('manual'); // 'manual' or 'gps'
const isGeocodingInProgress = ref(false);
const showMap = ref(false);
const props = defineProps({});

const form = useForm({
  certainty_level: 10,
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

onMounted(async () => {
  console.log("Mounting...");
  try {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();
    console.log("Municipalities:", municipalities.value);
  } catch (error) {
    console.error("Error fetching municipalities:", error);
  }
});

const fetchBarangays = async (municipalityId) => {
  if (!municipalityId) return;

  try {
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
  } catch (error) {
    console.error("Error fetching barangays:", error);
  }
};

// Watch for changes in municipality_id after form is initialized
watch(() => form.municipality_id, (newValue) => {
  if (newValue) {
    fetchBarangays(newValue);
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
      const matchedMunicipality = municipalities.value.find(m =>
        m.name.toLowerCase() === municipalityName.toLowerCase()
      );

      if (matchedMunicipality) {
        form.municipality_id = matchedMunicipality.id;
        await fetchBarangays(matchedMunicipality.id);

        // Find matching barangay in our database
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

const submit = () => {
  if (createRoute.value) {
    form.post(createRoute.value, {
      onSuccess: () => {
        formErrors.value = null; // Clear errors on successful submission
      },
      onError: (errors) => {
        formErrors.value = errors; // Set errors on failed submission
      },
    });
  } else {
    console.error("Create route is undefined");
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
  locationSource.value = 'gps';
  showMap.value = true;

  // Wait for the map container to be available in DOM
  await nextTick();

  // Clean up existing map if any
  cleanupMap();

  // Initialize new map
  initializeMap();

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude } = position.coords;
        form.latitude = latitude;
        form.longitude = longitude;

        if (map.value) {
          map.value.setView([latitude, longitude], 13);
          marker.value.setLatLng([latitude, longitude]);
          await reverseGeocode(latitude, longitude);
        }
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
</script>

<template>
  <Head title="Create Stranded Incident" />

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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Report New Stranded Incident</h2>

      <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Error Messages -->
          <div v-if="formErrors" class="p-4 bg-red-100 border border-red-400 rounded-lg text-red-600">
            <ul class="list-disc ml-4">
              <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
            </ul>
          </div>

          <!-- Form Grid -->
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
            </div>
            <div>
              <InputLabel for="weather" value="Weather" />
              <select v-model="form.weather" class="w-full">
                <option value="" disabled>Select an option</option>
                <option value="sunny">Sunny</option>
                <option value="cloudy">Cloudy</option>
                <option value="rainy">Rainy</option>
              </select>
              <InputError class="mt-2" :message="form.errors.weather" />
            </div>
            <div>
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

            <!--Location Selection Method-->
            <div class="sm:col-span-2">
              <div class="flex justify-center space-x-4 mb-4">
                <button
                  @click.prevent="setLocationFromMap"
                  class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm flex-1"
                  :class="{ 'bg-indigo-800': showMap }"
                >
                  Use GPS Location
                </button>
              </div>

              <!--Map (only shown when GPS is activated)-->
              <div v-if="showMap" class="relative">
                <div
                  id="map"
                  style="height: 400px; width: 100%;"
                  class="rounded-lg border shadow z-0 mb-4"
                ></div>

                <!-- Remove GPS Location button -->
                <button
                  @click.prevent="removeGpsLocation"
                  class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-lg shadow-sm"
                >
                  Remove GPS Location
                </button>

                <p class="text-sm text-gray-600 mb-2">
                  Latitude: {{ form.latitude || 'Not Set' }}, Longitude: {{ form.longitude || 'Not Set' }}
                </p>

                <div v-if="isGeocodingInProgress" class="text-sm text-indigo-600 mb-2">
                  Looking up location details...
                </div>
              </div>
            </div>

            <div>
              <InputLabel for="municipality_id" value="Municipality" />
              <select v-model="form.municipality_id" @change="fetchBarangays(form.municipality_id)" class="w-full" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                <option value="" disabled>Select a municipality</option>
                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                  {{ municipality.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="form.errors.municipality_id" />
            </div>

            <div>
              <InputLabel for="barangay_id" value="Barangay" />
              <select v-model="form.barangay_id" class="w-full" :disabled="locationSource === 'gps' && isGeocodingInProgress">
                <option value="" disabled>Select a barangay</option>
                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
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

            <div class="sm:col-span-2">
              <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
              <input type="file" accept="image/*,video/*" id="mediaFiles" @change="handleFileChange" multiple class="file-input w-full"/>
              <div v-if="previewImages.length" class="mt-2 flex gap-4 flex-wrap">
                <div v-for="(img, index) in previewImages" :key="index" class="relative">
                  <img :src="img" alt="Preview" class="w-20 h-20 object-cover rounded-lg"/>
                  <button @click="removeImage(index)" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1">X</button>
                </div>
              </div>
              <InputError class="mt-2" :message="form.errors.mediaFiles" />
            </div>
          </div>

          <div class="text-center mt-6">
            <PrimaryButton type="submit">Submit Report</PrimaryButton>
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
  width: 100%;
  height: 40px;
  color: white;
}

#map {
  height: 400px;
  width: 100%;
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
</style>
