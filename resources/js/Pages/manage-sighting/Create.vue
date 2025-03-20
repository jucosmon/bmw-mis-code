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

// Update geocodeLocation function
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

// Update watcher for municipality and barangay
watch([() => form.municipality_id, () => form.barangay_id], async ([newMunicipality, newBarangay]) => {
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
}, { immediate: false });

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

// Add cleanup function for map
const cleanupMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (marker.value) {
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

    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Info Card -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Your contribution helps protect marine wildlife. Please provide as much detail as possible.
              </p>
            </div>
          </div>
        </div>
        <h1 class="text-2xl font-semibold text-gray-800 text-center">Create Sighting Report</h1>

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

              <!-- Sighted Species -->
              <div class="bg-white rounded-xl shadow-lg p-6 mb-8 sm:col-span-2">
                <div class="border-b pb-4 mb-6">
                  <h3 class="text-xl font-semibold text-gray-800">Species Information</h3>
                  <p class="text-gray-600 text-sm mt-1">Add details about the marine species you observed</p>
                </div>

                <div v-for="(species, index) in form.sightedSpecies" :key="index"
                     class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200 hover:shadow-md transition-shadow">
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
                        class="w-full py-3 border-2 border-dashed border-indigo-300 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors flex items-center justify-center space-x-2">
                  <span class="material-icons">add_circle</span>
                  <span>Add Another Species</span>
                </button>
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
  @apply relative flex cursor-pointer rounded-lg border p-4 hover:bg-gray-50 focus:outline-none;
}

.size-option-label input:checked + .size-option-content {
  @apply border-indigo-600 ring-2 ring-indigo-600;
}

.size-option-content {
  @apply flex flex-col rounded-lg border p-3 w-full;
}

.step {
  @apply flex flex-col items-center;
}

.step-circle {
  @apply w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold;
}

.step.active .step-circle {
  @apply bg-indigo-600 text-white;
}

.step-text {
  @apply text-sm mt-2 text-gray-600;
}

.step-line {
  @apply flex-1 h-px bg-gray-200 mx-4;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .step-text {
    @apply text-xs;
  }

  .step-circle {
    @apply w-6 h-6 text-sm;
  }

  .step-line {
    @apply mx-2;
  }
}
</style>
