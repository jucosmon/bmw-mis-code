<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    let filtered = props.guidelines;

    if (filterStatus.value === 'active') {
        filtered = filtered.filter((guideline) => guideline.is_active);
    } else if (filterStatus.value === 'inactive') {
        filtered = filtered.filter((guideline) => !guideline.is_active);
    }

    if (filterCategory.value !== 'all') {
        filtered = filtered.filter((guideline) => guideline.category === filterCategory.value);
    }

    return filtered;
});

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
        <template #header>

            <h2 v-if="!props.archived" class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
            <SecondaryButton v-else @click="backRoute">
                Back
            </SecondaryButton>
        </template>

        <!-- Table Container -->
        <div class="container mx-auto px-7 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <div class="flex gap-4">
                    <button
                        :class="{'bg-blue-500 text-white': filterCategory === 'all', 'bg-gray-200': filterCategory !== 'all'}"
                        @click="filterCategory = 'all'"
                        class="px-4 py-2 rounded"
                    >
                        All
                    </button>
                    <button
                        :class="{'bg-blue-500 text-white': filterCategory === 'marine_turtles', 'bg-gray-200': filterCategory !== 'marine_turtles'}"
                        @click="filterCategory = 'marine_turtles'"
                        class="px-4 py-2 rounded"
                    >
                        Marine Turtles
                    </button>
                    <button
                        :class="{'bg-blue-500 text-white': filterCategory === 'marine_mammals', 'bg-gray-200': filterCategory !== 'marine_mammals'}"
                        @click="filterCategory = 'marine_mammals'"
                        class="px-4 py-2 rounded"
                    >
                        Marine Mammals
                    </button>
                    <button
                        :class="{'bg-blue-500 text-white': filterCategory === 'sharks_rays', 'bg-gray-200': filterCategory !== 'sharks_rays'}"
                        @click="filterCategory = 'sharks_rays'"
                        class="px-4 py-2 rounded"
                    >
                        Sharks and Rays
                    </button>

                </div>
                <div class="flex mx-10 gap-4">
                    <div class="flex items-center mr-1">
                        <span class="mr-2">{{ filterStatus === 'active' ? 'Active' : 'Inactive' }}</span>
                        <div
                            @click="toggleActiveInactive"
                            class="w-10 h-7 bg-gray-300 rounded-full flex items-center p-1 cursor-pointer"
                            :class="{ 'bg-green-400': filterStatus === 'active' }"
                        >
                            <div
                                class="bg-white w-5 h-5 rounded-full shadow-md transition-transform duration-300 ease-in-out"
                                :class="{ 'translate-x-3': filterStatus === 'active' }"
                            ></div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="createGuideline"
                        class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        Create
                    </button>
                </div>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left border border-gray-300">ID</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Title</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Status</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Category</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="guideline in paginatedGuidelines" :key="guideline.id">
                            <td class="px-4 py-2 border border-gray-300">{{ guideline.id }}</td>
                            <td class="px-4 py-2 border border-gray-300">{{ guideline.title }}</td>
                            <td class="px-4 py-2 border border-gray-300"> {{ guideline.is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-2 border border-gray-300">{{ guidelinesCategory(guideline.category) }}</td>
                            <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                <button
                                class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900"
                                @click="viewGuideline (guideline.id)"
                                >
                                    View
                                </button>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-center items-center">
                <button
                    v-if="currentPage > 1"
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 mr-2"
                    @click="currentPage--"
                >
                    Previous
                </button>

                <span v-for="page in totalPages" :key="page" class="mx-2">
                    <button
                        class="px-4 py-2 rounded-full"
                        :class="{
                            'bg-blue-500 text-white': currentPage === page,
                            'bg-gray-200 hover:bg-gray-300': currentPage !== page,
                        }"
                        @click="currentPage = page"
                    >
                        {{ page }}
                    </button>
                </span>

                <button
                    v-if="currentPage < totalPages"
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 ml-2"
                    @click="currentPage++"
                >
                    Next
                </button>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Ensure the table scrolls horizontally on smaller screens */
@media (max-width: 640px) {
  .overflow-x-auto {
    overflow-x: auto;
  }

  table {
    width: 100%;
    min-width: 800px; /* You can adjust this according to your data */
  }

  th, td {
    padding: 0.75rem;
    text-align: center;
  }

  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
