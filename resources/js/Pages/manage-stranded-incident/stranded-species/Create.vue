<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const formErrors = ref(null);
const props = defineProps({
    strandedIncident: {
        type: Object,
        required: true,
        default: () => ({ id: null, report_status: '' }),
    },
    species: {
        type: Array,
        required: true,
    }
});


const backRoute = computed(() => route('stranded.incident.view', { id: props.strandedIncident.id }));
const createRoute = computed(() => route('stranded.species.create', { id: props.strandedIncident.id }));

const form = useForm({
    condition_code: 1,
    latitude: props.strandedIncident.latitude || null,
    longitude: props.strandedIncident.longitude || null,
    sex: 'unknown',
    length: null,
    weight: null,
    girth: null,
    disposition: '',
    disposal_site: '',
    more_information: '',
    is_released: false,
    species_id: null,
});

const submit = () => {
  form.post(createRoute.value, {
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
    latitude: props.strandedIncident.latitude,
    longitude: props.strandedIncident.longitude
};

// Initialize map function
const initializeMap = () => {
    if (!showMap.value || !form.latitude || !form.longitude) return;

    try {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }

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

// Show map handler
const handleShowMap = async () => {
    if (!form.latitude || !form.longitude) {
        alert('No coordinates available to show on map');
        return;
    }

    showMap.value = true;
    await nextTick();
    setTimeout(() => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        initializeMap();
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

// Modified setLocationFromMap
const setLocationFromMap = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                form.latitude = latitude;
                form.longitude = longitude;
                showMap.value = true;
                nextTick(() => {
                    initializeMap();
                });
            },
            () => {
                alert('Failed to fetch current location. Please allow location access.');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
    }
};

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

// Filtered species list
const filteredSpecies = computed(() => {
  return props.species.filter((species) =>
    species.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

// Select species handler
const selectSpecies = (species) => {
  search.value = species.name; // Display selected name in input
  form.species_id = species.id; // Set species_id in form
  isDropdownVisible.value = false; // Hide dropdown
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
  <Head title="Create Species Form" />

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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Create Detailed Species Form</h2>

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
            <div class="sm:col-span-2">
                <div class="relative dropdown-container">
                    <InputLabel for="species" value="Species Involved" />
                    <input
                        id="species"
                        v-model="search"
                        @focus="isDropdownVisible = true"
                        @input="isDropdownVisible = true"
                        placeholder="Search and select what species is involved..."
                        class="w-full border rounded-lg p-2"
                    />
                    <InputError class="mt-2" :message="form.errors?.species_id" />

                    <!-- Dropdown -->
                    <ul
                        v-if="isDropdownVisible && filteredSpecies.length"
                        class="absolute bg-white border rounded-lg shadow-lg w-full max-h-40 overflow-y-auto z-10 mt-1"
                    >
                        <li
                        v-for="species in filteredSpecies"
                        :key="species.id"
                        @click="selectSpecies(species)"
                        class="px-4 py-2 hover:bg-indigo-100 cursor-pointer"
                        >
                        {{ species.name }}
                        </li>
                    </ul>
                    </div>

            </div>
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
            <!--Map-->
            <div class="sm:col-span-2 flex justify-end gap-3">
                <button
                    type="button"
                    @click.prevent="setLocationFromMap"
                    class="px-3 py-2 bg-white text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors shadow-lg"
                >
                    <span class="material-icons material-symbols-outlined">
                        my_location
                    </span>
                </button>
                <button
                    type="button"
                    @click="handleResetCoordinates"
                    class="px-3 py-2 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition-colors shadow-lg"
                >
                    <span class="material-icons material-symbols-outlined">
                        restart_alt
                    </span>
                </button>
            </div>
            <div class="mt-4 sm:col-span-2">
                <div v-if="!showMap" class="text-center py-4 bg-gray-100 rounded-lg">
                    No GPS coordinates available
                </div>

                <template v-else>
                    <div class="relative rounded-xl overflow-hidden shadow-lg">
                        <div id="map" class="h-[400px] w-full z-0"></div>
                        <!-- Map Controls -->
                        <div class="absolute top-4 right-4 z-10 flex space-x-2">
                            <button
                                type="button"
                                @click="handleRemoveMap"
                                class="px-3 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors shadow-lg"
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
                </template>
            </div>

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
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                placeholder="Please add more details of the disposal location site"
            ></textarea>
              <InputError class="mt-2" :message="form.errors.disposal_site" />
            </div>
            <div>
              <InputLabel for="more_information" value="More Information of the Incident" />
              <textarea
                id="more_information"
                v-model="form.more_information"
                autocomplete="more_information"
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                placeholder="Please share more information about the stranded species"
            ></textarea>
              <InputError class="mt-2" :message="form.errors.more_information" />
            </div>

          </div>

          <div class="text-center mt-6">
            <PrimaryButton type="submit">Submit</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
 #map {
    height: 400px; /* Ensure this is set */
    width: 100%; /* Ensure this is set */
}

</style>
