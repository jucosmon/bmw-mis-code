<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head } from '@inertiajs/vue3';
import { ArcElement, BarController, BarElement, CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PieController, PointElement, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as XLSX from 'xlsx';


// Register Chart.js components and plugins
Chart.register(LineController, BarController, PieController, CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler, ChartDataLabels);

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

// Add refs for modals
const showDownloadModal = ref(false);
const showExportModal = ref(false);
const isDownloading = ref(false);
const isExporting = ref(false);
const filteredData = ref([]);

// Add new refs for chart loading states
const chartsLoading = ref(true);

let yearlyTrendsChart, categoryTrendsChart, municipalityDistributionChart, conditionFrequencyChart;

const fetchData = async () => {
    try {
        // Fetch verified/resolved reports
        const [sightingsRes, strandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select(`
                    id,
                    date,
                    municipality_id,
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
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select(`
                    id,
                    date,
                    municipality_id,
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
        ]);

        if (sightingsRes.error) throw sightingsRes.error;
        if (strandingsRes.error) throw strandingsRes.error;

        // Log fetched data
        console.log('Sightings Data:', sightingsRes.data);
        console.log('Strandings Data:', strandingsRes.data);

        // Get false reports
        const [falseSightingsRes, falseStrandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select('*')
                .eq('report_status', 'false_report')
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select('*')
                .eq('report_status', 'false_report')
                .eq('is_active', true)
        ]);

        if (falseSightingsRes.error) throw falseSightingsRes.error;
        if (falseStrandingsRes.error) throw falseStrandingsRes.error;

        // Log false reports data
        console.log('False Sightings Data:', falseSightingsRes.data);
        console.log('False Strandings Data:', falseStrandingsRes.data);

        const verifiedAndResolvedData = [
            ...processSightings(sightingsRes.data || []),
            ...processStrandings(strandingsRes.data || [])
        ];

        const falseReportsData = [
            ...(falseSightingsRes.data || []),
            ...(falseStrandingsRes.data || [])
        ];

        // Apply filters to verified/resolved data
        const filtered = applyFilters(verifiedAndResolvedData);

        // Log filtered data
        console.log('Filtered Data After Applying Filters:', filtered);

        // Update filteredData ref
        filteredData.value = filtered;

        // Update the summary data with all the information
        updateSummaryData(verifiedAndResolvedData, falseReportsData, filtered);

    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

// Add these helper functions
const processSightings = (sightings) => {
    return sightings.flatMap(sighting =>
        sighting.sighted_species.map(ss => ({
            date: sighting.date,
            type: 'sighting',
            sighting_id: sighting.id,
            species_id: ss.species?.id,
            species_name: ss.species?.name ?? 'Unknown',
            category: ss.species?.category ?? 'Unknown',
            municipality_id: sighting.municipality_id,
            report_status: sighting.report_status,
            latitude: sighting.latitude,
            longitude: sighting.longitude
        }))
    );
};

const processStrandings = (incidents) => {
    return incidents.flatMap(incident =>
        incident.stranded_species.map(ss => ({
            date: incident.date,
            type: 'stranded',
            stranded_incident_id: incident.id,
            species_id: ss.species?.id,
            species_name: ss.species?.name ?? 'Unknown',
            category: ss.species?.category ?? 'Unknown',
            municipality_id: incident.municipality_id,
            report_status: incident.report_status,
            status: ss.condition_code,
            latitude: ss.latitude,
            longitude: ss.longitude
        }))
    );
};

const applyFilters = (data) => {
    return data.filter(item => {
        const yearMatch = !filters.value.year ||
            new Date(item.date).getFullYear() === parseInt(filters.value.year);
        const municipalityMatch = !filters.value.municipality ||
            item.municipality_id === parseInt(filters.value.municipality);
        const categoryMatch = !filters.value.category ||
            item.category === filters.value.category;
        const eventTypeMatch = !filters.value.eventType ||
            item.type.toLowerCase() === filters.value.eventType.toLowerCase();
        const statusMatch = (item.type === 'sighting' && item.report_status === 'verified') ||
            (item.type === 'stranded' && item.report_status === 'resolved');

        return yearMatch && municipalityMatch && categoryMatch && eventTypeMatch && statusMatch;
    });
};

const updateSummaryData = async (
    verifiedAndResolvedData,
    falseReportsData,
    filteredData
) => {
    try {
        // Process and update summaryData
        const uniqueEvents = new Set(filteredData.map(item => `${item.type}-${item.type === 'sighting' ? item.sighting_id : item.stranded_incident_id}`));
        summaryData.value.totalEvents = uniqueEvents.size;
        summaryData.value.totalSpecies = filteredData.length;
        summaryData.value.topCommonSpecies = getTopCommonSpecies(filteredData);
        summaryData.value.falseReports = falseReportsData.length;

        // Further processing for charts and distributions
        summaryData.value.yearlyTrends = getYearlyTrends(filteredData);
        summaryData.value.categoryDistribution = getCategoryDistribution(filteredData);
        summaryData.value.municipalityDistribution = getMunicipalityDistribution(filteredData);
        summaryData.value.conditionFrequency = getConditionFrequency(filteredData);

        // Wait for next tick before rendering charts
        await nextTick();
        await renderCharts();
    } catch (error) {
        console.error('Error updating summary data:', error);
    }
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

    const statusDescriptions = {
        1: 'Alive',
        2: 'Freshly Dead',
        3: 'Decomposed, but organs are intact ',
        4: 'Advanced Decomposition',
        5: 'Skeletal/Cartiginous Remains',
        6: 'Destroyed'
    };

    return Object.entries(frequency).map(([status, count]) => ({ status: statusDescriptions[status] || 'Unknown', count }));
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

const chartConfig = {
    'Yearly Trends': {
        type: 'line',
        id: 'yearlyTrendsChart',
        getData: () => ({
            labels: summaryData.value.yearlyTrends.map(item => item.year),
            data: summaryData.value.yearlyTrends.map(item => item.count)
        })
    },
    'Category Trends': {
        type: 'bar',
        id: 'categoryTrendsChart',
        getData: () => ({
            labels: summaryData.value.categoryDistribution.map(item => item.category),
            data: summaryData.value.categoryDistribution.map(item => item.count)
        })
    },
    'Municipality Distribution': {
        type: 'pie',
        id: 'municipalityDistributionChart',
        getData: () => ({
            labels: summaryData.value.municipalityDistribution.map(item => item.municipality),
            data: summaryData.value.municipalityDistribution.map(item => item.count)
        })
    },
    'Condition Frequency': {
        type: 'bar',
        id: 'conditionFrequencyChart',
        getData: () => ({
            labels: summaryData.value.conditionFrequency.map(item => item.status),
            data: summaryData.value.conditionFrequency.map(item => item.count)
        })
    }
};

// Add these new refs
const maxRetries = ref(3);
const retryCount = ref(0);

// Update the renderCharts function
const renderCharts = async () => {
    try {
        chartsLoading.value = true;

        // Wait longer for initial render
        await new Promise(resolve => setTimeout(resolve, 500));
        await nextTick();

        // Destroy existing charts with null check
        [yearlyTrendsChart, categoryTrendsChart, municipalityDistributionChart, conditionFrequencyChart].forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });

        // Reset chart instances
        yearlyTrendsChart = null;
        categoryTrendsChart = null;
        municipalityDistributionChart = null;
        conditionFrequencyChart = null;

        // Create charts only if we have data and elements exist
        for (const [name, chart] of Object.entries(chartConfig)) {
            const chartData = chart.getData();
            if (chartData.data.length > 0) {
                const element = document.getElementById(chart.id);
                if (!element) {
                    console.warn(`Chart element ${chart.id} not found`);
                    continue; // Skip this chart and try the next one
                }

                // Create chart based on type
                const chartInstance = new Chart(element, {
                    type: chart.type,
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: name,
                            data: chartData.data,
                            borderColor: chart.type === 'line' ? 'rgba(75, 192, 192, 1)' : undefined,
                            backgroundColor: chart.type === 'line'
                                ? 'rgba(75, 192, 192, 0.2)'
                                : chart.type === 'pie'
                                    ? [
                                        'rgba(255, 99, 132, 0.2)',
                                        'rgba(54, 162, 235, 0.2)',
                                        'rgba(255, 206, 86, 0.2)',
                                        'rgba(75, 192, 192, 0.2)',
                                        'rgba(153, 102, 255, 0.2)',
                                        'rgba(255, 159, 64, 0.2)'
                                    ]
                                    : 'rgba(153, 102, 255, 0.2)',
                            borderWidth: 1,
                            fill: chart.type === 'line'
                        }]
                    },
                    options: {
                        maintainAspectRatio: chart.type !== 'pie',
                        plugins: {
                            datalabels: {
                                display: true,
                                color: 'black',
                                align: 'top',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                formatter: (value) => value
                            }
                        },
                        scales: chart.type !== 'pie' ? {
                            x: {
                                ticks: {
                                    color: 'black',
                                    font: { size: 12 }
                                }
                            },
                            y: {
                                ticks: {
                                    color: 'black',
                                    font: { size: 12 }
                                }
                            }
                        } : undefined
                    }
                });

                // Store chart instance
                switch (chart.id) {
                    case 'yearlyTrendsChart':
                        yearlyTrendsChart = chartInstance;
                        break;
                    case 'categoryTrendsChart':
                        categoryTrendsChart = chartInstance;
                        break;
                    case 'municipalityDistributionChart':
                        municipalityDistributionChart = chartInstance;
                        break;
                    case 'conditionFrequencyChart':
                        conditionFrequencyChart = chartInstance;
                        break;
                }
            }
        }
    } catch (error) {
        console.error('Error rendering charts:', error);
    } finally {
        chartsLoading.value = false;
    }
};

// Add these refs for channels
const sightingsChannel = ref(null);
const strandingsChannel = ref(null);

const isLoading = ref(true);

// Add municipalities prop
const props = defineProps({
    municipalities: {
        type: Array,
        required: true
    }
});

// Update municipalities ref to use props
municipalities.value = props.municipalities;

onMounted(() => {
    // Register cleanup first, before any async operations
    onBeforeUnmount(() => {
        if (sightingsChannel.value) {
            supabase.removeChannel(sightingsChannel.value);
        }
        if (strandingsChannel.value) {
            supabase.removeChannel(strandingsChannel.value);
        }
    });

    // Setup real-time subscriptions
    sightingsChannel.value = supabase.channel('sightings-changes')
        .on(
            'postgres_changes',
            {
                event: '*',
                schema: 'public',
                table: 'sightings'
            },
            () => fetchData()
        )
        .subscribe();

    strandingsChannel.value = supabase.channel('strandings-changes')
        .on(
            'postgres_changes',
            {
                event: '*',
                schema: 'public',
                table: 'stranded_incidents'
            },
            () => fetchData()
        )
        .subscribe();

    // Initialize data
    initializeData();
});

const initializeData = async () => {
    try {
        isLoading.value = true;
        // Initial data fetch
        await fetchData();
    } catch (error) {
        console.error('Error in initialization:', error);
    } finally {
        isLoading.value = false;
    }
};

watch(filters, () => {
    fetchData();
}, { deep: true });

const resetFilters = () => {
    filters.value = {
        year: '',
        municipality: '',
        category: '',
        eventType: ''
    };
    fetchData();
};

// Show download confirmation modal
const showDownloadConfirmation = () => {
    showDownloadModal.value = true;
};

// Show export confirmation modal
const showExportConfirmation = () => {
    showExportModal.value = true;
};

// Download PDF implementation
const downloadPDF = async () => {
    try {
        isDownloading.value = true;
        showDownloadModal.value = false;

        // Get the dashboard content
        const dashboardElement = document.getElementById('dashboard-content');

        const filtersElement = dashboardElement.querySelector('.filters');
        filtersElement.style.display = 'none';

        // Create a canvas from the dashboard element
        const canvas = await html2canvas(dashboardElement, {
            scale: 2, // Higher scale for better quality
            useCORS: true, // Enable CORS for images
            logging: false,
            backgroundColor: '#ffffff'
        });

        filtersElement.style.display = ''; // Restore the display

        // Create PDF
        const pdf = new jsPDF('p', 'mm', 'a4');

        // Get the dimensions
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        // Add title
        const title = `Marine Life Analytics Report`;
        const subtitle = `Generated on ${new Date().toLocaleDateString()}`;

        pdf.setFontSize(18);
        pdf.text(title, 105, 20, { align: 'center' });
        pdf.setFontSize(12);
        pdf.text(subtitle, 105, 30, { align: 'center' });

        // Add filter information
        let filterText = 'Filters: ';
        filterText += filters.value.year ? `Year: ${filters.value.year}, ` : 'All Years, ';
        filterText += filters.value.municipality ? `Municipality: ${municipalities.value.find(m => m.id === parseInt(filters.value.municipality))?.name || 'Unknown'}, ` : 'All Municipalities, ';
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
        pdf.save(`marine-life-analytics-${new Date().toISOString().slice(0, 10)}.pdf`);

        isDownloading.value = false;
    } catch (error) {
        console.error('Error generating PDF:', error);
        isDownloading.value = false;
        alert('Error generating PDF. Please try again.');
    }
};

// Export to Excel implementation
const exportToExcel = () => {
    try {
        isExporting.value = true;
        showExportModal.value = false;

        // Log the filtered data to check its content
        console.log('Filtered Data:', filteredData.value);

        // Prepare data for export
        const dataToExport = filteredData.value.map(item => {
            // Find municipality name
            const municipality = municipalities.value.find(m => m.id === item.municipality_id);

            return {
                'Date': new Date(item.date).toLocaleDateString(),
                'Type': item.type.charAt(0).toUpperCase() + item.type.slice(1),
                'Species': item.species_name,
                'Category': item.category.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '),
                'Municipality': municipality ? municipality.name : 'Unknown',
                'Latitude': item.latitude,
                'Longitude': item.longitude,
                'Status': item.type === 'stranded' ?
                    ['Alive', 'Freshly Dead', 'Decomposed', 'Advanced Decomposition', 'Skeletal Remains', 'Destroyed'][item.status - 1] || 'Unknown'
                    : 'N/A',
                'Report Status': item.report_status.charAt(0).toUpperCase() + item.report_status.slice(1)
            };
        });

        // Log the data to export to check its content
        console.log('Data to Export:', dataToExport);

        // Create worksheet
        const worksheet = XLSX.utils.json_to_sheet(dataToExport);

        // Create workbook
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Marine Life Data');

        // Generate Excel file
        XLSX.writeFile(workbook, `marine-life-data-${new Date().toISOString().slice(0, 10)}.xlsx`);

        isExporting.value = false;
    } catch (error) {
        console.error('Error exporting data:', error);
        isExporting.value = false;
        alert('Error exporting data. Please try again.');
    }
};

// Add this function in the script section
const hasDataForChart = (chartName) => {
    switch (chartName) {
        case 'Yearly Trends':
            return summaryData.value.yearlyTrends.length > 0;
        case 'Category Trends':
            return summaryData.value.categoryDistribution.length > 0;
        case 'Municipality Distribution':
            return summaryData.value.municipalityDistribution.length > 0;
        case 'Condition Frequency':
            return summaryData.value.conditionFrequency.length > 0;
        default:
            return false;
    }
};
</script>

<template>
    <Head title="Summary Report" />
    <Sidebar>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Summary Report
                </h2>
            </div>
        </template>

        <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="loading-spinner-large"></div>
                <p class="mt-4 text-gray-600">Loading data...</p>
            </div>
        </div>

        <div v-else class="container mx-auto px-4 py-8" id="dashboard-content">
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

                <button @click="resetFilters" class="bg-gray-300 px-4 py-2 rounded">Reset</button>
                <button @click="showDownloadConfirmation" class="bg-green-500 text-white px-4 py-2 rounded">Download</button>
                <button @click="showExportConfirmation" class="bg-yellow-500 text-white px-4 py-2 rounded">Export</button>
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
                <div v-for="(chart, name) in chartConfig"
                     :key="name"
                     class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-semibold">{{ name }}</h3>
                    <div class="relative min-h-[300px]">
                        <div v-if="chartsLoading"
                             class="absolute inset-0 flex items-center justify-center">
                            <div class="loading-spinner-large"></div>
                        </div>
                        <div v-else-if="!hasDataForChart(name)"
                             class="absolute inset-0 flex items-center justify-center">
                            <div class="text-gray-500 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p>No data available</p>
                                <p class="text-sm">Try adjusting your filters</p>
                            </div>
                        </div>
                        <canvas v-show="!chartsLoading && hasDataForChart(name)"
                               :id="chart.id"
                               class="w-full h-full">
                        </canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Download Confirmation Modal -->
        <div v-if="showDownloadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Download Report</h3>
                <p class="mb-6">Are you sure you want to download the current report as a PDF?</p>
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

        <!-- Export Confirmation Modal -->
        <div v-if="showExportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Export Data</h3>
                <p class="mb-6">Are you sure you want to export the filtered data to Excel?</p>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showExportModal = false"
                        class="px-4 py-2 bg-gray-300 rounded">
                        Cancel
                    </button>
                    <button
                        @click="exportToExcel"
                        class="px-4 py-2 bg-yellow-500 text-white rounded"
                        :disabled="isExporting">
                        {{ isExporting ? 'Exporting...' : 'Export' }}
                    </button>
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

canvas {
    max-width: 100%;
    height: auto;
}

/* Loading spinner styles */
.loading-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Add larger loading spinner style */
.loading-spinner-large {
    display: inline-block;
    width: 3rem;
    height: 3rem;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top-color: #4f46e5;
    animation: spin 1s ease-in-out infinite;
}

</style>

