<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    user_role: String,
    guidelines: Array,
    success: String,
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
const filterStatus = ref('all'); // Default filter

// Filter guideline based on the selected status
const filteredGuidelines = computed(() => {
    if (filterStatus.value === 'marine_turtles') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_turtles');
    } else if (filterStatus.value === 'marine_mammals') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_mammals'); // Fixed this line
    } else if (filterStatus.value === 'sharks_rays') {
        return props.guidelines.filter((guideline) => guideline.category === 'sharks_rays'); // Fixed this line
    } else {
        return props.guidelines; // 'all'
    }
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
    Inertia.get(route('manage.guideline.createPage', { user_role: props.user_role }));
};
const viewGuideline = (id) => {
    Inertia.visit(route('manage.guideline.view', { id }));
};
</script>


<template>
    <Head title="Manage Guideline" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <!-- Table Container -->
        <div class="container mx-auto px-7 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">{{ guidelinesRole }} List</h2>
                <div class="flex mx-10 gap-4">
                    <select
                            v-model="filterStatus"
                            class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 w-auto pr-8"
                        >
                            <option value="marine_turtles">Marine Turtles</option>
                            <option value="marine_mammals">Marine Mammals</option>
                            <option value="sharks_rays">Shark and Rays</option>
                            <option value="all">All</option>
                        </select>
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
