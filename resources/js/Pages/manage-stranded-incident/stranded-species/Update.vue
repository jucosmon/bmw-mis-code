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
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const formErrors = ref(null);
const page = usePage(); // Ensure page is initialized

const props = defineProps({
    strandedSpecies: {
        type: Object,
        required: true,
    },
    species: {
        type: Array,
        required: true,
    }
});

// Add defensive check
if (!page || !page.props) {
    console.error('Page object is null or undefined');
}

const backRoute = computed(() => route('stranded.species.view', { id: props.strandedSpecies.id }));
const updateRoute = computed(() => route('stranded.species.update', { id: props.strandedSpecies.id }));

const form = useForm({
    condition_code: props.strandedSpecies.condition_code || 1,
    latitude: props.strandedSpecies.latitude || null,
    longitude: props.strandedSpecies.longitude || null,
    sex: props.strandedSpecies.sex || 'unknown',
    length: props.strandedSpecies.length ||  null,
    weight: props.strandedSpecies.weight ||  null,
    girth: props.strandedSpecies.girth || null,
    disposition: props.strandedSpecies.disposition || '',
    disposal_site: props.strandedSpecies.disposal_site || '',
    more_information: props.strandedSpecies.more_information ||  '',
    is_released: props.strandedSpecies.is_released ||  false,
    species_id: props.strandedSpecies.species_id ||  null,
});

const submit = () => {
  form.post(updateRoute.value, {
    onSuccess: () => {
      formErrors.value = null; // Clear errors on successful submission
    },
    onError: (errors) => {
      formErrors.value = errors; // Set errors on failed submission
    },
  });
};

// Map references
const map = ref(null);
const marker = ref(null);

// Map state controls
const showMap = ref(false);
const originalCoordinates = {
    latitude: props.strandedSpecies.latitude,
    longitude: props.strandedSpecies.longitude
};

// Modified map initialization
onMounted(() => {
    // Check if we have valid coordinates
    if (form.latitude && form.longitude) {
        showMap.value = true;
        nextTick(() => {
            initializeMap();
        });
    }
});

// Initialize map function
const initializeMap = () => {
    if (!showMap.value || !form.latitude || !form.longitude) return;

    try {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        // Fix for Leaflet default icon
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: markerIcon,
            iconUrl: markerIcon,
            shadowUrl: markerShadow,
        });

        map.value = L.map('map').setView([form.latitude, form.longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map.value);

        if (marker.value) {
            marker.value.remove();
        }

        marker.value = L.marker([form.latitude, form.longitude], {
            draggable: true
        }).addTo(map.value);

        marker.value.on('dragend', (e) => {
            const { lat, lng } = e.target.getLatLng();
            form.latitude = lat;
            form.longitude = lng;
        });

        // Force a map refresh
        map.value.invalidateSize();
    } catch (error) {
        console.error('Error initializing map:', error);
    }
};

// Show map handler
const handleShowMap = async () => {
    if (!form.latitude || !form.longitude) {
        alert('No coordinates available to show on map');
        return;
    }

    showMap.value = true;
    // Wait for DOM to update
    await nextTick();
    // Need to wait a bit more for the map container to be fully rendered
    setTimeout(() => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        initializeMap();
        // Force a map refresh
        if (map.value) {
            map.value.invalidateSize();
        }
    }, 100);
};

// Remove map handler
const handleRemoveMap = () => {
    showMap.value = false;
    form.latitude = null;
    form.longitude = null;

    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (marker.value) {
        marker.value.remove();
        marker.value = null;
    }
};

// Reset coordinates handler
const handleResetCoordinates = () => {
    if (originalCoordinates.latitude && originalCoordinates.longitude) {
        form.latitude = originalCoordinates.latitude;
        form.longitude = originalCoordinates.longitude;
        showMap.value = true;
        nextTick(() => {
            initializeMap();
        });
    } else {
        handleRemoveMap();
    }
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
            alert(`Using approximate location: ${fallbackLocation.latitude}, ${fallbackLocation.longitude}`);
        } catch (fallbackError) {
            console.error('Fallback location failed', fallbackError);
        }
    }
};

// Cleanup on unmount
onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (marker.value) {
        marker.value.remove();
        marker.value = null;
    }
    document.removeEventListener('click', closeDropdown);
});



// Search and Select for species
const search = ref('');
const isDropdownVisible = ref(false);

// Set the search input to the existing species name if species is defined
onMounted(() => {
    if (props.species && props.species.length) {
        const existingSpecies = props.species.find(species => species.id === form.species_id);
        if (existingSpecies) {
            search.value = existingSpecies.name; // Pre-populate the search input
        }
    }
});
// Filtered species list
const filteredSpecies = computed(() => {
  return props.species.filter((species) =>
    species.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

// Select species handler
const selectSpecies = (species) => {
    search.value = species.name;
    form.species_id = species.id;
    isDropdownVisible.value = false;
};


const closeDropdown = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    isDropdownVisible.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
  // Clean up the event listener when the component is unmounted
  document.removeEventListener('click', closeDropdown);
});



</script>

<template>
  <Head title="Update Species Form" />

  <Sidebar>
    <div class="relative min-h-screen">
      <!-- Background image with oceanic overlay -->
      <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/images/landing.jpg');">
        <div class="absolute inset-0 bg-gradient-overlay"></div>
      </div>

      <!-- Main content -->
      <div class="relative z-10">
        <div class="container mx-auto px-4 py-16">
          <h2 class="title-gradient mb-6">Update Species Form</h2>

          <form @submit.prevent="submit" class="space-y-8 max-w-4xl mx-auto">
            <!-- Error Messages -->
            <div v-if="formErrors" class="error-container">
              <ul class="list-disc ml-4">
                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
              </ul>
            </div>

            <!-- Species Selection Section -->
            <div class="form-section">
              <h3 class="section-title">
                <span class="material-icons text-cyan-400 mr-2">pets</span>
                Species Identification
              </h3>
              <div class="sm:col-span-2">
                <div class="relative dropdown-container">
                  <InputLabel for="species" value="Species Involved" />
                  <input
                    id="species"
                    v-model="search"
                    @focus="isDropdownVisible = true"
                    @input="isDropdownVisible = true"
                    placeholder="Search and select what species is involved..."
                    class="w-full rounded-lg"
                  />
                  <InputError class="mt-2" :message="form.errors?.species_id" />

                  <!-- Dropdown -->
                  <ul
                    v-if="isDropdownVisible && filteredSpecies.length"
                    class="dropdown-list"
                  >
                    <li
                      v-for="species in filteredSpecies"
                      :key="species.id"
                      @click="selectSpecies(species)"
                      class="dropdown-item"
                    >
                      {{ species.name }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Species Details Section -->
            <div class="form-section">
              <h3 class="section-title">
                <span class="material-icons text-cyan-400 mr-2">description</span>
                Species Condition & Characteristics
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="condition_code" value="Condition" />
                  <select v-model.number="form.condition_code" class="w-full" required>
                    <option value="" disabled>Select an option</option>
                    <option value=1>1 = Alive</option>
                    <option value=2>2 = Freshly Dead</option>
                    <option value=3>3 = Decomposed, but organs are intact</option>
                    <option value=4>4 = Advanced Decomposition</option>
                    <option value=5>5 = Skeletal/Cartiginous Remains</option>
                    <option value=6>6 = Destroyed (slaughtered or burned)</option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.condition_code" />
                </div>
                <div>
                  <InputLabel for="sex" value="Sex" />
                  <select v-model="form.sex" class="w-full">
                    <option value="" disabled>Select an option</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="unknown">Unknown</option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.sex" />
                </div>

                <div>
                  <InputLabel for="length" value="Length (cm)" />
                  <input id="length" type="number" step="0.01" min="0" v-model="form.length" class="w-full" placeholder="Enter the length of the species in cm" />
                  <InputError class="mt-2" :message="form.errors.length" />
                </div>
                <div>
                  <InputLabel for="weight" value="Weight (kg)" />
                  <input id="weight" type="number" step="0.01" min="0" v-model="form.weight" class="w-full" placeholder="Enter the weight of the species in kg" />
                  <InputError class="mt-2" :message="form.errors.weight" />
                </div>
                <div>
                  <InputLabel for="girth" value="Girth (cm)" />
                  <input id="girth" type="number" step="0.01" min="0" v-model="form.girth" class="w-full" placeholder="Enter the girth of the species in cm" />
                  <InputError class="mt-2" :message="form.errors.girth" />
                </div>
                <div>
                  <InputLabel for="is_released" value="Released?" />
                  <select v-model="form.is_released" class="w-full">
                    <option value="" disabled>Select an option</option>
                    <option :value="true">Yes</option>
                    <option :value="false">No</option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.is_released" />
                </div>
              </div>
            </div>

            <!-- Location Section -->
            <div class="form-section">
              <h3 class="section-title">
                <span class="material-icons text-cyan-400 mr-2">place</span>
                Location Information
              </h3>

              <div class="sm:col-span-2 flex justify-end gap-3 mb-4">
                <button
                  type="button"
                  @click.prevent="setLocationFromMap"
                  class="location-button"
                >
                  <span class="material-icons">my_location</span>
                  <span>Current Location</span>
                </button>
                <button
                  type="button"
                  @click="handleResetCoordinates"
                  class="location-button bg-amber-600 hover:bg-amber-700"
                >
                  <span class="material-icons">restart_alt</span>
                  <span>Reset Location</span>
                </button>
              </div>

              <div class="mt-4">
                <div v-if="!showMap" class="no-location-display">
                  No GPS coordinates available
                </div>

                <template v-else>
                  <div class="relative rounded-xl overflow-hidden shadow-lg map-container">
                    <div id="map" class="h-[400px] w-full z-0"></div>
                    <!-- Map Controls -->
                    <div class="absolute top-4 right-4 z-10">
                      <button
                        type="button"
                        @click="handleRemoveMap"
                        class="remove-location-button"
                      >
                        <span class="material-icons">location_off</span>
                      </button>
                    </div>
                    <div class="coordinates-display">
                      <p class="text-sm font-medium text-white">
                        Latitude: {{ form.latitude || 'Not Set' }}<br>
                        Longitude: {{ form.longitude || 'Not Set' }}
                      </p>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <!-- Disposition Section -->
            <div class="form-section">
              <h3 class="section-title">
                <span class="material-icons text-cyan-400 mr-2">info</span>
                Additional Information
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="sm:col-span-2">
                  <InputLabel for="disposition" value="Disposition" />
                  <TextInput id="disposition" v-model="form.disposition" class="w-full" placeholder="e.g. Buried" />
                  <InputError class="mt-2" :message="form.errors.disposition" />
                </div>
                <div>
                  <InputLabel for="disposal_site" value="Disposal Site" />
                  <textarea
                    id="disposal_site"
                    v-model="form.disposal_site"
                    autocomplete="disposal_site"
                    class="w-full h-24"
                    placeholder="Please add more details of the disposal location site"
                  ></textarea>
                  <InputError class="mt-2" :message="form.errors.disposal_site" />
                </div>
                <div>
                  <InputLabel for="more_information" value="More Information" />
                  <textarea
                    id="more_information"
                    v-model="form.more_information"
                    autocomplete="more_information"
                    class="w-full h-24"
                    placeholder="Please share more information about the stranded species"
                  ></textarea>
                  <InputError class="mt-2" :message="form.errors.more_information" />
                </div>
              </div>
            </div>

            <div class="flex justify-between items-center mt-6">
              <Link :href="backRoute" class="cancel-button">Cancel</Link>
              <PrimaryButton type="submit" class="create-button" :disabled="form.processing">
                Update
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
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

.section-title {
  display: flex;
  align-items: center;
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: white;
  border-bottom: 1px solid rgba(0, 204, 255, 0.3);
  padding-bottom: 0.75rem;
}

.form-section {
  background: rgba(0, 51, 102, 0.35);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 12px;
  width: 100%;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 2rem;
  position: relative;
  z-index: 1;
}

/* Form Input Styles */
input[type="text"],
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
input[type="number"]:focus,
textarea:focus,
select:focus {
  background: rgba(255, 255, 255, 0.12) !important;
  border-color: rgba(0, 204, 255, 0.5) !important;
  box-shadow: 0 0 0 2px rgba(0, 204, 255, 0.25) !important;
  outline: none !important;
}

/* Species Search Input */
#species {
  border: 1px solid rgba(0, 204, 255, 0.4) !important;
  background: rgba(0, 51, 102, 0.4) !important;
  box-shadow: 0 0 8px rgba(0, 204, 255, 0.1);
  color: white !important;
}

#species::placeholder {
  color: rgba(255, 255, 255, 0.6) !important;
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

/* Dropdown styling */
.dropdown-container {
  position: relative;
  z-index: 1000;
}

.dropdown-list {
  position: absolute;
  background: #003366;
  border: 1px solid rgba(0, 204, 255, 0.4);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 204, 255, 0.25);
  width: 100%;
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 4px;
  animation: fadeIn 0.2s ease-out;
  transform-origin: top center;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.dropdown-item {
  padding: 0.75rem 1rem;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.dropdown-item:hover {
  background-color: rgba(0, 204, 255, 0.2);
  padding-left: 1.5rem;
}

.dropdown-item:hover::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 70%;
  background: rgba(0, 204, 255, 0.8);
  border-radius: 2px;
}

/* Map styles */
.map-container {
  border: 1px solid rgba(0, 204, 255, 0.4);
  margin-bottom: 1.5rem;
}

.no-location-display {
  text-align: center;
  padding: 2rem;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.coordinates-display {
  position: absolute;
  bottom: 12px;
  left: 12px;
  background: rgba(0, 51, 102, 0.8);
  backdrop-filter: blur(4px);
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

/* Button Styles */
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

.location-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #00a3cc, #00ccff);
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 50px;
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.location-button:hover {
  background: linear-gradient(135deg, #00b3cc, #00d9ff);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.remove-location-button {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #cc0000, #ff3333);
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
}

.remove-location-button:hover {
  transform: translateY(-1px) scale(1.05);
  box-shadow: 0 6px 20px rgba(255, 0, 0, 0.4);
}

.create-button {
  background: linear-gradient(135deg, #00a3cc, #00ccff);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  border: none;
  box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
  font-size: 0.875rem;
  font-weight: 500;
  min-width: 140px;
  text-align: center;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.create-button:hover {
  background: linear-gradient(135deg, #00b3cc, #00d9ff);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.cancel-button {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(4px);
  font-size: 0.875rem;
  font-weight: 500;
  min-width: 140px;
  text-align: center;
  transition: all 0.3s ease;
}

.cancel-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
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

#map {
  height: 400px;
  width: 100%;
}

/* Species Selection Section specific */
.form-section:first-of-type {
  z-index: 10;
}
</style>
