<script setup>
import L from 'leaflet';
import 'leaflet.markercluster';
import { defineProps, onMounted, ref } from 'vue';

const props = defineProps({
    sightings: {
        type: Object,
        required: true
    },
    strandedIncidents: {
        type: Object,
        required: true
    }
});

const map = ref(null);
const markers = ref(null);
const filters = ref({
    year: '',
    incidentType: ''
});



const years = ref([2021, 2022, 2023]);
const types = ref(['stranding', 'sighting']);

onMounted(() => {
    map.value = L.map('map').setView([51.505, -0.09], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    markers.value = L.markerClusterGroup();
    map.value.addLayer(markers.value);

    // Load initial data
    loadData();
});

const loadData = () => {
    // Fetch data from the server based on filters
    // This is a placeholder for actual data fetching logic
    const data = [
        { lat: 51.505, lng: -0.09, info: 'Incident 1' },
        { lat: 51.515, lng: -0.1, info: 'Incident 2' },
        // Add more data points here
    ];

    markers.value.clearLayers();
    data.forEach(point => {
        const marker = L.marker([point.lat, point.lng]);
        marker.bindPopup(point.info);
        markers.value.addLayer(marker);
    });
};

const applyFilters = () => {
    loadData();
};

const resetFilters = () => {
    filters.value = {
        year: '',
        municipality: '',
        incidentType: ''
    };
    loadData();
};
</script>

<template>
    <div>
        <h1>Marine Wildlife Incident Cluster Map</h1>
        <div class="filters">
            <label for="year">Year:</label>
            <select v-model="filters.year" id="year">
                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
            </select>

            <label for="incidentType">Incident Type:</label>
            <select v-model="filters.incidentType" id="incidentType">
                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
            </select>

            <button @click="applyFilters">Apply</button>
            <button @click="resetFilters">Reset</button>
        </div>
        <div id="map" style="height: 500px;"></div>
    </div>
</template>


<style>
#map {
    height: 500px;
}
.filters {
    margin-bottom: 20px;
}
</style>
