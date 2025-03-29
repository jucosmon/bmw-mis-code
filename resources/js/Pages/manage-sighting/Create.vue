<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
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
const maxDate = new Date().toISOString().split('T')[0];

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
    // Fix for Leaflet default icon
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

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

// // Add getFallbackLocation helper function
// const getFallbackLocation = async () => {
//     try {
//         const response = await fetch('https://ipapi.co/json/');
//         const data = await response.json();
//         return {
//             latitude: data.latitude,
//             longitude: data.longitude
//         };
//     } catch (error) {
//         console.error('Fallback location fetch failed:', error);
//         throw new Error('Could not retrieve fallback location');
//     }
// };

// Update setLocationFromMap function
const setLocationFromMap = async () => {
    // Check if geolocation is supported
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        // Use fallback immediately since GPS is not available
        return;
    }

    // Add detailed options for geolocation
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };

    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, options);
        });

        const { latitude, longitude } = position.coords;

        locationSource.value = 'manual';
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
        alert(`Location found: ${latitude}, ${longitude}`);

    } catch (error) {
        let errorMessage = 'Failed to fetch current location.';

        // Only use fallback for POSITION_UNAVAILABLE
        if (error.code === error.POSITION_UNAVAILABLE) {
            errorMessage = 'Location information is currently unavailable. Trying alternative method...';
            alert(errorMessage);

            // try {
            //     const fallbackLocation = await getFallbackLocation();
            //     form.latitude = fallbackLocation.latitude;
            //     form.longitude = fallbackLocation.longitude;
            //     showMap.value = true;
            //     await nextTick();
            //     await initializeMap();
            //     await reverseGeocode(fallbackLocation.latitude, fallbackLocation.longitude);
            //     alert(`Using approximate location: ${fallbackLocation.latitude}, ${fallbackLocation.longitude}`);
            // } catch (fallbackError) {
            //     console.error('Fallback location failed', fallbackError);
            //     alert('Could not determine your location through any available method.');
            // }
        } else if (error.code === error.PERMISSION_DENIED) {
            errorMessage = 'Location access was denied. Please enable location permissions in your browser settings.';
            alert(errorMessage);
        } else if (error.code === error.TIMEOUT) {
            errorMessage = 'Location request timed out. Please check your internet connection and try again.';
            alert(errorMessage);
        } else {
            alert(errorMessage);
        }
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
        <h2 class="title-gradient mb-6">Create Sighting Report</h2>

        <!-- Rest of your form content -->
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
                  Your contribution helps protect marine wildlife. Please provide as much detail as possible.
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

            <!-- Form Grid -->
            <div class="guideline-info-container">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="date" value="Date of the Incident" />
                  <TextInput required id="date" type="date" :max="maxDate" v-model="form.date" autocomplete="date" class="w-full" />
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
                  <p class="text-center text-white">{{ form.certainty_level }}</p>
                  <InputError class="mt-2" :message="form.errors.certainty_level" />
                </div>
              </div>
            </div>

            <!-- Location Section -->
            <div class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Location Information</h3>
              </div>

              <div class="item-content">
                <!-- Map -->
                <div class="flex justify-center space-x-4 mb-4 sm:col-span-2">
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

                <div v-if="showMap" class="sm:col-span-2 relative rounded-xl overflow-hidden shadow-lg mb-6">
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
                    <select required v-model="form.municipality_id" class="form-select">
                      <option value="" disabled>Select a municipality</option>
                      <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
                        {{ municipality.name }}
                      </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.municipality_id" />
                  </div>

                  <div>
                    <InputLabel for="barangay_id" value="Barangay" />
                    <select required v-model="form.barangay_id" class="form-select">
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
                      class="form-textarea"
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
                      class="form-textarea"
                      placeholder="Please share more information of the incident"
                    ></textarea>
                    <InputError class="mt-2" :message="form.errors.more_information" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Sighted Species -->
            <div class="space-y-6">
              <div v-for="(species, index) in form.sightedSpecies" :key="index"
                   class="item-card">
                <div class="item-header mb-4">
                  <h3 class="text-xl font-bold text-white">Species #{{ index + 1 }}</h3>
                </div>

                <div class="item-content space-y-4">
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
                        class="search-input"
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
                        class="search-dropdown">
                      <li v-for="species in filteredSpecies(index).value"
                          :key="species.id"
                          @click="selectSpecies(species, index)"
                          class="dropdown-item">
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
                          <span class="text-sm font-medium text-white">{{ size.label }}</span>
                          <span class="text-xs text-gray-300">{{ size.description }}</span>
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
                          class="form-textarea"
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
                          class="form-textarea"
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

                <!-- Action Buttons -->
                <div class="item-actions mt-6 pt-4 border-t border-white/10">
                  <div class="flex justify-end gap-2">
                    <button v-if="form.sightedSpecies.length > 1"
                            @click.prevent="removeSpeciesEntry(index)"
                            type="button"
                            class="action-button delete-button">
                      <span class="material-icons text-xl leading-none">delete</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Add Species Button -->
              <button @click.prevent="addSpeciesEntry"
                      type="button"
                      class="add-species-button">
                <span class="material-icons">add_circle</span>
                <span>Add Another Species</span>
              </button>
            </div>

            <!-- Media Upload Section -->
            <div class="item-card">
              <div class="item-header mb-4">
                <h3 class="text-xl font-bold text-white">Media Files</h3>
                <p class="text-sm text-white">Upload images or videos related to the sighting</p>
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
                    <div v-for="(img, index) in previewImages" :key="index" class="preview-item">
                      <img :src="img" alt="Preview" class="preview-image" />
                      <button @click="removeImage(index)" type="button" class="remove-button" title="Remove">
                        <span class="material-icons">close</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <InputError class="mt-2" :message="form.errors.mediaFiles" />
            </div>

            <!-- Form buttons -->
            <div class="flex justify-between items-center mt-6 m-3">
              <Link :href="backRoute"
                    class="cancel-button">
                Back
              </Link>
              <PrimaryButton type="submit"
                           :disabled="form.processing"
                           class="create-button"
                           :class="{ 'opacity-25': form.processing }">
                Submit Report
              </PrimaryButton>
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

/* Form containers - updated to match guideline */
.guideline-info-container, .item-card {
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

/* Form controls - updated styles */
.form-select, .form-textarea, :deep(input[type="text"]), :deep(input[type="date"]), :deep(input[type="time"]) {
    @apply bg-white/5 border-white/10 text-white text-sm;
    backdrop-filter: blur(4px);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.form-select:focus, .form-textarea:focus, :deep(input[type="text"]:focus), :deep(input[type="date"]:focus), :deep(input[type="time"]:focus) {
    @apply ring-1 ring-blue-400;
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
    border-color: rgba(147, 197, 253, 0.5);
}
.form-select option {
    background-color: #1a365d;
    color: white;
    padding: 0.5rem;
}
/* Button styles */
.action-button {
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.2);
    border: 1px solid rgba(255, 68, 68, 0.4);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

.delete-button:hover {
    background: rgba(255, 68, 68, 0.3);
    transform: translateY(-1px);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.08);
    border: 1px solid rgba(0, 204, 255, 0.2);
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

.create-button:hover {
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

/* Size options */
.size-option-label {
    @apply relative flex cursor-pointer rounded-lg border p-4 hover:bg-white/10 focus:outline-none;
    border-color: rgba(255, 255, 255, 0.1);
    transition: all 0.2s ease;
}

.size-option-label input:checked + .size-option-content {
    @apply border-blue-600 ring-2 ring-blue-600;
    background: rgba(0, 204, 255, 0.1);
}

.size-option-content {
    @apply flex flex-col rounded-lg border p-3 w-full;
    border-color: rgba(255, 255, 255, 0.1);
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
    grid-template-columns: repeat(auto-fill, minmax(100px, 120px));
    gap: 0.75rem;
    @apply p-2;
    justify-content: start;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    @apply rounded-lg overflow-hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    max-width: 120px;
}

.preview-image {
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

/* Map styles */
#map {
    @apply h-[300px] sm:h-[500px] w-full rounded-lg shadow-lg;
    z-index: 1;
    background: white !important;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

/* Location button */
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

/* Search input and dropdown styles */
.search-input {
    @apply text-sm;
    width: 100%;
    background-color: white;
    color: #333;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 12px 12px 12px 36px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.search-dropdown {
    position: absolute;
    z-index: 50;
    width: 100%;
    margin-top: 4px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
    max-height: 60vh;
    overflow-y: auto;
}

.dropdown-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background-color: rgba(74, 144, 226, 0.1);
}

/* Add Species Button - more modern styling */
.add-species-button {
    width: 100%;
    padding: 16px;
    border: 2px dashed rgba(100, 200, 255, 0.5);
    border-radius: 8px;
    background-color: rgba(0, 102, 204, 0.15);
    color: #00ccff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 16px;
}

.add-species-button:hover {
    background-color: rgba(0, 102, 204, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

.add-species-button .material-icons {
    font-size: 24px;
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

.big-delete-button .button-text {
    font-size: 16px;
}

.big-delete-button:hover {
    background-color: #ff0000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}

.item-card {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.item-content {
    padding: 1rem 0;
}

.media-section {
    margin-top: 1.5rem;
}

.item-actions {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
}

input[type="range"] {
    @apply accent-blue-500;
}

:deep(label) {
    font-family: 'Inter', sans-serif;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.5rem;
    display: block;
    letter-spacing: 0.01em;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
}

.form-textarea {
    min-height: 6rem;
    height: auto;
    resize: vertical;
    width: 100%;
}

.form-select {
    height: 2.5rem;
    width: 100%;
}
</style>
