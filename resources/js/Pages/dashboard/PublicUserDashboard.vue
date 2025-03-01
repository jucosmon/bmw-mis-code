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
        console.log('Fetching activities for user:', userId); // Debug log

        const [sightingsRes, strandingsRes] = await Promise.all([
            supabase
                .from('sightings')
                .select(`
                    id,
                    date,
                    report_status,
                    municipality:municipality_id(name),
                    sighted_species (
                        species (
                            name
                        )
                    ),
                    user_id
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
                    stranded_species (
                        species (
                            name
                        )
                    ),
                    user_id
                `)
                .eq('user_id', userId)
                .eq('is_active', true)
        ]);

        console.log('Sightings response:', sightingsRes); // Debug log
        console.log('Strandings response:', strandingsRes); // Debug log

        if (sightingsRes.error) throw sightingsRes.error;
        if (strandingsRes.error) throw strandingsRes.error;

        const activities = [
            ...(sightingsRes.data?.map(sighting => ({
                id: `sighting-${sighting.id}`,
                type: 'sighting',
                date: sighting.date,
                status: sighting.report_status,
                species: sighting.sighted_species[0]?.species?.name || 'Unknown Species',
                location: sighting.municipality?.name || 'Unknown Location',
                viewUrl: route('sighting.view', sighting.id)
            })) || []),
            ...(strandingsRes.data?.map(stranding => ({
                id: `stranding-${stranding.id}`,
                type: 'stranded',
                date: stranding.date,
                status: stranding.report_status,
                species: stranding.stranded_species[0]?.species?.name || 'Unknown Species',
                location: stranding.municipality?.name || 'Unknown Location',
                viewUrl: route('stranded.incident.view', stranding.id)
            })) || [])
        ];

        console.log('Processed activities:', activities); // Debug log

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
        <div class="min-h-screen bg-gray-50">
            <!-- Hero Section with Wave -->
            <div class="relative bg-indigo-800 pb-24 pt-12 sm:pb-32">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center text-white">
                        <h1 class="text-2xl font-bold sm:text-3xl">Welcome, {{ user.first_name }}!</h1>
                        <p class="mt-2 text-lg">Help us protect marine wildlife</p>
                        <div class="mt-6">
                            <PrimaryButton @click="showReportModal = true"
                                class="animate-bounce  py-3 text-base text-blue-600 hover:bg-blue-50 sm:px-8 sm:py-4 sm:text-lg">
                                🐋 Report Marine Wildlife
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Main Content -->
            <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">

                <!-- Featured Species and Quick Links -->
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
                    <!-- Featured Species - Simplified List View -->
                    <div class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Most Reported Species</h3>
                            <Link :href="route('species.index')"
                                class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
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
                                class="block rounded-lg border p-3 transition hover:bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ species.name }}</h4>
                                        <p class="text-sm text-gray-500 italic">{{ species.scientificName }}</p>
                                        <p class="mt-1 text-sm text-gray-600">
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
                        <!-- Quick Access - Updated -->
                        <div class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
                            <h3 class="mb-4 text-lg font-semibold">Quick Access</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <Link :href="route('guideline.index')"
                                    class="flex items-center rounded-lg border bg-blue-50 p-3 transition-colors hover:bg-blue-100">
                                    <span class="mr-3 text-2xl">📋</span>
                                    <span class="font-medium text-blue-900">Guidelines</span>
                                </Link>
                                <Link :href="route('species.index')"
                                    class="flex items-center rounded-lg border bg-green-50 p-3 transition-colors hover:bg-green-100">
                                    <span class="mr-3 text-2xl">🔍</span>
                                    <span class="font-medium text-green-900">Species</span>
                                </Link>
                                <Link :href="route('stranded.incident.index')"
                                    class="flex items-center rounded-lg border bg-red-50 p-3 transition-colors hover:bg-red-100">
                                    <span class="mr-3 text-2xl">🚨</span>
                                    <span class="font-medium text-red-900">Stranded Reports</span>
                                </Link>
                                <Link :href="route('sighting.index')"
                                    class="flex items-center rounded-lg border bg-purple-50 p-3 transition-colors hover:bg-purple-100">
                                    <span class="mr-3 text-2xl">👁️</span>
                                    <span class="font-medium text-purple-900">Sighting Reports</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Recent Activity - Keep existing but adjust padding -->
                        <div class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
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
                                    <div v-for="activity in paginatedActivities" :key="activity.id"
                                        class="flex items-center rounded-lg border p-3 hover:bg-gray-50">
                                        <div class="mr-4 flex-shrink-0">
                                            <span class="text-2xl">
                                                {{ activity.type === 'stranded' ? '🚨' : '👁️' }}
                                            </span>
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="font-medium">{{ activity.species }}</h4>
                                                <span :class="[
                                                    'text-xs px-2 py-0.5 rounded-full',
                                                    activity.type === 'stranded'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-purple-100 text-purple-800'
                                                ]">
                                                    {{ activity.type === 'stranded' ? 'Stranded' : 'Sighting' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">{{ activity.location }}</p>
                                            <p class="text-xs text-gray-500">{{ activity.date }}</p>
                                        </div>
                                        <div class="ml-4">
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
                                    </div>
                                    <div v-if="filteredActivities.length === 0"
                                        class="text-center text-gray-500 py-4">
                                        No recent activities
                                    </div>

                                    <!-- Pagination Controls -->
                                    <div v-if="filteredActivities.length > 0"
                                        class="mt-4 flex items-center justify-between border-t pt-4">
                                        <span class="text-sm text-gray-700">
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

        <!-- Report Modal -->
        <Modal :show="showReportModal" @close="showReportModal = false">
            <div class="p-6">
                <h3 class="mb-6 text-center text-xl font-medium">What would you like to report?</h3>
                <div class="space-y-4">
                    <button @click="navigateToReport('emergency')"
                        class="w-full rounded-lg border-2 border-red-500 bg-red-50 p-4 text-left transition hover:bg-red-100">
                        <div class="flex items-center">
                            <span class="text-2xl">🚨</span>
                            <div class="ml-3">
                                <h4 class="font-medium text-red-700">Emergency Report</h4>
                                <p class="text-sm text-red-600">Stranded or injured marine wildlife</p>
                            </div>
                        </div>
                    </button>
                    <button @click="navigateToReport('sighting')"
                        class="w-full rounded-lg border-2 border-blue-500 bg-blue-50 p-4 text-left transition hover:bg-blue-100">
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
