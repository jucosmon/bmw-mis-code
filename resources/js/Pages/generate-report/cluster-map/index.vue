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

        const processedSightings = sightingsRes.data.flatMap(sighting =>
            sighting.sighted_species.map(ss => ({
                latitude: sighting.latitude,
                longitude: sighting.longitude,
                date: sighting.date,
                type: 'sighting',
                species_id: ss.species?.id,
                species_name: ss.species?.name ?? 'Unknown',
                category: ss.species?.category ?? 'Unknown',
            }))
        );

        const processedStrandedIncidents = strandingsRes.data.flatMap(incident =>
            incident.stranded_species.map(ss => ({
                latitude: ss.latitude,
                longitude: ss.longitude,
                date: incident.date,
                type: 'stranded',
                status: ss.condition_code,
                species_id: ss.species?.id,
                species_name: ss.species?.name ?? 'Unknown',
                category: ss.species?.category ?? 'Unknown',
            }))
        );

        incidents.value = [...processedSightings, ...processedStrandedIncidents];
        loadData();

    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

const loadData = () => {
    markers.value.clearLayers();

    incidents.value.forEach(incident => {
        // Apply filters
        if (filters.value.year && new Date(incident.date).getFullYear() !== parseInt(filters.value.year)) {
            return;
        }
        if (filters.value.category && incident.category !== filters.value.category) {
            return;
        }
        if (filters.value.eventType && incident.type.toLowerCase() !== filters.value.eventType.toLowerCase()) {
            return;
        }

        const markerColor = incident.type.toLowerCase() === 'sighting' ? 'blue' : 'red';
        const markerIcon = L.icon({
            iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-${markerColor}.png`,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const marker = L.marker([incident.latitude, incident.longitude], { icon: markerIcon });
        let popupContent = `Date: ${incident.date}<br>Species: ${incident.species_name}<br>Type: ${incident.type}`;
        if (incident.type.toLowerCase() === 'stranded') { // Ensure case insensitivity
            popupContent += `<br>Status: ${statusText(incident.status)}`;
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
    loadData();
};

// Show download confirmation modal
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
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Marine Wildlife Incident Cluster Map
                </h2>
            </div>
        </template>

        <div class="container mx-auto px-4 py-8">
            <div class="filters flex flex-wrap items-center gap-4 mb-4">
                <label for="year" class="font-medium">Year:</label>
                <select v-model="filters.year" id="year" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                </select>

                <label for="category" class="font-medium">Category:</label>
                <select v-model="filters.category" id="category" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="marine_mammals">Marine Mammals</option>
                    <option value="marine_turtles">Marine Turtles</option>
                    <option value="sharks_rays">Shark and Rays</option>
                </select>

                <label for="eventType" class="font-medium">Incident Type:</label>
                <select v-model="filters.eventType" id="eventType" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="Sighting">Sighting</option>
                    <option value="Stranded">Stranded</option>
                </select>

                <button @click="resetFilters" class="bg-gray-300 px-4 py-2 rounded">Reset</button>
                <button @click="showDownloadConfirmation" class="bg-green-500 text-white px-4 py-2 rounded">Download</button>
            </div>
            <div id="map" style="height: 500px; z-index: 0;"></div>
        </div>
    </Sidebar>

    <!-- Download Confirmation Modal -->
    <div v-if="showDownloadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <h3 class="text-lg font-semibold mb-4">Download Map</h3>
            <p class="mb-6">Are you sure you want to download the current map as a PDF?</p>
            <div class="flex justify-end space-x-3">
                <button
                    @click="showDownloadModal = false"
                    class="px-4 py-2 bg-gray-300 rounded">
                    Cancel
                </button>
                <button
                    @click="downloadPDF"
                    class="px-4 py-2 bg-green-500 text-white rounded"
                    :disabled="isDownloading">
                    {{ isDownloading ? 'Downloading...' : 'Download' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style>
#map {
    height: 500px;
    z-index: 0;
}
.filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
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
    background-color: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}
.marker-cluster span {
    font-size: 12px;
    font-weight: bold;
}
</style>
