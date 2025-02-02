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

const page = usePage();
const formErrors = ref(null);
const previewImages = ref([]);
const municipalities = ref([]);
const barangays = ref([]);
const props = defineProps({});


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
  const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
  barangays.value = await response.json();
};

const backRoute = computed(() => route('stranded.incident.index'));
const createRoute = computed(() => route('stranded.incident.create'));

const form = useForm({
  certainty_level: 10,
  date: new Date().toISOString().split('T')[0],
  time: new Date().toTimeString().split(' ')[0],
  species_involved: '',
  quantity: 1,
  condition: '',
  latitude: 9.57849189779755,
  longitude: 123.74536514282228,
  sea_state: '',
  weather: '',
  beach_type: '',
  detailed_location: '',
  more_information: '',
  municipality_id: '',
  barangay_id: '',
  mediaFiles: [],
});

const handleFileChange = (event) => {
  const files = event.target.files;
  form.mediaFiles = Array.from(files); // Store the selected files in form.mediaFiles

  previewImages.value = Array.from(files).map((file) => {
    return URL.createObjectURL(file);
  });
};

const removeImage = (index) => {
  previewImages.value.splice(index, 1);
  form.mediaFiles.splice(index, 1);
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

// location
// Map references
const map = ref(null);
const marker = ref(null);

// Initialize Leaflet map
onMounted(() => {
  nextTick(() => {
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
const setLocationFromMap = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude } = position.coords;

        // Update the form's latitude and longitude
        form.latitude = latitude; // No .value needed
        form.longitude = longitude; // No .value needed

        // Update the map view and marker position
        map.value.setView([latitude, longitude], 13);
        marker.value.setLatLng([latitude, longitude]);
      },
      () => {
        alert('Failed to fetch current location. Please allow location access.');
      }
    );
  } else {
    alert('Geolocation is not supported by your browser.');
  }
};

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
            <div class="mt-4 sm:col-span-2">
                <button
                    @click.prevent="setLocationFromMap"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm w-full"
                >
                    Use Current Location
                </button>
                <p class="text-sm text-gray-600 mt-2">
                    Latitude: {{ form.latitude || 'Not Set' }}, Longitude: {{ form.longitude || 'Not Set' }}
                </p>
                <div
                    id="map"
                    style="height: 400px; width: 100%; margin-top: 10px;"
                    class="rounded-lg border shadow"
                ></div>
                </div>
            <div>
              <InputLabel for="municipality_id" value="Municipality" />
              <select required v-model="form.municipality_id" @change="fetchBarangays(form.municipality_id)" class="w-full">
                <option value="" disabled>Select a municipality</option>
                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                  {{ municipality.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="form.errors.municipality_id" />
            </div>

            <div>
              <InputLabel for="barangay_id" value="Barangay" />
              <select required v-model="form.barangay_id" class="w-full">
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
              <input type="file" accept="image/*,video/*"  id="mediaFiles" @change="handleFileChange" multiple class="file-input w-full"/>
              <div v-if="previewImages.length" class="mt-2 flex gap-4">
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
/* Custom styles can go here */
</style>



<style scoped>
/* Hide the file name (text) but keep the button */
.file-input {
  position: relative;
  overflow: hidden;
  width: 100%; /* Adjust as needed */
  height: 40px; /* Adjust height as needed */
  color: white;


}
#map {
    height: 400px; /* Ensure this is set */
    width: 100%; /* Ensure this is set */
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
