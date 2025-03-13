<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();

const formErrors = ref(null);
const previewImages = ref([]);
const props = defineProps({
    species: {
        type: Array,
        required: true,
    },
    municipalities: {
        type: Array,
        required: true,
    },
    barangays: {
        type: Array,
        required: true,
    }
});

const filteredBarangays = computed(() => {
    console.log('Municipality ID:', form.municipality_id);
    console.log('All barangays:', props.barangays);
    if (!form.municipality_id) return [];
    const filtered = props.barangays.filter(barangay => barangay.municipality_id === form.municipality_id);
    console.log('Filtered barangays:', filtered);
    return filtered;
});

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

// Add new refs for location functionality
const locationSource = ref('manual');
const isGeocodingInProgress = ref(false);
const showMap = ref(false);

// Nominatim reverse geocoding function
const reverseGeocode = async (latitude, longitude) => {
  isGeocodingInProgress.value = true;

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`);
    const data = await response.json();

    console.log("Nominatim response:", data);
    const address = data.address;
    let municipalityName = address.city || address.town || address.municipality;
    let barangayName = address.quarter || address.village || address.suburb || address.neighbourhood || address.hamlet;

    console.log("Found municipality:", municipalityName);
    console.log("Found barangay:", barangayName);

    form.municipality_id = '';
    form.barangay_id = '';

    if (municipalityName) {
      const matchedMunicipality = props.municipalities.find(m =>
        m.name.toLowerCase() === municipalityName.toLowerCase()
      );

      if (matchedMunicipality) {
        form.municipality_id = matchedMunicipality.id;

        if (barangayName && filteredBarangays.value.length > 0) {
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
  nextTick(() => {
    map.value = L.map('map').setView([form.latitude, form.longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    marker.value = L.marker([form.latitude, form.longitude], {
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
  });
};

// Update setLocationFromMap function
const setLocationFromMap = async () => {
  locationSource.value = 'gps';
  showMap.value = true;

  await nextTick();
  cleanupMap();
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

// Add cleanup function for map
const cleanupMap = () => {
  if (map.value) {
    map.value.remove();
    map.value = null;
    marker.value = null;
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

// Watch for showMap changes
watch(showMap, (newValue) => {
  if (!newValue) {
    cleanupMap();
  }
});

// Species
const searches = ref([]);
const dropdownVisibility = ref([]);
const isDropdownVisible = ref([]);

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

const closeDropdown = (event, index) => {
  if (event && !event.target.closest('.dropdown-container')) {
    dropdownVisibility.value[index] = false;
  }
};

// Use global setTimeout directly
const handleBlur = (event, index) => {
    closeTimeout = setTimeout(() => {
        closeDropdown(event, index);
    }, 100);
};

onMounted(() => {
    document.addEventListener('click', (event) => {
        dropdownVisibility.value.forEach((isVisible, index) => {
            if (isVisible) {
                closeDropdown(event, index);
            }
        });
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

// Add size options data
const sizeOptions = [
  { value: 'tiny', label: 'Tiny', description: '< 1 foot' },
  { value: 'small', label: 'Small', description: '1-3 feet' },
  { value: 'medium', label: 'Medium', description: '3-10 feet' },
  { value: 'large', label: 'Large', description: '10-20 feet' },
  { value: 'very_large', label: 'Very Large', description: '20-30 feet' },
  { value: 'giant', label: 'Giant', description: '> 30 feet' },
];

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

    <div class="min-h-screen bg-cover bg-center bg-gradient-overlay" style="background-image: url('/images/landing.jpg')">
      <div class="container mx-auto px-4 py-8">
        <div class="title-container mb-8">
          <h2 class="title-gradient">Create Sighting Report</h2>
        </div>
        <div class="max-w-4xl mx-auto space-y-6">
          <!-- Info Card -->
          <div class="oceanic-card">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a 1 1 0 100-2v-3a 1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  Your contribution helps protect marine wildlife. Please provide as much detail as possible.
                </p>
              </div>
            </div>
          </div>

          <!-- Sighting Report Container -->
          <div class="report-container">
            <h3 class="text-xl font-semibold text-white mb-4">Sighting Details</h3>
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
                  <input 
                    required 
                    id="certainty_level" 
                    type="range" 
                    min="1" 
                    max="10" 
                    v-model="form.certainty_level"
                    :style="{ '--value': form.certainty_level / 10 }"
                    class="w-full custom-range" 
                  />
                  <p class="certainty-value">{{ form.certainty_level }}</p>
                  <InputError class="mt-2" :message="form.errors.certainty_level" />
                </div>

                <!-- Map -->
                <div class="flex justify-center space-x-4 mb-4 sm:col-span-2">
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

                <div v-if="showMap" class="sm:col-span-2 relative rounded-xl overflow-hidden shadow-lg">
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

                <div>
                  <InputLabel for="municipality_id" value="Municipality" />
                  <select required v-model="form.municipality_id" class="w-full">
                    <option value="" disabled>Select a municipality</option>
                    <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
                      {{ municipality.name }}
                    </option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.municipality_id" />
                </div>

                <div>
                  <InputLabel for="barangay_id" value="Barangay" />
                  <select required v-model="form.barangay_id" class="w-full">
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
              </div>
            </form>
          </div>

          <!-- Species Information Section -->
          <div class="section-header">
            <h3 class="text-xl font-semibold text-white">Species Information</h3>
            <p class="text-blue-200 text-sm mt-1">Add details about the marine species you observed</p>
          </div>

          <div v-for="(species, index) in form.sightedSpecies" :key="index"
               class="species-card">
            <div class="flex justify-between items-center mb-4">
              <h4 class="text-lg font-medium text-indigo-900">Species #{{ index + 1 }}</h4>
              <div class="flex space-x-2">
                <button v-if="form.sightedSpecies.length > 1"
                        @click.prevent="removeSpeciesEntry(index)"
                        class="text-red-500 hover:text-red-700 transition-colors">
                  <span class="material-icons">delete</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
              <!-- Species Search with Improved Dropdown -->
              <div class="relative dropdown-container">
                <InputLabel :for="'species-' + index" value="Search Species" />
                <div class="relative">
                  <input
                    :id="'species-' + index"
                    v-model="searches[index]"
                    @focus="toggleDropdown(index)"
                    @input="filteredSpecies(index)"
                    @blur="(event) => handleBlur(event, index)"
                    placeholder="Start typing to search species..."
                    class="w-full border rounded-lg p-3 pl-10 focus:ring-2 focus:ring-indigo-500"
                    autocomplete="off"
                  />
                  <span class="absolute left-3 top-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </span>
                </div>

                <!-- Enhanced Dropdown -->
                <ul v-if="dropdownVisibility[index] && filteredSpecies(index).value.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-xl border border-gray-200 max-h-60 overflow-y-auto">
                  <li v-for="species in filteredSpecies(index).value"
                      :key="species.id"
                      @click="selectSpecies(species, index)"
                      class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b last:border-0">
                    <div class="font-medium text-gray-800">{{ species.name }}</div>
                    <div class="text-sm text-gray-500">Common name: {{ species.common_name || 'N/A' }}</div>
                  </li>
                </ul>
              </div>

              <!-- Size Selection with Visual Indicators -->
              <div>
                <InputLabel :for="'size-' + index" value="Approximate Size" />
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-2">
                  <label v-for="size in sizeOptions" :key="size.value"
                         class="size-option-label">
                    <input type="radio"
                           :value="size.value"
                           v-model="form.sightedSpecies[index].size"
                           class="sr-only" />
                    <div class="size-option-content">
                      <span class="text-sm font-medium">{{ size.label }}</span>
                      <span class="text-xs text-gray-500">{{ size.description }}</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Enhanced Textareas -->
              <div class="space-y-4">
                <div>
                  <InputLabel :for="'description-' + index" value="Physical Description" />
                  <div class="relative">
                    <textarea
                      :id="'description-' + index"
                      v-model="form.sightedSpecies[index].species_description"
                      rows="3"
                      class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200"
                      placeholder="Describe the physical appearance (color, patterns, distinctive features)"
                    ></textarea>
                    <div class="absolute bottom-2 right-2 text-xs text-gray-400">
                      Optional
                    </div>
                  </div>
                </div>

                <div>
                  <InputLabel :for="'behavior-' + index" value="Observed Behavior" required />
                  <div class="relative">
                    <textarea
                      :id="'behavior-' + index"
                      v-model="form.sightedSpecies[index].behavior_observed"
                      rows="3"
                      class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200"
                      placeholder="What was the species doing? (e.g., feeding, swimming, resting)"
                      required
                    ></textarea>
                    <div class="absolute bottom-2 right-2 text-xs text-gray-400">
                      Required
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Add Species Button -->
          <button @click.prevent="addSpeciesEntry"
                  class="add-species-button">
            <span class="material-icons">add_circle</span>
            <span>Add Another Species</span>
          </button>

          <!-- Submit Button Container -->
          <div class="text-center">
            <PrimaryButton type="submit">Submit Report</PrimaryButton>
          </div>
        </div>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
.bg-gradient-overlay {
    position: relative;
}

.bg-gradient-overlay::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(0, 40, 80, 0.8) 0%,
        rgba(0, 96, 128, 0.75) 50%,
        rgba(0, 48, 96, 0.8) 100%
    );
    pointer-events: none;
}

.title-gradient {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

.input-field {
    @apply mt-2 block w-full rounded-xl border-0 shadow-sm;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    color: white;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

/* ...rest of the new styles... */

.action-button:hover {
    background: rgba(0, 204, 255, 0.2);
    transform: translateY(-1px);
}

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

/* Add new map-related styles */
#map {
  @apply h-[300px] sm:h-[500px] w-full rounded-lg shadow-lg;
}

.leaflet-control-zoom {
  @apply shadow-lg rounded-lg overflow-hidden;
}

.leaflet-control-zoom a {
  @apply bg-white text-gray-700 hover:bg-gray-50 transition-colors;
}

/* New Styles for Species Section */
.size-option-label {
  @apply relative flex cursor-pointer rounded-lg p-2 focus:outline-none; /* reduced padding */
  background: linear-gradient(180deg, rgba(13, 71, 161, 0.6), rgba(0, 60, 120, 0.4));
  border: 1px solid rgba(255, 255, 255, 0.15);
  margin-bottom: 0.25rem; /* reduced margin */
}

.size-option-content {
  @apply flex flex-col rounded-lg w-full;
  padding: 0.5rem; /* reduced padding */
  background: transparent;
  border: 1px solid transparent;
}

.size-option-content span.text-sm {
  font-size: 0.9rem; /* smaller font size */
  margin-bottom: 0.25rem; /* reduced margin */
}

.size-option-content span.text-xs {
  font-size: 0.75rem; /* smaller font size */
}

/* Update the checkmark style */
.size-option-label input:checked + .size-option-content::before {
  content: '✓';
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  color: #ffffff;
  font-size: 1.25rem;
  font-weight: bold;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

/* Optional: Add a subtle indicator for selected option */
.size-option-label input:checked + .size-option-content::before {
  content: '✓';
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  color: #60a5fa;
  font-size: 1rem;
  font-weight: bold;
}

/* Update text colors */
label, 
h1, 
h2, 
h3, 
h4 {
  color: rgba(255, 255, 255, 0.9);
}

/* Make the species cards more oceanic */
.bg-gray-50 {
  background: rgba(0, 51, 102, 0.2);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Update button styling */
.submit-button,
button[type="submit"] {
  background: linear-gradient(135deg, #0288d1, #01579b);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.submit-button:hover,
button[type="submit"]:hover {
  background: linear-gradient(135deg, #039be5, #0277bd);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(2, 136, 209, 0.3);
}

/* Update container styles */
.species-section {
  margin-top: 2rem;
  padding: 1.5rem;
  background: rgba(0, 51, 102, 0.3);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.section-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
  background: rgba(0, 51, 102, 0.25);
  backdrop-filter: blur(12px);
  border-radius: 16px 16px 0 0;
  padding: 1.5rem;
}

.species-card {
  background: rgba(0, 51, 102, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
}

.species-card:hover {
  background: rgba(0, 51, 102, 0.5);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  transform: translateY(-2px);
}

.add-species-button {
  width: 100%;
  padding: 0.75rem;
  margin-top: 1rem;
  border: 2px dashed rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.9);
  background: rgba(0, 51, 102, 0.2);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.add-species-button:hover {
  background: rgba(0, 51, 102, 0.3);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

/* Remove these classes as we're no longer using them */
.bg-white,
.bg-gray-50 {
  background: transparent;
}

/* Update container styles for separation */
.report-container {
  background: rgba(0, 51, 102, 0.25);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

/* Remove the oceanic-container class as it's no longer needed */
.oceanic-container {
  display: none;
}

/* Add these new styles for the range input */
.custom-range {
  -webkit-appearance: none;
  height: 8px;
  background: linear-gradient(to right, #60a5fa 0%, #60a5fa calc(var(--value) * 10%), rgba(255, 255, 255, 0.2) calc(var(--value) * 10%));
  border-radius: 4px;
  outline: none;
  padding: 0;
  margin: 25px 0;
  position: relative;
}

.custom-range::before {
  content: '';
  position: absolute;
  top: -8px;
  left: 0;
  right: 0;
  height: 24px;
  background: repeating-linear-gradient(
    to right,
    transparent,
    transparent calc(10% - 4px),
    #60a5fa calc(10% - 4px),
    #60a5fa calc(10% - 2px),
    transparent calc(10% - 2px),
    transparent 10%
  ) 0 50%/100% 2px no-repeat;
  pointer-events: none;
}

.custom-range::after {
  content: '';
  position: absolute;
  top: -12px;
  left: 0;
  right: 0;
  height: 32px;
  background-image: radial-gradient(circle at center, rgba(96, 165, 250, 0.5) 0, rgba(96, 165, 250, 0.5) 3px, transparent 3px);
  background-size: 10% 100%;
  background-repeat: repeat-x;
  pointer-events: none;
}

.custom-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #60a5fa, #3b82f6);
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 0 10px rgba(96, 165, 250, 0.5);
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.custom-range::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.6);
}

.certainty-value {
  text-align: center;
  color: #60a5fa;
  font-size: 1.2rem;
  font-weight: 600;
  margin-top: 0.5rem;
  text-shadow: 0 0 10px rgba(96, 165, 250, 0.3);
}

/* Add these new styles for the species dropdown */
.dropdown-container {
  position: relative;
}

.dropdown-container input {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-color: rgba(255, 255, 255, 0.3);
}

.dropdown-container input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.dropdown-container ul {
  background: rgba(13, 71, 161, 0.95);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.dropdown-container li {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
}

.dropdown-container li:hover {
  background: rgba(59, 130, 246, 0.3);
  transform: translateX(4px);
}

.dropdown-container li .font-medium {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  margin-bottom: 0.25rem;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

.dropdown-container li .text-gray-500 {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
}

/* ...rest of existing styles... */

.gradient-title {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.8rem;
  font-weight: 700;
  background: linear-gradient(to right, #ffffff, #00ccff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 1.5rem;
  text-shadow: 0 2px 15px rgba(59, 130, 246, 0.2);
}

.title-container {
  position: relative;
  padding: 1rem;
  text-align: center;
  background: rgba(0, 51, 102, 0.4);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  max-width: 600px;
  margin: 0 auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.title-gradient {
  font-family: 'Montserrat', sans-serif;
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(to right, #ffffff, #00ccff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.5px;
  margin: 0;
}

/* Update container styles for consistent oceanic theme */
.report-container,
.oceanic-card,
.species-card,
.section-header {
  background: linear-gradient(180deg, rgba(13, 71, 161, 0.6), rgba(0, 60, 120, 0.4));
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 15px rgba(0, 149, 255, 0.15) inset;
}

/* Update form inputs and selects */
select,
textarea,
input[type="text"],
input[type="date"],
input[type="time"],
.dropdown-container input {
  background: rgba(13, 71, 161, 0.3) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  color: white !important;
}

select option {
  background: rgba(13, 71, 161, 0.95);
  color: white;
}

/* Update dropdowns */
.dropdown-container ul {
  background: linear-gradient(180deg, rgba(13, 71, 161, 0.95), rgba(0, 60, 120, 0.9));
  backdrop-filter: blur(12px);
}

.dropdown-container li:hover {
  background: rgba(30, 136, 229, 0.4);
}

/* Update size options */
.size-option-label {
  background: linear-gradient(180deg, rgba(13, 71, 161, 0.6), rgba(0, 60, 120, 0.4));
}

.size-option-label:hover {
  background: linear-gradient(180deg, rgba(25, 118, 210, 0.7), rgba(13, 71, 161, 0.5));
}

.size-option-label input:checked + .size-option-content {
  background: linear-gradient(180deg, rgba(30, 136, 229, 0.6), rgba(21, 101, 192, 0.5));
}

/* Update text colors for better visibility */
.dropdown-container li .font-medium {
  color: white;
}

.dropdown-container li .text-gray-500,
.text-gray-400 {
  color: rgba(255, 255, 255, 0.7);
}

/* Update container styles for stability */
.report-container,
.oceanic-card,
.species-card,
.section-header {
  background: linear-gradient(180deg, rgba(13, 71, 161, 0.6), rgba(0, 60, 120, 0.4));
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 15px rgba(0, 149, 255, 0.15) inset;
  transform: none; /* Prevent any transforms */
  transition: none; /* Remove transitions */
}

.species-card {
  background: rgba(0, 51, 102, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  transform: none !important; /* Force disable transforms */
  transition: none !important; /* Force disable transitions */
}

.species-card:hover {
  background: rgba(0, 51, 102, 0.4); /* Same as non-hover state */
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 15px rgba(0, 149, 255, 0.15) inset;
  transform: none !important;
}

/* ...rest of existing styles... */

/* Update submit button with gradient */
:deep(.primary-button) {
  background: linear-gradient(135deg, #0288d1, #01579b);
  transition: all 0.3s ease;
  border: none;
  padding: 0.75rem 2.5rem;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  box-shadow: 
    0 4px 12px rgba(2, 136, 209, 0.3),
    inset 0 1px 1px rgba(255, 255, 255, 0.2);
}

:deep(.primary-button:hover) {
  background: linear-gradient(135deg, #039be5, #0277bd);
  transform: translateY(-1px);
  box-shadow: 
    0 6px 16px rgba(2, 136, 209, 0.4),
    inset 0 1px 1px rgba(255, 255, 255, 0.3);
}

:deep(.primary-button:active) {
  transform: translateY(0);
  box-shadow: 
    0 2px 8px rgba(2, 136, 209, 0.3),
    inset 0 1px 1px rgba(255, 255, 255, 0.2);
}

/* ...rest of existing styles... */
</style>
