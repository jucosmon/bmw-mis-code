<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import L from 'leaflet';
import 'leaflet.markercluster/dist/leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const map = ref(null);
const markers = ref(null);
const filters = ref({
    year: '',
    category: '',
    eventType: ''
});

const years = ref([2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025]);

const incidents = ref([]);

onMounted(() => {
    console.log('Incidents:', incidents.value);

    map.value = L.map('map').setView([9.8500, 124.1833], 10); // Bohol coordinates

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map.value);

    markers.value = L.markerClusterGroup({
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            let size = 'small';
            if (count > 10) {
                size = 'medium';
            }
            if (count > 50) {
                size = 'large';
            }
            return L.divIcon({
                html: `<div><span>${count}</span></div>`,
                className: `marker-cluster marker-cluster-${size}`,
                iconSize: L.point(40, 40, true),
            });
        }
    });
    map.value.addLayer(markers.value);

    // Load initial data
    fetchData();

    // Subscribe to Supabase Realtime
    const channel = supabase.channel('public:incidents')
        .on('postgres_changes', { event: '*', schema: 'public', table: 'incidents' }, payload => {
            console.log('Change received!', payload);
            fetchData();
        })
        .subscribe();

    onBeforeUnmount(() => {
        supabase.removeChannel(channel);
    });
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
    const { data: sightings, error: sightingsError } = await supabase
        .from('sightings')
        .select('*, sighted_species(*, species(*))')
        .eq('is_active', true)
        .eq('report_status', 'verified');

    const { data: strandedIncidents, error: strandedIncidentsError } = await supabase
        .from('stranded_incidents')
        .select('*, stranded_species(*, species(*))')
        .eq('is_active', true)
        .eq('report_status', 'resolved');

    if (sightingsError || strandedIncidentsError) {
        console.error('Error fetching data:', sightingsError || strandedIncidentsError);
    } else {
        const processedSightings = sightings.flatMap(sighting => {
            return sighting.sighted_species.map(sighted_species => ({
                latitude: sighting.latitude,
                longitude: sighting.longitude,
                date: sighting.date,
                type: 'sighting',
                species_id: sighted_species.species.id,
                species_name: sighted_species.species.name ?? 'Unknown',
                category: sighted_species.species.category ?? 'Unknown',
            }));
        });

        const processedStrandedIncidents = strandedIncidents.flatMap(incident => {
            return incident.stranded_species.map(stranded_species => {
                const status = stranded_species.condition_code == 1 ? 'Alive' : (stranded_species.condition_code >= 2 && stranded_species.condition_code <= 5 ? 'Dead' : 'Unknown');
                return {
                    latitude: stranded_species.latitude,
                    longitude: stranded_species.longitude,
                    date: incident.date,
                    type: 'stranded',
                    status: stranded_species.condition_code,
                    species_id: stranded_species.species.id,
                    species_name: stranded_species.species.name ?? 'Unknown',
                    category: stranded_species.species.category ?? 'Unknown',
                };
            });
        });

        incidents.value = [...processedSightings, ...processedStrandedIncidents];
        loadData();
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

const applyFilters = () => {
    loadData();
};

const resetFilters = () => {
    filters.value = {
        year: '',
        category: '',
        eventType: ''
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
