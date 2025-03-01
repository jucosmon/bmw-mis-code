<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();

const formErrors = ref(null);
const previewImages = ref([]);
const municipalities = ref([]);
const barangays = ref([]);
const props = defineProps({
    species: {
        type: Array,
        required: true,
    }
});

onMounted(async () => {
  const response = await fetch('/municipalities');
  municipalities.value = await response.json();
});

const fetchBarangays = async (municipalityId) => {
  const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
  barangays.value = await response.json();
};

const backRoute = computed(() => route('sighting.index'));
const createRoute = computed(() => route('sighting.create'));

const form = useForm({
  certainty_level: 10,
  date: new Date().toISOString().split('T')[0],
  time: new Date().toTimeString().split(' ')[0],
  latitude: 9.57849189779755,
  longitude: 123.74536514282228,
  detailed_location: '',
  more_information: '',
  municipality_id: '',
  barangay_id: '',
  mediaFiles: [],
  sightedSpecies: [
    {
      size: '',
      species_description: '',
      behavior_observed: '',
      species_id: '',
    },
  ],
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
  if (!form.sightedSpecies.length) {
    alert("Please add at least one species.");
    return;
  }
  form.post(createRoute.value, {
    onSuccess: () => {
      formErrors.value = null;
    },
    onError: (errors) => {
      formErrors.value = errors;
    },
  });
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

// Species
const searches = ref([]);
const dropdownVisibility = ref([]);

const filteredSpecies = (index) => {
  return computed(() => {
    const searchTerm = searches.value[index]?.toLowerCase() || '';
    console.log('Filtering species with term:', searchTerm);
    return props.species.filter((species) =>
      species.name.toLowerCase().includes(searchTerm)
    );
  });
};

const addSpeciesEntry = () => {
    form.sightedSpecies.push({
        size: '',
        species_description: '',
        behavior_observed: '',
        species_id: '',
    });
    searches.value.push(''); // Ensure this is done
    dropdownVisibility.value.push(false);
    console.log('Added species entry:', form.sightedSpecies);
    console.log('Updated searches:', searches.value); // Log the updated searches array
};

const selectSpecies = (species, index) => {
    console.log('Selected species:', species); // Debugging line
    console.log('Index:', index); // Log the index
    console.log('Searches length:', searches.value.length); // Log the length of searches

    searches.value[index] = species.name; // Update the search term with the selected species name
    form.sightedSpecies[index].species_id = species.id; // Assign species_id
    dropdownVisibility.value[index] = false; // Hide dropdown
    console.log('Species ID assigned:', species.id); // Debugging line

};

const toggleDropdown = (index) => {
  dropdownVisibility.value[index] = true; // Show dropdown on focus
  console.log('Toggled dropdown for index:', index);
};

let closeTimeout; // Variable to hold the timeout ID

const closeDropdown = (index) => {
    if (!event.target.closest('.dropdown-container')) {
        isDropdownVisible.value[index] = false;
  }
};



// Use global setTimeout directly
const handleBlur = (index) => {
    closeTimeout = setTimeout(() => {
        closeDropdown(index);
    }, 100);
};

onMounted(() => {
    document.addEventListener('click', (event) => {
        for (let i = 0; i < isDropdownVisible.value.length; i++) {
            if (isDropdownVisible.value[i]) {
                document.addEventListener('click', closeDropdown);
            }
        }
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
    clearTimeout(closeTimeout); // Clear the timeout on unmount

});

const removeSpeciesEntry = (index) => {
  form.sightedSpecies.splice(index, 1);
  searches.value.splice(index, 1);
  dropdownVisibility.value.splice(index, 1);
  console.log('Removed species entry at index:', index);
};

</script>

<template>
  <Head title="Create Sighting Report" />

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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Report New Sighting</h2>

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
              <InputLabel for="certainty_level" value="Certainty Level (1-10)" />
              <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full" />
              <p class="text-center">{{ form.certainty_level }}</p>
              <InputError class="mt-2" :message="form.errors.certainty_level" />
            </div>

            <!-- Map -->
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
                class="rounded-lg border shadow z-0"
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

            <!-- Sighted Species -->
            <div v-for="(species, index) in form.sightedSpecies" :key="index" class="border p-5 rounded-lg mb-4 sm:col-span-2 bg-gray-50">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <div class="relative dropdown-container">
                    <InputLabel :for="'species-' + index" value="Species Involved" />
                    <input
                      :id="'species-' + index"
                      v-model="searches[index]"
                      @focus="toggleDropdown(index)"
                      @input="filteredSpecies(index)"
                      @blur="handleBlur"
                      placeholder="Search and select what species is involved..."
                      class="w-full border rounded-lg p-2"
                      autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors?.sightedSpecies?.[index]?.species_id" />

                    <!-- Dropdown -->
                    <ul
                      :id="'dropdown-' + index"
                      v-if="dropdownVisibility[index] && filteredSpecies(index).value.length > 0"
                      class="absolute bg-white border rounded-lg shadow-lg w-full max-h-40 overflow-y-auto z-10 mt-1"
                    >
                      <li
                        v-for="species in filteredSpecies(index).value"
                        :key="species.id"
                        @click="selectSpecies(species, index)"
                        class="px-4 py-2 hover:bg-indigo-100 cursor-pointer"
                      >
                        {{ species.name }}
                      </li>
                    </ul>
                  </div>
                </div>
                <div>
                  <InputLabel :for="'size-' + index" value="Size" />
                  <select v-model="form.sightedSpecies[index].size" class="w-full" required>
                    <option value="" disabled>Select an option</option>
                    <option value="tiny">Tiny (Less than 1 foot)</option>
                    <option value="small">Small (1 - 3 feet)</option>
                    <option value="medium">Medium (3 - 10 feet)</option>
                    <option value="large">Large (10 - 20 feet)</option>
                    <option value="very_large">Very Large (20 - 30 feet)</option>
                    <option value="giant">Giant (Over 30 feet)</option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.sightedSpecies?.[index]?.size" />
                </div>
                <div>
                  <InputLabel :for="'description-' + index" value="Species Description" />
                  <textarea
                    :id="'description-' + index"
                    v-model="form.sightedSpecies[index].species_description"
                    class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                    placeholder="Please add physical description of the species especially if you can't identify"
                  ></textarea>
                  <InputError class="mt-2" :message="form.errors.sightedSpecies?.[index]?.species_description" />
                </div>
                <div>
                  <InputLabel :for="'behavior-' + index" value="Behavior Observed" />
                  <textarea
                    :id="'behavior-' + index"
                    v-model="form.sightedSpecies[index].behavior_observed"
                    class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                    placeholder="(e.g. feeding, swimming, resting)"
                    required
                  ></textarea>
                  <InputError class="mt-2" :message="form.errors.sightedSpecies?.[index]?.behavior_observed" />
                </div>
              </div>
              <div class="flex justify-end mt-5">
                <button v-if="form.sightedSpecies.length > 1" @click.prevent="removeSpeciesEntry(index)">
                  <span class="material-icons text-xl mr-2 leading-none text-red-600">delete</span>
                </button>
                <button @click.prevent="addSpeciesEntry">
                  <span class="material-icons text-xl mr-2 leading-none text-indigo-900">add_circle</span>
                </button>
              </div>
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
              <input type="file" accept="image/*,video/*" id="mediaFiles" @change="handleFileChange" multiple class="file-input w-full" />
              <div v-if="previewImages.length" class="mt-2 flex gap-4">
                <div v-for="(img, index) in previewImages" :key="index" class="relative">
                  <img :src="img" alt="Preview" class="w-20 h-20 object-cover rounded-lg" />
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
