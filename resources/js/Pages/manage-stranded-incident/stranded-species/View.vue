<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import html2pdf from 'html2pdf.js';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref } from 'vue';



const page = usePage();
const props = defineProps({
    strandedIncident: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    strandedSpecies: {
        type: Object,
        default: () => ({}),
    }
});

// Form defaults
const form = useForm({
    is_active: true,
    password: '',
    text: '',
    stranded_incident_id: props.strandedIncident.id,
});

// Routes
const backRoute = computed(() => {
    return route('stranded.incident.view', { id: props.strandedIncident.id });
});

const updateRoute = computed(() => {
    return route('stranded.species.update.page', { id: props.strandedIncident.id });
});

const archiveRoute = computed(() => {
    return route('stranded.species.archive', { id: props.strandedSpecies.id });
});

const unarchiveRoute = computed(() => {
    return route('stranded.species.unarchive', { id: props.strandedSpecies.id });
});

// Methods
const updateSpeciesForm = () => {
    Inertia.visit(updateRoute.value);
};

const showConfirmArchiveModal = ref(false);

const confirmArchiveSpeciesForm = () => {
    showConfirmArchiveModal.value = true;
};

const closeModal = () => {
    showConfirmArchiveModal.value = false;
};

const archiveIncident = () => {
    if (props.strandedSpecies.is_active) {
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

// Location data
const municipalityData = ref([]);
const barangayData = ref([]);

const municipalityName = computed(() => {
    const municipality = municipalityData.value.find(
        (m) => m.id === props.strandedIncident.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = barangayData.value.find(
        (b) => b.id === props.strandedIncident.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

onMounted(async () => {
    try {
        const municipalityResponse = await axios.get('/municipalities');
        municipalityData.value = municipalityResponse.data;

        const barangayResponse = await axios.get(`/barangays?municipality_id=${props.strandedIncident.municipality_id}`);
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
        map.value = L.map('map', {
            dragging: false,
            scrollWheelZoom: false,
            touchZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
        }).setView([props.strandedSpecies.latitude, props.strandedSpecies.longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map.value);

        marker.value = L.marker([props.strandedSpecies.latitude, props.strandedSpecies.longitude]).addTo(map.value);
    });
});


const showDownloadConfirmModal = ref(false);

const confirmDownload = () => {
    showDownloadConfirmModal.value = true; // Show the confirmation modal
};

const closeDownloadModal = () => {
    showDownloadConfirmModal.value = false; // Close the modal
};

const downloadReport = () => {
    const element = document.querySelector(".exportable-content"); // Target the exportable content

    const options = {
        filename: `Stranded_Incident_Report_${props.strandedIncident.id}.pdf`,
        margin: [5, 5, 5, 5], // Further reduced margins (top, right, bottom, left)
        jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
        html2canvas: { scale: 1.5, useCORS: true }, // Adjust scale for better quality
        image: { type: 'jpeg', quality: 0.98 }, // Higher quality for images
    };

    html2pdf().from(element).set(options).save().then(() => {
        closeDownloadModal(); // Close the modal after download
    });
};

const getConditionDescription = (code) => {
    switch (code) {
        case 1: return 'Alive';
        case 2: return 'Freshly Dead';
        case 3: return 'Decomposed, but organs are intact';
        case 4: return 'Advanced Decomposition';
        case 5: return 'Skeletal/Cartilaginous Remains';
        case 6: return 'Destroyed (slaughtered or burned)';
        default: return 'No information available';
    }
};
</script>

<template>
    <Head title="View Stranded Incident" />
    <Sidebar>
        <template #header>
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-700 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                <Link :href="backRoute" class="flex items-center">Back</Link>
            </button>
        </template>

        <div class="container mx-auto px-6 pb-6 max-w-5xl ">
            <div class="exportable-content">
            <!-- Main Title Header -->
            <div class=" text-center mb-8">
                <h1 class="text-3xl font-semibold text-indigo-700">Stranded Species-Specific Report</h1>
                <p class="mt-2 text-gray-600">Detailed information about the species involved in a stranded incident.</p>
            </div>

            <!-- Stranded Incident Details -->
            <div class=" bg-white shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4">Incident Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mx-5">
                    <p><strong>Date:</strong> {{ strandedIncident.date || 'No Information Available' }}</p>
                    <p><strong>Time:</strong> {{ strandedIncident.time || 'No Information Available' }}</p>
                </div>
            </div>

            <!-- Species Details -->
            <div class=" bg-white shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4">Species Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mx-5">
                    <p><strong>Species:</strong> {{ strandedSpecies.species_name || 'No Information Available' }}</p>
                    <p><strong>Sex:</strong> {{ strandedSpecies.sex || 'No Information Available' }}</p>
                    <p><strong>Length:</strong> {{ strandedSpecies.length || 'No Information Available' }}</p>
                    <p><strong>Weight:</strong> {{ strandedSpecies.weight || 'No Information Available' }}</p>
                    <p><strong>Girth:</strong> {{ strandedSpecies.girth || 'No Information Available' }}</p>
                    <p><strong>Condition:</strong>
                        <span v-if="strandedSpecies.condition_code === 1">Alive</span>
                        <span v-else-if="strandedSpecies.condition_code === 2">Freshly Dead</span>
                        <span v-else-if="strandedSpecies.condition_code === 3">Decomposed, but organs are intact</span>
                        <span v-else-if="strandedSpecies.condition_code === 4">Advanced Decomposition</span>
                        <span v-else-if="strandedSpecies.condition_code === 5">Skeletal/Cartilaginous Remains</span>
                        <span v-else-if="strandedSpecies.condition_code === 6">Destroyed (slaughtered or burned)</span>
                        <span v-else>No information available</span>
                    </p>
                    <p><strong>Released?</strong> {{ strandedSpecies.is_released ? 'Yes' : 'No' || 'No Information Available' }}</p>

                    <p><strong>Disposition:</strong> {{ strandedSpecies.disposition || 'No Information Available' }}</p>
                    <p><strong>Disposal Site:</strong> {{ strandedSpecies.disposal_site || 'No Information Available' }}</p>
                    <p><strong>More Information:</strong> {{ strandedSpecies.more_information || 'No Information Available' }}</p>
                </div>
            </div>

            <!-- Condition, Sea State, Weather, and Beach Type Section -->
            <div class=" bg-white shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4">Environmental Conditions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mx-5">
                    <p><strong>Sea State:</strong> {{ strandedIncident.sea_state || 'No Information Available' }}</p>
                    <p><strong>Weather:</strong> {{ strandedIncident.weather || 'No Information Available' }}</p>
                    <p><strong>Beach Type:</strong> {{ strandedIncident.beach_type || 'No Information Available' }}</p>
                </div>
            </div>

            <!-- Incident Location -->
            <div class=" bg-white shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4">Incident Location</h2>
                <p class="mb-2 mx-5"><strong>Location:</strong> {{ barangayName }}, {{ municipalityName }}</p>
                <div id="map" style="height: 400px; width: 100%;" class="mb-3"></div>
                <p class="mt-2 text-gray-500 text-sm text-center">{{ strandedSpecies.latitude }} lat. | {{ strandedSpecies.longitude }} long.</p>
            </div>
        </div>
             <!-- Action Buttons Section -->
             <div class="flex justify-end space-x-4 mb-8">


                <button
                    class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red- 700 transition"
                    @click="confirmArchiveSpeciesForm"
                    v-if="props.strandedSpecies.is_active"
                >
                    Archive
                </button>
                <button
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
                    @click="confirmArchiveSpeciesForm"
                    v-if="props.strandedSpecies.is_active === false"
                >
                    Unarchive
                </button>

                <Modal :show="showConfirmArchiveModal" @close="closeModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ props.strandedSpecies.is_active ? 'Are you sure you want to archive this stranded incident report?' : 'Are you sure you want to unarchive this stranded incident report?' }}
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
                            <DangerButton @click="archiveIncident">Confirm</DangerButton>
                        </div>
                    </div>
                </Modal>

                <button
                    class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                    @click="updateSpeciesForm"
                >
                    Update
                </button>
                <button class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition" @click="confirmDownload">
                    Download
                </button>

                <!-- Download Confirmation Modal -->
                <Modal :show="showDownloadConfirmModal" @close="closeDownloadModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800">Are you sure you want to download the report?</h2>
                        <div class="mt-4 flex justify-end space-x-4">
                            <SecondaryButton @click="closeDownloadModal">Cancel</SecondaryButton>
                            <DangerButton @click="downloadReport">Download</DangerButton>
                        </div>
                    </div>
                </Modal>
            </div>

        </div>
    </Sidebar>
</template>

<style scoped>

.bg-white {
    background-color: #ffffff;
}

.container {
    max-width: 80%;
    padding: 0 2rem; /* Reduced padding */
}

.text-indigo-700 {
    color: #4f46e5;
}

.text-gray-600 {
    color: #4b5563;
}

.text-gray-500 {
    color: #6b7280;
}

@media print {
    button {
        display: none; /* Hide buttons when printing */
    }
}
</style>
