<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';


const page = usePage();
const props = defineProps({
    sightings: Array,
    success: String,
});

// Button routes
const createSightings = () => {
    router.get(route('sighting.createPage'));
};
const viewSighting = (id) => {
    router.visit(route('sighting.view', { id }));
};

// Filter status
const filterStatus = ref('pending'); // Default filter is "Pending"

// Filter Sightings based on the selected status
const filteredSightings = computed(() => {
    return props.sightings.filter(sighting => sighting.report_status === filterStatus.value);
});

// Determine button classes based on filter status
const getButtonClasses = (status) => {
    return filterStatus.value === status
        ? 'filter-button-active'
        : 'filter-button';
};

// Update the getSpeciesNames function
const getSpeciesNames = (sighting) => {
    if (!sighting.sighted_species || sighting.sighted_species.length === 0) {
        return 'Unknown';
    }
    return sighting.sighted_species
        .map(sightedSpecies => sightedSpecies.species?.name || 'Unknown')
        .filter(name => name !== 'Unknown')
        .join(', ') || 'Unknown';
};

</script>

<template>
    <Head title="Marine Wildlife Sightings" />
    <Sidebar>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Success message -->
                    <div v-if="props?.success" class="glass-panel mb-6 p-4 border border-green-400/30 text-green-400">
                        <strong class="font-bold">Success! </strong>
                        <span>{{ props?.success}}</span>
                    </div>

                    <!-- Header section -->
                    <div class="mb-6 text-center">
                        <h3 class="profile-title-gradient mb-5">
                            Marine Wildlife Sightings
                        </h3>
                        <div class="flex flex-wrap justify-between items-center gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button @click="() => filterStatus = 'pending'" :class="getButtonClasses('pending')">
                                    Pending
                                </button>
                                <button @click="() => filterStatus = 'verified'" :class="getButtonClasses('verified')">
                                    Verified
                                </button>
                                <button @click="() => filterStatus = 'false'" :class="getButtonClasses('false')">
                                    False
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="createSightings"
                                class="create-button flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create
                            </button>
                        </div>
                    </div>

                    <!-- Sightings List -->
                    <div v-if="filteredSightings.length > 0">
                        <h3 class="text-base sm:text-lg font-semibold mb-3 capitalize px-2 text-white/80">{{ filterStatus }} Sightings</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <div v-for="sighting in filteredSightings"
                                 :key="sighting.id"
                                 class="incident-card">
                                <div class="p-3 sm:p-4">
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center justify-between">
                                        <!-- ID and Title -->
                                        <div class="flex flex-row items-center gap-2 sm:gap-3 min-w-0">
                                            <span class="text-xs sm:text-sm font-medium text-white/60 flex-shrink-0">#{{ sighting.id }}</span>
                                            <h4 class="text-sm sm:text-base font-semibold text-white truncate">{{ getSpeciesNames(sighting) }}</h4>
                                            <span :class="{
                                                'status-badge': true,
                                                'status-pending': sighting.report_status === 'pending',
                                                'status-verified': sighting.report_status === 'verified',
                                                'status-false': sighting.report_status === 'false'
                                            }">
                                                {{ sighting.report_status }}
                                            </span>
                                        </div>

                                        <!-- View Button (hidden on mobile, visible on sm+) -->
                                        <button
                                            @click="viewSighting(sighting.id)"
                                            class="view-button hidden sm:flex sm:items-center sm:gap-1"
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
                                            <span class="truncate">{{ sighting.user?.first_name }} {{ sighting.user?.last_name }}</span>
                                        </span>

                                        <!-- Location information if available -->
                                        <span class="flex items-center min-w-0 flex-1">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="truncate">
                                                {{ sighting.detailed_location || sighting.location || (sighting.barangay?.name ?
                                                   (sighting.barangay.name + (sighting.municipality?.name ? ', ' + sighting.municipality.name : '')) :
                                                   (sighting.municipality?.name || 'Location not specified')) }}
                                            </span>
                                        </span>

                                        <!-- Date/Time -->
                                        <span class="flex items-center whitespace-nowrap flex-shrink-0">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ sighting.date }} - {{ sighting.time }}
                                        </span>
                                    </div>

                                    <!-- View Button (only on mobile, at bottom) -->
                                    <div class="mt-3 sm:hidden">
                                        <button
                                            @click="viewSighting(sighting.id)"
                                            class="view-button w-full flex items-center justify-center gap-1"
                                        >
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Sighting
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Show No Sightings Message -->
                    <div v-if="filteredSightings.length === 0"
                         class="glass-panel text-center py-10 px-4">
                        <svg class="mx-auto h-10 w-10 sm:h-16 sm:w-16 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg sm:text-xl font-medium text-white">No sightings found</h3>
                        <p class="mt-2 text-sm text-white/60">No sightings match the selected status.</p>
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
    font-size: 2rem;
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
.filter-button, .filter-button-active, .view-button, .create-button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filter-button {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.filter-button-active {
    background: linear-gradient(
        135deg,
        rgba(0, 102, 204, 0.9) 0%,
        rgba(0, 153, 255, 0.8) 100%
    );
    color: white;
    border-color: rgba(0, 153, 255, 0.3);
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

.filter-button:hover, .view-button:hover, .create-button:hover {
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

.status-false {
    background: rgba(165, 165, 165, 0.15);
    color: #cccccc;
    border-color: rgba(165, 165, 165, 0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .filter-button, .filter-button-active, .view-button, .create-button {
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
