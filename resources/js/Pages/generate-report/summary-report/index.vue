<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head } from '@inertiajs/vue3';
import { ArcElement, BarController, BarElement, CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PieController, PointElement, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
                species_id: sighted_species.species?.id ?? null,
                species_name: sighted_species.species?.name ?? 'Unknown',
                category: sighted_species.species?.category ?? 'Unknown',
                municipality_id: sighting.municipality_id,
                report_status: sighting.report_status
            }));
        });

        const processedStrandedIncidents = strandedIncidents.flatMap(incident => {
            return incident.stranded_species.map(stranded_species => {
                const status = stranded_species.condition_code == 1 ? 'Alive' :
                    (stranded_species.condition_code >= 2 && stranded_species.condition_code <= 5 ? 'Dead' : 'Unknown');
                return {
                    latitude: stranded_species.latitude,
                    longitude: stranded_species.longitude,
                    date: incident.date,
                    type: 'stranded',
                    stranded_incident_id: incident.id,
                    status: stranded_species.condition_code,
                    species_id: stranded_species.species?.id ?? null,
                    species_name: stranded_species.species?.name ?? 'Unknown',
                    category: stranded_species.species?.category ?? 'Unknown',
                    municipality_id: incident.municipality_id,
                    report_status: incident.report_status
                };
            });
        });

        const combinedData = [...processedSightings, ...processedStrandedIncidents];

        const filtered = combinedData.filter((item) => {
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

        // Store filtered data for export
        filteredData.value = filtered;

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

        console.log('Filtered Data:', filtered.map(item => ({ category: item.category, report_status: item.report_status, type: item.type })));
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
                item.category === filters.value.category;
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
                item.category === filters.value.category;
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
            filtered,
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

const renderCharts = () => {
    if (yearlyTrendsChart) {
        yearlyTrendsChart.destroy();
        yearlyTrendsChart = null;
    }
    if (categoryTrendsChart) {
        categoryTrendsChart.destroy();
        categoryTrendsChart = null;
    }
    if (municipalityDistributionChart) {
        municipalityDistributionChart.destroy();
        municipalityDistributionChart = null;
    }
    if (conditionFrequencyChart) {
        conditionFrequencyChart.destroy();
        conditionFrequencyChart = null;
    }

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
        },
        options: {
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
            scales: {
                x: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                }
            }
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
        },
        options: {
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
            scales: {
                x: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                }
            }
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
            maintainAspectRatio: false,
            plugins: {
                datalabels: {
                    display: true,
                    color: 'black',
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    formatter: (value) => value
                }
            }
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
        },
        options: {
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
            scales: {
                x: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    ticks: {
                        color: 'black',
                        font: {
                            size: 12
                        }
                    }
                }
            }
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

        <div class="container mx-auto px-4 py-8" id="dashboard-content">
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
</style>

