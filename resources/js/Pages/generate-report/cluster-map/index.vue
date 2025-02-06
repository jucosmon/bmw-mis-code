<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import L from 'leaflet';
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';
import { defineProps, onMounted, ref } from 'vue';

const props = defineProps({
    incidents: {
        type: Array,
        required: true
    }
});

const map = ref(null);
const markers = ref(null);
const filters = ref({
    year: '',
    category: '',
    incidentType: ''
});

const years = ref([2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025]);
const categories = ref(['Marine Mammals', 'Marine Turtles', 'Shark and Rays']);
const types = ref(['Stranded', 'Sighted']);

onMounted(() => {
    console.log('Incidents:', props.incidents);

    map.value = L.map('map').setView([9.8500, 124.1833], 10); // Bohol coordinates

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    markers.value = L.markerClusterGroup();
    map.value.addLayer(markers.value);

    // Load initial data
    loadData();
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

const loadData = () => {
    markers.value.clearLayers();

    props.incidents.forEach(incident => {
        // Apply filters
        if (filters.value.year && new Date(incident.date).getFullYear() !== parseInt(filters.value.year)) {
            return;
        }
        if (filters.value.category && !incident.species.some(s => s.category === filters.value.category)) {
            return;
        }
        if (filters.value.incidentType && incident.type.toLowerCase() !== filters.value.incidentType.toLowerCase()) {
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
        const speciesInfo = incident.species ? incident.species.map(s => s.species_name).join(', ') : 'Unknown';
        let popupContent = `Date: ${incident.date}<br>Species: ${speciesInfo}<br>Type: ${incident.type}`;
        if (incident.type.toLowerCase() === 'stranded') { // Ensure case insensitivity
            popupContent += `<br>Status: ${statusText(incident.status)}`;
        }
        marker.bindPopup(popupContent);
        markers.value.addLayer(marker);
    });
};

const applyFilters = () => {
    loadData();
};

const resetFilters = () => {
    filters.value = {
        year: '',
        category: '',
        incidentType: ''
    };
    loadData();
};
</script>

<template>
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
                    <option value="marine_turtles">Marine Turtles</option>
                    <option value="marine_mammals">Marine Mammals</option>
                    <option value="sharks_rays">Shark and Rays</option>
                </select>

                <label for="incidentType" class="font-medium">Incident Type:</label>
                <select v-model="filters.incidentType" id="incidentType" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="sighting">Sighting</option>
                    <option value="stranded">Stranded</option>
                </select>

                <button @click="applyFilters" class="bg-blue-500 text-white px-4 py-2 rounded">Apply</button>
                <button @click="resetFilters" class="bg-gray-300 px-4 py-2 rounded">Reset</button>
            </div>
            <div id="map" style="height: 500px;"></div>
        </div>
    </Sidebar>
</template>

<style>
#map {
    height: 500px;
}
.filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.custom-marker {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
