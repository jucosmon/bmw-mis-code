<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    sighting: {
        type: Object,
        required: true
    },
    species: {
        type: Array,
        required: true,
    }
});

const page = usePage();
const currentUser = page.props.auth.user.user_role;
const deletedImages = ref([]);
const previewNewImages = ref([]);
const deletedSightedSpecies = ref([]);
const municipalities = ref([]);
const barangays = ref([]);

onMounted(async () => {
    console.log('Component mounted'); // Debugging: Check if the component is mounted
    console.log('Props:', props); // Log the entire props object

    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    if (props.sighting.municipality_id) {
        await fetchBarangays();
    }
});

const fetchBarangays = async (municipalityId = props.sighting.municipality_id) => {
    if (!municipalityId) return;
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
};

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
    latitude: props.sighting.latitude || 9.57849189779755,
    longitude: props.sighting.longitude || 123.74536514282228,
    detailed_location: props.sighting.detailed_location || '',
    more_information: props.sighting.more_information || '',
    municipality_id: props.sighting.municipality_id || '',
    barangay_id: props.sighting.barangay_id || '',
    report_status: props.sighting.report_status || '',
    mediaFiles: [],
    deletedImages: [],
    sightedSpecies: props.sighting.sighted_species.map(species => ({
        id: species.id,
        species_id: species.species_id || '',
        size: species.size || '',
        species_description: species.species_description || '',
        behavior_observed: species.behavior_observed || '',
    })) || [],

    deletedSightedSpecies: [],
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

const hasChanges = computed(() => {
    const currentData = form.data();

    // Check if basic fields are different
    const dataChanged = Object.keys(currentData).some((key) => {
        if (key === 'mediaFiles' || key === 'deletedImages') return false;
        return normalizeValue(currentData[key]) !== normalizeValue(props.sighting[key]);
    });

    const mediaFilesChanged = form.mediaFiles.length > 0;
    const deletedImagesChanged = deletedImages.value.length > 0;

    // Check for changes in sightedSpecies
    const sightedSpeciesChanged = form.sightedSpecies.some((species, index) => {
        const originalSpecies = props.sighting.sighted_species[index] || {};
        return (
            species.species_id !== originalSpecies.species_id || // Check if species_id has changed
            species.size !== originalSpecies.size || // Check if size has changed
            species.species_description !== originalSpecies.species_description || // Check if description has changed
            species.behavior_observed !== originalSpecies.behavior_observed // Check if behavior has changed
        );
    });

    // Check for new species added
    const newSpeciesAdded = form.sightedSpecies.length > props.sighting.sighted_species.length;

    // Check for deleted species
    const deletedSightedSpeciesChanged = deletedSightedSpecies.value.length > 0;

    const reportStatusChanged = form.report_status !== props.sighting.report_status;

    // Return true if any of the checks indicate changes
    return (
        dataChanged ||
        mediaFilesChanged ||
        deletedImagesChanged ||
        sightedSpeciesChanged ||
        newSpeciesAdded ||
        deletedSightedSpeciesChanged ||
        reportStatusChanged
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

onMounted(() => {
    nextTick(() => {
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

const setLocationFromMap = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                form.latitude = latitude;
                form.longitude = longitude;
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

const buttonStatus = computed(() => {
    return (props.sighting.report_status === 'pending' || props.sighting.report_status === 'false') && currentUser!== 'public_user';
});

const falseIncident = () => {
    form.report_status = 'false'; // Set report status
};

const verifyIncident = () => {
    form.report_status = 'verified';
};

const submit = () => {
    if (hasChanges.value) {
        form.deletedImages = deletedImages.value;
        form.deletedSightedSpecies = deletedSightedSpecies.value;

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

const closeDropdown = (index) => {
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
                document.addEventListener('click', closeDropdown);
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
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Sighting Report</h2>

            <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Error Messages -->
                    <div v-if="formErrors" class="p-4 bg-red-100 border border-red-400 rounded-lg text-red-600">
                        <ul class="list-disc ml-4">
                            <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>
                    <!-- Form Fields -->
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

                        <!-- Sighted Species -->
                        <div v-for="(species, index) in form.sightedSpecies" :key="species.id || index" class="border p-5 rounded-lg mb-4 sm:col-span-2 bg-gray-50">
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

                                        <ul
                                        :id="'dropdown-' + index"
                                        v-if="isDropdownVisible[index] && filteredSpecies(index).value.length > 0"
                                        class="absolute bg-white border rounded-lg shadow-lg w-full max-h-40 overflow-y-auto z-50 mt-1"
                                        @click="handleDropdownClick(index)"
                                    >
                                            <li
                                                v-for="species in filteredSpecies(index).value"
                                                :key="species.id"
                                                @click="selectSpecies(species, index)"
                                                class=" px-4 py-2 z-100 hover:bg-indigo-100 cursor-pointer"
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
                                <button v-if="form.sightedSpecies.length > 1" @click.prevent="removeSpeciesEntry(species, index)">
                                    <span class="material-icons text-xl mr-2 leading-none text-red-600">delete</span>
                                </button>
                                <button @click.prevent="addSpeciesEntry">
                                    <span class="material-icons text-xl mr-2 leading-none text-indigo-900">add_circle</span>
                                </button>
                            </div>

                        </div>
                        <!-- Existing Image Previews -->
                        <div class="mt-4 sm:col-span-2 col-span-1">
                            <InputLabel value="Existing Images" />
                            <div>
                                <div v-if="existingImages.length === 0" class="flex flex-wrap gap-2">No existing images</div>
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
                        <div class="sm:col-span-2">
                            <InputLabel for="mediaFiles" value="Upload Media Files (Images/Videos)" />
                            <input type="file" accept="image/*,video/*" id="mediaFiles" @change="handleNewFileChange" multiple class="file-input w-full" />
                            <div v-if="previewNewImages.length" class="mt-2 flex gap-4">
                                <div v-for="(img, index) in previewNewImages" :key="index" class="relative">
                                    <img :src="img" alt="Preview" class="w-20 h-20 object-cover rounded-lg" />
                                    <button @click="removeNewImage(index)" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1">X</button>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.mediaFiles" />
                        </div>
                    </div>

                    <!-- Submit and Cancel Buttons for regular update -->
                    <div v-if="!buttonStatus" class="flex items-center justify-between mt-6">
                        <Link :href="backRoute" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>

                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900">
                            Update Incident
                        </PrimaryButton>
                    </div>

                    <!-- False and Verify button for pending cases-->
                    <div v-else class="flex items-center justify-end gap-5 mt-6">
                        <DangerButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900" @click="falseIncident">
                            Mark as False
                        </DangerButton>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900" @click="verifyIncident">
                            Verify as True
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>

.file-input {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 40px;
    color: white;
}

.file-input::-webkit-file-upload-button {
    visibility: hidden;
}

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
