<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { ArcElement, BarController, BarElement, CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PieController, PointElement, Tooltip } from 'chart.js';
import { onBeforeUnmount, onMounted, ref } from 'vue';

// Register Chart.js components
Chart.register(LineController, BarController, PieController, CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler);

const filters = ref({
    year: '',
    municipality: '',
    category: '',
    eventType: ''
});

const years = ref([2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025]);
const municipalities = ref([]);

const summaryData = ref({
    yearlyTrends: [],
    categoryDistribution: [],
    municipalityDistribution: [],
    conditionFrequency: [],
    totalEvents: 0,
    totalSpecies: 0,
    topCommonSpecies: [],
    falseReports: 0
});

let yearlyTrendsChart, categoryTrendsChart, municipalityDistributionChart, conditionFrequencyChart;

const fetchData = async () => {
    const { data: sightings, error: sightingsError } = await supabase
        .from('sightings')
        .select('*, sighted_species(*, species(*))')
        .eq('is_active', true);

    const { data: strandedIncidents, error: strandedIncidentsError } = await supabase
        .from('stranded_incidents')
        .select('*, stranded_species(*, species(*))')
        .eq('is_active', true);


    if (sightingsError || strandedIncidentsError) {
        console.error('Error fetching data:', sightingsError || strandedIncidentsError);
    } else {
        const processedSightings = sightings.flatMap(sighting => {
            return sighting.sighted_species.map(sighted_species => ({
                latitude: sighting.latitude,
                longitude: sighting.longitude,
                date: sighting.date,
                type: 'sighting',
                sighting_id: sighting.id,
                species_id: sighted_species.species.id,
                species_name: sighted_species.species.name ?? 'Unknown',
                category: sighted_species.species.category ?? 'Unknown',
                municipality_id: sighting.municipality_id,
                report_status: sighting.report_status
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
                    stranded_incident_id: incident.id,
                    status: stranded_species.condition_code,
                    species_id: stranded_species.species.id,
                    species_name: stranded_species.species.name ?? 'Unknown',
                    category: stranded_species.species.category ?? 'Unknown',
                    municipality_id: incident.municipality_id,
                    report_status: incident.report_status
                };
            });
        });

        const combinedData = [...processedSightings, ...processedStrandedIncidents];

        const filteredData = combinedData.filter((item) => {
            const yearMatch =
                !filters.value.year ||
                new Date(item.date).getFullYear() ===
                    parseInt(filters.value.year);
            const municipalityMatch =
                !filters.value.municipality ||
                item.municipality_id === parseInt(filters.value.municipality);
            const categoryMatch =
                !filters.value.category ||
                item.category === filters.value.category;
            const eventTypeMatch =
                !filters.value.eventType ||
                (item.type && item.type.toLowerCase() === filters.value.eventType.toLowerCase());
            const statusMatch =
                (item.type === 'sighting' && item.report_status === 'verified') ||
                (item.type === 'stranded' && item.report_status === 'resolved');

            return (
                yearMatch &&
                municipalityMatch &&
                categoryMatch &&
                eventTypeMatch &&
                statusMatch
            );
        });

        const falseReportsData = combinedData.filter((item) => {
            const yearMatch =
                !filters.value.year ||
                new Date(item.date).getFullYear() ===
                    parseInt(filters.value.year);
            const municipalityMatch =
                !filters.value.municipality ||
                item.municipality_id === parseInt(filters.value.municipality);
            const categoryMatch =
                !filters.value.category ||
                item.category === filters.value.category;
            const eventTypeMatch =
                !filters.value.eventType ||
                (item.type && item.type.toLowerCase() === filters.value.eventType.toLowerCase());

            return (
                yearMatch &&
                municipalityMatch &&
                categoryMatch &&
                eventTypeMatch &&
                item.report_status === "false"
            );
        });

        console.log('Filtered Data:', filteredData.map(item => ({ category: item.category, report_status: item.report_status, type: item.type })));
        console.log('Combined Data:', combinedData.map(item => ({ category: item.category, report_status: item.report_status, type: item.type })));

        const verifiedSightings = processedSightings.filter(
            (item) => item.report_status === "verified"
        );

        const resolvedStrandedIncidents = processedStrandedIncidents.filter(
            (item) => item.report_status === "resolved"
        );

        const filteredVerifiedSightings = verifiedSightings.filter((item) => {
            const yearMatch =
                !filters.value.year ||
                new Date(item.date).getFullYear() ===
                    parseInt(filters.value.year);
            const municipalityMatch =
                !filters.value.municipality ||
                item.municipality_id === parseInt(filters.value.municipality);
            const categoryMatch =
                !filters.value.category ||
                item.sighted_species.some(s => s.species.category === filters.value.category);
            const eventTypeMatch =
                !filters.value.eventType ||
                (item.type && item.type.toLowerCase() === filters.value.eventType.toLowerCase());

            return (
                yearMatch &&
                municipalityMatch &&
                categoryMatch &&
                eventTypeMatch
            );
        });

        const filteredResolvedStrandedIncidents = resolvedStrandedIncidents.filter((item) => {
            const yearMatch =
                !filters.value.year ||
                new Date(item.date).getFullYear() ===
                    parseInt(filters.value.year);
            const municipalityMatch =
                !filters.value.municipality ||
                item.municipality_id === parseInt(filters.value.municipality);
            const categoryMatch =
                !filters.value.category ||
                item.stranded_species.some(s => s.species.category === filters.value.category);
            const eventTypeMatch =
                !filters.value.eventType ||
                (item.type && item.type.toLowerCase() === filters.value.eventType.toLowerCase());

            return (
                yearMatch &&
                municipalityMatch &&
                categoryMatch &&
                eventTypeMatch
            );
        });

        const verifiedAndResolvedData = [...filteredVerifiedSightings, ...filteredResolvedStrandedIncidents];

        updateSummaryData(
            verifiedAndResolvedData,
            falseReportsData,
            filteredData,
        );
    }
};

const updateSummaryData = (
    verifiedAndResolvedData,
    falseReportsData,
    filteredData
) => {
    // Process and update summaryData
    const uniqueEvents = new Set(filteredData.map(item => `${item.type}-${item.type === 'sighting' ? item.sighting_id : item.stranded_incident_id}`));
    console.log('Unique Events:', Array.from(uniqueEvents));
    summaryData.value.totalEvents = uniqueEvents.size;
    summaryData.value.totalSpecies = filteredData.length;
    summaryData.value.topCommonSpecies = getTopCommonSpecies(filteredData);
    summaryData.value.falseReports = falseReportsData.length;

    // Further processing for charts and distributions
    summaryData.value.yearlyTrends = getYearlyTrends(filteredData);
    summaryData.value.categoryDistribution =
        getCategoryDistribution(filteredData);
    summaryData.value.municipalityDistribution =
        getMunicipalityDistribution(filteredData);
    summaryData.value.conditionFrequency = getConditionFrequency(filteredData);

    renderCharts();
};

const getYearlyTrends = (data) => {
    const trends = data.reduce((acc, item) => {
        const year = new Date(item.date).getFullYear();
        acc[year] = (acc[year] || 0) + 1;
        return acc;
    }, {});

    return Object.entries(trends).map(([year, count]) => ({ year, count }));
};

const getCategoryDistribution = (data) => {
    const distribution = data.reduce((acc, item) => {
        acc[item.category] = (acc[item.category] || 0) + 1;
        return acc;
    }, {});

    return Object.entries(distribution).map(([category, count]) => ({ category, count }));
};

const getMunicipalityDistribution = (data) => {
    const distribution = data.reduce((acc, item) => {
        acc[item.municipality_id] = (acc[item.municipality_id] || 0) + 1;
        return acc;
    }, {});

    return Object.entries(distribution).map(([municipality_id, count]) => {
        const municipality = municipalities.value.find(m => m.id === parseInt(municipality_id));
        return { municipality: municipality ? municipality.name : 'Unknown', count };
    });
};

const getConditionFrequency = (data) => {
    const frequency = data.reduce((acc, item) => {
        if (item.type === 'stranded') {
            acc[item.status] = (acc[item.status] || 0) + 1;
        }
        return acc;
    }, {});

    return Object.entries(frequency).map(([status, count]) => ({ status, count }));
};

const getTopCommonSpecies = (data) => {
    const speciesCount = data.reduce((acc, item) => {
        acc[item.species_name] = (acc[item.species_name] || 0) + 1;
        return acc;
    }, {});

    return Object.entries(speciesCount)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5)
        .map(([name, count]) => ({ name, count }));
};

const renderCharts = () => {
    if (yearlyTrendsChart) yearlyTrendsChart.destroy();
    if (categoryTrendsChart) categoryTrendsChart.destroy();
    if (municipalityDistributionChart) municipalityDistributionChart.destroy();
    if (conditionFrequencyChart) conditionFrequencyChart.destroy();

    yearlyTrendsChart = new Chart(document.getElementById('yearlyTrendsChart'), {
        type: 'line',
        data: {
            labels: summaryData.value.yearlyTrends.map(item => item.year),
            datasets: [{
                label: 'Yearly Trends',
                data: summaryData.value.yearlyTrends.map(item => item.count),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true
            }]
        }
    });

    categoryTrendsChart = new Chart(document.getElementById('categoryTrendsChart'), {
        type: 'bar',
        data: {
            labels: summaryData.value.categoryDistribution.map(item => item.category),
            datasets: [{
                label: 'Category Trends',
                data: summaryData.value.categoryDistribution.map(item => item.count),
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        }
    });

    municipalityDistributionChart = new Chart(document.getElementById('municipalityDistributionChart'), {
        type: 'pie',
        data: {
            labels: summaryData.value.municipalityDistribution.map(item => item.municipality),
            datasets: [{
                label: 'Municipality Distribution',
                data: summaryData.value.municipalityDistribution.map(item => item.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    });

    conditionFrequencyChart = new Chart(document.getElementById('conditionFrequencyChart'), {
        type: 'bar',
        data: {
            labels: summaryData.value.conditionFrequency.map(item => item.status),
            datasets: [{
                label: 'Condition Frequency',
                data: summaryData.value.conditionFrequency.map(item => item.count),
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        }
    });
};

onMounted(async () => {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    // Subscribe to Supabase Realtime
    const channel = supabase.channel('public:incidents')
        .on('postgres_changes', { event: '*', schema: 'public', table: 'incidents' }, payload => {
            console.log('Change received!', payload);
            fetchData();
        })
        .subscribe();

    // Move onBeforeUnmount here
    onBeforeUnmount(() => {
        supabase.removeChannel(channel);
    });

    fetchData();
});


const applyFilters = () => {
    fetchData();
};

const resetFilters = () => {
    filters.value = {
        year: '',
        municipality: '',
        category: '',
        eventType: ''
    };
    fetchData();
};

const downloadPDF = () => {
    // Implement PDF download logic
    alert('You have successfully downloaded the file');
};

const exportData = () => {
    // Implement export logic
};

</script>

<template>
    <Sidebar>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Summary Report
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

                <label for="municipality" class="font-medium">Municipality:</label>
                <select v-model="filters.municipality" id="municipality" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                </select>

                <label for="category" class="font-medium">Category:</label>
                <select v-model="filters.category" id="category" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="marine_mammals">Marine Mammals</option>
                    <option value="marine_turtles">Marine Turtles</option>
                    <option value="sharks_rays">Shark and Rays</option>
                </select>

                <label for="eventType" class="font-medium">Event Type:</label>
                <select v-model="filters.eventType" id="eventType" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="Sighting">Sighting</option>
                    <option value="Stranded">Stranded</option>
                </select>

                <button @click="applyFilters" class="bg-blue-500 text-white px-4 py-2 rounded">Apply</button>
                <button @click="resetFilters" class="bg-gray-300 px-4 py-2 rounded">Reset</button>
                <button @click="downloadPDF" class="bg-green-500 text-white px-4 py-2 rounded">Download</button>
                <button @click="exportData" class="bg-yellow-500 text-white px-4 py-2 rounded">Export</button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-semibold">Total Reports</h3>
                    <p class="text-2xl">{{ summaryData.totalEvents }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-semibold">Total No. of Species Involved</h3>
                    <p class="text-2xl">{{ summaryData.totalSpecies }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-semibold">Total False Reports</h3>
                    <p class="text-2xl">{{ summaryData.falseReports }}</p>
                </div>
            </div>

            <!-- Top 5 Common Species -->
            <div class="bg-white p-4 rounded shadow mb-8">
                <h3 class="text-lg font-semibold">Top 5 Common Species</h3>
                <ul>
                    <li v-for="species in summaryData.topCommonSpecies" :key="species.name">
                        {{ species.name }}: {{ species.count }}
                    </li>
                </ul>
            </div>

            <!-- Summary Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-lg font-semibold">Yearly Trends</h3>
                    <canvas id="yearlyTrendsChart"></canvas>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Category Trends</h3>
                    <canvas id="categoryTrendsChart"></canvas>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Municipality Distribution</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="municipalityDistributionChart"></canvas>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Condition Frequency</h3>
                    <canvas id="conditionFrequencyChart"></canvas>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style>
.filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
</style>
