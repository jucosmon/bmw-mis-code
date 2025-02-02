<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const props = defineProps({
    topSpecies: Array,
    categories: Array,
    success: String,
});

const searchQuery = ref('');
const selectedCategory = ref(null);

const searchSpecies = () => {
    Inertia.get(route('explore.species.search'), { query: searchQuery.value });
};

const viewSpecies = (id) => {
    Inertia.visit(route('explore.species.view', { id }));
};

const identifySpecies = () => {
    Inertia.visit(route('explore.species.identify'));
};

const selectCategory = (category) => {
    selectedCategory.value = category;
    Inertia.get(route('explore.species.category', { category }));
};
</script>

<template>
    <Head title="Explore Marine Wildlife Species" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Explore Marine Wildlife Species
            </h2>
        </template>

        <div class="container mx-auto px-6 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success }}</span>
            </div>

            <div class="mb-6">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search species..."
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                    @click="searchSpecies"
                    class="mt-2 px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Search
                </button>
            </div>

            <div class="mb-6">
                <button
                    @click="identifySpecies"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    Identify Species
                </button>
            </div>

            <div class="mb-6 flex flex-wrap gap-4">
                <button
                    v-for="category in props.categories"
                    :key="category"
                    @click="selectCategory(category)"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ category }}
                </button>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Top 5 Species Commonly Involved in Sightings</h3>
                <div class="space-y-4">
                    <div v-for="species in props.topSpecies" :key="species.id" class="p-4 bg-gray-50 rounded-lg shadow-sm">
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
