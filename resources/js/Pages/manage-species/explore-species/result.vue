<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const species = page.props.species || [];

const viewSpecies = (id) => {
    Inertia.visit(route('explore.species.view', { id }));
};
</script>
<template>
    <Head title="Search Results" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Search Results
            </h2>
        </template>

        <div class="container mx-auto px-6 py-8">
            <div v-if="species.length === 0" class="text-center text-gray-700">
                <p>No species found for the given criteria.</p>
            </div>
            <div v-else>
                <h3 class="text-lg font-semibold mb-4">Search Results</h3>
                <div class="space-y-4 mx-10">
                    <div v-for="species in species" :key="species.id" class="p-4 bg-gray-50 rounded-lg shadow-sm flex justify-between">
                        <h4 class="text-md font-semibold text-gray-700">{{ species.name }}</h4>
                        <button
                            @click="viewSpecies(species.id)"
                            class="mt-2 px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>
<style scoped>
/* Add any necessary styles here */
</style>
