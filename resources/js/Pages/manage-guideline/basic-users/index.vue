<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    guidelines: Array,
});

const guidelinesCategory = (category) => {
    switch (category) {
        case 'marine_turtles':
            return 'Marine Turtles';
        case 'marine_mammals':
            return 'Marine Mammals';
        case 'sharks_rays':
            return 'Sharks and Rays';
        default:
            return 'Unknown Category';
    }
};

const guidelinesRole = computed(() => {
    switch (page.props.auth.user.user_role) {
        case 'lgu_responder':
            return 'LGU Responder';
        case 'barangay_official':
            return 'Barangay Official';
        case 'public_user':
            return 'Public User';
        default:
            return 'Invalid Role';
    }
});
const title = computed(() => `Guidelines (${guidelinesRole.value})`);

const filterStatus = ref('all'); // Default filter

// Filter guideline based on the selected status
const filteredGuidelines = computed(() => {
    if (filterStatus.value === 'marine_turtles') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_turtles');
    } else if (filterStatus.value === 'marine_mammals') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_mammals');
    } else if (filterStatus.value === 'sharks_rays') {
        return props.guidelines.filter((guideline) => guideline.category === 'sharks_rays');
    } else {
        return props.guidelines; // 'all'
    }
});

// Button routes
const viewGuideline = (id) => {
    return router.visit(route('guideline.view', { id }));
};
</script>

<template>
    <Head title="Guidelines" />
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
                    <!-- Header section -->
                    <div class="mb-6">
                        <h3 class="profile-title-gradient text-center mb-4">
                            {{ title }}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="filterStatus = 'all'"
                                    :class="[
                                        'filter-button',
                                        filterStatus === 'all' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    All
                                </button>
                                <button
                                    @click="filterStatus = 'marine_turtles'"
                                    :class="[
                                        'filter-button',
                                        filterStatus === 'marine_turtles' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Marine Turtles
                                </button>
                                <button
                                    @click="filterStatus = 'marine_mammals'"
                                    :class="[
                                        'filter-button',
                                        filterStatus === 'marine_mammals' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Marine Mammals
                                </button>
                                <button
                                    @click="filterStatus = 'sharks_rays'"
                                    :class="[
                                        'filter-button',
                                        filterStatus === 'sharks_rays' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Sharks and Rays
                                </button>
                            </div>
                        </div>

                        <!-- Guidelines List -->
                        <div class="space-y-3 mx-4">
                            <div v-for="guideline in filteredGuidelines" :key="guideline.id" class="incident-card ">
                                <div class="p-3 sm:p-4">
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center justify-between">
                                        <!-- Title and Category -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm sm:text-base font-semibold text-white truncate max-w-[300px]">
                                                {{ guideline.title }}
                                            </h4>
                                            <span class="text-xs sm:text-sm font-medium text-white/60 mt-1 block">
                                                {{ guidelinesCategory(guideline.category) }}
                                            </span>
                                        </div>

                                        <!-- View Button (hidden on mobile) -->
                                        <button
                                            @click="viewGuideline(guideline.id)"
                                            class="view-button hidden sm:flex sm:items-center sm:gap-1 flex-shrink-0"
                                        >
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                    </div>

                                    <!-- View Button (only on mobile, at bottom) -->
                                    <div class="mt-3 sm:hidden">
                                        <button
                                            @click="viewGuideline(guideline.id)"
                                            class="view-button w-full flex items-center justify-center gap-1"
                                        >
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Guideline
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Guidelines Message -->
                        <div v-if="filteredGuidelines.length === 0" class="glass-panel text-center py-10 px-4">
                            <svg class="mx-auto h-10 w-10 sm:h-16 sm:w-16 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg sm:text-xl font-medium text-white">No guidelines found</h3>
                            <p class="mt-2 text-sm text-white/60">No guidelines match the selected category.</p>
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
.filter-button, .view-button {
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

.filter-button:hover, .view-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

.view-button:hover {
    background: rgba(77, 171, 247, 0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .filter-button, .view-button {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .profile-title-gradient {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .incident-card {
        margin: 0.5rem 0;
    }
}

@media (max-width: 480px) {
    .incident-card {
        margin-left: -1rem;
        margin-right: -1rem;
        border-radius: 0;
    }
}
</style>
