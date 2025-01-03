<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    category: String,
    species: Array,
    message: String,
});

const speciesCategory = computed(() => {
    switch (props.category) {
        case 'marine_turtles':
            return 'Marine Turtles';
        case 'marine_mammals':
            return 'Marine Mammals';
        case 'sharks_rays':
            return 'Sharks and Rays';
        default:
            return 'Unknown Category';
    }
});
const title = computed(() => `Manage Species (${speciesCategory.value})`);

const PER_PAGE = 5; // Number of items per page
const currentPage = ref(1); // Initialize current page
const filterStatus = ref('active'); // Default filter

// Filter species based on the selected status
const filteredSpecies = computed(() => {
    if (filterStatus.value === 'active') {
        return props.species.filter((species) => species.is_active);
    } else if (filterStatus.value === 'inactive') {
        return props.species.filter((species) => !species.is_active);
    } else {
        return props.species; // 'all'
    }
});

// Paginate the filtered species
const paginatedSpecies = computed(() => {
    const startIndex = (currentPage.value - 1) * PER_PAGE;
    const endIndex = startIndex + PER_PAGE;
    return filteredSpecies.value.slice(startIndex, endIndex);
});

// Update pagination calculations
const totalPages = computed(() => Math.ceil(filteredSpecies.value.length / PER_PAGE));

const hasMorePages = computed(() => filteredSpecies.value.length > PER_PAGE * currentPage.value);

// Button routes
const createSpecies = () => {
    Inertia.get(route('bpemo.admin.manage.species.create.page', { category: props.category }));
};
const viewSpecies = (id) => {
    Inertia.visit(route('bpemo.admin.manage.species.view', { id }));
};
</script>


<template>
    <Head title="Manage Species" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <!-- Table Container -->
        <div class="container mx-auto px-7 py-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">{{ speciesCatagory }} List</h2>
                <div class="flex mx-10 gap-4">
                    <select
                            v-model="filterStatus"
                            class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 w-auto pr-8"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="all">All</option>
                        </select>
                    <button
                        type="button"
                        @click="createSpecies"
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
                            <th class="px-4 py-2 text-left border border-gray-300">Name</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Status</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="species in paginatedSpecies" :key="species.id">
                            <td class="px-4 py-2 border border-gray-300">{{ species.id }}</td>
                            <td class="px-4 py-2 border border-gray-300">{{ species.name }}</td>
                            <td class="px-4 py-2 border border-gray-300"> {{ species.is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                <button
                                class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900"
                                @click="viewSpecies (species.id)"
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
