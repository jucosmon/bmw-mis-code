<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head } from '@inertiajs/vue3';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import L from 'leaflet';
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    municipalities: {
        type: Array,
        required: true
    },
    barangays: {
        type: Array,
        required: true
    }
});

const map = ref(null);
const markers = ref(null);
const filters = ref({
    year: '',
    category: '',
    eventType: ''
});

const years = ref([2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025]);

const incidents = ref([]);
const showDownloadModal = ref(false);
const isDownloading = ref(false);

// Add these refs for channels
const sightingsChannel = ref(null);
const strandingsChannel = ref(null);

const showOnlyAccurateGPS = ref(false);

const getLocationName = (id, type) => {
    if (type === 'municipality') {
        const municipality = props.municipalities.find(m => m.id === id);
        return municipality ? municipality.name : '';
    } else {
        const barangay = props.barangays.find(b => b.id === id);
        return barangay ? barangay.name : '';
    }
};

const getCoordinatesFromNominatim = async (barangayId, municipalityId) => {
    try {
        const barangayName = getLocationName(barangayId, 'barangay');
        const municipalityName = getLocationName(municipalityId, 'municipality');

        if (!barangayName || !municipalityName) return null;

        const query = `${barangayName}, ${municipalityName}, Bohol, Philippines`;
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`
        );
        const data = await response.json();

        if (data && data.length > 0) {
            return {
                latitude: parseFloat(data[0].lat),
                longitude: parseFloat(data[0].lon),
                isEstimated: true
            };
        }
        return null;
    } catch (error) {
        console.error('Error fetching coordinates:', error);
        return null;
    }
};

onMounted(() => {
    // Initialize map first
    map.value = L.map('map').setView([9.8500, 124.1833], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    markers.value = L.markerClusterGroup({
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            let size = 'small';
            if (count > 10) size = 'medium';
            if (count > 50) size = 'large';
            return L.divIcon({
                html: `<div><span>${count}</span></div>`,
                className: `marker-cluster marker-cluster-${size}`,
                iconSize: L.point(40, 40, true),
            });
        }
    });
    map.value.addLayer(markers.value);

    // Register cleanup first, before any async operations
    onBeforeUnmount(() => {
        if (sightingsChannel.value) {
            supabase.removeChannel(sightingsChannel.value);
        }
        if (strandingsChannel.value) {
            supabase.removeChannel(strandingsChannel.value);
        }
        if (map.value) map.value.remove();
    });

    // Setup real-time subscriptions
    sightingsChannel.value = supabase.channel('cluster-sightings-changes')
        .on(
            'postgres_changes',
            {
                event: '*',
                schema: 'public',
                table: 'sightings',
                filter: 'is_active=eq.true'
            },
            () => fetchData()
        )
        .subscribe();

    strandingsChannel.value = supabase.channel('cluster-strandings-changes')
        .on(
            'postgres_changes',
            {
                event: '*',
                schema: 'public',
                table: 'stranded_incidents',
                filter: 'is_active=eq.true'
            },
            () => fetchData()
        )
        .subscribe();

    // Initial data fetch
    fetchData();
});

const statusText = (status) => {
    switch (status) {
        case 1:
            return 'Alive';
        case 2:
            return 'Freshly Dead';
        case 3:
            return 'Moderately Decomposed';
        case 4:
            return 'Severely Decomposed';
        case 5:
            return 'Dried';
        default:
            return 'Unknown';
    }
};

const fetchData = async () => {
    try {
        const [sightingsRes, strandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select(`
                    id,
                    date,
                    latitude,
                    longitude,
                    municipality_id,
                    barangay_id,
                    report_status,
                    is_active,
                    sighted_species (
                        id,
                        species (
                            id,
                            name,
                            category
                        )
                    )
                `)
                .eq('is_active', true)
                .eq('report_status', 'verified'),

            supabase
                .from('stranded_incidents')
                .select(`
                    id,
                    date,
                    municipality_id,
                    barangay_id,
                    report_status,
                    is_active,
                    stranded_species (
                        id,
                        condition_code,
                        latitude,
                        longitude,
                        species (
                            id,
                            name,
                            category
                        )
                    )
                `)
                .eq('is_active', true)
                .eq('report_status', 'resolved')
        ]);

        if (sightingsRes.error) throw sightingsRes.error;
        if (strandingsRes.error) throw strandingsRes.error;

        // Process sightings with coordinate estimation
        const processedSightings = await Promise.all(sightingsRes.data.flatMap(async sighting => {
            let coords = {
                latitude: sighting.latitude,
                longitude: sighting.longitude,
                isEstimated: false,
                municipality_id: sighting.municipality_id,
                barangay_id: sighting.barangay_id
            };

            if (!sighting.latitude || !sighting.longitude) {
                const estimatedCoords = await getCoordinatesFromNominatim(sighting.barangay_id, sighting.municipality_id);
                if (estimatedCoords) coords = { ...estimatedCoords, municipality_id: sighting.municipality_id, barangay_id: sighting.barangay_id };
                else return []; // Skip if no coordinates can be found
            }

            return sighting.sighted_species.map(ss => ({
                ...coords,
                date: sighting.date,
                type: 'sighting',
                species_id: ss.species?.id,
                species_name: ss.species?.name ?? 'Unknown',
                category: ss.species?.category ?? 'Unknown',
            }));
        }));

        // Process stranded incidents with coordinate estimation
        const processedStrandedIncidents = await Promise.all(strandingsRes.data.flatMap(async incident => {
            return Promise.all(incident.stranded_species.map(async ss => {
                let coords = {
                    latitude: ss.latitude,
                    longitude: ss.longitude,
                    isEstimated: false,
                    municipality_id: incident.municipality_id,
                    barangay_id: incident.barangay_id
                };

                if (!ss.latitude || !ss.longitude) {
                    const estimatedCoords = await getCoordinatesFromNominatim(incident.barangay_id, incident.municipality_id);
                    if (estimatedCoords) coords = { ...estimatedCoords, municipality_id: incident.municipality_id, barangay_id: incident.barangay_id };
                    else return null; // Skip if no coordinates can be found
                }

                return {
                    ...coords,
                    date: incident.date,
                    type: 'stranded',
                    status: ss.condition_code,
                    species_id: ss.species?.id,
                    species_name: ss.species?.name ?? 'Unknown',
                    category: ss.species?.category ?? 'Unknown',
                };
            }));
        }));

        incidents.value = [...processedSightings.flat(), ...processedStrandedIncidents.flat()].filter(Boolean);
        loadData();

    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

const loadData = () => {
    markers.value.clearLayers();

    incidents.value.forEach(incident => {
        if (showOnlyAccurateGPS.value && incident.isEstimated) return;

        // Apply filters
        if (filters.value.year && new Date(incident.date).getFullYear() !== parseInt(filters.value.year)) return;
        if (filters.value.category && incident.category !== filters.value.category) return;
        if (filters.value.eventType && incident.type.toLowerCase() !== filters.value.eventType.toLowerCase()) return;

        const markerColor = incident.type.toLowerCase() === 'sighting' ? 'green' : 'red';
        const markerOptions = {
            icon: L.icon({
                iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-${markerColor}.png`,
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }),
            opacity: incident.isEstimated ? 0.6 : 1 // Use opacity to indicate estimated locations
        };

        const marker = L.marker([incident.latitude, incident.longitude], markerOptions);

        let popupContent = `
            Date: ${incident.date}<br>
            Species: ${incident.species_name}<br>
            Type: ${incident.type}<br>
            Location: ${getLocationName(incident.municipality_id, 'municipality')}, ${getLocationName(incident.barangay_id, 'barangay')}<br>
            ${incident.isEstimated ? '<strong>(Estimated Location)</strong><br>' : ''}
        `;

        if (incident.type.toLowerCase() === 'stranded') {
            popupContent += `Status: ${statusText(incident.status)}`;
        }

        marker.bindPopup(popupContent);
        markers.value.addLayer(marker);
    });
};

watch(filters, () => {
    loadData();
}, { deep: true });

const resetFilters = () => {
    filters.value = {
        year: '',
        category: '',
        eventType: ''
    };
    showOnlyAccurateGPS.value = false; // Reset the accurate GPS filter
    loadData();
};

// Add watch for showOnlyAccurateGPS
watch(showOnlyAccurateGPS, () => {
    loadData();
});

const showDownloadConfirmation = () => {
    showDownloadModal.value = true;
};

// Download PDF implementation
const downloadPDF = async () => {
    // if (!isMapLoaded.value) {
    //     alert('Map is still loading. Please wait.');
    //     return;
    // }

    try {
        isDownloading.value = true;
        showDownloadModal.value = false;

        // Get the map content
        const mapElement = document.getElementById('map');

        // Temporarily hide the modal to capture the map correctly
        const modalElement = document.querySelector('.fixed.inset-0');
        if (modalElement) {
            modalElement.style.display = 'none';
        }

        // Create a canvas from the map element
        const canvas = await html2canvas(mapElement, {
            scale: 2, // Higher scale for better quality
            useCORS: true, // Enable CORS for images
            logging: false,
            backgroundColor: '#ffffff'
        });

        // Restore the modal display
        if (modalElement) {
            modalElement.style.display = '';
        }

        // Create PDF
        const pdf = new jsPDF('p', 'mm', 'a4');

        // Get the dimensions
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        // Add title
        const title = `Marine Wildlife Incident Cluster Map`;
        const subtitle = `Generated on ${new Date().toLocaleDateString()}`;

        pdf.setFontSize(18);
        pdf.text(title, 105, 20, { align: 'center' });
        pdf.setFontSize(12);
        pdf.text(subtitle, 105, 30, { align: 'center' });

        // Add filter information
        let filterText = 'Filters: ';
        filterText += filters.value.year ? `Year: ${filters.value.year}, ` : 'All Years, ';
        filterText += filters.value.category ? `Category: ${filters.value.category}, ` : 'All Categories, ';
        filterText += filters.value.eventType ? `Event Type: ${filters.value.eventType}` : 'All Event Types';

        pdf.setFontSize(10);
        const splitFilterText = pdf.splitTextToSize(filterText, 190); // Split text if it's too long

        const y = 40; // You can change this value to adjust the vertical position

        // Add the text to the PDF
        pdf.text(splitFilterText, 105, y, { align: 'center' });

        // Add the image to the PDF
        const imgData = canvas.toDataURL('image/png');
        // Calculate the height of the filter text
        const filterTextHeight = pdf.getTextDimensions(splitFilterText).h; // Get the height of the filter text

        // Set the position for the image, reducing the space
        let position = y + filterTextHeight + 1; // Add a small margin (5 mm) below the filter text

        // Split the image across multiple pages if needed
        let heightLeft = imgHeight;

        pdf.addImage(imgData, 'PNG', 10, position, imgWidth - 20, imgHeight);
        heightLeft -= (pageHeight - position);

        // Add more pages if the content is longer than one page
        while (heightLeft > 0) {
            position = 0;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 10, position, imgWidth - 20, imgHeight);
            heightLeft -= pageHeight;
        }

        // Save the PDF
        pdf.save(`marine-wildlife-cluster-map-${new Date().toISOString().slice(0, 10)}.pdf`);

        isDownloading.value = false;
    } catch (error) {
        console.error('Error generating PDF:', error);
        isDownloading.value = false;
        alert('Error generating PDF. Please try again.');
    }
};
</script>

<template>
    <Head title="Cluster Map" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Marine Wildlife Incident Cluster Map
            </h2>
        </template>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Page Title with Gradient -->
                    <div class="mb-6">
                        <h3 class="profile-title-gradient">Marine Wildlife Incident Map</h3>
                        <p class="text-white text-opacity-80">Visualize and analyze incident clusters across Bohol</p>
                    </div>

                    <!-- Filters Card -->
                    <div class="glass-panel mb-6">
                        <div class="p-5 border-b border-white/10">
                            <h3 class="font-semibold text-white flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filters
                            </h3>
                        </div>

                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-5">
                                <div>
                                    <label for="year" class="block text-sm font-medium text-white mb-1">Year</label>
                                    <select v-model="filters.year" id="year" class="w-full bg-white/20 border border-white/20 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                                        <option value="" class="bg-blue-900 text-white">All Years</option>
                                        <option v-for="year in years" :key="year" :value="year" class="bg-blue-900 text-white">{{ year }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="category" class="block text-sm font-medium text-white mb-1">Category</label>
                                    <select v-model="filters.category" id="category" class="w-full bg-white/20 border border-white/20 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                                        <option value="" class="bg-blue-900 text-white">All Categories</option>
                                        <option value="marine_mammals" class="bg-blue-900 text-white">Marine Mammals</option>
                                        <option value="marine_turtles" class="bg-blue-900 text-white">Marine Turtles</option>
                                        <option value="sharks_rays" class="bg-blue-900 text-white">Shark and Rays</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="eventType" class="block text-sm font-medium text-white mb-1">Incident Type</label>
                                    <select v-model="filters.eventType" id="eventType" class="w-full bg-white/20 border border-white/20 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                                        <option value="" class="bg-blue-900 text-white">All Types</option>
                                        <option value="Sighting" class="bg-blue-900 text-white">Sighting</option>
                                        <option value="Stranded" class="bg-blue-900 text-white">Stranded</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="accurateGPS"
                                        v-model="showOnlyAccurateGPS"
                                        class="form-checkbox h-5 w-5 text-blue-400 rounded-md cursor-pointer bg-white/10 border-white/20"
                                    >
                                    <label for="accurateGPS" class="text-sm font-medium text-white cursor-pointer">Show only GPS-verified locations</label>
                                </div>

                                <div class="flex gap-3">
                                    <button @click="resetFilters" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-300 flex items-center space-x-2 border border-white/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span>Reset Filters</span>
                                    </button>

                                    <button @click="showDownloadConfirmation" class="px-4 py-2 bg-blue-500/80 hover:bg-blue-600/80 text-white rounded-lg transition-colors duration-300 flex items-center space-x-2 border border-blue-400/30">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span>Download Map</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend and Map Container -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Legend Card -->
                        <div class="glass-panel">
                            <div class="p-5 border-b border-white/10">
                                <h3 class="font-semibold text-white flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                    Map Legend
                                </h3>
                            </div>

                            <div class="p-5 space-y-6">
                                <!-- Markers -->
                                <div>
                                    <h4 class="font-medium text-white mb-3">Markers</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 legend-item">
                                            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png"
                                                alt="Sighting" class="h-7 drop-shadow-glow">
                                            <span class="text-sm text-white">Sighting</span>
                                        </div>
                                        <div class="flex items-center gap-3 legend-item">
                                            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png"
                                                alt="Stranding" class="h-7 drop-shadow-glow">
                                            <span class="text-sm text-white">Stranding</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Type -->
                                <div>
                                    <h4 class="font-medium text-white mb-3">Location Type</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 legend-item">
                                            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png"
                                                 alt="GPS Verified" class="h-7 drop-shadow-glow">
                                            <span class="text-sm text-white">GPS Verified</span>
                                        </div>
                                        <div class="flex items-center gap-3 legend-item">
                                            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png"
                                                 alt="Area Estimated" class="h-7 drop-shadow-glow opacity-50">
                                            <span class="text-sm text-white">Area Estimated</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Clusters -->
                                <div>
                                    <h4 class="font-medium text-white mb-3">Clusters</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 legend-item">
                                            <div class="w-7 h-7 rounded-full bg-[rgba(181,226,140,0.6)] flex items-center justify-center drop-shadow-glow">
                                                <span class="text-xs font-bold text-[#006400]">&lt;10</span>
                                            </div>
                                            <span class="text-sm text-white">Small</span>
                                        </div>
                                        <div class="flex items-center gap-3 legend-item">
                                            <div class="w-7 h-7 rounded-full bg-[rgba(241,211,87,0.6)] flex items-center justify-center drop-shadow-glow">
                                                <span class="text-xs font-bold text-[#8B4513]">&lt;50</span>
                                            </div>
                                            <span class="text-sm text-white">Medium</span>
                                        </div>
                                        <div class="flex items-center gap-3 legend-item">
                                            <div class="w-7 h-7 rounded-full bg-[rgba(253,156,115,0.6)] flex items-center justify-center drop-shadow-glow">
                                                <span class="text-xs font-bold text-[#8B0000]">50+</span>
                                            </div>
                                            <span class="text-sm text-white">Large</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div class="lg:col-span-3">
                            <div class="glass-panel h-full">
                                <!-- Map header -->
                                <div class="p-3 border-b border-white/10">
                                    <h3 class="font-medium text-white text-sm">Bohol Province Map View</h3>
                                </div>
                                <div id="map" class="w-full h-[580px] z-0 rounded-b-xl overflow-hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>

    <!-- Download Confirmation Modal -->
    <div v-if="showDownloadModal" class="fixed inset-0 bg-blue-900/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="glass-panel p-6 rounded-xl max-w-md w-full border border-white/20">
            <h3 class="text-lg font-semibold mb-2 text-white">Download Map</h3>
            <p class="mb-6 text-white">Are you sure you want to download the current map as a PDF?</p>
            <div class="flex justify-end space-x-3">
                <button
                    @click="showDownloadModal = false"
                    class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-300 border border-white/20">
                    Cancel
                </button>
                <button
                    @click="downloadPDF"
                    class="px-4 py-2 bg-blue-500/80 hover:bg-blue-600/80 text-white rounded-lg transition-colors duration-300 flex items-center border border-blue-400/30"
                    :disabled="isDownloading">
                    <svg v-if="isDownloading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isDownloading ? 'Downloading...' : 'Download' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Map styling */
#map {
    z-index: 0;
    filter: saturate(0.8) brightness(0.95);
}

/* Marker styling */
.marker-cluster-small {
    background-color: rgba(181, 226, 140, 0.6);
    color: #006400;
}

.marker-cluster-medium {
    background-color: rgba(241, 211, 87, 0.6);
    color: #8B4513;
}

.marker-cluster-large {
    background-color: rgba(253, 156, 115, 0.6);
    color: #8B0000;
}

.marker-cluster div {
    background-color: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.marker-cluster span {
    font-size: 12px;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

/* Ocean theme styling - scoped to this component only */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.glass-panel {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.glass-panel:hover {
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.drop-shadow-glow {
    filter: drop-shadow(0 0 4px rgba(0, 204, 255, 0.5));
}

.legend-item {
    @apply bg-white/5 backdrop-blur-md p-2 rounded-lg border border-white/10 transition-all duration-200;
}

.legend-item:hover {
    @apply bg-white/10 border-white/20 transform -translate-y-0.5;
}

/* Custom scrollbar - only applied within this component */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Loading spinner animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}

/* Ocean-themed focus outline - only applies within this component */
.glass-panel *:focus {
    outline: 2px solid rgba(0, 204, 255, 0.5);
    outline-offset: 2px;
}

/* Select dropdown styling - scoped to our specific component */
.glass-panel select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.glass-panel select option {
    margin: 0.5rem 0;
    padding: 0.5rem;
}

/* Map popup styling - Leaflet specific elements */
:deep(.leaflet-popup-content-wrapper) {
    background: rgba(0, 51, 102, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

:deep(.leaflet-popup-tip) {
    background: rgba(0, 51, 102, 0.95);
}

:deep(.leaflet-popup-content) {
    color: rgba(255, 255, 255, 0.9);
}

:deep(.leaflet-popup-content a) {
    color: #00ccff;
}

/* Marker styling */
:deep(.leaflet-marker-icon) {
    filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.3));
}

/* Scoped Button effects - only for buttons in this component */
.glass-panel button,
.fixed.inset-0 button {
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.glass-panel button:hover:not(:disabled),
.fixed.inset-0 button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

.glass-panel button:disabled,
.fixed.inset-0 button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
