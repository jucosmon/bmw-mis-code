<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref } from 'vue';


const page = usePage();
const props = defineProps({
    sighting: {
        type: Object,
        required: true,
        default: () => ({ id: null, report_status: '' }), // Default value
    },
    sightedSpecies:{
        type: Array,
        default: () => [],
    },
    success: String,
});

const isPublicUser  = computed(() => page.props.auth.user.user_role === 'public_user');
const isBpemoAdmin = computed(() => page.props.auth.user.user_role === 'bpemo_admin');
const isBpemoStaff = computed(() => page.props.auth.user.user_role === 'bpemo_staff');
const isLguResponder = computed(() => page.props.auth.user.user_role === 'lgu_responder');
const isBarangayOfficial = computed(() => page.props.auth.user.user_role === 'barangay_official');


// form defaults
const form = useForm({
    is_active: true,
    password: '',
    text: '',
    sighting_id: props.sighting.id,
});

const sizeText = (sightedSpeciesSize) => {
    switch(sightedSpeciesSize) {
        case 'tiny':
            return 'Tiny (less than 1 ft)';
        case 'small':
            return 'Small (1-3 ft)';
        case 'medium':
            return 'Medium (3-10 ft)';
        case 'large':
            return 'Large (10-20 ft)';
        case 'extra_large':
            return 'Extra Large (20-30 ft)';
        case 'giant':
            return 'Giant (30+ ft)';
        default:
            return 'Unknown';
    }
};

//routes
const backRoute = computed(() => {
    return route('sighting.index');
});


const updateRoute = computed(() => {
    const isResponder = isBarangayOfficial.value || isBpemoAdmin.value || isBpemoStaff.value || isLguResponder.value;
    const isResponderEligible = isResponder &&
        (props.sighting.report_status === 'pending' || props.sighting.report_status === 'verified'  || props.sighting.report_status === 'completed'
            || props.sighting.report_status === 'false'
        );

    if (isPublicUser.value) {
        return route('sighting.update.page', { id: props.sighting.id });
    } else if (isResponderEligible) {
        return route('sighting.responder.update.page', { id: props.sighting.id });
    } else {
        return null; // Explicitly return null if no conditions are met
    }
});


const archiveRoute = computed(() => {
    return route('sighting.archive', {
        id: props.sighting.id,
        category: props.sighting.category,
    });
});
const unarchiveRoute = computed(() => {
    return route('sighting.unarchive', {
        id: props.sighting.id
    });
});

// main methods with consecutive modals
const updateSighting = () => {
    Inertia.visit(updateRoute.value);
};

const showConfirmArchiveModal = ref(false);

const confirmArchiveSighting = () => {
    showConfirmArchiveModal.value = true;
};

const closeModal = () => {
    showConfirmArchiveModal.value = false;
};

// Archiving For public users only
const archiveButtonStatus = computed(() => {
    return ((isPublicUser.value || isBarangayOfficial.value || isLguResponder.value)
    && props.sighting.report_status === 'pending');
});

const archiveSighting = () => {
    if(props.sighting.is_active){
        form.patch(archiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    } else {
        form.patch(unarchiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    }

};

// Update button validation for regular sighting reports
const updateButton = computed(() => {
    return archiveButtonStatus.value;
});


// Update button validation for verifiers
const updateButtonStatusVerifier = computed(() => {
    if (isPublicUser.value || isBarangayOfficial.value || isLguResponder.value) {
        return false;
    }
    return true;
});


//unverify button
const unverifyButtonStatus = computed(() => {
    return props.sighting.report_status === 'resolved' &&
           (isBpemoAdmin.value || isBpemoStaff.value);
});

const unverifyModalVisible = ref(false);

const showUnverifyModal = () => {
    unverifyModalVisible.value = true;
};

const handleUnverifyAction = (response) => {
    if (response === 'yes') {
        Inertia.patch(
            route('sighting.unverify', { id: props.sighting.id }), // Pass the ID here
            {},
            {
                onSuccess: () => {
                    unverifyModalVisible.value = false;
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    } else {
        unverifyModalVisible.value = false;
    }
};


// location data
const municipalityData = ref([]);
const barangayData = ref([]);

const municipalityName = computed(() => {
    const municipality = municipalityData.value.find(
        (m) => m.id === props.sighting.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = barangayData.value.find(
        (b) => b.id === props.sighting.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

onMounted(async () => {
    try {
        const municipalityResponse = await axios.get('/municipalities');
        municipalityData.value = municipalityResponse.data;

        const barangayResponse = await axios.get(
            `/barangays?municipality_id=${props.sighting.municipality_id}`
        );
        barangayData.value = barangayResponse.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
});

// Map references
const map = ref(null);
const marker = ref(null);

// Initialize Leaflet map
onMounted(() => {
  nextTick(() => {
    console.log('Sighting:', props.sighting); // Log the Sighting for debugging

    // Initialize the map with the latitude and longitude from props
    map.value = L.map('map', {
      dragging: false, // Disable dragging
      scrollWheelZoom: false, // Disable zooming with the mouse wheel
      touchZoom: false, // Disable touch zooming on mobile
      doubleClickZoom: false, // Disable double-click zooming
      boxZoom: false, // Disable box zooming
    }).setView([props.sighting.latitude, props.sighting.longitude], 13);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    // Add a marker at the specified location (non-draggable)
    marker.value = L.marker([props.sighting.latitude, props.sighting.longitude]).addTo(map.value);
  });
});
</script>

<template>
    <Head title="View Sighting" />
    <Sidebar>
        <template #header>
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-700 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                <Link :href="backRoute" class="flex items-center">
                    Back
                </Link>
            </button>
        </template>

        <div class="container mx-auto px-6 pb-6 max-w-5xl">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-6 rounded-lg shadow-lg mb-8">
                <h1 class="text-3xl font-bold">Sighting Information</h1>
                <span class="text-xs text-gray-100 italic mb-4">[{{ props.sighting.id }}] {{ props.sighting.date }} : {{ props.sighting.time }}</span>
                <p class="text-sm mt-2">{{ props.sighting.is_active ? 'Active' : 'Inactive' }} ({{ props.sighting.report_status }})</p>
                <div class="flex justify-end space-x-4">
                    <button
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition"
                        @click="confirmArchiveSighting"
                        v-if="props.sighting.is_active && archiveButtonStatus"
                    >
                        Cancel Report
                    </button>
                    <button
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
                        @click="confirmArchiveSighting"
                        v-if="props.sighting.is_active===false && isPublicUser"
                    >
                        Unarchive Incident
                    </button>

                    <Modal :show="showConfirmArchiveModal" @close="closeModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                               {{ props.sighting.is_active ? 'Are you sure you want to archive this Sighting report?' : 'Are you sure you want to unarchive this Sighting report?'}}
                            </h2>
                            <div class="mt-4">
                                <label for="admin-password" class="text-sm text-gray-500">
                                    Confirm by entering your password
                                </label>
                                <input
                                    type="password"
                                    id="admin-password"
                                    v-model="form.password"
                                    class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                                <DangerButton @click="archiveSighting">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <button
                        v-if="updateButton"
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="updateSighting"
                    >
                        Update Sighting
                    </button>
                    <button
                        v-if="updateButtonStatusVerifier"
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="updateSighting"
                    >
                    {{ (props.sighting.report_status==='pending' || props.sighting.report_status==='false') &&
                    (isBpemoAdmin || isBpemoStaff)
                        ? 'Verify Sighting' : 'Update Sighting' }}
                    </button>

                    <button
                        v-if="unverifyButtonStatus"
                        class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 transition"
                        @click="showUnverifyModal"
                    >
                        Unverify Sighting
                    </button>
                    <Modal :show="unverifyModalVisible" @close="unverifyModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Do you confirm to unverify this sighting report?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="unverifyModalVisible = false">No</SecondaryButton>
                                <DangerButton @click="handleUnverifyAction('yes')">Yes</DangerButton>
                            </div>
                        </div>
                    </Modal>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Text Details -->
                <div class="shadow-xl rounded-2xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-indigo-800 flex items-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-3 text-indigo-700" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-9-4h2v2H9V6zm0 4h2v6H9v-6z" />
                        </svg>
                        Sighting Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="p-4 bg-white shadow-md rounded-lg">
                            <p class="text-lg font-medium text-gray-700">
                                <span class="block text-indigo-700 font-bold">Certainty Level:</span>
                                {{ props.sighting.certainty_level }}
                            </p>
                        </div>
                        <div class="p-4 bg-white shadow-md rounded-lg">
                            <p class="text-lg font-medium text-gray-700">
                                <span class="block text-indigo-700 font-bold">Additional Information:</span>
                                {{ props.sighting.more_information }}
                            </p>
                        </div>
                    </div>
                    <div v-if="props.sightedSpecies && props.sightedSpecies.length > 0" class="mt-8">
                        <h3 class="text-xl font-bold text-indigo-800 mb-4">Sighted Species</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
                            <div
                                v-for="(sightedSpecies, index) in props.sightedSpecies"
                                :key="sightedSpecies.id"
                                class="bg-gray-50 shadow-lg rounded-lg overflow-hidden transition-transform transform hover:scale-105 p-6"
                            >
                                <p class="text-lg font-bold text-indigo-800">{{ sightedSpecies.species_name }}</p>
                                <p class="text-sm text-gray-600 italic mt-1">{{ sizeText(sightedSpecies.size) }} - {{ sightedSpecies.species_description }}</p>
                                <p class="mt-4 text-gray-700">
                                    <span class="font-bold">Behavior Observed:</span> {{ sightedSpecies.behavior_observed }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-6">No detailed species information available.</p>
                </div>

                <div class="bg-gray-50 shadow-xl rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-indigo-800 mb-6">Sighting Location</h2>
                    <div class="space-y-4 mb-6">
                        <p class="text-lg font-medium text-gray-700">
                            <span class="block text-indigo-700 font-bold">Location:</span>
                            {{ barangayName }}, {{ municipalityName }}
                        </p>
                        <p class="text-lg font-medium text-gray-700">
                            <span class="block text-indigo-700 font-bold">Detailed Location:</span>
                            {{ props.sighting.detailed_location }}
                        </p>
                    </div>
                    <div id="map" class="rounded-lg overflow-hidden shadow-md" style="height: 400px; width: 100%;"></div>
                    <p class="mt-4 text-center text-sm text-gray-500">
                        <span class="font-medium text-indigo-700">Coordinates:</span> {{ props.sighting.latitude }} lat | {{ props.sighting.longitude }} long
                    </p>
                </div>


                <!--Media Files section -->
                <div class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 mr-2 text-indigo-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path d="M4.75 4A2.75 2.75 0 002 6.75v6.5A2.75 2.75 0 004.75 16h10.5A2.75 2.75 0 0018 13.25v-6.5A2.75 2.75 0 0015.25 4H4.75zM9.5 8.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H10.5a.75.75 0 01-.75-.75zm-3.25 4.25a.75.75 0 110-1.5h7.5a.75.75 0 110 1.5H6.25z" />
                        </svg>
                        Media Files
                    </h2>
                    <div v-if="props.sighting.mediaFiles && props.sighting.mediaFiles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="file in props.sighting.mediaFiles"
                            :key="file.id"
                            class="bg-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition"
                        >
                            <img
                                :src="file.url"
                                :alt="`Image of ${props.sighting.name}`"
                                class="w-full h-48 object-cover"
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No media files available</p>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style>
#map {
    height: 400px; /* Ensure this is set */
    width: 100%; /* Ensure this is set */
}</style>
