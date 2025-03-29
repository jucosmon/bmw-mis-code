<script setup>
import CustomButton from '@/Components/CustomButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue'; // Add this import
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage(); // Ensure page is initialized

const props = defineProps({
    sighting: {
        type: Object,
        required: true
    },
    species: {
        type: Array,
        required: true,
    },
    municipalities: {  // Add this prop
        type: Array,
        required: true,
    },
    barangays: {      // Add this prop
        type: Array,
        required: true,
    }
});

// Define variables outside of onMounted
const maxDate = new Date().toISOString().split('T')[0];
const currentUser = ref(null);
const deletedImages = ref([]);
const previewNewImages = ref([]);
const deletedSightedSpecies = ref([]);
const filteredBarangays = computed(() => {
    if (!form.municipality_id) return [];
    return props.barangays.filter(barangay => barangay.municipality_id === form.municipality_id);
});

onMounted(async () => {
    // Add defensive check
    if (!page || !page.props) {
        console.error('Page object is null or undefined');
        return;
    }

    if (!props.sighting || !props.species) {
        console.error('sighitng props or species is null');
        return;
    }

    currentUser.value = page.props.auth.user.user_role;

    console.log('Component mounted'); // Debugging: Check if the component is mounted
    console.log('The Props:', props.sighting); // Log the entire props object
});

const existingImages = ref(props.sighting.mediaFiles ? props.sighting.mediaFiles : []);

const backRoute = computed(() => {
    return route('sighting.view', { id: props.sighting.id });
});

const updateRoute = computed(() => {
    return route('sighting.update', {
        id: props.sighting.id,
    });
});

// Initialize form with existing sighted species
const form = useForm({
    certainty_level: props.sighting.certainty_level || null,
    date: props.sighting.date || new Date().toISOString().split('T')[0],
    time: props.sighting.time || new Date().toTimeString().split(' ')[0],
    latitude: props.sighting.latitude || '',
    longitude: props.sighting.longitude || '',
    detailed_location: props.sighting.detailed_location || '',
    more_information: props.sighting.more_information || '',
    municipality_id: props.sighting.municipality_id || '',
    barangay_id: props.sighting.barangay_id || '',
    report_status: props.sighting.report_status || '',
    mediaFiles: [],
    deletedImages: [],
    sightedSpecies: (props.sighting.sighted_species || []).map(species => ({
        id: species.id,
        species_id: species.species_id || '',
        size: species.size || '',
        species_description: species.species_description || '',
        behavior_observed: species.behavior_observed || '',
    })) || [],

    deletedSightedSpecies: [],
    clearErrors() {
        this.errors = {};
    },
    setError(errors) {
        this.errors = errors;
    }
});

// Function to add a new species entry
const addSpeciesEntry = () => {
    form.sightedSpecies.push({
        id: null, // Set to null for new entries
        size: '',
        species_description: '',
        behavior_observed: '',
        species_id: '',
    });
    searches.value.push('');
    isDropdownVisible.value.push(false);
};

// Create a mapping of species IDs to species names
const speciesMap = computed(() => {
    const map = {};
    props.species.forEach(species => {
        map[species.id] = species.name; // Map species ID to species name
    });
    return map;
});

// Initialize searches and dropdown visibility based on existing sighted species
onMounted(() => {
    form.sightedSpecies.forEach((sightedSpecies, index) => {
        // Check if species_id is valid and set the initial value accordingly
        const speciesName = sightedSpecies.species_id ? speciesMap.value[sightedSpecies.species_id] : '';
        searches.value.push(speciesName); // Set the initial value for searches
        isDropdownVisible.value.push(false); // Initialize dropdown visibility
    });
});
const formErrors = ref(null);

const normalizeValue = (value) => {
    if (value instanceof Date) return value.toISOString().split('T')[0];
    if (typeof value === 'number') return String(value);
    if (value === null || value === undefined) return '';
    return value;
};

const normalizeDescription = (value) => {
    return value === null ? '' : value; // Convert null to empty string
};



const hasChanges = computed(() => {
    const currentData = form.data();
    props.sighting.deletedSightedSpecies = form.deletedSightedSpecies || [];

    // Check if basic fields are different, excluding sightedSpecies
    const dataChanged = Object.keys(currentData).some((key) => {
        //dont inlcude the deletedSightedSpecies in here wplease
        // Skip mediaFiles, deletedImages, and sightedSpecies
        if (key === 'mediaFiles' || key === 'deletedImages' || key === 'sightedSpecies') return false;

        const currentValue = normalizeValue(currentData[key]);
        const originalValue = normalizeValue(props.sighting[key]);

        console.log(`Comparing ${key}: currentValue=${currentValue}, originalValue=${originalValue}, changed=${currentValue !== originalValue}`);



        return currentValue !== originalValue;
    });

    const mediaFilesChanged = form.mediaFiles.length > 0;
    const deletedImagesChanged = deletedImages.value.length > 0;
    const deletedSightedSpeciesChanged = deletedSightedSpecies.value.length > 0;
    console.log('Comparing deletedSightedSpecies:');
    console.log(`  currentValue: ${deletedSightedSpecies}, changed: ${deletedSightedSpeciesChanged}`);

    // Check for changes in sightedSpecies separately
    const sightedSpeciesChanged = form.sightedSpecies.some((species, index) => {
        const originalSpecies = (props.sighting.sighted_species || [])[index] || {};

        // Log the species being compared
        console.log(`Comparing species ${index}:`, species, originalSpecies);

        // Compare each attribute individually
        const speciesIdChanged = species.species_id !== originalSpecies.species_id;
        const sizeChanged = species.size !== originalSpecies.size;
        const speciesDescriptionChanged = normalizeDescription(species.species_description) !== normalizeDescription(originalSpecies.species_description);
        const behaviorObservedChanged = species.behavior_observed !== originalSpecies.behavior_observed;

        // Log the results of each comparison
        console.log(`  species_id: current=${species.species_id}, original=${originalSpecies.species_id}, changed=${speciesIdChanged}`);
        console.log(`  size: current=${species.size}, original=${originalSpecies.size}, changed=${sizeChanged}`);
        console.log(`  species_description: current=${species.species_description}, original=${originalSpecies.species_description}, changed=${speciesDescriptionChanged}`);
        console.log(`  behavior_observed: current=${species.behavior_observed}, original=${originalSpecies.behavior_observed}, changed=${behaviorObservedChanged}`);

        // Return true if any attribute has changed
        return speciesIdChanged || sizeChanged || speciesDescriptionChanged || behaviorObservedChanged;
    });

    // Check for new species added
    const newSpeciesAdded = form.sightedSpecies.length > props.sighting.sighted_species.length;
    console.log('New species added:', newSpeciesAdded);


    // Return true if any of the checks indicate changes
    return (
        dataChanged ||
        mediaFilesChanged ||
        deletedImagesChanged ||
        sightedSpeciesChanged ||
        newSpeciesAdded ||
        deletedSightedSpeciesChanged
    );
});

const handleNewFileChange = (event) => {
    const files = event.target.files;
    form.mediaFiles.push(...Array.from(files));
    previewNewImages.value = Array.from(files).map(file => URL.createObjectURL(file));
};

const removeNewImage = (index) => {
    previewNewImages.value.splice(index, 1);
    form.mediaFiles.splice(index, 1);
};

const removeExistingImage = (index) => {
    const imageToDelete = existingImages.value[index];
    deletedImages.value.push(imageToDelete.id); // Assuming each image has an `id`
    existingImages.value.splice(index, 1);
};

const map = ref(null);
const marker = ref(null);
const locationSource = ref('original');
const isGeocodingInProgress = ref(false);
const showMap = ref(false); // Change initial value to false
const originalLocation = ref({
    latitude: props.sighting.latitude || null,
    longitude: props.sighting.longitude || null,
    municipality_id: props.sighting.municipality_id || '',
    barangay_id: props.sighting.barangay_id || '',
    detailed_location: props.sighting.detailed_location || ''
});

// Single onMounted hook for map
onMounted(() => {
    // Only show and initialize map if coordinates exist
    if (originalLocation.value.latitude && originalLocation.value.longitude) {
        showMap.value = true;
        nextTick(() => {
            const mapElement = document.getElementById('map');
            if (mapElement) {
                initializeMap();
            }
        });
    } else {
        showMap.value = false;
    }
});

const initializeMap = async () => {
    try {
        const defaultLat = form.latitude || originalLocation.value.latitude;
        const defaultLng = form.longitude || originalLocation.value.longitude;

        if (!defaultLat || !defaultLng) {
            console.error('No valid coordinates available');
            showMap.value = false;
            return;
        }
          // Fix for Leaflet default icon
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: markerIcon,
            iconUrl: markerIcon,
            shadowUrl: markerShadow,
        });

        await nextTick();
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.error('Map container not found');
            return;
        }

        // Always cleanup before creating new map
        cleanupMap();

        map.value = L.map('map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map.value);

        marker.value = L.marker([defaultLat, defaultLng], {
            draggable: true,
        }).addTo(map.value);

        // Add event listeners
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

        // Ensure map is properly sized
        await nextTick();
        map.value.invalidateSize();
    } catch (error) {
        console.error('Error initializing map:', error);
        showMap.value = false;
        cleanupMap();
    }
};

const isSameLocation = (lat1, lng1, lat2, lng2, tolerance = 0.0001) => {
    if (!lat1 || !lng1 || !lat2 || !lng2) return false;
    if (lat1 === form.latitude && lng1 === form.longitude) {
        return true;
    }
    return false;
};

const setLocationFromMap = async () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude } = position.coords;
                locationSource.value = 'manual'; // Changed from 'gps' to 'manual'

                if (isSameLocation(latitude, longitude, form.latitude, form.longitude)) {
                    alert("You're already using these coordinates!");
                    return;
                }

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

const reverseGeocode = async (latitude, longitude) => {
    isGeocodingInProgress.value = true;

    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`);
        const data = await response.json();
        const address = data.address;

        let municipalityName = address.city || address.town || address.municipality;
        let barangayName = address.quarter || address.village || address.suburb || address.neighbourhood || address.hamlet;

        form.municipality_id = '';
        form.barangay_id = '';

        if (municipalityName) {
            const matchedMunicipality = props.municipalities.find(m =>
                m.name.toLowerCase() === municipalityName.toLowerCase()
            );

            if (matchedMunicipality) {
                form.municipality_id = matchedMunicipality.id;
                locationSource.value = 'gps';

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

const resetToOriginal = async (e) => {
    e.preventDefault();
    locationSource.value = 'original';
    cleanupMap(); // Clean up existing map

    if (originalLocation.value.latitude && originalLocation.value.longitude) {
        showMap.value = true;
        await nextTick();
        form.latitude = originalLocation.value.latitude;
        form.longitude = originalLocation.value.longitude;
        form.municipality_id = originalLocation.value.municipality_id;
        form.barangay_id = originalLocation.value.barangay_id;
        form.detailed_location = originalLocation.value.detailed_location;

        const mapElement = document.getElementById('map');
        if (mapElement) {
            await initializeMap();
        }
    } else {
        showMap.value = false;
        form.latitude = '';
        form.longitude = '';
        form.municipality_id = originalLocation.value.municipality_id;
        form.barangay_id = originalLocation.value.barangay_id;
        form.detailed_location = originalLocation.value.detailed_location;
    }
};

const removeGpsLocation = () => {
    locationSource.value = 'manual';
    showMap.value = false;
    form.latitude = '';
    form.longitude = '';
    form.municipality_id = '';
    form.barangay_id = '';
    form.detailed_location = '';
    cleanupMap();
};

const cleanupMap = () => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (marker.value) {
        marker.value = null;
    }
};

// Add new functions for geocoding
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
watch([() => form.municipality_id, () => form.barangay_id],
    async ([newMunicipality, newBarangay]) => {
        // Remove locationSource check to allow updates in all modes
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

// Update watchers
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

const buttonStatus = computed(() => {
    return (props.sighting.report_status === 'pending' || props.sighting.report_status === 'false') &&
    (currentUser.value !== 'lgu_responder'  && currentUser.value !== 'barangay_official' && currentUser.value !== 'public_user');
});
const showVerifyModal = ref(false);
const showFalseModal = ref(false);

const falseIncident = () => {
    showFalseModal.value = true;
};

const validateForm = () => {
    const requiredFields = {
        date: 'Date',
        time: 'Time',
        sightedSpecies: 'Species details',
        certainty_level: 'Certainty level',
        municipality_id: 'Municipality',
        barangay_id: 'Barangay',
        detailed_location: 'Detailed location'
    };

    let hasErrors = false;
    const errors = {};

    Object.entries(requiredFields).forEach(([field, label]) => {
        if (field === 'sightedSpecies') {
            if (!form.sightedSpecies.length) {
                errors[field] = 'At least one species must be added';
                hasErrors = true;
            } else {
                // Validate each species entry
                form.sightedSpecies.forEach((species, index) => {
                    if (!species.species_id || !species.size || !species.behavior_observed) {
                        if (!errors.sightedSpecies) errors.sightedSpecies = [];
                        errors.sightedSpecies[index] = 'All required fields must be filled';
                        hasErrors = true;
                    }
                });
            }
        } else if (!form[field]) {
            errors[field] = `${label} is required`;
            hasErrors = true;
        }
    });

    if (hasErrors) {
        form.setError(errors); // Fixed the typo here (was f(errors))
        return false;
    }

    return true;
};

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

const submitForm = () => {
    form.deletedImages = deletedImages.value;
    form.deletedSightedSpecies = deletedSightedSpecies.value;

    form.post(updateRoute.value, {
        onSuccess: () => {
            formErrors.value = null; // Fixed (was s.value)
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};

const confirmFalse = () => {
    form.report_status = 'false';
    submitForm();
    showFalseModal.value = false;
};

const verifyIncident = () => {
    if (!validateForm()) {
        alert('Please fill in all required fields before verifying the incident.');
        return;
    }
    showVerifyModal.value = true;
};

const confirmVerify = () => {
    form.report_status = 'verified';
    submitForm();
    showVerifyModal.value = false;
};

const searches = ref([]);
const isDropdownVisible = ref([]);

// Initialize searches and dropdown visibility based on existing sighted species
onMounted(() => {
    form.sightedSpecies.forEach(() => {
        searches.value.push('');
        isDropdownVisible.value.push(false);
    });
});

const filteredSpecies = (index) => {
    return computed(() => {
        const searchTerm = searches.value[index]?.toLowerCase() || '';
        return props.species.filter((species) =>
            species.name.toLowerCase().includes(searchTerm)
        );
    });

};


const selectSpecies = (species, index) => {
    console.log(`Dropdown visibility for index ${species}:`, isDropdownVisible.value[species]);
    searches.value[index] = species.name;
    form.sightedSpecies[index].species_id = species.id;
    isDropdownVisible.value[index] = false;

};

const toggleDropdown = (index) => {
    isDropdownVisible.value[index] = !isDropdownVisible.value[index];
    console.log(`Dropdown visibility for index ${index}:`, isDropdownVisible.value[index]);
};

let closeTimeout; // Variable to hold the timeout ID

const closeDropdown = (event, index) => {
    if (!event.target.closest('.dropdown-container')) {
        isDropdownVisible.value[index] = false;
  }
};


const handleBlur = (index) => {
    closeTimeout = setTimeout(() => {
        closeDropdown(index);
    }, 100);
};


onMounted(() => {
    document.addEventListener('click', (event) => {
        for (let i = 0; i < isDropdownVisible.value.length; i++) {
            if (isDropdownVisible.value[i]) {
                document.addEventListener('click', closeDropdown(event,i));
            }
        }
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
    clearTimeout(closeTimeout); // Clear the timeout on unmount

});

const removeSpeciesEntry = (species, index) => {

    if (species.id) {
        console.log(`Marking species ID for deletion: ${species.id}`);
        deletedSightedSpecies.value.push(species.id); // Mark for deletion
    } else {
        console.log(`Removing new species entry, not marking for deletion:`, form.sightedSpecies[index]);
    }

    // Remove the species from the form
    form.sightedSpecies.splice(index, 1);
    searches.value.splice(index, 1); // Remove the search term
    isDropdownVisible.value.splice(index, 1); // Remove the dropdown visibility state
};

const handleDropdownClick = (index) => {
    console.log(`Dropdown clicked for index: ${index}`);
};



</script>

<template>
    <Head title="Update Sighting Report" />

    <Sidebar>
        <div class="relative min-h-screen">
            <!-- Background image with oceanic overlay -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main content -->
            <div class="relative z-10">
                <div class="container mx-auto px-4 py-16">
                    <h2 class="title-gradient mb-6">Update Sighting Report</h2>

                    <form @submit.prevent="submit" class="space-y-8 max-w-4xl mx-auto">
                        <!-- Error Messages -->
                        <div v-if="formErrors" class="error-container">
                            <ul class="list-disc ml-4">
                                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Form Fields -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons text-cyan-400 mr-2">calendar_today</span>
                                Date & Time Information
                            </h3>
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
                                    <input required id="certainty_level" type="range" min="1" max="10" v-model="form.certainty_level" class="w-full range-input" />
                                    <p class="text-center text-white">{{ form.certainty_level }}</p>
                                    <InputError class="mt-2" :message="form.errors.certainty_level" />
                                </div>
                            </div>
                        </div>

                        <!-- Map & Location Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons text-cyan-400 mr-2">place</span>
                                Location Details
                            </h3>

                            <div class="mt-4 space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex-grow"></div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click.prevent="setLocationFromMap"
                                            class="location-button"
                                        >
                                            <span class="material-icons">my_location</span>
                                            <span class="hidden md:block">Use Current Location</span>
                                        </button>
                                        <button
                                            type="button"
                                            v-if="!showMap || locationSource !== 'original'"
                                            @click="resetToOriginal"
                                            class="location-button bg-amber-600 hover:bg-amber-700"
                                        >
                                            <span class="material-icons">restart_alt</span>
                                            <span class="hidden md:block">Reset Location</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="showMap" class="relative rounded-xl overflow-hidden shadow-lg map-container">
                                    <div id="map" class="h-[400px] w-full z-0"></div>
                                    <div class="absolute top-4 right-4 z-10">
                                        <button
                                            type="button"
                                            @click="removeGpsLocation"
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

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
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
                                            required
                                            id="detailed_location"
                                            v-model="form.detailed_location"
                                            autocomplete="detailed_location"
                                            class="w-full"
                                            placeholder="Please add more details of the exact location"
                                        ></textarea>
                                        <InputError class="mt-2" :message="form.errors.detailed_location" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons text-cyan-400 mr-2">info</span>
                                Additional Information
                            </h3>
                            <div>
                                <InputLabel for="more_information" value="More Information of the Incident" />
                                <textarea
                                    id="more_information"
                                    v-model="form.more_information"
                                    autocomplete="more_information"
                                    class="w-full h-24"
                                    placeholder="Please share more information of the incident"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.more_information" />
                            </div>
                        </div>

                        <!-- Sighted Species Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons text-cyan-400 mr-2">water_drop</span>
                                Species Information
                            </h3>

                            <div class="space-y-6">
                                <div v-for="(species, index) in form.sightedSpecies" :key="species.id || index" class="species-card">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="text-lg font-semibold text-white">Species #{{ index + 1 }}</h4>
                                    </div>

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
                                                    class="w-full"
                                                    autocomplete="off"
                                                />
                                                <InputError class="mt-2" :message="form.errors?.sightedSpecies?.[index]?.species_id" />

                                                <ul
                                                    :id="'dropdown-' + index"
                                                    v-if="isDropdownVisible[index] && filteredSpecies(index).value.length > 0"
                                                    class="dropdown-list"
                                                    @click="handleDropdownClick(index)"
                                                >
                                                    <li
                                                        v-for="speciesItem in filteredSpecies(index).value"
                                                        :key="speciesItem.id"
                                                        @click="selectSpecies(speciesItem, index)"
                                                        class="dropdown-item"
                                                    >
                                                        {{ speciesItem.name }}
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
                                                class="w-full h-24"
                                                placeholder="Please add physical description of the species especially if you can't identify"
                                            ></textarea>
                                            <InputError class="mt-2" :message="form.errors.sightedSpecies?.[index]?.species_description" />
                                        </div>
                                        <div>
                                            <InputLabel :for="'behavior-' + index" value="Behavior Observed" />
                                            <textarea
                                                :id="'behavior-' + index"
                                                v-model="form.sightedSpecies[index].behavior_observed"
                                                class="w-full h-24"
                                                placeholder="(e.g. feeding, swimming, resting)"
                                                required
                                            ></textarea>
                                            <InputError class="mt-2" :message="form.errors.sightedSpecies?.[index]?.behavior_observed" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-5 gap-2">
                                        <button
                                            type="button"
                                            v-if="form.sightedSpecies.length > 1"
                                            @click.prevent="removeSpeciesEntry(species, index)"
                                            class="action-button delete-button"
                                        >
                                            <span class="material-icons">delete</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click.prevent="addSpeciesEntry"
                                            class="action-button add-button"
                                        >
                                            <span class="material-icons">add_circle</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Files Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons text-cyan-400 mr-2">image</span>
                                Media Files
                            </h3>

                            <!-- Existing Image Previews -->
                            <div class="mt-4">
                                <InputLabel value="Existing Images" />
                                <div v-if="existingImages.length === 0" class="text-white opacity-80 my-2">No existing images</div>
                                <div v-if="existingImages.length" class="preview-grid">
                                    <div v-for="(image, index) in existingImages" :key="index" class="preview-item">
                                        <img :src="image.url" alt="Image Preview" class="preview-image"/>
                                        <button
                                            type="button"
                                            @click.prevent="removeExistingImage(index)"
                                            class="remove-button"
                                            title="Remove"
                                        >
                                            <span class="material-icons">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload New Media -->
                            <div class="mt-6">
                                <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
                                <label :for="'mediaFiles'" class="browse-button" tabindex="0" role="button" @keypress.enter="$event.target.click()">
                                    <span class="material-icons mr-2">cloud_upload</span>
                                    Choose Files
                                </label>
                                <input
                                    type="file"
                                    accept="image/*,video/*"
                                    id="mediaFiles"
                                    @change="handleNewFileChange"
                                    multiple
                                    class="hidden"
                                />
                                <div v-if="previewNewImages.length" class="preview-grid mt-4">
                                    <div v-for="(img, index) in previewNewImages" :key="index" class="preview-item">
                                        <img :src="img" alt="Preview" class="preview-image" />
                                        <button
                                            type="button"
                                            @click="removeNewImage(index)"
                                            class="remove-button"
                                            title="Remove"
                                        >
                                            <span class="material-icons">close</span>
                                        </button>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.mediaFiles" />
                            </div>
                        </div>

                        <!-- Submit and Cancel Buttons for regular update -->
                        <div v-if="!buttonStatus" class="flex items-center justify-end gap-4 mt-6">
                            <CustomButton :onClick="backRoute" icon="cancel" variant="secondary">Cancel</CustomButton>
                            <CustomButton
                                icon="save"
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                            >
                                Save
                            </CustomButton>
                        </div>

                        <!-- False and Verify button for pending cases-->
                        <div v-else class="flex items-center justify-end gap-4 mt-6">
                            <CustomButton :onClick="backRoute" icon="arrow_back" variant="secondary">Cancel</CustomButton>

                            <CustomButton
                                type="button"
                                variant="danger"
                                icon="dangerous"
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                                :onClick="falseIncident"
                                formnovalidate
                            >
                                Mark as False
                            </CustomButton>
                            <CustomButton
                                type="button"
                                icon="check_circle"
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                                :onClick="verifyIncident"
                            >
                                Verify as True
                            </CustomButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal components with improved styling -->
        <Modal :show="showVerifyModal" @close="showVerifyModal = false">
            <div class="modal-container verify-modal">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-green-500"></div>
                <h2 class="modal-title">
                    <span class="material-icons text-emerald-500 mr-2">check_circle</span>
                    Confirm Verification
                </h2>
                <p class="modal-content">
                    Are you sure that the report is true and accurate?
                </p>
                <div class="modal-actions">
                    <button
                        type="button"
                        class="modal-cancel-button"
                        @click="showVerifyModal = false"
                    >
                        Cancel
                    </button>
                    <button @click="confirmVerify" class="modal-confirm-button verify-confirm-button">
                        Confirm
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="showFalseModal" @close="showFalseModal = false">
            <div class="modal-container false-modal">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-400 to-red-600"></div>
                <h2 class="modal-title">
                    <span class="material-icons text-red-500 mr-2">cancel</span>
                    Confirm False Report
                </h2>
                <p class="modal-content">
                    Are you sure the report is false?
                </p>
                <div class="modal-actions">
                    <button
                        type="button"
                        class="modal-cancel-button"
                        @click="showFalseModal = false"
                    >
                        Cancel
                    </button>
                    <button @click="confirmFalse" class="modal-confirm-button false-confirm-button">
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
}

.species-card {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Form Input Styles */
input[type="text"],
input[type="date"],
input[type="time"],
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
textarea:focus,
select:focus {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(0, 204, 255, 0.5) !important;
    box-shadow: 0 0 0 2px rgba(0, 204, 255, 0.25) !important;
    outline: none !important;
}

/* Fix color of input type date and time icons */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) opacity(0.7);
}

/* Range input styling */
input[type="range"].range-input {
    background: rgba(0, 204, 255, 0.1) !important;
    height: 8px;
    border-radius: 8px !important;
    outline: none;
    opacity: 0.7;
    -webkit-appearance: none;
    appearance: none;
    transition: opacity 0.2s;
    margin: 0.75rem 0;
}

input[type="range"].range-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    cursor: pointer;
    border: 2px solid rgba(255, 255, 255, 0.8);
}

input[type="range"].range-input::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    cursor: pointer;
    border: 2px solid rgba(255, 255, 255, 0.8);
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
}

.dropdown-list {
    position: absolute;
    background: #003366;
    border: 1px solid rgba(0, 204, 255, 0.4);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    z-index: 50;
    margin-top: 4px;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    color: white;
    cursor: pointer;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: rgba(0, 204, 255, 0.2);
}

/* Media preview grid */
.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease;
}

.preview-item:hover {
    transform: scale(1.05);
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
}

.remove-button:hover {
    background: rgba(0, 0, 0, 0.7);
    transform: scale(1.1);
}

/* Map styles */
.map-container {
    border: 1px solid rgba(0, 204, 255, 0.4);
    margin-bottom: 1.5rem;
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

.browse-button {
    display: inline-flex;
    align-items: center;
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

.browse-button:hover {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
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

.action-button {
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0.25rem;
    border: none;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.1);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.1);
}

/* Modal styling */
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


.verify-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.false-button {
    background: linear-gradient(135deg, #cc0000, #ff3333);
    box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
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
</style>
