<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    strandedIncidents: Array,
});

const filterStatus = ref('resolved'); // Default filter is "all"

// Filter strandedIncidents based on the selected status
const filteredStrandedIncidents = computed(() => {
    return props.strandedIncidents.filter(incident => incident.report_status === filterStatus.value);
});

// Group stranded incidents by status
const groupedIncidents = computed(() => {
    return {
        false: filteredStrandedIncidents.value.filter(incident => incident.report_status === 'false'),
        resolved: filteredStrandedIncidents.value.filter(incident => incident.report_status === 'resolved'),
    };
});


const viewStrandedIncidents = (id) => {
    Inertia.visit(route('stranded.incident.view', { id }));
};

const status = computed(() => {
  return filterStatus.value === 'resolved' ? 'Resolved' : 'False';
});

const backRoute = computed(() => {
    return route('stranded.incident.index');
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

            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">{{ status }} Stranded Incident List</h2>
                <div class="flex gap-3">

                    <select
                        v-model="filterStatus"
                        class=" border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="resolved">Resolved</option>
                        <option value="false">False</option>
                    </select>
                </div>
            </div>

            <!-- Conditional Rendering based on Filtered Status -->
            <div v-if="filterStatus !== 'all' && groupedIncidents[filterStatus].length > 0">
                <h3 class="text-lg font-semibold mt-4 capitalize">{{ filterStatus }} Incidents</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 text-left border border-gray-300">ID</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Date & Time</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Species Involved</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Report Status</th>
                                <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="strandedIncidents in groupedIncidents[filterStatus]" :key="strandedIncidents.id">
                                <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.id }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.date }} - {{ strandedIncidents.time }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.species_involved }}</td>
                                <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.report_status }}</td>
                                <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                    <button
                                        class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900"
                                        @click="viewStrandedIncidents(strandedIncidents.id)"
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

            <!-- Show All Incidents if 'All' is Selected -->
            <div v-if="filterStatus === 'all'">
                <div v-for="(incidents, status) in groupedIncidents" :key="status">
                    <h3 v-if="incidents.length > 0" class="text-lg font-semibold mt-4 capitalize">{{ status }} Incidents</h3>
                    <div class="overflow-x-auto" v-if="incidents.length > 0">
                        <table class="min-w-full table-auto border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 text-left border border-gray-300">ID</th>
                                    <th class="px-4 py-2 text-left border border-gray-300">Date & Time</th>
                                    <th class="px-4 py-2 text-left border border-gray-300">Species Involved</th>
                                    <th class="px-4 py-2 text-left border border-gray-300">Report Status</th>
                                    <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="strandedIncidents in incidents" :key="strandedIncidents.id">
                                    <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.id }}</td>
                                    <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.date }} - {{ strandedIncidents.time }}</td>
                                    <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.species_involved }}</td>
                                    <td class="px-4 py-2 border border-gray-300">{{ strandedIncidents.report_status }}</td>
                                    <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                        <button
                                            class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900"
                                            @click="viewStrandedIncidents(strandedIncidents.id)"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
