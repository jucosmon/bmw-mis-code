<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const showReportModal = ref(false);

const userStats = ref({
    totalActive: 0,
    pendingSightings: 0,
    verifiedSightings: 0,
    resolvedSightings: 0,
    falseReports: 0
});

const featuredSpecies = ref([]);
const recentActivities = ref([]);
const isLoading = ref(true);

const navigateToReport = (type) => {
    if (type === 'emergency') {
        window.location.href = route('stranded.incident.createPage');
    } else {
        window.location.href = route('sighting.createPage');
    }
    showReportModal.value = false;
};

const activeFilter = ref('all'); // 'all', 'sightings', 'stranded'

// Add pagination refs
const currentPage = ref(1);
const perPage = ref(5);
const totalActivities = computed(() => filteredActivities.value.length);
const totalPages = computed(() => Math.ceil(totalActivities.value / perPage.value));

// Add fetchUserStats function
const fetchUserStats = async () => {
    try {
        const userId = user.value.id;

        // Fetch user's sightings stats
        const [sightingsStats, strandingsStats] = await Promise.all([
            supabase
                .from('sightings')
                .select('id, report_status')
                .eq('user_id', userId)
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select('id, report_status')
                .eq('user_id', userId)
                .eq('is_active', true)
        ]);

        if (sightingsStats.error) throw sightingsStats.error;
        if (strandingsStats.error) throw strandingsStats.error;

        // Calculate statistics
        const sightings = sightingsStats.data || [];
        const strandings = strandingsStats.data || [];

        userStats.value = {
            totalActive: sightings.length + strandings.length,
            pendingSightings: sightings.filter(s => s.report_status === 'pending').length,
            verifiedSightings: sightings.filter(s => s.report_status === 'verified').length,
            resolvedSightings: strandings.filter(s => s.report_status === 'resolved').length,
            falseReports: [...sightings, ...strandings].filter(r => r.report_status === 'false_report').length
        };

    } catch (error) {
        console.error('Error fetching user stats:', error);
    }
};

// Update fetchTopSpecies function
const fetchTopSpecies = async () => {
    try {
        // Fetch all reports to calculate most reported species
        const [sightingsRes, strandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select(`
                    sighted_species (
                        species (
                            id,
                            name,
                            scientific_name,
                            conservation_status,
                            category
                        )
                    )
                `)
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select(`
                    stranded_species (
                        species (
                            id,
                            name,
                            scientific_name,
                            conservation_status,
                            category
                        )
                    )
                `)
                .eq('is_active', true)
        ]);

        if (sightingsRes.error) throw sightingsRes.error;
        if (strandingsRes.error) throw strandingsRes.error;

        // Process and combine species data
        const speciesCount = {};
        const speciesDetails = {};

        // Process sightings
        sightingsRes.data?.forEach(sighting => {
            sighting.sighted_species.forEach(ss => {
                const species = ss.species;
                if (species) {
                    speciesCount[species.id] = (speciesCount[species.id] || 0) + 1;
                    speciesDetails[species.id] = species;
                }
            });
        });

        // Process strandings
        strandingsRes.data?.forEach(stranding => {
            stranding.stranded_species.forEach(ss => {
                const species = ss.species;
                if (species) {
                    speciesCount[species.id] = (speciesCount[species.id] || 0) + 1;
                    speciesDetails[species.id] = species;
                }
            });
        });

        // Get top 5 species
        featuredSpecies.value = Object.entries(speciesCount)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 7)
            .map(([id]) => ({
                id: parseInt(id),
                name: speciesDetails[id].name,
                scientificName: speciesDetails[id].scientific_name,
                status: speciesDetails[id].conservation_status,
                category: speciesDetails[id].category,
                reportCount: speciesCount[id]
            }));

    } catch (error) {
        console.error('Error fetching top species:', error);
    }
};

// Update fetchUserActivities function
const fetchUserActivities = async () => {
    try {
        const userId = user.value.id;
        console.log('Fetching activities for user:', userId);

        const [sightingsRes, strandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select(`
                    id,
                    date,
                    report_status,
                    municipality:municipality_id(name),
                    barangay:barangay_id(name),
                    sighted_species (
                        species (
                            name
                        )
                    )
                `)
                .eq('user_id', userId)
                .eq('is_active', true),
            supabase
                .from('stranded_incidents')
                .select(`
                    id,
                    date,
                    report_status,
                    municipality:municipality_id(name),
                    barangay:barangay_id(name),
                    species_involved,
                    user_id
                `)
                .eq('user_id', userId)
                .eq('is_active', true)
        ]);

        if (sightingsRes.error) throw sightingsRes.error;
        if (strandingsRes.error) throw strandingsRes.error;

        const activities = [
            ...(sightingsRes.data?.map(sighting => ({
                id: `sighting-${sighting.id}`,
                type: 'sighting',
                species: sighting.sighted_species
                    .map(ss => ss.species?.name)
                    .filter(Boolean)
                    .join(', ') || 'Unknown Species',
                location: `${sighting.barangay?.name || 'Unknown Location'}, ${sighting.municipality?.name || 'Unknown Location'}`,
                date: sighting.date,
                status: sighting.report_status,
                viewUrl: route('sighting.view', sighting.id)
            })) || []),
            ...(strandingsRes.data?.map(stranding => ({
                id: `stranding-${stranding.id}`,
                type: 'stranded',
                date: stranding.date,
                status: stranding.report_status,
                species: stranding.species_involved || 'Unknown Species',
                location: `${stranding.barangay?.name || 'Unknown Location'}, ${stranding.municipality?.name || 'Unknown Location'}`,
                viewUrl: route('stranded.incident.view', stranding.id)
            })) || [])
        ];

        console.log('Processed activities:', activities);

        recentActivities.value = activities.sort((a, b) =>
            new Date(b.date) - new Date(a.date)
        );

    } catch (error) {
        console.error('Error fetching user activities:', error);
    } finally {
        isLoading.value = false;
    }
};

// Add pagination methods
const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

// Initialize data
onMounted(async () => {
    try {
        isLoading.value = true;
        await Promise.all([
            fetchUserStats(),
            fetchTopSpecies(),
            fetchUserActivities()
        ]);
    } catch (error) {
        console.error('Error initializing dashboard:', error);
    } finally {
        isLoading.value = false;
    }
});

// Update the existing filter computed
const filteredActivities = computed(() => {
    if (activeFilter.value === 'all') return recentActivities.value;
    return recentActivities.value.filter(activity => activity.type === activeFilter.value);
});

// Update filtered activities computed to include pagination
const paginatedActivities = computed(() => {
    const filtered = filteredActivities.value;
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filtered.slice(start, end);
});
</script>

<template>
    <Head title="Dashboard" />

    <Sidebar>

        <div class="min-h-screen bg-cover bg-center relative oceanic-overlay" style="background-image: url('/images/landing.jpg')">
            <div class="relative">
                <!-- Hero Section -->
                <div class="relative flex items-center justify-center min-h-[300px] py-10 pt-12">
                    <div class="absolute inset-0 bg-gradient-to-b from-blue-900/80 to-cyan-800/90"></div>
                    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="text-center text-white">
                            <h1 class="text-2xl font-bold sm:text-3xl text-shadow">Welcome, {{ user.first_name }}!</h1>
                            <p class="mt-2 text-lg text-shadow">Help us protect marine wildlife</p>
                            <div class="mt-6">
                                <PrimaryButton @click="showReportModal = true"
                                    class="animate-bounce py-3 text-base text-blue-600 hover:bg-blue-50 sm:px-8 sm:py-4 sm:text-lg">
                                    🐋 Report Marine Wildlife
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Added spacing class mt-8 (2rem/32px) between hero and main content -->
                <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8 mt-8">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
                        <!-- Featured Species - Updated with oceanic blue -->
                        <div class="rounded-lg oceanic-container p-4 shadow-lg sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-white">Most Reported Species</h3>
                                <Link :href="route('species.index')"
                                    class="text-sm text-cyan-300 hover:text-cyan-200 hover:underline">
                                    View All →
                                </Link>
                            </div>
                            <div v-if="isLoading" class="text-center py-4">
                                Loading...
                            </div>
                            <div v-else class="space-y-3">
                                <Link v-for="species in featuredSpecies"
                                    :key="species.id"
                                    :href="route('species.view', species.id)"
                                    class="block rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.02] border border-cyan-100">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-cyan-900">{{ species.name }}</h4>
                                            <p class="text-sm text-cyan-600 italic">{{ species.scientificName }}</p>
                                            <p class="mt-1 text-sm text-cyan-700">
                                                Reports: {{ species.reportCount }}
                                            </p>
                                        </div>
                                        <span class="inline-block rounded-full px-2 py-1 text-xs"
                                            :class="{
                                                'bg-red-100 text-red-800': species.status === 'Endangered',
                                                'bg-yellow-100 text-yellow-800': species.status === 'Vulnerable',
                                                'bg-green-100 text-green-800': species.status === 'Least Concern'
                                            }">
                                            {{ species.status }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Quick Actions and Recent Activity -->
                        <div class="space-y-4">
                            <!-- Quick Access - Updated with oceanic blue -->
                            <div class="rounded-lg oceanic-container p-4 shadow-lg sm:p-6">
                                <h3 class="mb-4 text-lg font-semibold text-white">Quick Access</h3>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <Link :href="route('guideline.index')"
                                        class="flex items-center rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.02] border border-cyan-100">
                                        <span class="mr-3 text-2xl">📋</span>
                                        <span class="font-medium text-cyan-900">Guidelines</span>
                                    </Link>
                                    <Link :href="route('species.index')"
                                        class="flex items-center rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.02] border border-cyan-100">
                                        <span class="mr-3 text-2xl">🔍</span>
                                        <span class="font-medium text-cyan-900">Species</span>
                                    </Link>
                                    <Link :href="route('stranded.incident.index')"
                                        class="flex items-center rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.02] border border-cyan-100">
                                        <span class="mr-3 text-2xl">🚨</span>
                                        <span class="font-medium text-cyan-900">Stranded Reports</span>
                                    </Link>
                                    <Link :href="route('sighting.index')"
                                        class="flex items-center rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.02] border border-cyan-100">
                                        <span class="mr-3 text-2xl">👁️</span>
                                        <span class="font-medium text-cyan-900">Sighting Reports</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Recent Activity - Updated with oceanic blue -->
                            <div class="rounded-lg oceanic-container p-4 shadow-lg sm:p-6">
                                <div class="mb-3 flex flex-col space-y-2 sm:mb-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                                    <h3 class="text-lg font-semibold">Recent Activity</h3>
                                    <div class="flex space-x-1 sm:space-x-2">
                                        <button
                                            @click="activeFilter = 'all'"
                                            :class="['px-3 py-1 rounded-full text-sm',
                                                activeFilter === 'all'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                                            All
                                        </button>
                                        <button
                                            @click="activeFilter = 'sighting'"
                                            :class="['px-3 py-1 rounded-full text-sm',
                                                activeFilter === 'sighting'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                                            Sightings
                                        </button>
                                        <button
                                            @click="activeFilter = 'stranded'"
                                            :class="['px-3 py-1 rounded-full text-sm',
                                                activeFilter === 'stranded'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                                            Stranded
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div v-if="isLoading" class="text-center py-4">
                                        Loading...
                                    </div>
                                    <template v-else>
                                        <Link v-for="activity in paginatedActivities" :key="activity.id"
                                            :href="activity.viewUrl"
                                            class="flex items-center rounded-lg bg-white/80 p-3 transition-all duration-200 hover:bg-white hover:shadow-lg hover:scale-[1.01] border border-cyan-100">
                                            <div class="mr-4 flex-shrink-0">
                                                <span class="text-2xl">
                                                    {{ activity.type === 'stranded' ? '🚨' : '👁️' }}
                                                </span>
                                            </div>
                                            <div class="flex-grow">
                                                <div class="flex items-center gap-2 mb-1 justify-between">
                                                    <h4 class="font-medium text-indigo-800">
                                                        {{ activity.species }}
                                                    </h4>
                                                    <span :class="[
                                                        'inline-block rounded-full px-2 py-1 text-xs',
                                                        activity.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                        activity.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                        activity.status === 'resolved' ? 'bg-blue-100 text-blue-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    ]">
                                                        {{ activity.status }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600">{{ activity.location }}</p>
                                                <p class="text-xs text-gray-500">{{ activity.date }}</p>
                                            </div>

                                        </Link>
                                        <div v-if="filteredActivities.length === 0"
                                            class="text-center text-gray-200 py-4">
                                            No recent activities
                                        </div>

                                        <!-- Pagination Controls -->
                                        <div v-if="filteredActivities.length > 0"
                                            class="mt-4 flex items-center justify-between border-t pt-4">
                                            <span class="text-sm text-gray-300">
                                                Showing {{ ((currentPage - 1) * perPage) + 1 }} to
                                                {{ Math.min(currentPage * perPage, totalActivities) }}
                                                of {{ totalActivities }} entries
                                            </span>
                                            <div class="flex space-x-2">
                                                <button @click="previousPage"
                                                    :disabled="currentPage === 1"
                                                    :class="['px-3 py-1 border rounded text-sm',
                                                        currentPage === 1
                                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                            : 'bg-white text-gray-700 hover:bg-gray-50']">
                                                    Previous
                                                </button>
                                                <button @click="nextPage"
                                                    :disabled="currentPage >= totalPages"
                                                    :class="['px-3 py-1 border rounded text-sm',
                                                        currentPage >= totalPages
                                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                            : 'bg-blue-600 text-white hover:bg-blue-700']">
                                                    Next
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Modal -->
        <Modal :show="showReportModal" @close="showReportModal = false">
            <div class="p-6 modal-content">
                <h3 class="mb-6 text-center text-xl font-medium text-white">What would you like to report?</h3>
                <div class="space-y-4">
                    <button @click="navigateToReport('emergency')"
                        class="w-full rounded-lg p-4 text-left transition modal-report-button">
                        <div class="flex items-center">
                            <span class="text-2xl">🚨</span>
                            <div class="ml-3">
                                <h4 class="font-medium text-red-700">Emergency Report</h4>
                                <p class="text-sm text-red-600">Stranded or injured marine wildlife</p>
                            </div>
                        </div>
                    </button>
                    <button @click="navigateToReport('sighting')"
                        class="w-full rounded-lg p-4 text-left transition modal-sighting-button">
                        <div class="flex items-center">
                            <span class="text-2xl">👁️</span>
                            <div class="ml-3">
                                <h4 class="font-medium text-blue-700">Sighting Report</h4>
                                <p class="text-sm text-blue-600">Non-emergency wildlife observation</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>

<style scoped>
.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.oceanic-overlay {
    position: relative;
}

.oceanic-overlay::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.7) 0%,
        rgba(0, 128, 170, 0.65) 50%,
        rgba(0, 76, 153, 0.7) 100%
    );
    pointer-events: none;
}

.oceanic-container {
    background: linear-gradient(180deg, rgba(13, 71, 161, 0.4), rgba(0, 60, 120, 0.3));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 15px rgba(0, 149, 255, 0.1) inset;
}

.oceanic-container:hover {
    background: linear-gradient(180deg, rgba(13, 71, 161, 0.5), rgba(0, 60, 120, 0.4));
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 149, 255, 0.15) inset;
}

/* Adjust inner content contrast */
.oceanic-container .bg-white\/80 {
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(0, 149, 255, 0.15);
    backdrop-filter: blur(4px);
}

.oceanic-container .bg-white\/80:hover {
    background: rgba(255, 255, 255, 0.92);
    border-color: rgba(0, 149, 255, 0.25);
}

/* Update text colors for better contrast */
.oceanic-container h3 {
    color: rgb(250, 249, 253);
}

.oceanic-container a:not(.text-cyan-300),
.oceanic-container button:not(.bg-blue-600) {
    color: rgba(255, 255, 255, 0.347);
}

.oceanic-container a:hover:not(.text-cyan-300),
.oceanic-container button:hover:not(.bg-blue-600) {
    color: rgb(255, 255, 255);
}

/* Button Gradients */
.oceanic-container button.rounded-full {
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.8), rgba(0, 119, 190, 0.9));
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.oceanic-container button.rounded-full:hover {
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.9), rgba(0, 119, 190, 1));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 119, 190, 0.2);
}

/* Active button state */
.oceanic-container button.rounded-full[class*="bg-blue-600"] {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Pagination buttons */
.oceanic-container button.border.rounded {
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.1), rgba(0, 119, 190, 0.2));
    border: 1px solid rgba(0, 119, 190, 0.3);
    color: white;
}

.oceanic-container button.border.rounded:hover:not([disabled]) {
    background: linear-gradient(135deg, rgba(30, 144, 255, 0.2), rgba(0, 119, 190, 0.3));
    transform: translateY(-1px);
}

.oceanic-container button.border.rounded[class*="bg-blue-600"] {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

/* Disabled button state */
.oceanic-container button[disabled] {
    background: linear-gradient(135deg, rgba(156, 163, 175, 0.3), rgba(107, 114, 128, 0.4));
    cursor: not-allowed;
    transform: none;
}

/* Report Modal Gradients */
.modal-report-button {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(220, 38, 38, 0.35));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(239, 68, 68, 0.5) !important;
    transition: all 0.3s ease;
}

.modal-report-button:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.35), rgba(220, 38, 38, 0.45));
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
}

.modal-report-button h4 {
    color: rgb(240, 82, 82); /* Brighter red */
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.modal-report-button p {
    color: rgb(248, 113, 113); /* Lighter red text */
}

.modal-sighting-button {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(37, 99, 235, 0.35));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(59, 130, 246, 0.5) !important;
    transition: all 0.3s ease;
}

.modal-sighting-button:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.35), rgba(37, 99, 235, 0.45));
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
}

.modal-sighting-button h4 {
    color: rgb(59, 130, 246); /* Brighter blue */
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.modal-sighting-button p {
    color: rgb(96, 165, 250); /* Lighter blue text */
}

/* Add subtle glow effect */
.modal-report-button,
.modal-sighting-button {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 8px rgba(255, 255, 255, 0.1);
}

/* Modal Styles */
.modal-content {
    background: linear-gradient(180deg, rgba(13, 71, 161, 0.95), rgba(0, 60, 120, 0.9));
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), 0 0 15px rgba(0, 149, 255, 0.15) inset;
}

/* Adjust modal button colors for better contrast on dark background */
.modal-report-button {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.25));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(239, 68, 68, 0.4) !important;
}

.modal-sighting-button {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.25));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(59, 130, 246, 0.4) !important;
}

/* Updated Modal Button Styles */
.modal-report-button {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.85));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-report-button:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 1), rgba(220, 38, 38, 0.95));
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.modal-report-button h4 {
    color: rgba(255, 255, 255, 0.95);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    font-weight: 600;
}

.modal-report-button p {
    color: rgba(255, 255, 255, 0.85);
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

.modal-sighting-button {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(37, 99, 235, 0.85));
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-sighting-button:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 1), rgba(37, 99, 235, 0.95));
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
}

.modal-sighting-button h4 {
    color: rgba(255, 255, 255, 0.95);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    font-weight: 600;
}

.modal-sighting-button p {
    color: rgba(255, 255, 255, 0.85);
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}
</style>
