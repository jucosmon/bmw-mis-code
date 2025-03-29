<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head, usePage } from '@inertiajs/vue3';
import { ArcElement, BarController, BarElement, CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PieController, PointElement, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import html2pdf from 'html2pdf.js';
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

const page = usePage();

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

        // Get false reports - First check all records to see what statuses exist
        const [allSightingsRes, allStrandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select('id, report_status, is_active')
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select('id, report_status, is_active')
                .eq('is_active', true)
        ]);

        // Log all records to see what statuses exist
        console.log('All Sightings Statuses:', allSightingsRes.data?.map(s => s.report_status));
        console.log('All Strandings Statuses:', allStrandingsRes.data?.map(s => s.report_status));

        // Now try to get false reports with different possible status values
        const [falseSightingsRes, falseStrandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select('*')
                .in('report_status', ['false_report', 'false', 'False Report', 'FALSE_REPORT']),
            supabase
                .from('stranded_incidents')
                .select('*')
                .in('report_status', ['false_report', 'false', 'False Report', 'FALSE_REPORT'])
        ]);

        if (falseSightingsRes.error) throw falseSightingsRes.error;
        if (falseStrandingsRes.error) throw falseStrandingsRes.error;

        // Add detailed logging for false reports
        console.log('False Sightings Response:', falseSightingsRes);
        console.log('False Strandings Response:', falseStrandingsRes);
        console.log('False Sightings Data:', falseSightingsRes.data);
        console.log('False Strandings Data:', falseStrandingsRes.data);

        const falseReportsData = [
            ...(falseSightingsRes.data || []),
            ...(falseStrandingsRes.data || [])
        ];

        // Log the combined false reports data
        console.log('Combined False Reports Data:', falseReportsData);
        console.log('False Reports Count:', falseReportsData.length);

        const verifiedAndResolvedData = [
            ...processSightings(sightingsRes.data || []),
            ...processStrandings(strandingsRes.data || [])
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
        await setupCharts();
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
        await new Promise(resolve => setTimeout(resolve, 800));
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

        // Oceanic theme colors
        const oceanicColors = {
            backgroundColor: [
                'rgba(77, 171, 247, 0.6)',
                'rgba(109, 213, 167, 0.6)',
                'rgba(255, 217, 61, 0.6)',
                'rgba(255, 107, 107, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)',
                'rgba(0, 204, 255, 0.6)',
                'rgba(0, 255, 136, 0.6)'
            ],
            borderColor: [
                'rgba(77, 171, 247, 1)',
                'rgba(109, 213, 167, 1)',
                'rgba(255, 217, 61, 1)',
                'rgba(255, 107, 107, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(0, 204, 255, 1)',
                'rgba(0, 255, 136, 1)'
            ],
            textColor: 'rgba(255, 255, 255, 0.9)'
        };

        // Create charts only if we have data and elements exist
        for (const [name, chart] of Object.entries(chartConfig)) {
            const chartData = chart.getData();
            if (chartData.data.length > 0) {
                const element = document.getElementById(chart.id);
                if (!element) {
                    console.warn(`Chart element ${chart.id} not found`);
                    continue; // Skip this chart and try the next one
                }

                // Get the chart context
                const ctx = element.getContext('2d');
                if (!ctx) {
                    console.warn(`Could not get context for ${chart.id}`);
                    continue;
                }

                // Configure colors based on chart type
                let backgroundColor, borderColor;
                if (chart.type === 'pie') {
                    backgroundColor = oceanicColors.backgroundColor;
                    borderColor = Array(chartData.labels.length).fill('rgba(255, 255, 255, 0.1)');
                } else if (chart.type === 'line') {
                    backgroundColor = 'rgba(77, 171, 247, 0.3)';
                    borderColor = 'rgba(77, 171, 247, 1)';
                } else {
                    // For bar charts, create array of colors based on data length
                    backgroundColor = chartData.data.map((_, index) =>
                        oceanicColors.backgroundColor[index % oceanicColors.backgroundColor.length]
                    );
                    borderColor = chartData.data.map((_, index) =>
                        oceanicColors.borderColor[index % oceanicColors.borderColor.length]
                    );
                }

                // Configure datalabels based on chart type
                let datalabelsConfig = {
                    display: true,
                    color: 'white',
                    textStrokeColor: 'rgba(0, 0, 0, 0.5)',
                    textStrokeWidth: 2,
                    textShadowBlur: 5,
                    textShadowColor: 'rgba(0, 0, 0, 0.5)',
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: {
                        top: 4,
                        bottom: 4
                    }
                };

                // Customize datalabels for bar charts (Category Trends and Condition Frequency)
                if (chart.type === 'bar') {
                    datalabelsConfig = {
                        ...datalabelsConfig,
                        align: 'center',
                        anchor: 'center',
                        formatter: (value) => {
                            return value > 0 ? value : ''; // Only show label if value is greater than 0
                        }
                    };
                } else if (chart.type === 'pie') {
                    datalabelsConfig = {
                        ...datalabelsConfig,
                        align: 'center',
                        anchor: 'center'
                    };
                } else {
                    datalabelsConfig = {
                        ...datalabelsConfig,
                        align: 'top',
                        anchor: 'end'
                    };
                }

                // Create chart based on type
                const chartInstance = new Chart(ctx, {
                    type: chart.type,
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: name,
                            data: chartData.data,
                            backgroundColor: backgroundColor,
                            borderColor: borderColor,
                            borderWidth: 1,
                            fill: chart.type === 'line',
                            pointBackgroundColor: chart.type === 'line' ? 'rgba(77, 171, 247, 1)' : undefined,
                            pointBorderColor: chart.type === 'line' ? '#fff' : undefined,
                            pointHoverBackgroundColor: chart.type === 'line' ? '#fff' : undefined,
                            pointHoverBorderColor: chart.type === 'line' ? 'rgba(77, 171, 247, 1)' : undefined,
                            pointRadius: chart.type === 'line' ? 4 : undefined,
                            tension: chart.type === 'line' ? 0.4 : undefined,
                            // Add minimum bar height for better label visibility
                            minBarLength: chart.type === 'bar' ? 20 : undefined
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: chart.type !== 'pie',
                        animation: {
                            duration: 1000
                        },
                        plugins: {
                            legend: {
                                display: chart.type === 'pie',
                                position: 'bottom',
                                labels: {
                                    color: oceanicColors.textColor,
                                    font: {
                                        size: 12
                                    },
                                    padding: 20
                                }
                            },
                            datalabels: datalabelsConfig,
                            tooltip: {
                                backgroundColor: 'rgba(0, 51, 102, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 255, 255, 0.2)',
                                borderWidth: 1,
                                padding: 10,
                                cornerRadius: 6,
                                displayColors: false,
                                caretSize: 8
                            }
                        },
                        scales: chart.type !== 'pie' ? {
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: oceanicColors.textColor,
                                    font: { size: 12 }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: oceanicColors.textColor,
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

        // Small delay before removing loading state
        setTimeout(() => {
            chartsLoading.value = false;
        }, 500);

    } catch (error) {
        console.error('Error rendering charts:', error);
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

const resetFilters = async () => {
    // Reset filter values
    filters.value = {
        year: '',
        municipality: '',
        category: '',
        eventType: ''
    };

    // Show loading state
    chartsLoading.value = true;

    // Fetch data with a small delay to avoid chart rendering issues
    setTimeout(async () => {
        await fetchData();
    }, 100);
};

// Add this function to handle chart canvas background
const setupChartCanvasBackground = () => {
    // Find all chart canvases and set their background color
    document.querySelectorAll('.chart-canvas').forEach(canvas => {
        canvas.style.backgroundColor = 'rgba(255, 255, 255, 0.04)';
        canvas.style.borderRadius = '8px';
        canvas.style.padding = '8px';
    });
};

// Call setupChartCanvasBackground after charts are rendered
const setupCharts = async () => {
    await renderCharts();
    setupChartCanvasBackground();
};

// Show download confirmation modal
const showDownloadConfirmation = () => {
    showDownloadModal.value = true;
};

// Show export confirmation modal
const showExportConfirmation = () => {
    showExportModal.value = true;
};

// Add these refs for notifications
const showNotification = ref(false);
const notificationMessage = ref('');
const notificationType = ref('success'); // 'success', 'error', 'info'

// Helper function to show notifications
const notify = (message, type = 'success', duration = 3000) => {
    notificationMessage.value = message;
    notificationType.value = type;
    showNotification.value = true;

    // Auto hide after duration
    setTimeout(() => {
        showNotification.value = false;
    }, duration);
};

// Update downloadPDF to use notifications
const downloadPDF = async () => {
    try {
        isDownloading.value = true;
        showDownloadModal.value = false;

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
        title.textContent = 'Marine Wildlife Summary Report';
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

        // Helper function to add sections
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

        // ===== FILTER INFORMATION =====
        const filterText = document.createElement('p');
        filterText.style.fontSize = '12px';
        filterText.style.lineHeight = '1.4';

        // Build simple filter string
        filterText.innerHTML = '<strong>Filters applied:</strong> ';

        let filterParts = [];

        if (filters.value.year) {
            filterParts.push(` ${filters.value.year}`);
        } else {
            filterParts.push(" All Years");
        }

        if (filters.value.municipality) {
            const municipalityName = municipalities.value.find(m => m.id === parseInt(filters.value.municipality))?.name || 'Unknown';
            filterParts.push(`${municipalityName}`);
        } else {
            filterParts.push("All Municipalities");
        }

        if (filters.value.category) {
            filterParts.push(`${filters.value.category}`);
        } else {
            filterParts.push("All Categories");
        }

        if (filters.value.eventType) {
            filterParts.push(`${filters.value.eventType}`);
        } else {
            filterParts.push("All Types");
        }

        filterText.innerHTML += filterParts.join(', ');

        pdfContainer.appendChild(addSection('Report Filters', filterText));

        // ===== SUMMARY STATISTICS =====
        const statsText = document.createElement('p');
        statsText.style.fontSize = '12px';
        statsText.style.lineHeight = '1.4';
        statsText.innerHTML =
            `<strong>Total Reports:</strong> ${summaryData.value.totalEvents}<br>` +
            `<strong>Species Involved:</strong> ${summaryData.value.totalSpecies}<br>` +
            `<strong>False Reports:</strong> ${summaryData.value.falseReports}`;

        pdfContainer.appendChild(addSection('Summary Statistics', statsText));

        // ===== TOP SPECIES =====
        if (summaryData.value.topCommonSpecies && summaryData.value.topCommonSpecies.length > 0) {
            const topSpeciesText = document.createElement('p');
            topSpeciesText.style.fontSize = '12px';
            topSpeciesText.style.lineHeight = '1.4';
            topSpeciesText.innerHTML = 'Most commonly reported species:<br>';

            summaryData.value.topCommonSpecies.forEach((species, index) => {
                topSpeciesText.innerHTML += `${index + 1}. <strong>${species.name}</strong>: ${species.count} reports<br>`;
            });

            pdfContainer.appendChild(addSection('Top Common Species', topSpeciesText));
        }

        // ===== YEARLY TRENDS AS TEXT =====
        if (summaryData.value.yearlyTrends && summaryData.value.yearlyTrends.length > 0) {
            const yearlyTrendsText = document.createElement('p');
            yearlyTrendsText.style.fontSize = '12px';
            yearlyTrendsText.style.lineHeight = '1.4';
            yearlyTrendsText.innerHTML = '<strong>Report count by year:</strong><br>';

            summaryData.value.yearlyTrends.forEach(item => {
                yearlyTrendsText.innerHTML += `${item.year}: ${item.count} reports<br>`;
            });

            pdfContainer.appendChild(addSection('Yearly Trends', yearlyTrendsText));
        }

        // ===== CATEGORY DISTRIBUTION AS TEXT =====
        if (summaryData.value.categoryDistribution && summaryData.value.categoryDistribution.length > 0) {
            const categoryText = document.createElement('p');
            categoryText.style.fontSize = '12px';
            categoryText.style.lineHeight = '1.4';
            categoryText.innerHTML = '<strong>Report distribution by category:</strong><br>';

            summaryData.value.categoryDistribution.forEach(item => {
                const formattedCategory = item.category
                    .replace(/_/g, ' ')
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                categoryText.innerHTML += `${formattedCategory}: ${item.count} reports<br>`;
            });

            pdfContainer.appendChild(addSection('Category Distribution', categoryText));
        }

        // ===== MUNICIPALITY DISTRIBUTION AS TEXT =====
        if (summaryData.value.municipalityDistribution && summaryData.value.municipalityDistribution.length > 0) {
            const municipalityText = document.createElement('p');
            municipalityText.style.fontSize = '12px';
            municipalityText.style.lineHeight = '1.4';
            municipalityText.innerHTML = '<strong>Report distribution by municipality:</strong><br>';

            summaryData.value.municipalityDistribution.forEach(item => {
                // Check for all possible property names that might contain the municipality name
                const municipalityName = item.name || item.municipality_name || item.municipality || 'Unknown Municipality';
                municipalityText.innerHTML += `${municipalityName}: ${item.count} reports<br>`;
            });

            pdfContainer.appendChild(addSection('Municipality Distribution', municipalityText));
        }

        // ===== CONDITION FREQUENCY AS TEXT =====
        if (summaryData.value.conditionFrequency && summaryData.value.conditionFrequency.length > 0) {
            const conditionText = document.createElement('p');
            conditionText.style.fontSize = '12px';
            conditionText.style.lineHeight = '1.4';
            conditionText.innerHTML = '<strong>Report distribution by condition:</strong><br>';

            summaryData.value.conditionFrequency.forEach(item => {
                // Check for all possible property names that might contain the condition description
                const conditionName = item.condition || item.condition_name || item.description || 'Unknown Condition';
                conditionText.innerHTML += `${conditionName}: ${item.count} reports<br>`;
            });

            pdfContainer.appendChild(addSection('Condition Frequency', conditionText));
        }

        // ===== FOOTER =====
        const footer = document.createElement('div');
        footer.style.borderTop = '1px solid #e5e7eb';
        footer.style.paddingTop = '3mm';
        footer.style.marginTop = '5mm';
        footer.style.textAlign = 'center';
        footer.style.fontSize = '10px';
        footer.style.color = '#6b7280';
        footer.textContent = 'Marine Marine Wildlife Management Information System (BMW-MIS)';

        pdfContainer.appendChild(footer);

        // Add the container to document temporarily
        document.body.appendChild(pdfContainer);

        // PDF generation options
        const options = {
            filename: `Marine_Wildlife_Analytics_Report_${new Date().toISOString().slice(0, 10)}.pdf`,
            margin: [5, 5, 5, 5],
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Generate PDF
        await html2pdf().from(pdfContainer).set(options).save();

        // Clean up
        document.body.removeChild(pdfContainer);
        notify('PDF downloaded successfully', 'success', 3000);
    } catch (error) {
        console.error('PDF download error:', error);
        notify('Error generating PDF. Please try again.', 'error', 5000);
    } finally {
        isDownloading.value = false;
    }
};

// Update exportToExcel to use notifications
const exportToExcel = () => {
    try {
        isExporting.value = true;
        showExportModal.value = false;

        // Log the filtered data to check its content
        console.log('Exporting filtered data:', filteredData.value);

        if (!filteredData.value || filteredData.value.length === 0) {
            // Show alert if no data
            notify('No data available to export. Please adjust your filters or try again later.', 'error');
            isExporting.value = false;
            return;
        }

        // Prepare data for export
        const dataToExport = filteredData.value.map(item => {
            // Find municipality name
            const municipality = municipalities.value.find(m => m.id === item.municipality_id);

            // Format condition status
            let conditionStatus = 'N/A';
            if (item.type === 'stranded' && item.status) {
                const conditionCodes = {
                    1: 'Alive',
                    2: 'Freshly Dead',
                    3: 'Decomposed, but organs are intact',
                    4: 'Advanced Decomposition',
                    5: 'Skeletal/Cartiginous Remains',
                    6: 'Destroyed'
                };
                conditionStatus = conditionCodes[item.status] || `Code ${item.status}`;
            }

            // Format category name
            const categoryFormatted = item.category
                ? item.category.replace(/_/g, ' ')
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ')
                : 'Unknown';

            return {
                'Date': new Date(item.date).toLocaleDateString(),
                'Type': item.type.charAt(0).toUpperCase() + item.type.slice(1),
                'Species': item.species_name || 'Unknown',
                'Category': categoryFormatted,
                'Municipality': municipality ? municipality.name : 'Unknown',
                'Latitude': item.latitude,
                'Longitude': item.longitude,
                'Condition Status': conditionStatus,
                'Report Status': item.report_status.charAt(0).toUpperCase() + item.report_status.slice(1)
            };
        });

        // Log the data to export to check its content
        console.log('Prepared data for export:', dataToExport);

        // Create worksheet
        const worksheet = XLSX.utils.json_to_sheet(dataToExport);

        // Add some styling to worksheet
        worksheet['!cols'] = [
            { width: 15 }, // Date
            { width: 10 }, // Type
            { width: 25 }, // Species
            { width: 20 }, // Category
            { width: 20 }, // Municipality
            { width: 12 }, // Latitude
            { width: 12 }, // Longitude
            { width: 25 }, // Condition Status
            { width: 15 }  // Report Status
        ];

        // Create workbook
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Marine Life Data');

        // Generate Excel file
        const fileName = `marine-life-data-${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        console.log('Excel file exported successfully:', fileName);
        isExporting.value = false;
        notify(`Excel file exported successfully!`);
    } catch (error) {
        console.error('Error exporting data:', error);
        isExporting.value = false;
        notify('Error exporting data: ' + error.message, 'error');
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
        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Toast Notification -->
            <div v-if="showNotification" :class="['notification', notificationType]">
                <div class="flex items-center">
                    <i v-if="notificationType === 'success'" class="fas fa-check-circle mr-2"></i>
                    <i v-else-if="notificationType === 'error'" class="fas fa-exclamation-circle mr-2"></i>
                    <i v-else class="fas fa-info-circle mr-2"></i>
                    <span>{{ notificationMessage }}</span>
                    <button @click="showNotification = false" class="ml-2 text-white opacity-70 hover:opacity-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div v-if="isLoading" class="relative flex items-center justify-center min-h-screen">
                <div class="text-center">
                    <div class="loading-spinner-large"></div>
                    <p class="mt-4 text-white">Loading data...</p>
                </div>
            </div>

            <div v-else class="relative container mx-auto px-4 py-16" id="dashboard-content">
                <div class="mb-4 text-center">
                    <h3 class="profile-title-gradient mb-0">
                        Marine Wildlife Analytics
                    </h3>
                    <p class="text-white text-opacity-80 text-sm">
                        Comprehensive summary of marine wildlife data
                    </p>
                </div>

                <!-- Filters Section - Redesigned to be more compact -->
                <div class="glass-container p-3 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <div class="filter-group">
                            <label for="year" class="filter-label">Year:</label>
                            <select v-model="filters.year" id="year" class="filter-select">
                                <option value="">All</option>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="municipality" class="filter-label">Municipality:</label>
                            <select v-model="filters.municipality" id="municipality" class="filter-select">
                                <option value="">All</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="category" class="filter-label">Category:</label>
                            <select v-model="filters.category" id="category" class="filter-select">
                                <option value="">All</option>
                                <option value="marine_mammals">Marine Mammals</option>
                                <option value="marine_turtles">Marine Turtles</option>
                                <option value="sharks_rays">Shark and Rays</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="eventType" class="filter-label">Event Type:</label>
                            <select v-model="filters.eventType" id="eventType" class="filter-select">
                                <option value="">All</option>
                                <option value="Sighting">Sighting</option>
                                <option value="Stranded">Stranded</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action buttons in a separate row -->
                    <div class="flex justify-end mt-3 gap-2">
                        <button @click="resetFilters" class="action-button secondary-button">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </button>
                        <button @click="showDownloadConfirmation" class="action-button primary-button" data-action="download">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </button>
                        <button v-if="page.props.auth.user.user_role!=='lgu_responder'" @click="showExportConfirmation" class="action-button primary-button" data-action="export">
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </button>
                    </div>
                </div>

                <!-- Stats Row - Redesigned to be more compact -->
                <div class="grid grid-cols-3 mb-4">
                    <div class="glass-container p-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-2 hidden md:block">
                                <i class="fas fa-clipboard-list text-blue-600 text-lg"></i>
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <div class="text-xs font-medium text-white text-opacity-70 truncate">Total Reports</div>
                                <div class="text-md md:text-xl font-semibold text-white">{{ summaryData.totalEvents }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="glass-container p-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-2 hidden md:block">
                                <i class="fas fa-fish text-green-600 text-lg"></i>
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <div class="text-xs  font-medium text-white text-opacity-70 truncate">Species Involved</div>
                                <div class="text-md md:text-xl font-semibold text-white">{{ summaryData.totalSpecies }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="glass-container p-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-md p-2 hidden md:block">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <div class="text-xs font-medium text-white text-opacity-70 truncate">False Reports</div>
                                <div class="text-md md:text-xl font-semibold text-white">{{ summaryData.falseReports }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area - Improve chart positioning -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- Top 5 species -->
                    <div class="glass-container p-3">
                        <h3 class="text-base font-medium text-white mb-2 pb-1">Top 5 Common Species</h3>
                        <div class="species-grid">
                            <div v-for="species in summaryData.topCommonSpecies" :key="species.name"
                                class="species-item">
                                <span class="species-name">{{ species.name }}</span>
                                <span class="species-count">{{ species.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- First two charts side by side in remaining space -->
                    <div v-for="(chart, name, index) in { 'Yearly Trends': chartConfig['Yearly Trends'], 'Category Trends': chartConfig['Category Trends'] }"
                        :key="name"
                        v-show="index < 2"
                        class="glass-container p-3">
                        <h3 class="text-base font-medium text-white mb-2 pb-1">{{ name }}</h3>
                        <div class="relative chart-container" style="height: 210px;">
                            <div v-if="chartsLoading"
                                class="absolute inset-0 flex items-center justify-center">
                                <div class="loading-spinner-large"></div>
                            </div>
                            <div v-else-if="!hasDataForChart(name)"
                                class="absolute inset-0 flex items-center justify-center">
                                <div class="text-white text-opacity-80 text-center text-sm">
                                    <i class="fas fa-chart-bar text-2xl mb-2 opacity-50"></i>
                                    <p>No data available</p>
                                </div>
                            </div>
                            <canvas v-show="!chartsLoading && hasDataForChart(name)"
                                :id="chart.id"
                                class="w-full h-full chart-canvas">
                            </canvas>
                        </div>
                    </div>
                </div>

                <!-- Bottom row of charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(chart, name, index) in { 'Municipality Distribution': chartConfig['Municipality Distribution'], 'Condition Frequency': chartConfig['Condition Frequency'] }"
                        :key="name"
                        v-show="index < 2"
                        class="glass-container p-3">
                        <h3 class="text-base font-medium text-white mb-2 pb-1">{{ name }}</h3>
                        <div class="relative chart-container" style="height: 230px;">
                            <div v-if="chartsLoading"
                                class="absolute inset-0 flex items-center justify-center">
                                <div class="loading-spinner-large"></div>
                            </div>
                            <div v-else-if="!hasDataForChart(name)"
                                class="absolute inset-0 flex items-center justify-center">
                                <div class="text-white text-opacity-80 text-center text-sm">
                                    <i class="fas fa-chart-pie text-2xl mb-2 opacity-50"></i>
                                    <p>No data available</p>
                                </div>
                            </div>
                            <canvas v-show="!chartsLoading && hasDataForChart(name)"
                                :id="chart.id"
                                class="w-full h-full chart-canvas">
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Confirmation Modal -->
            <div v-if="showDownloadModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
                <div class="glass-modal p-4 rounded-lg shadow-lg max-w-md w-full">
                    <h3 class="text-lg font-semibold mb-3 text-white">Download Report</h3>
                    <p class="mb-4 text-white text-opacity-80">Are you sure you want to download the current report as a PDF?</p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showDownloadModal = false"
                            class="action-button secondary-button">
                            Cancel
                        </button>
                        <button
                            @click="downloadPDF"
                            class="action-button primary-button"
                            data-action="download"
                            :disabled="isDownloading">
                            {{ isDownloading ? 'Downloading...' : 'Download' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Export Confirmation Modal -->
            <div v-if="showExportModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
                <div class="glass-modal p-4 rounded-lg shadow-lg max-w-md w-full">
                    <h3 class="text-lg font-semibold mb-3 text-white">Export Data</h3>
                    <p class="mb-4 text-white text-opacity-80">Are you sure you want to export the filtered data to Excel?</p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showExportModal = false"
                            class="action-button secondary-button">
                            Cancel
                        </button>
                        <button
                            @click="exportToExcel"
                            class="action-button primary-button"
                            data-action="export"
                            :disabled="isExporting">
                            {{ isExporting ? 'Exporting...' : 'Export' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style>
/* Ocean theme styling */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 40, 80, 0.92) 0%,
        rgba(0, 60, 110, 0.85) 50%,
        rgba(0, 30, 60, 0.92) 100%
    );
}

.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #4dabf7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    margin-bottom: 0.5rem;
}

.glass-container {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.glass-container:hover {
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.glass-modal {
    background: rgba(0, 51, 102, 0.85);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    animation: modal-appear 0.3s ease-out;
    margin: 1rem !important;
    max-height: calc(100vh - 2rem) !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

@keyframes modal-appear {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Improved filter select styling */
.filter-select {
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    font-weight: 500;
}

.filter-select:focus {
    background-color: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    outline: none;
    box-shadow: 0 0 0 3px rgba(77, 171, 247, 0.4);
}

/* Force the select dropdown options to be visible */
.filter-select option {
    background-color: #003366 !important;
    color: white !important;
    font-weight: normal;
    padding: 8px;
}

/* Enhanced PDF mode styles for better contrast */
.pdf-mode {
    background: white !important;
}

.pdf-mode .glass-container {
    background: #f5f8fa !important;
    backdrop-filter: none !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
}

.pdf-mode .text-white,
.pdf-mode h3,
.pdf-mode .font-medium,
.pdf-mode .font-semibold {
    color: #2d3748 !important;
    text-shadow: none !important;
}

.pdf-mode .profile-title-gradient {
    color: #003366 !important;
    background: #003366 !important;
    -webkit-text-fill-color: #003366 !important;
    text-shadow: none !important;
}

.pdf-mode .flex-shrink-0 {
    background: white !important;
    border: 1px solid #e2e8f0 !important;
}

.pdf-mode .bg-white\/5,
.pdf-mode .bg-blue-500\/20,
.pdf-mode .bg-blue-500\/30 {
    background: #ebf4ff !important;
}

.pdf-mode .chart-canvas {
    background: white !important;
    filter: none !important;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .glass-container {
        margin: 0.5rem;
        padding: 1rem;
    }

    .profile-title-gradient {
        font-size: 1.5rem;
    }

    .filters {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-select, .action-button {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    /* Optimize top species on mobile */
    .top-species-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* Enhanced buttons with better visual hierarchy */
.action-button {
    padding: 0.55rem 1.2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    letter-spacing: 0.01em;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.primary-button {
    background: linear-gradient(
        135deg,
        #4dabf7 0%,
        #2b8cd8 100%
    );
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.primary-button:hover:not(:disabled) {
    background: linear-gradient(
        135deg,
        #60b6ff 0%,
        #3a99e6 100%
    );
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(42, 139, 218, 0.35);
}

.secondary-button {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: white;
}

.secondary-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    border-color: rgba(255, 255, 255, 0.35);
}

.action-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.action-button:active {
    transform: translateY(1px);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.action-button i {
    margin-right: 0.5rem;
}

/* Stats cards styling with enhanced colors */
.glass-container .flex-shrink-0 {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.glass-container:hover .flex-shrink-0 {
    background: rgba(255, 255, 255, 0.18) !important;
    border-color: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

/* Improved chart styling */
.chart-canvas {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    border-radius: 4px;
}

.chart-container {
    background-color: rgba(0, 30, 60, 0.95) !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    margin: 0 auto;
    max-width: 100%;
    position: relative;
}

/* Fix chart aspect ratio and scaling */
canvas.chart-canvas {
    width: 100% !important;
    height: 100% !important;
    max-height: 100%;
    object-fit: contain;
}

/* Loading spinner styles */
.loading-spinner-large {
    display: inline-block;
    width: 3rem;
    height: 3rem;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    border-top-color: #4dabf7;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Enhanced status colors for better visibility and contrast */
.text-red-600 { color: #ff6b6b !important; }
.text-yellow-600 { color: #ffd93d !important; }
.text-green-600 { color: #6dd5a7 !important; }
.text-blue-600 { color: #4dabf7 !important; }

/* Enhanced icon container backgrounds */
.bg-red-100 { background: rgba(255, 107, 107, 0.25) !important; border: 1px solid rgba(255, 107, 107, 0.4) !important; }
.bg-yellow-100 { background: rgba(255, 217, 61, 0.25) !important; border: 1px solid rgba(255, 217, 61, 0.4) !important; }
.bg-green-100 { background: rgba(109, 213, 167, 0.25) !important; border: 1px solid rgba(109, 213, 167, 0.4) !important; }
.bg-blue-100 { background: rgba(77, 171, 247, 0.25) !important; border: 1px solid rgba(77, 171, 247, 0.4) !important; }

/* Text visibility enhancements */
.text-white {
    color: rgba(255, 255, 255, 0.95) !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.font-medium, .font-semibold {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Enhanced notification styling */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    color: white;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    animation: notification-slide-in 0.3s ease-out;
    display: flex;
    align-items: center;
}

@keyframes notification-slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification.success {
    background: linear-gradient(135deg, #10b981, #059669);
    border-left: 4px solid #10b981;
}

.notification.error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-left: 4px solid #ef4444;
}

.notification.info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-left: 4px solid #3b82f6;
}

/* Enhanced Top 5 Species styling */
.top-species-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.top-species-grid > div {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.top-species-grid > div:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.top-species-grid > div span:first-child {
    font-weight: 500;
    max-width: 80%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.top-species-grid > div span:last-child {
    background: rgba(77, 171, 247, 0.3);
    border: 1px solid rgba(77, 171, 247, 0.5);
}

/* Enhanced card header styling */
.glass-container h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Download and Export buttons with special styling */
.action-button.primary-button[data-action="download"] {
    background: linear-gradient(135deg, #6dd5a7, #10b981);
    border-color: rgba(109, 213, 167, 0.5);
}

.action-button.primary-button[data-action="download"]:hover:not(:disabled) {
    background: linear-gradient(135deg, #7de0b2, #20c997);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.35);
}

.action-button.primary-button[data-action="export"] {
    background: linear-gradient(135deg, #ffd93d, #f59e0b);
    border-color: rgba(255, 217, 61, 0.5);
}

.action-button.primary-button[data-action="export"]:hover:not(:disabled) {
    background: linear-gradient(135deg, #ffe44d, #f7ae19);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.35);
}

/* Updated stat card styling */
.glass-container .flex-shrink-0 {
    padding: 0.5rem !important;
}

.loading-spinner-large {
    width: 2rem;
    height: 2rem;
    border-width: 3px;
}

@media (min-width: 768px) {
    .species-grid {
        grid-template-columns: 1fr 1fr;
    }
}

/* New and updated styles for more compact layout */
.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: rgba(255, 255, 255, 0.8);
}

.filter-select {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    padding-right: 2rem;
}

/* More compact spacing */
.mb-4 {
    margin-bottom: 1rem !important;
}

.p-3 {
    padding: 0.75rem !important;
}

.species-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.species-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.species-item:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
}

.species-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 70%;
}

.species-count {
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(77, 171, 247, 0.3);
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    min-width: 1.5rem;
    text-align: center;
    border: 1px solid rgba(77, 171, 247, 0.5);
}

/* Enhanced action buttons styling */
.action-button {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.action-button i {
    margin-right: 0.25rem;
}

/* Enhanced card header styling */
.glass-container h3 {
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 0.25rem;
    margin-bottom: 0.5rem;
}

/* Clearer visual hierarchy for filter buttons */
.action-button[data-action="download"] {
    background: linear-gradient(135deg, #6dd5a7, #10b981);
    border-color: rgba(109, 213, 167, 0.5);
}

.action-button[data-action="download"]:hover:not(:disabled) {
    background: linear-gradient(135deg, #7de0b2, #20c997);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.35);
}

.action-button[data-action="export"] {
    background: linear-gradient(135deg, #ffd93d, #f59e0b);
    border-color: rgba(255, 217, 61, 0.5);
}

.action-button[data-action="export"]:hover:not(:disabled) {
    background: linear-gradient(135deg, #ffe44d, #f7ae19);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.35);
}

/* Updated stat card styling */
.glass-container .flex-shrink-0 {
    padding: 0.5rem !important;
}

.loading-spinner-large {
    width: 2rem;
    height: 2rem;
    border-width: 3px;
}

@media (min-width: 768px) {
    .species-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

