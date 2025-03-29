<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import html2canvas from 'html2canvas';
import html2pdf from 'html2pdf.js';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
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
    },
    municipalities: {
        type: Array,
        default: () => [],
    },
    barangays: {
        type: Array,
        default: () => [],
    },
});

// Form defaults
const form = useForm({
    is_active: true,
    password: '',
    text: '',
    stranded_incident_id: props.strandedIncident.id,
});

const showPassword = ref(false);
// Routes
const backRoute = computed(() => {
    return route('stranded.incident.view', { id: props.strandedIncident.id });
});

const updateRoute = computed(() => {
    return route('stranded.species.update.page', { id: props.strandedSpecies.id });
});

const archiveRoute = computed(() => {
    return route('stranded.species.archive', { id: props.strandedSpecies.id });
});

const unarchiveRoute = computed(() => {
    return route('stranded.species.unarchive', { id: props.strandedSpecies.id });
});

// Methods
const updateSpeciesForm = () => {
    router.visit(updateRoute.value);
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
    const municipality = props.municipalities.find(
        (m) => m.id === props.strandedIncident.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = props.barangays.find(
        (b) => b.id === props.strandedIncident.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

// Map references
const map = ref(null);
const marker = ref(null);

const hasValidCoordinates = computed(() => {
    return props.strandedSpecies.latitude != null &&
           props.strandedSpecies.longitude != null &&
           props.strandedSpecies.latitude !== '' &&
           props.strandedSpecies.longitude !== '';
});

// Initialize Leaflet map
onMounted(() => {
    if (hasValidCoordinates.value) {
        nextTick(() => {
            // Fix for Leaflet default icon
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: markerIcon,
                iconUrl: markerIcon,
                shadowUrl: markerShadow,
            });
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
    }
});


const showDownloadConfirmModal = ref(false);

const confirmDownload = () => {
    showDownloadConfirmModal.value = true; // Show the confirmation modal
};

const closeDownloadModal = () => {
    showDownloadConfirmModal.value = false; // Close the modal
};

const downloadReport = () => {
    // Create a clean PDF document container
    const pdfContainer = document.createElement('div');
    pdfContainer.className = 'pdf-export';
    pdfContainer.style.width = '210mm';
    pdfContainer.style.padding = '10mm';
    pdfContainer.style.backgroundColor = 'white';
    pdfContainer.style.color = '#333';
    pdfContainer.style.fontFamily = 'Arial, sans-serif';

    // ===== HEADER =====
    const header = document.createElement('div');
    header.style.textAlign = 'center';
    header.style.marginBottom = '5mm';

    const title = document.createElement('h1');
    title.textContent = 'Stranded Species Report';
    title.style.fontSize = '20px';
    title.style.color = '#003366';
    title.style.marginBottom = '2mm';
    title.style.fontWeight = 'bold';

    const subtitle = document.createElement('p');
    subtitle.textContent = `Generated on ${new Date().toLocaleDateString()}`;
    subtitle.style.fontSize = '12px';
    subtitle.style.color = '#666';

    header.appendChild(title);
    header.appendChild(subtitle);
    pdfContainer.appendChild(header);

    // Function to add section with minimal spacing
    const addSection = (title, content) => {
        const section = document.createElement('div');
        section.style.marginBottom = '6mm';

        const sectionTitle = document.createElement('div');
        sectionTitle.textContent = title;
        sectionTitle.style.fontSize = '14px';
        sectionTitle.style.fontWeight = 'bold';
        sectionTitle.style.color = '#003366';
        sectionTitle.style.marginBottom = '2mm';
        sectionTitle.style.paddingBottom = '1mm';
        sectionTitle.style.borderBottom = '1px solid #e5e7eb';

        section.appendChild(sectionTitle);

        if (typeof content === 'string') {
            const paragraph = document.createElement('p');
            paragraph.textContent = content;
            paragraph.style.lineHeight = '1.4';
            paragraph.style.fontSize = '12px';
            section.appendChild(paragraph);
        } else {
            section.appendChild(content);
        }

        return section;
    };

    // Incident details with minimal spacing
    const incidentDetails = document.createElement('p');
    incidentDetails.style.fontSize = '12px';
    incidentDetails.style.lineHeight = '1.4';
    incidentDetails.innerHTML =
        `<strong>Date:</strong> ${props.strandedIncident.date || 'Not specified'}<br>` +
        `<strong>Time:</strong> ${props.strandedIncident.time || 'Not specified'}<br>` +
        `<strong>Location:</strong> ${barangayName.value}, ${municipalityName.value}<br>` +
        `<strong>Status:</strong> ${props.strandedSpecies.is_active ? 'Active' : 'Inactive'}`;

    pdfContainer.appendChild(addSection('Incident Details', incidentDetails));

    // Species details with minimal spacing
    const speciesDetails = document.createElement('p');
    speciesDetails.style.fontSize = '12px';
    speciesDetails.style.lineHeight = '1.4';
    speciesDetails.innerHTML =
        `<strong>Species:</strong> ${props.strandedSpecies.species_name || 'Not specified'}<br>` +
        `<strong>Sex:</strong> ${props.strandedSpecies.sex || 'Not specified'}<br>` +
        `<strong>Length:</strong> ${props.strandedSpecies.length || 'Not specified'}<br>` +
        `<strong>Weight:</strong> ${props.strandedSpecies.weight || 'Not specified'}<br>` +
        `<strong>Girth:</strong> ${props.strandedSpecies.girth || 'Not specified'}<br>` +
        `<strong>Condition:</strong> ${getConditionDescription(props.strandedSpecies.condition_code) || 'Not specified'}<br>` +
        `<strong>Released:</strong> ${props.strandedSpecies.is_released ? 'Yes' : 'No'}<br>` +
        `<strong>Disposition:</strong> ${props.strandedSpecies.disposition || 'Not specified'}`;

    // Add more information if available with minimal spacing
    if (props.strandedSpecies.more_information) {
        speciesDetails.innerHTML += `<br><br><strong>Additional Information:</strong> ${props.strandedSpecies.more_information}`;
    }

    pdfContainer.appendChild(addSection('Species Information', speciesDetails));

    // Environmental conditions with minimal spacing
    const envDetails = document.createElement('p');
    envDetails.style.fontSize = '12px';
    envDetails.style.lineHeight = '1.4';
    envDetails.innerHTML =
        `<strong>Sea State:</strong> ${props.strandedIncident.sea_state || 'Not specified'}<br>` +
        `<strong>Weather:</strong> ${props.strandedIncident.weather || 'Not specified'}<br>` +
        `<strong>Beach Type:</strong> ${props.strandedIncident.beach_type || 'Not specified'}`;

    pdfContainer.appendChild(addSection('Environmental Conditions', envDetails));

    // Location and map with minimal spacing
    const locationSection = document.createElement('div');
    locationSection.style.marginBottom = '6mm';

    const locationTitle = document.createElement('div');
    locationTitle.textContent = 'Location Information';
    locationTitle.style.fontSize = '14px';
    locationTitle.style.fontWeight = 'bold';
    locationTitle.style.color = '#003366';
    locationTitle.style.marginBottom = '2mm';
    locationTitle.style.paddingBottom = '1mm';
    locationTitle.style.borderBottom = '1px solid #e5e7eb';
    locationSection.appendChild(locationTitle);

    // Location text
    const locationText = document.createElement('p');
    locationText.style.fontSize = '12px';
    locationText.style.lineHeight = '1.4';
    locationText.style.marginBottom = '2mm';

    if (hasValidCoordinates.value) {
        locationText.innerHTML =
            `<strong>Coordinates:</strong> ${props.strandedSpecies.latitude} lat. | ${props.strandedSpecies.longitude} long.`;
    } else {
        locationText.innerHTML = '<strong>Coordinates:</strong> No GPS coordinates available';
    }

    locationSection.appendChild(locationText);

    // Add map only if coordinates are valid
    if (hasValidCoordinates.value) {
        // Function to capture map and add to PDF
        const captureMap = async () => {
            try {
                const mapElement = document.getElementById('map');
                if (mapElement) {
                    // Ensure the map has fully loaded before capturing
                    // Increased delay for map tiles to load properly
                    await new Promise(resolve => setTimeout(resolve, 1000));

                    // Force a map repaint to ensure visibility
                    if (map.value) {
                        map.value.invalidateSize();
                    }

                    // Use a higher scale for better quality
                    const canvas = await html2canvas(mapElement, {
                        useCORS: true,
                        scale: 2,
                        logging: true, // Enable logging to debug issues
                        backgroundColor: '#ffffff',
                        allowTaint: true,
                        foreignObjectRendering: false
                    });

                    const mapImage = document.createElement('img');
                    mapImage.src = canvas.toDataURL('image/png');
                    mapImage.style.width = '100%';
                    mapImage.style.maxHeight = '120mm';
                    mapImage.style.border = '1px solid #e5e7eb';

                    locationSection.appendChild(mapImage);
                }
            } catch (error) {
                console.error('Error capturing map:', error);
                const errorText = document.createElement('p');
                errorText.textContent = 'Unable to display map. Error: ' + error.message;
                errorText.style.color = '#dc2626';
                errorText.style.fontSize = '12px';
                locationSection.appendChild(errorText);
            }
        };

        // Call the map capture function
        captureMap();
    }

    pdfContainer.appendChild(locationSection);

    // Add the container to document temporarily
    document.body.appendChild(pdfContainer);

    // Wait longer to ensure map renders completely before generating PDF
    setTimeout(() => {
        // PDF generation options
        const options = {
            filename: `Stranded_Species_Report_${props.strandedSpecies.id}.pdf`,
            margin: [5, 5, 5, 5],
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                allowTaint: true
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Generate PDF
        html2pdf().from(pdfContainer).set(options).save().then(() => {
            // Clean up
            document.body.removeChild(pdfContainer);
            closeDownloadModal();
        });
    }, 1500);
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
        <div class="relative min-h-screen">
            <!-- Background image -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0"
                 style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main Content -->
            <div class="relative z-10 container mx-auto px-6 py-16 max-w-5xl">
                <div class="exportable-content">
                    <!-- Main Title Header -->
                    <div class="text-center mb-10">
                        <h1 class="text-3xl font-bold text-white mb-2">Stranded Species-Specific Report</h1>
                        <p class="text-blue-200">Detailed information about the species involved in a stranded incident.</p>
                    </div>

                    <!-- Stranded Incident Details -->
                    <div class="bg-blue-900/40 backdrop-blur-md shadow-lg rounded-xl p-6 mb-8 border border-blue-800/30">
                        <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Incident Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Date</h4>
                                <p class="text-white">{{ strandedIncident.date || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Time</h4>
                                <p class="text-white">{{ strandedIncident.time || 'No Information Available' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Species Details -->
                    <div class="bg-blue-900/40 backdrop-blur-md shadow-lg rounded-xl p-6 mb-8 border border-blue-800/30">
                        <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Species Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Species</h4>
                                <p class="text-white">{{ strandedSpecies.species_name || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Sex</h4>
                                <p class="text-white">{{ strandedSpecies.sex || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Length</h4>
                                <p class="text-white">{{ strandedSpecies.length || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Weight</h4>
                                <p class="text-white">{{ strandedSpecies.weight || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Girth</h4>
                                <p class="text-white">{{ strandedSpecies.girth || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Condition</h4>
                                <p class="text-white">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': strandedSpecies.condition_code === 1,
                                            'bg-red-100 text-red-800': strandedSpecies.condition_code === 2,
                                            'bg-yellow-100 text-yellow-800': strandedSpecies.condition_code === 3,
                                            'bg-orange-100 text-orange-800': strandedSpecies.condition_code === 4,
                                            'bg-gray-100 text-gray-800': strandedSpecies.condition_code === 5,
                                            'bg-purple-100 text-purple-800': strandedSpecies.condition_code === 6,
                                        }">
                                        {{ getConditionDescription(strandedSpecies.condition_code) }}
                                    </span>
                                </p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Released</h4>
                                <p class="text-white">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': strandedSpecies.is_released,
                                            'bg-red-100 text-red-800': !strandedSpecies.is_released
                                        }">
                                        {{ strandedSpecies.is_released ? 'Yes' : 'No' }}
                                    </span>
                                </p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Disposition</h4>
                                <p class="text-white">{{ strandedSpecies.disposition || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Disposal Site</h4>
                                <p class="text-white">{{ strandedSpecies.disposal_site || 'No Information Available' }}</p>
                            </div>
                            <div class="md:col-span-2 bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">More Information</h4>
                                <p class="text-white">{{ strandedSpecies.more_information || 'No Information Available' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Environmental Conditions -->
                    <div class="bg-blue-900/40 backdrop-blur-md shadow-lg rounded-xl p-6 mb-8 border border-blue-800/30">
                        <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            Environmental Conditions
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Sea State</h4>
                                <p class="text-white">{{ strandedIncident.sea_state || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Weather</h4>
                                <p class="text-white">{{ strandedIncident.weather || 'No Information Available' }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Beach Type</h4>
                                <p class="text-white">{{ strandedIncident.beach_type || 'No Information Available' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Incident Location -->
                    <div class="bg-blue-900/40 backdrop-blur-md shadow-lg rounded-xl p-6 mb-8 border border-blue-800/30">
                        <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Incident Location
                        </h2>
                        <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40 mb-4">
                            <h4 class="text-blue-300 text-sm mb-2 font-medium">Location</h4>
                            <p class="text-white">{{ barangayName }}, {{ municipalityName }}</p>
                        </div>
                        <div v-if="hasValidCoordinates" id="map" style="height: 400px; width: 100%;" class="mb-3 rounded-lg shadow-md border border-blue-800/40"></div>
                        <div v-else class="flex items-center justify-center p-8 bg-blue-950/50 rounded-lg border border-blue-800/40">
                            <p class="text-blue-200 text-center">
                                <span class="block text-lg font-medium mb-2">📍 No GPS Coordinates Available</span>
                                <span class="text-sm">The exact location for this incident was not recorded.</span>
                            </p>
                        </div>
                        <p v-if="hasValidCoordinates" class="mt-3 text-blue-200 text-sm text-center bg-blue-950/50 p-2 rounded-lg inline-block mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ strandedSpecies.latitude }} lat. | {{ strandedSpecies.longitude }} long.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons Section -->
                <div class="flex justify-end space-x-4 mb-8 action-buttons">
                    <button
                        class="bg-red-600/80 hover:bg-red-700/80 text-white px-6 py-2 rounded-lg backdrop-blur-sm border border-red-500/30 hover:shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1 flex items-center"
                        @click="confirmArchiveSpeciesForm"
                        v-if="props.strandedSpecies.is_active"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Archive
                    </button>
                    <button
                        class="bg-green-600/80 hover:bg-green-700/80 text-white px-6 py-2 rounded-lg backdrop-blur-sm border border-green-500/30 hover:shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1 flex items-center"
                        @click="confirmArchiveSpeciesForm"
                        v-if="props.strandedSpecies.is_active === false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Unarchive
                    </button>

                    <button
                        class="bg-blue-600/80 hover:bg-blue-700/80 text-white px-6 py-2 rounded-lg backdrop-blur-sm border border-blue-500/30 hover:shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1 flex items-center"
                        @click="updateSpeciesForm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Update
                    </button>
                    <button
                        class="bg-teal-600/80 hover:bg-teal-700/80 text-white px-6 py-2 rounded-lg backdrop-blur-sm border border-teal-500/30 hover:shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1 flex items-center"
                        @click="confirmDownload"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download
                    </button>
                </div>
            </div>
        </div>

        <!-- Archive/Unarchive Modal -->
        <Modal :show="showConfirmArchiveModal" @close="closeModal">
            <div class="p-6 bg-blue-900/90 backdrop-blur-md rounded-lg border border-blue-800/30">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 bg-red-100/20 rounded-full p-2 mr-3">
                        <svg class="h-6 w-6 text-red-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-white">
                        {{ props.strandedSpecies.is_active ? 'Archive Species' : 'Unarchive Species' }}
                    </h3>
                </div>

                <div class="mt-2">
                    <p class="text-sm text-blue-200">
                        {{ props.strandedSpecies.is_active
                            ? 'Are you sure you want to archive this stranded incident report? This will make it invisible to regular users.'
                            : 'Are you sure you want to unarchive this stranded incident report? This will make it visible to all users again.' }}
                    </p>
                </div>

                <div class="mt-4">
                    <label for="admin-password" class="block text-sm font-medium text-blue-200">
                        Confirm by entering your password
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="admin-password"
                            v-model="form.password"
                            class="bg-blue-950/50 border border-blue-800/40 text-white mt-1 block w-full px-4 py-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Enter your password"
                        />
                    </div>
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">
                        {{ form.errors.password }}
                    </p>
                </div>
                <div class="flex my-4">
                    <Checkbox name="showPassword" v-model:checked="showPassword" />
                    <span class="ms-2 text-sm text-white">Show Password</span>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <DangerButton @click="archiveIncident">
                        Confirm
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Download Confirmation Modal -->
        <Modal :show="showDownloadConfirmModal" @close="closeDownloadModal">
            <div class="p-6 bg-blue-900/90 backdrop-blur-md rounded-lg border border-blue-800/30">
                <h2 class="text-lg font-semibold text-white">Are you sure you want to download the report?</h2>
                <div class="mt-4 flex justify-end space-x-4">
                    <SecondaryButton @click="closeDownloadModal">Cancel</SecondaryButton>
                    <DangerButton @click="downloadReport">Download</DangerButton>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>

<style scoped>
.bg-gradient-overlay {
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.9) 0%, rgba(0, 64, 128, 0.8) 50%, rgba(0, 31, 63, 0.9) 100%);
}

/* PDF Generation Styles */
.pdf-mode {
    background: white !important;
    color: #111827 !important;
    padding: 0 !important;
    max-width: 100% !important;
}

.pdf-mode .container {
    max-width: 100% !important;
    padding: 0 !important;
}

.pdf-mode .text-center {
    text-align: center !important;
    margin-bottom: 1rem !important;
}

.pdf-mode h1 {
    font-size: 1.5rem !important;
    margin-bottom: 0.25rem !important;
    color: #1e40af !important;
}

.pdf-mode h2 {
    font-size: 1.25rem !important;
    color: #1e40af !important;
    margin-bottom: 0.75rem !important;
    padding-bottom: 0.25rem !important;
    border-bottom: 1px solid #e5e7eb !important;
}

.pdf-mode .bg-blue-900\/40,
.pdf-mode .bg-blue-950\/50,
.pdf-mode .bg-blue-800\/50 {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    margin-bottom: 1rem !important;
    padding: 0.75rem !important;
}

.pdf-mode .text-white {
    color: #111827 !important;
}

.pdf-mode .text-blue-300,
.pdf-mode .text-blue-200 {
    color: #1e40af !important;
    font-weight: 600 !important;
}

.pdf-mode .bg-blue-950\/50 {
    background: #f9fafb !important;
    border: 1px solid #e5e7eb !important;
    margin-bottom: 0.5rem !important;
    padding: 0.5rem !important;
}

.pdf-mode [class*="inline-flex"] {
    background: #f3f4f6 !important;
    border: 1px solid #e5e7eb !important;
    color: #374151 !important;
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
}

.pdf-mode #map {
    border: 1px solid #e5e7eb !important;
    margin: 0.5rem 0 !important;
}

.pdf-mode .backdrop-blur-md {
    backdrop-filter: none !important;
}

.pdf-mode .exportable-content > div {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 1rem !important;
}

.pdf-mode .p-6 {
    padding: 0.75rem !important;
}

.pdf-mode .mb-8 {
    margin-bottom: 1rem !important;
}

.pdf-mode .grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 0.5rem !important;
    margin: 0 !important;
}

.pdf-mode .grid > div {
    margin-bottom: 0 !important;
}

.pdf-mode .text-gray-600,
.pdf-mode .text-gray-500 {
    color: #4b5563 !important;
}

.pdf-mode a {
    color: #2563eb !important;
    text-decoration: none !important;
}

.pdf-mode .border-blue-800\/30 {
    border-color: #e5e7eb !important;
}

.pdf-mode .shadow-lg {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}

.pdf-mode h4 {
    color: #1e40af !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.25rem !important;
}

.pdf-mode p {
    margin: 0 !important;
    font-size: 0.875rem !important;
}

/* Print Styles */
@media print {
    .action-buttons, button {
        display: none !important;
    }

    .bg-gradient-overlay {
        background: none !important;
    }

    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .text-center {
        text-align: center !important;
        margin-bottom: 1rem !important;
    }

    h1 {
        font-size: 1.5rem !important;
        margin-bottom: 0.25rem !important;
        color: #1e40af !important;
    }

    h2 {
        font-size: 1.25rem !important;
        color: #1e40af !important;
        margin-bottom: 0.75rem !important;
        padding-bottom: 0.25rem !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .bg-blue-900\/40, .bg-blue-950\/50, .bg-blue-800\/50 {
        background: white !important;
        border: 1px solid #e5e7eb !important;
        margin-bottom: 1rem !important;
        padding: 0.75rem !important;
    }

    .text-white {
        color: #111827 !important;
    }

    .text-blue-300, .text-blue-200 {
        color: #1e40af !important;
        font-weight: 600 !important;
    }

    .bg-blue-950\/50 {
        background: #f9fafb !important;
        border: 1px solid #e5e7eb !important;
        margin-bottom: 0.5rem !important;
        padding: 0.5rem !important;
    }

    [class*="inline-flex"] {
        background: #f3f4f6 !important;
        border: 1px solid #e5e7eb !important;
        color: #374151 !important;
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }

    #map {
        border: 1px solid #e5e7eb !important;
        margin: 0.5rem 0 !important;
    }

    .backdrop-blur-md {
        backdrop-filter: none !important;
    }

    .exportable-content > div {
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 1rem !important;
    }

    .p-6 {
        padding: 0.75rem !important;
    }

    .mb-8 {
        margin-bottom: 1rem !important;
    }

    .grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.5rem !important;
        margin: 0 !important;
    }

    .grid > div {
        margin-bottom: 0 !important;
    }

    .text-gray-600, .text-gray-500 {
        color: #4b5563 !important;
    }

    a {
        color: #2563eb !important;
        text-decoration: none !important;
    }

    .border-blue-800\/30 {
        border-color: #e5e7eb !important;
    }

    .shadow-lg {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    }

    h4 {
        color: #1e40af !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.25rem !important;
    }

    p {
        margin: 0 !important;
        font-size: 0.875rem !important;
    }
}
</style>
