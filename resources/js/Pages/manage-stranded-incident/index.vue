<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Ensure usePage is not null
const page = usePage();
const props = page ? page.props : {
    strandedIncidents: [],
    notifications: [],
    success: '',
};

// Determine if the user is a public_user or not
const isPublicUser = computed(() => page && page.props.auth.user.user_role === 'public_user');

const filterStatus = ref('all'); // Default filter is "all"

// Filter strandedIncidents based on the selected status
const filteredStrandedIncidents = computed(() => {
    if (filterStatus.value === 'all') {
        return props.strandedIncidents;
    }
    return props.strandedIncidents.filter(incident => incident.report_status === filterStatus.value);
});

// Group stranded incidents by status
const groupedIncidents = computed(() => {
    return {
        pending: filteredStrandedIncidents.value.filter(incident => incident.report_status === 'pending'),
        verified: filteredStrandedIncidents.value.filter(incident => incident.report_status === 'verified'),
        completed: filteredStrandedIncidents.value.filter(incident => incident.report_status === 'completed'),
        resolved: isPublicUser.value
            ? filteredStrandedIncidents.value.filter(incident => incident.report_status === 'resolved')
            : [], // Don't include resolved incidents if not a public_user
    };
});

// Button routes
const createStrandedIncidents = () => {
    router.get(route('stranded.incident.createPage'));
};
const viewStrandedIncidents = (id) => {
    router.visit(route('stranded.incident.view', { id }));
};


const resolvedIncidentsButton = () => {
    router.visit(route('resolved.incidents.index'));
}

</script>

<template>
    <Head title="Stranded Incidents" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Stranded Incidents
            </h2>
        </template>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Success message -->
                    <div v-if="props?.success" class="glass-panel mb-6 p-4 border border-green-400/30 text-green-400">
                        <strong class="font-bold">Success! </strong>
                        <span>{{ props?.success}}</span>
                    </div>

                    <!-- Header section -->
                    <div class="mb-6">
                        <h3 class="profile-title-gradient mb-2">
                            Active Stranded Incident List
                        </h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <button v-if="!isPublicUser" @click="resolvedIncidentsButton"
                                class="action-button flex items-center justify-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-0.5 -0.5 30 30" height="20" width="20">
                                    <path fill="currentColor" d="M2.31873125 8.663810416666665v15.47875c0 0.9421374999999999 0.37428125 1.845789583333333 1.0404958333333332
                                    2.5120041666666664s1.5698062499999998 1.0404958333333332 2.51194375 1.0404958333333332h17.255060416666666c0.9421374999999999 0 1.8457291666666666
                                    -0.37428125 2.5120041666666664 -1.0404958333333332 0.6661541666666666 -0.6662145833333333 1.0404354166666665 -1.5698666666666665 1.0404354166666665
                                    -2.5120041666666664v-15.47875c0 -0.06730416666666666 -0.026643749999999997 -0.13182916666666666 -0.07437291666666666 -0.17937708333333333
                                    -0.04754791666666667 -0.047668749999999996 -0.1120125 -0.07437291666666666 -0.17937708333333333 -0.07437291666666666H2.57248125c-0.06730416666666666 0
                                    -0.1318895833333333 0.026704166666666668 -0.17943749999999997 0.07437291666666666 -0.04760833333333333 0.04754791666666667 -0.07431249999999999 0.11207291666666666
                                    -0.07431249999999999 0.17937708333333333Zm16.937752083333333 9.618997916666666 -4.040243749999999 4.0397c-0.19037291666666664 0.19019166666666668 -0.4484729166666666
                                    0.29712916666666667 -0.7175083333333333 0.29712916666666667 -0.2690958333333333 0 -0.5271958333333333 -0.10693749999999999 -0.7174479166666666 -0.29712916666666667l-4.040364583333333
                                    -4.0397c-0.3863645833333333 -0.3863041666666666 -0.4218895833333333 -1.015 -0.05395208333333333 -1.41973125 0.09237708333333333 -0.10174166666666666 0.20438958333333332
                                    -0.18366666666666664 0.32939166666666664 -0.24076041666666667 0.12494166666666666 -0.057154166666666666 0.260275 -0.08838958333333334 0.3976020833333333 -0.09165208333333333
                                    0.1373875 -0.0033229166666666663 0.27398958333333334 0.02120625 0.4015291666666666 0.07231875 0.1276 0.050991666666666664 0.24347916666666666 0.12741875 0.34068958333333327
                                    0.22456874999999998l2.3275520833333334 2.3268874999999998V12.498577083333332c0 -0.5462270833333334 0.41995625000000003 -1.015 0.9661229166666666 -1.0422479166666667 0.13726666666666668
                                    -0.0065854166666666665 0.27435208333333333 0.014741666666666667 0.40309999999999996 0.06265208333333333 0.12886874999999998 0.04797083333333333 0.24649999999999997 0.12155833333333332
                                    0.34600624999999996 0.21629166666666663 0.09944583333333333 0.09485416666666666 0.17865208333333332 0.20879999999999999 0.232725 0.33513124999999994 0.05413333333333333 0.12633125
                                    0.08198541666666666 0.26226875 0.08198541666666666 0.39965624999999994v6.684379166666667l2.3275520833333334 -2.3268874999999998c0.09714999999999999 -0.09714999999999999 0.21308958333333333
                                    -0.17357708333333333 0.34062916666666665 -0.22456874999999998 0.1276 -0.05111249999999999 0.26420208333333334 -0.07564166666666666 0.4015895833333333 -0.07237916666666666 0.13732708333333332
                                    0.0033229166666666663 0.2726604166666666 0.03455833333333333 0.3976625 0.09171249999999999 0.12488124999999999 0.05709375 0.23695416666666666 0.13901875 0.32933125 0.24076041666666667
                                    0.3678770833333333 0.40412708333333336 0.3323520833333333 1.0334270833333332 -0.05395208333333333 1.41973125Z" stroke-width="1"></path>
                                    <path fill="currentColor" d="M26.679999999999996 1.3049395833333333H2.32c-1.1211520833333333 0 -2.03 0.9089083333333332 -2.03 2.03v1.015c0 1.1212125 0.9088479166666666 2.03 2.03 2.03h24.36c1.1211520833333333
                                    0 2.03 -0.9087875 2.03 -2.03v-1.015c0 -1.1210916666666666 -0.9088479166666666 -2.03 -2.03 -2.03Z" stroke-width="1"></path>
                                </svg>
                                <span class="hidden sm:inline">Resolved</span>
                            </button>
                            <select
                                v-model="filterStatus"
                                class="filter-select"
                            >
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="verified">Verified</option>
                                <option value="completed">Completed</option>
                                <option v-if="isPublicUser" value="resolved">Resolved</option>
                            </select>
                            <button
                                type="button"
                                @click="createStrandedIncidents"
                                class="create-button flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create
                            </button>
                        </div>
                    </div>

                    <!-- Conditional Rendering based on Filtered Status -->
                    <div v-if="filterStatus !== 'all' && groupedIncidents[filterStatus].length > 0">
                        <h3 class="text-base sm:text-lg font-semibold mb-3 capitalize px-2 text-white/80">{{ filterStatus }} Incidents</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div v-for="strandedIncidents in groupedIncidents[filterStatus]"
                                :key="strandedIncidents.id"
                                class="incident-card">
                                <div class="p-3 sm:p-4">
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center justify-between">
                                        <!-- ID and Title -->
                                        <div class="flex flex-row items-center gap-2 sm:gap-3 min-w-0">
                                            <span class="text-xs sm:text-sm font-medium text-white/60 flex-shrink-0">#{{ strandedIncidents.id }}</span>
                                            <h4 class="text-sm sm:text-base font-semibold text-white truncate">{{ strandedIncidents.species_involved }}</h4>
                                            <span :class="{
                                                'status-badge': true,
                                                'status-pending': strandedIncidents.report_status === 'pending',
                                                'status-verified': strandedIncidents.report_status === 'verified',
                                                'status-completed': strandedIncidents.report_status === 'completed',
                                                'status-resolved': strandedIncidents.report_status === 'resolved'
                                            }">
                                                {{ strandedIncidents.report_status }}
                                            </span>
                                        </div>

                                        <!-- View Button -->
                                        <button
                                            @click="viewStrandedIncidents(strandedIncidents.id)"
                                            class="view-button flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                    </div>

                                    <!-- Info Row -->
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center mt-2 sm:mt-3 text-xs sm:text-sm text-white/70">
                                        <!-- Reporter -->
                                        <span class="flex items-center min-w-0">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="truncate">{{ strandedIncidents.user?.first_name }} {{ strandedIncidents.user?.last_name }}</span>
                                        </span>

                                        <!-- Location -->
                                        <span class="flex items-center min-w-0 flex-1">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="truncate">{{ strandedIncidents.detailed_location }}</span>
                                        </span>

                                        <!-- Date/Time -->
                                        <span class="flex items-center whitespace-nowrap flex-shrink-0">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ strandedIncidents.date }} - {{ strandedIncidents.time }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Show No Incidents Message if Filtered Status has No Results -->
                    <div v-if="filterStatus !== 'all' && groupedIncidents[filterStatus].length === 0"
                        class="glass-panel text-center py-10 px-4">
                        <svg class="mx-auto h-10 w-10 sm:h-16 sm:w-16 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg sm:text-xl font-medium text-white">No incidents found</h3>
                        <p class="mt-2 text-sm text-white/60">No incidents match the selected status.</p>
                    </div>

                    <!-- Show All Incidents if 'All' is Selected -->
                    <div v-if="filterStatus === 'all'" class="space-y-6 sm:space-y-8">
                        <div v-for="(incidents, status) in groupedIncidents" :key="status" v-show="incidents.length > 0">
                            <h3 v-if="incidents.length > 0" class="text-base sm:text-lg font-semibold mb-3 capitalize px-2 text-white/80">{{ status }} Incidents</h3>

                            <div class="space-y-2 sm:space-y-3">
                                <div v-for="strandedIncidents in incidents"
                                    :key="strandedIncidents.id"
                                    class="incident-card">
                                    <div class="p-3 sm:p-4">
                                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center justify-between">
                                            <!-- ID and Title -->
                                            <div class="flex flex-row items-center gap-2 sm:gap-3 min-w-0">
                                                <span class="text-xs sm:text-sm font-medium text-white/60 flex-shrink-0">#{{ strandedIncidents.id }}</span>
                                                <h4 class="text-sm sm:text-base font-semibold text-white truncate">{{ strandedIncidents.species_involved }}</h4>
                                                <span :class="{
                                                    'status-badge': true,
                                                    'status-pending': strandedIncidents.report_status === 'pending',
                                                    'status-verified': strandedIncidents.report_status === 'verified',
                                                    'status-completed': strandedIncidents.report_status === 'completed',
                                                    'status-resolved': strandedIncidents.report_status === 'resolved'
                                                }">
                                                    {{ strandedIncidents.report_status }}
                                                </span>
                                            </div>

                                            <!-- View Button -->
                                            <button
                                                @click="viewStrandedIncidents(strandedIncidents.id)"
                                                class="view-button flex items-center gap-1"
                                            >
                                                <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View
                                            </button>
                                        </div>

                                        <!-- Info Row -->
                                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center mt-2 sm:mt-3 text-xs sm:text-sm text-white/70">
                                            <!-- Reporter -->
                                            <span class="flex items-center min-w-0">
                                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="truncate">{{ strandedIncidents.user?.first_name }} {{ strandedIncidents.user?.last_name }}</span>
                                            </span>

                                            <!-- Location -->
                                            <span class="flex items-center min-w-0 flex-1">
                                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="truncate">{{ strandedIncidents.detailed_location }}</span>
                                            </span>

                                            <!-- Date/Time -->
                                            <span class="flex items-center whitespace-nowrap flex-shrink-0">
                                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ strandedIncidents.date }} - {{ strandedIncidents.time }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Ocean theme styling */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.profile-title-gradient {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

/* Glass panels */
.glass-panel {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.incident-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.incident-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.12);
}

/* Buttons */
.action-button, .filter-select, .view-button, .create-button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.action-button {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.filter-select {
    background: rgba(0, 51, 102, 0.5);
    color: white;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.view-button {
    background: rgba(77, 171, 247, 0.2);
    color: #4dabf7;
    border-color: rgba(77, 171, 247, 0.3);
}

.create-button {
    background: linear-gradient(
        135deg,
        rgba(0, 102, 204, 0.9) 0%,
        rgba(0, 153, 255, 0.8) 100%
    );
    color: white;
    border-color: rgba(0, 153, 255, 0.3);
}

.action-button:hover, .view-button:hover, .create-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

.view-button:hover {
    background: rgba(77, 171, 247, 0.3);
}

.create-button:hover {
    background: linear-gradient(
        135deg,
        rgba(0, 153, 255, 0.95) 0%,
        rgba(0, 102, 204, 0.85) 100%
    );
}

/* Status badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-pending {
    background: rgba(255, 107, 107, 0.15);
    color: #ff8f8f;
    border-color: rgba(255, 107, 107, 0.3);
}

.status-verified {
    background: rgba(255, 217, 61, 0.15);
    color: #ffe074;
    border-color: rgba(255, 217, 61, 0.3);
}

.status-completed {
    background: rgba(109, 213, 167, 0.15);
    color: #84e4b8;
    border-color: rgba(109, 213, 167, 0.3);
}

.status-resolved {
    background: rgba(196, 134, 252, 0.15);
    color: #d4a6ff;
    border-color: rgba(196, 134, 252, 0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .action-button, .filter-select, .view-button, .create-button {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .profile-title-gradient {
        font-size: 1.25rem;
    }

    .status-badge {
        padding: 0.125rem 0.5rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .incident-card {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>
