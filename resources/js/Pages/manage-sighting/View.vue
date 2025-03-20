<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
    municipalities: {
        type: Array,
        default: () => [],
    },
    barangays: {
        type: Array,
        default: () => [],
    },
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
    unverify_password: '',
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
    return route('sighting.update.page', { id: props.sighting.id });
});


const archiveRoute = computed(() => {
    return route('sighting.archive', {
        id: props.sighting.id,
    });
});
const unarchiveRoute = computed(() => {
    return route('sighting.unarchive', {
        id: props.sighting.id
    });
});

// main methods with consecutive modals
const updateSighting = () => {
    router.visit(updateRoute.value);
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
    return ((isPublicUser.value || isBarangayOfficial.value || isLguResponder.value)
    && props.sighting.report_status === 'pending' && props.sighting.is_active === true
    && page.props.auth.user.id === props.sighting.user_id);
});


// Update button validation for verifiers
const updateButtonStatusVerifier = computed(() => {
    return ((isBpemoAdmin.value || isBpemoStaff.value)
    && (props.sighting.report_status != 'false')
    && props.sighting.is_active === true);
});

//unverify button
const unverifyButtonStatus = computed(() => {
    return props.sighting.report_status === 'verified' &&
           (isBpemoAdmin.value || isBpemoStaff.value)
           && props.sighting.is_active === true;
});

console.log('verify button',updateButtonStatusVerifier.value);
console.log('unverify button',unverifyButtonStatus.value);
console.log('archive button',archiveButtonStatus.value);
console.log('update button for reporters',updateButton.value);
const unverifyModalVisible = ref(false);

const showUnverifyModal = () => {
    unverifyModalVisible.value = true;
};

const closeUnverifyModal = () => {
    unverifyModalVisible.value = false;
};
const handleUnverifyAction = () => {
    form.patch(
        route('sighting.unverify', { id: props.sighting.id }),
        {
            onSuccess: () => {
                closeUnverifyModal(); // Close only on success
                form.reset('unverify_password'); // Reset form only on success
            },
            onError: (errors) => {
                console.error(errors); // Log errors for debugging
            },
        }
    );
};


// location data
const municipalityData = ref([]);
const barangayData = ref([]);

const municipalityName = computed(() => {
    const municipality = props.municipalities.find(
        (m) => m.id === props.sighting.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = props.barangays.find(
        (b) => b.id === props.sighting.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
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

// Media preview modal
const showFileModal = ref(false);
const currentMediaFile = ref(null);

const openFileModal = (mediaFile) => {
    currentMediaFile.value = mediaFile;
    showFileModal.value = true;
};

const closeFileModal = () => {
    showFileModal.value = false;
    currentMediaFile.value = null;
};
</script>

<template>
    <Head title="View Sighting" />
    <Sidebar>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative container mx-auto px-4 py-16 max-w-5xl">
                <!-- Success Message -->
                <div v-if="props?.success" class="success-notification" role="alert">
                    <div class="flex-1 flex items-center">
                        <span class="material-icons material-icons-round text-2xl mr-3">check_circle</span>
                        <p class="notification-text">{{ props?.success }}</p>
                    </div>
                </div>

                <!-- Sighting Header Card -->
                <div class="profile-card mb-6">
                    <div class="profile-header">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-col">
                                <h1 class="profile-title-gradient">Sighting Information</h1>
                                <div class="flex flex-wrap items-center text-indigo-200 mt-2">
                                    <div class="flex items-center mr-4 mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">event</span>
                                        <p class="text-xs text-gray-100 italic">[{{ props.sighting.id }}] {{ props.sighting.date }}</p>
                                    </div>
                                    <div class="flex items-center mr-4 mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">schedule</span>
                                        <p class="text-xs text-gray-100 italic">{{ props.sighting.time }}</p>
                                    </div>
                                    <div class="flex items-center mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">verified</span>
                                        <p class="text-xs">{{ props.sighting.is_active ? 'Active' : 'Inactive' }} ({{ props.sighting.report_status }})</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-start sm:justify-end space-x-2 mt-4 sm:mt-0">
                                <button
                                    class="action-button-gradient danger text-sm mb-2"
                                    @click="confirmArchiveSighting"
                                    v-if="props.sighting.is_active && archiveButtonStatus"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">cancel</span>
                                    Cancel
                                </button>
                                <button
                                    class="action-button-gradient success text-sm mb-2"
                                    @click="confirmArchiveSighting"
                                    v-if="props.sighting.is_active===false && isPublicUser"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">restore</span>
                                    Unarchive
                                </button>

                                <button
                                    v-if="updateButton"
                                    class="action-button-gradient primary text-sm mb-2"
                                    @click="updateSighting"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">edit</span>
                                    Update
                                </button>

                                <button
                                    v-if="updateButtonStatusVerifier"
                                    class="action-button-gradient primary text-sm mb-2"
                                    @click="updateSighting"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">
                                        {{ (props.sighting.report_status === 'pending' || props.sighting.report_status === 'false') ? 'verified' : 'edit' }}
                                    </span>
                                    {{ (props.sighting.report_status === 'pending' || props.sighting.report_status === 'false') ? 'Verify' : 'Update' }}
                                </button>

                                <button
                                    v-if="unverifyButtonStatus"
                                    class="action-button-gradient warning text-sm mb-2"
                                    @click="showUnverifyModal"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">gpp_bad</span>
                                    Unverify
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Reporter Information -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">person</span>
                                Reporter Information
                            </h2>
                        </div>
                        <div class="p-6 pt-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">account_circle</span>
                                    <span class="ml-3 text-sm">{{ props.sighting.user ? `${props.sighting.user.first_name} ${props.sighting.user.last_name}` : 'Unknown Reporter' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">phone</span>
                                    <span class="ml-3 text-sm">{{ props.sighting.user && props.sighting.user.contact_number ? props.sighting.user.contact_number : 'No contact number available' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">email</span>
                                    <span class="ml-3 text-sm">{{ props.sighting.user ? props.sighting.user.email : 'No email available' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">badge</span>
                                    <span class="ml-3 text-sm">{{ props.sighting.user ? props.sighting.user.user_role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown role' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sighting Details -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">info</span>
                                Sighting Details
                            </h2>
                        </div>
                        <div class="p-6 pt-5 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">verified</span>
                                        <div>
                                            <h3 class="info-card-title">Certainty Level</h3>
                                            <p class="info-card-content">{{ props.sighting.certainty_level }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">notes</span>
                                        <div>
                                            <h3 class="info-card-title">Additional Information</h3>
                                            <p class="info-card-content">{{ props.sighting.more_information }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="props.sightedSpecies && props.sightedSpecies.length > 0" class="mt-6">
                                <h3 class="text-lg font-semibold text-white mb-3 flex items-center border-b border-white/10 pb-2">
                                    <span class="material-icons material-icons-round mr-2">pets</span>
                                    Sighted Species
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div
                                        v-for="(sightedSpecies, index) in props.sightedSpecies"
                                        :key="sightedSpecies.id"
                                        class="species-card"
                                    >
                                        <div class="flex items-start">
                                            <span class="material-icons material-icons-round text-xl mr-3 mt-1">water</span>
                                            <div>
                                                <h4 class="species-name">{{ sightedSpecies.species_name }}</h4>
                                                <p class="species-description">{{ sizeText(sightedSpecies.size) }} - {{ sightedSpecies.species_description }}</p>
                                                <div class="mt-2">
                                                    <p class="text-white/90 text-sm">
                                                        <span class="font-medium text-white/80">Behavior:</span> {{ sightedSpecies.behavior_observed }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-gray-300 text-center py-6 italic">No detailed species information available.</p>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">location_on</span>
                                Sighting Location
                            </h2>
                        </div>
                        <div class="p-6 pt-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">place</span>
                                    <span class="ml-3 text-sm">{{ barangayName }}, {{ municipalityName }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">info</span>
                                    <span class="ml-3 text-sm">{{ props.sighting.detailed_location }}</span>
                                </div>
                            </div>

                            <div id="map" class="rounded-lg overflow-hidden shadow-md z-0" style="height: 350px; width: 100%;"></div>
                            <p class="text-center text-xs text-indigo-200 flex items-center justify-center mt-2">
                                <span class="material-icons material-icons-round text-sm mr-1">my_location</span>
                                <span>{{ props.sighting.latitude }} lat | {{ props.sighting.longitude }} long</span>
                            </p>
                        </div>
                    </div>

                    <!-- Media Files -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">perm_media</span>
                                Media Files
                            </h2>
                        </div>
                        <div class="p-6 pt-5">
                            <div v-if="props.sighting.mediaFiles && props.sighting.mediaFiles.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div
                                    v-for="file in props.sighting.mediaFiles"
                                    :key="file.id"
                                    class="media-item"
                                    @click="openFileModal(file)"
                                >
                                    <template v-if="file.type.startsWith('image/')">
                                        <img :src="file.url" :alt="`Image of ${props.sighting.name}`" class="media-preview" />
                                    </template>
                                    <template v-else-if="file.type.startsWith('video/')">
                                        <div class="media-preview flex items-center justify-center">
                                            <span class="material-icons material-icons-round text-3xl">play_circle</span>
                                        </div>
                                    </template>
                                    <div class="media-overlay">
                                        <span class="material-icons material-icons-round">visibility</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-gray-300 text-center py-4 italic">No media files available</p>
                        </div>
                    </div>
                </div>

                <!-- Modals -->
                <Modal :show="showConfirmArchiveModal" @close="closeModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-100">
                            {{ props.sighting.is_active ? 'Are you sure you want to archive this Sighting report?' : 'Are you sure you want to unarchive this Sighting report?'}}
                        </h2>
                        <div class="mt-4">
                            <label for="admin-password" class="text-sm text-gray-250">
                                Confirm by entering your password
                            </label>
                            <input
                                type="password"
                                id="admin-password"
                                v-model="form.password"
                                class="text-black mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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

                <Modal :show="unverifyModalVisible" @close="closeUnverifyModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Are you sure you want to unverify this verified sighting report?
                        </h2>
                        <div class="mt-4">
                            <label for="bpemo-password" class="text-sm text-gray-500">
                                Confirm by entering your password
                            </label>
                            <input
                                type="password"
                                id="bpemo-password"
                                v-model="form.unverify_password"
                                class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter your password"
                            />
                            <p v-if="form.errors.unverify_password" class="text-sm text-red-500 mt-1">
                                {{ form.errors.unverify_password }}
                            </p>
                        </div>
                        <div class="mt-6 flex justify-end space-x-4">
                            <SecondaryButton @click="closeUnverifyModal">Cancel</SecondaryButton>
                            <DangerButton @click="handleUnverifyAction">Confirm</DangerButton>
                        </div>
                    </div>
                </Modal>

                <!-- Media Preview Modal -->
                <Modal :show="showFileModal" @close="closeFileModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Media Preview</h2>
                        <div class="mt-4" v-if="currentMediaFile">
                            <template v-if="currentMediaFile.type.startsWith('image/')">
                                <img :src="currentMediaFile.url" alt="Preview" class="w-full h-auto rounded-lg" />
                            </template>
                            <template v-else-if="currentMediaFile.type.startsWith('video/')">
                                <video controls class="w-full h-auto rounded-lg">
                                    <source :src="currentMediaFile.url" :type="currentMediaFile.type" />
                                    Your browser does not support the video tag.
                                </video>
                            </template>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Oceanic Theme */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

/* Profile Card */
.profile-card {
    @apply rounded-xl shadow-lg overflow-hidden mb-6;
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.profile-header {
    @apply px-6 py-6;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.25rem 1.5rem;
}

.section-title {
    @apply text-lg font-semibold flex items-center text-white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Text Styling */
.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

/* Information Row Styling */
.info-row {
    @apply flex items-center p-3 rounded-lg transition-all duration-200 my-2;
    color: rgba(255, 255, 255, 0.9);
}

.info-row span {
    color: rgba(255, 255, 255, 0.9) !important;
}

.info-row:hover {
    transform: translateX(4px);
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(4px);
}

.info-row .material-icons-round {
    transition: color 0.3s ease;
}

.info-row:hover .material-icons-round {
    color: #00ccff !important;
}

/* Info Card */
.info-card {
    @apply p-3 rounded-lg;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

.info-card:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.info-card-title {
    @apply text-base font-semibold mb-1 text-white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-card-content {
    @apply text-white/90 text-sm;
}

/* Species Card */
.species-card {
    @apply p-3 rounded-lg transition-all duration-200;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.species-card:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.species-name {
    @apply text-base font-bold mb-0.5;
    color: #00ccff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.species-description {
    @apply text-xs italic text-white/80;
}

/* Media Items */
.media-item {
    @apply relative rounded-lg overflow-hidden cursor-pointer transition-all duration-200;
    border: 1px solid rgba(255, 255, 255, 0.1);
    height: 120px;
}

.media-preview {
    @apply w-full h-full object-cover;
    background: rgba(0, 0, 0, 0.2);
}

.media-overlay {
    @apply absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200;
    background: rgba(0, 51, 102, 0.6);
}

.media-item:hover .media-overlay {
    opacity: 1;
}

.media-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

/* Button Gradients */
.action-button-gradient {
    @apply rounded-lg flex items-center transition-all duration-300;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
}

.action-button-gradient.primary {
    background: linear-gradient(135deg, #4f46e5, #3730a3) !important;
}

.action-button-gradient.danger {
    background: linear-gradient(135deg, #dc2626, #991b1b) !important;
}

.action-button-gradient.success {
    background: linear-gradient(135deg, #059669, #065f46) !important;
}

.action-button-gradient.warning {
    background: linear-gradient(135deg, #d97706, #92400e) !important;
}

.action-button-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    filter: brightness(110%);
}

.action-button-gradient:active {
    transform: translateY(0);
}

/* Background Styles */
.bg-gray-50 {
    background: rgba(0, 51, 102, 0.2) !important;
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Material Icons */
.material-icons-round {
    color: rgba(0, 204, 255, 0.9) !important;
}

/* Group hover effects */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-title-gradient {
        font-size: 1.5rem;
    }
    .container {
        padding: 1rem;
    }
    .profile-card {
        margin: 0.5rem;
    }
    .media-item {
        height: 100px;
    }
}

@media (max-width: 640px) {
    .media-item {
        height: 90px;
    }
}

/* Notifications */
.success-notification {
    @apply flex items-center justify-between mb-6 px-6 py-4 rounded-xl backdrop-blur-md;
    animation: slideIn 0.3s ease-out;
    background: rgba(16, 185, 129, 0.15);
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.notification-text {
    @apply text-white text-base font-medium;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Map Styling */
#map {
    height: 350px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    #map {
        height: 250px;
    }
}
</style>
