<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const props = defineProps({
    user_role: String,
    guidelines: Array,
    success: String,
    archived: Boolean,
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
    switch (props.user_role) {
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
const title = computed(() => `Manage Guidelines (${guidelinesRole.value})`);

const PER_PAGE = 5; // Number of items per page
const currentPage = ref(1); // Initialize current page
const filterStatus = ref('active'); // Default filter to 'active'
const filterCategory = ref('all'); // Default filter to 'all'

// Filter guideline based on the selected status and category
const filteredGuidelines = computed(() => {
    return props.guidelines.filter(guideline => {
        const statusMatch = filterStatus.value === 'active'
            ? guideline.is_active
            : !guideline.is_active;

        const categoryMatch = filterCategory.value === 'all'
            || guideline.category === filterCategory.value;

        return statusMatch && categoryMatch;
    });
});

// Watch for filter changes and reset pagination
watch([filterStatus, filterCategory], () => {
    currentPage.value = 1;
}, { immediate: true });

// Paginate the filtered guideline
const paginatedGuidelines = computed(() => {
    const startIndex = (currentPage.value - 1) * PER_PAGE;
    const endIndex = startIndex + PER_PAGE;
    return filteredGuidelines.value.slice(startIndex, endIndex);
});

// Update pagination calculations
const totalPages = computed(() => Math.ceil(filteredGuidelines.value.length / PER_PAGE));

const hasMorePages = computed(() => filteredGuidelines.value.length > PER_PAGE * currentPage.value);

// Button routes
const createGuideline = () => {
    return router.visit(route('manage.guideline.createPage', { user_role: props.user_role }));
};

const viewGuideline = (id) => {
    return router.visit(route('manage.guideline.view', { id }));
};

const archivedButton = () => {
    return router.get(route('manage.guideline.index', { user_role: props.user_role, archived: true }));
};

const backRoute = () => {
    return router.get(route('manage.guideline.index', { user_role: props.user_role, archived: false }));
};

// Toggle between active and inactive guidelines
const toggleActiveInactive = () => {
    filterStatus.value = filterStatus.value === 'active' ? 'inactive' : 'active';
};

</script>


<template>
    <Head title="Manage Guideline" />
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
                        <h3 class="profile-title-gradient">
                            {{ title }}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="filterCategory = 'all'"
                                    :class="[
                                        'filter-button',
                                        filterCategory === 'all' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    All
                                </button>
                                <button
                                    @click="filterCategory = 'marine_turtles'"
                                    :class="[
                                        'filter-button',
                                        filterCategory === 'marine_turtles' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Marine Turtles
                                </button>
                                <button
                                    @click="filterCategory = 'marine_mammals'"
                                    :class="[
                                        'filter-button',
                                        filterCategory === 'marine_mammals' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Marine Mammals
                                </button>
                                <button
                                    @click="filterCategory = 'sharks_rays'"
                                    :class="[
                                        'filter-button',
                                        filterCategory === 'sharks_rays' ? 'filter-button-active' : ''
                                    ]"
                                >
                                    Sharks and Rays
                                </button>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex items-center">
                                    <span class="text-white mr-2">{{ filterStatus === 'active' ? 'Active' : 'Inactive' }}</span>
                                    <div
                                        @click="toggleActiveInactive"
                                        class="w-10 h-6 rounded-full flex items-center p-1 cursor-pointer transition-colors duration-300"
                                        :class="{ 'bg-green-400/50': filterStatus === 'active', 'bg-gray-300/30': filterStatus !== 'active' }"
                                    >
                                        <div
                                            class="bg-white w-4 h-4 rounded-full shadow-md transition-transform duration-300 ease-in-out"
                                            :class="{ 'translate-x-4': filterStatus === 'active' }"
                                        ></div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="createGuideline"
                                    class="create-button flex items-center gap-1"
                                >
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create
                                </button>
                            </div>
                        </div>

                        <!-- Guidelines List -->
                        <div class="space-y-3">
                            <div v-for="guideline in paginatedGuidelines" :key="guideline.id" class="incident-card">
                                <div class="p-3 sm:p-4">
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                                        <!-- Title and Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex gap-2">
                                                <span class="text-xs text-white/70">#{{ guideline.id }}</span>
                                                    <h4 class="text-sm sm:text-base font-semibold text-white truncate max-w-[300px]">
                                                    {{ guideline.title }}
                                                </h4>
                                            </div>
                                            <div class="mt-2 flex gap-4">
                                                <span class="text-xs sm:text-sm font-medium text-white/60 mt-1">
                                                    {{ guidelinesCategory(guideline.category) }}
                                                </span>
                                                <span :class="{
                                                        'status-badge': true,
                                                        'status-active': guideline.is_active,
                                                        'status-inactive': !guideline.is_active
                                                    }">
                                                    {{ guideline.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- View Button (hidden on mobile) -->
                                        <button
                                            @click="viewGuideline(guideline.id)"
                                            class="view-button hidden sm:flex sm:max-h-10 sm:items-center sm:gap-1"
                                        >
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                    </div>

                                    <!-- View Button (mobile only) -->
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
                        <div v-if="paginatedGuidelines.length === 0" class="text-center py-10 px-4">
                            <svg class="mx-auto h-10 w-10 sm:h-16 sm:w-16 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg sm:text-xl font-medium text-white">No guidelines found</h3>
                            <p class="mt-2 text-sm text-white/60">No guidelines match the selected filters.</p>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex justify-center items-center">
                            <button
                                v-if="currentPage > 1"
                                class="action-button mr-2"
                                @click="currentPage--"
                            >
                                Previous
                            </button>

                            <div class="flex gap-2">
                                <button
                                    v-for="page in totalPages"
                                    :key="page"
                                    class="pagination-button"
                                    :class="{'pagination-active': currentPage === page}"
                                    @click="currentPage = page"
                                >
                                    {{ page }}
                                </button>
                            </div>

                            <button
                                v-if="currentPage < totalPages"
                                class="action-button ml-2"
                                @click="currentPage++"
                            >
                                Next
                            </button>
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
    margin-bottom: 2rem;
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

/* Incident card */
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
.action-button, .filter-button, .view-button, .create-button, .pagination-button {
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

.pagination-button {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 9999px;
}

.pagination-active {
    background: linear-gradient(
        135deg,
        rgba(0, 102, 204, 0.9) 0%,
        rgba(0, 153, 255, 0.8) 100%
    );
    color: white;
    border-color: rgba(0, 153, 255, 0.3);
}

.action-button:hover, .filter-button:hover, .view-button:hover, .create-button:hover, .pagination-button:hover {
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

.status-active {
    background: rgba(109, 213, 167, 0.15);
    color: #84e4b8;
    border-color: rgba(109, 213, 167, 0.3);
}

.status-inactive {
    background: rgba(165, 165, 165, 0.15);
    color: #cccccc;
    border-color: rgba(165, 165, 165, 0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .action-button, .filter-button, .view-button, .create-button, .pagination-button {
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

    .pagination-button {
        width: 2rem;
        height: 2rem;
    }
}

@media (max-width: 480px) {
    table {
        font-size: 0.75rem;
    }

    th, td {
        padding: 0.5rem 0.75rem;
    }
}
</style>
