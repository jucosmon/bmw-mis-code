<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';


const props = defineProps({
    sightings: Array,
    success: String,
});

const filterStatus = ref('verified'); // Default filter is "all"

// Filter Sightings based on the selected status
const filteredSightings = computed(() => {
    return props.sightings.filter(incident => incident.report_status === filterStatus.value);
});

// Group stranded incidents by status
    const groupedIncidents = computed(() => {
    return {
        false: filteredSightings.value.filter(incident => incident.report_status === 'false'),
        verified: filteredSightings.value.filter(incident => incident.report_status === 'verified'),
    };
});

// Button routes
const createSightings = () => {
    Inertia.get(route('sighting.createPage'));
};

const viewSighting = (id) => {
    Inertia.visit(route('sighting.view', { id }));
};

const status = computed(() => {
  return filterStatus.value === 'verified' ? 'Verified' : 'False';
});

const backRoute = computed(() => {
    return route('sighting.index');
});


</script>

<template>
    <Head title="Stranded Incidents" />
    <Sidebar>
        <template #header>
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-700 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                <Link :href="backRoute" class="flex items-center">
                    Back
                </Link>
            </button>
        </template>

        <div class="container mx-auto px-7 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">{{ status }} Sightings List</h2>
                <div class="flex gap-3">
                    <select
                        v-model="filterStatus"
                        class=" border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="verified">Verified</option>
                        <option value="false">False</option>
                    </select>
                </div>
            </div>

            <div v-if="filterStatus !== 'all' && groupedIncidents[filterStatus] && groupedIncidents[filterStatus].length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 text-left border border-gray-300">ID</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Date & Time</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Species Involved</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Status</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sighting in groupedIncidents[filterStatus]" :key="sighting.id">
                                <td class="px-4 py-2 border border-gray-300">{{ sighting.id }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ sighting.date }} - {{ sighting.time }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ sighting.species_involved }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ sighting.report_status }}</td>
                                <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                    <button
                                        class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900"
                                        @click="viewSighting(sighting.id)"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Show No Incidents Message if Filtered Status has No Results -->
            <div v-if="filterStatus !== 'all' && groupedIncidents[filterStatus].length === 0" class="text-center mt-4">
                <p class="text-lg text-gray-600">No incidents for the selected status.</p>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Sidebar and Content Styles */
.sidebar {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.sidebar-content {
    flex-grow: 1;
    overflow-y: auto;
}

/* Main Content Styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

/* Table Styles */
table {
    width: 100%;
    margin-top: 1rem;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #f7fafc;
}

button {
    transition: background-color 0.2s ease;
}

button:hover {
    background-color: #2c5282;
}

/* Select and Button Styles */
select, button {
    border-radius: 5px;
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
/* Media Queries for Smaller Screens */
@media (max-width: 640px) {
  .overflow-x-auto {
    overflow-x: auto;
  }

  table {
    width: 100%;
    min-width: 800px;
  }

  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
}


</style>
