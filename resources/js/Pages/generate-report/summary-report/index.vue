<script setup>
import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, PieElement, Title, Tooltip } from 'chart.js';
import { defineProps, ref } from 'vue';
import { Bar, Pie } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PieElement);

const props = defineProps({
    sightings: {
        type: Object,
        required: true
    },
    strandedIncidents: {
        type: Object,
        required: true
    },
    analyticsData: {
        type: Object,
        required: true
    }
});

const filters = ref({
    year: '',
    municipality: '',
    category: '',
    incidentType: ''
});

const years = ref([2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023]);
const municipalities = ref(['Municipality 1', 'Municipality 2', 'Municipality 3']);
const categories = ref(['Category 1', 'Category 2', 'Category 3']);
const types = ref(['stranding', 'sighting']);

const applyFilters = () => {
    // Fetch and display filtered data
};

const resetFilters = () => {
    filters.value = {
        year: '',
        municipality: '',
        category: '',
        incidentType: ''
    };
    // Fetch and display default data
};

const downloadReport = () => {
    // Logic to download the report
};
</script>

<template>
    <div>
        <h1>Marine Wildlife Incident Summary Report</h1>
        <div class="filters">
            <label for="year">Year:</label>
            <select v-model="filters.year" id="year">
                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
            </select>

            <label for="municipality">Municipality:</label>
            <select v-model="filters.municipality" id="municipality">
                <option v-for="municipality in municipalities" :key="municipality" :value="municipality">{{ municipality }}</option>
            </select>

            <label for="category">Category:</label>
            <select v-model="filters.category" id="category">
                <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
            </select>

            <label for="incidentType">Incident Type:</label>
            <select v-model="filters.incidentType" id="incidentType">
                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
            </select>

            <button @click="applyFilters">Apply</button>
            <button @click="resetFilters">Reset</button>
            <button @click="downloadReport">Download</button>
        </div>
        <div class="report-content">
            <h2>Yearly Trends</h2>
            <Bar :data="props.analyticsData.yearlyTrends" />

            <h2>Municipality Distribution</h2>
            <Pie :data="props.analyticsData.municipalityDistribution" />

            <h2>Conditions Frequency</h2>
            <Pie :data="props.analyticsData.conditionsFrequency" />

            <h2>Total Incidents: {{ props.analyticsData.totalIncidents }}</h2>
            <h2>Total Species Involved: {{ props.analyticsData.totalSpeciesInvolved }}</h2>
            <h2>Top 5 Common Species</h2>
            <ul>
                <li v-for="species in props.analyticsData.topCommonSpecies" :key="species.name">
                    {{ species.name }}: {{ species.count }}
                </li>
            </ul>

            <h2>Total False Reports: {{ props.analyticsData.totalFalseReports }}</h2>
        </div>
    </div>
</template>

<style>
.filters {
    margin-bottom: 20px;
}
.report-content {
    margin-top: 20px;
}
</style>
