<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    sightings: Array,
    success: String,
});

// Button routes
const createSightings = () => {
    Inertia.get(route('sighting.createPage'));
};
const viewSighting = (id) => {
    Inertia.visit(route('sighting.view', { id }));
};
const verifiedSightingsButton = () => {
    Inertia.visit(route('sighting.verified.index'));
}

</script>

<template>
    <Head title="Stranded Incidents" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Sightings
            </h2>
        </template>

        <div class="container mx-auto px-7 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">Pending Sightings List</h2>
                <div class="flex gap-3">
                    <button @click="verifiedSightingsButton" class="cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-0.5 -0.5 30 30" id="Archive--Streamline-Ionic-Filled" height="30" width="30">
                            <desc>Archive Streamline Icon: https://streamlinehq.com</desc>
                            <path fill="#1A237E" d="M2.31873125 8.663810416666665v15.47875c0 0.9421374999999999 0.37428125 1.845789583333333 1.0404958333333332
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
                            <path fill="#1A237E" d="M26.679999999999996 1.3049395833333333H2.32c-1.1211520833333333 0 -2.03 0.9089083333333332 -2.03 2.03v1.015c0 1.1212125 0.9088479166666666 2.03 2.03 2.03h24.36c1.1211520833333333
                            0 2.03 -0.9087875 2.03 -2.03v-1.015c0 -1.1210916666666666 -0.9088479166666666 -2.03 -2.03 -2.03Z" stroke-width="1"></path>
                        </svg>
                    </button>'
                    <button
                        type="button"
                        @click="createSightings"
                        class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        Create
                    </button>
                </div>
            </div>

            <div>
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
                            <tr v-for="sighting in props.sightings" :key="sighting.id">
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

            <!-- Show No Active Sightings Message if Filtered Status has No Results -->
            <div v-if = "props.sightings.length === 0" class="text-center mt-4">
                <p class="text-lg text-gray-600">No pending sightings.</p>
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
