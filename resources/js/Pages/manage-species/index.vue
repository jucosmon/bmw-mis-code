<script setup>
import Modal from '@/Components/Modal.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    species: Array,
    categories: Array,
    success: String,
    colors: Array,
    userRole: String,
});

const selectedCategory = ref('all'); // Default filter
const showActive = ref(true); // Default to showing active species

// State for the search query and selected category
const searchQuery = ref('');

// State for the Identify Species modal
const showIdentifyModal = ref(false);
const identifyForm = ref({
    colors: [],
    size: '',
    shape: '',
    dangerToHumans: false,
    category: '',
});

// Filter species based on the selected category, search query, and active/inactive status
const filteredSpecies = computed(() => {
    let speciesList = props.species.filter(species => species.is_active === showActive.value);

    if (selectedCategory.value !== 'all') {
        speciesList = speciesList.filter((species) => species.category === selectedCategory.value);
    }

    if (searchQuery.value.trim() !== '') {
        speciesList = speciesList.filter((species) => species.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }

    return speciesList.sort((a, b) => a.name.localeCompare(b.name));
});

// Methods for navigation and actions
const searchSpecies = () => {
    if (searchQuery.value.trim() === '') return;
    Inertia.get(route('species.search'), { query: searchQuery.value, searchType: 'name' });
};

const selectCategory = (category) => {
    selectedCategory.value = category;
};

// Methods for the Identify Species modal
const openIdentifyModal = () => {
    showIdentifyModal.value = true;
};

const closeIdentifyModal = () => {
    showIdentifyModal.value = false;
    resetIdentifyForm();
};

const resetIdentifyForm = () => {
    identifyForm.value = { colors: [], size: '', shape: '', dangerToHumans: false, category: '' };
    selectedColors.value = [];
};

// colors
const selectedColors = ref([]);

const addColor = (event) => {
    const selectedColor = event.target.value;
    if (selectedColor && !selectedColors.value.includes(selectedColor)) {
        selectedColors.value.push(selectedColor);
        identifyForm.value.colors.push(selectedColor); // Corrected reference
        document.getElementById("colors").value = "";
    }
};

const removeColor = (color) => {
    selectedColors.value = selectedColors.value.filter(c => c !== color);
    identifyForm.value.colors = identifyForm.value.colors.filter(c => c !== color); // Corrected reference
};

const submitIdentifyForm = () => {
    if (selectedColors.value.length === 0 && !identifyForm.value.size && !identifyForm.value.shape && !identifyForm.value.dangerToHumans && !identifyForm.value.category) return;
    identifyForm.value.colors = selectedColors.value;
    Inertia.get(route('explore.species.search'), { ...identifyForm.value, searchType: 'attributes' });
};

const categoryText = (category) => {
    switch(category){
        case 'marine_turtles': return 'Marine Turtles';break;
        case 'marine_mammals': return 'Marine Mammals'; break;
        case 'sharks_rays': return 'Shark and Rays';break;
        default: return 'Unknown Category';
    }
}

// Button routes
const createSpecies = () => {
    Inertia.get(route('bpemo.admin.manage.species.create.page'));
};
const viewSpecies = (id) => {
    Inertia.visit(route('species.view', { id }));
};

// Toggle between active and inactive species
const toggleActiveInactive = () => {
    showActive.value = !showActive.value;
};
</script>

<template>
    <Head title="Manage Species" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Marine Wildlife Species
            </h2>
        </template>

        <div class="container mx-auto px-6 py-8">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success }}</span>
            </div>

            <div class="mb-6 flex gap-2">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search species..."
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                    @click="searchSpecies"
                    class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Search
                </button>
                <button
                    @click="openIdentifyModal"
                    class="px-4 py-2 bg-green-600 text-sm text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    Identify Species
                </button>
                <button
                    v-if="page.props.auth.user.user_role === 'bpemo_admin'"
                    type="button"
                    @click="createSpecies()"
                    class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    Create
                </button>

            </div>

            <div class="mb-6 flex flex-wrap gap-4 justify-center">
                <button
                    @click="selectCategory('all')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    All
                </button>
                <button
                    v-for="category in props.categories"
                    :key="category"
                    @click="selectCategory(category)"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ categoryText(category) }}
                </button>
                <button
                    v-if="page.props.auth.user.user_role === 'bpemo_admin'"
                    type="button"
                    @click="toggleActiveInactive"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ showActive ? 'Show Inactive' : 'Show Active' }}
                </button>
            </div>

            <div>
                <div class="space-y-4 mx-10">
                    <div v-for="species in filteredSpecies" :key="species.id" class="p-4 bg-gray-50 rounded-lg shadow-sm flex justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700">{{ species.name }}</h4>
                            <p class="text-sm font-bold text-green-700">{{ categoryText(species.category) }}</p>
                        </div>
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
        <Modal :show="showIdentifyModal" @close="closeIdentifyModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800">Identify Species</h2>
                <form @submit.prevent="submitIdentifyForm" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-gray-700">Colors</label>
                        <select id="colors" @change="addColor" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="" disabled selected>Choose a color</option>
                            <option v-for="color in props.colors" :key="color.name" :value="color.name">
                                {{ color.name }}
                            </option>
                        </select>
                        <div v-if="selectedColors.length" class="flex flex-wrap gap-2 mt-3">
                            <div v-for="color in selectedColors" :key="color" class="flex items-center space-x-2 bg-gray-200 px-3 py-1 rounded-lg">
                                <div :style="{ backgroundColor: color }" class="w-6 h-6 rounded-full"></div>
                                <span>{{ color }}</span>
                                <button @click="removeColor(color)" class="text-red-600 hover:text-red-800 font-bold">X</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700">Size</label>
                        <input v-model="identifyForm.size" type="text" class="mt-1 block w-full px-3 py-2 border rounded" placeholder="Enter size" />
                    </div>
                    <div>
                        <label class="block text-gray-700">Shape</label>
                        <select v-model="identifyForm.shape" class="mt-1 block w-full px-3 py-2 border rounded">
                            <option value="" disabled>Select shape</option>
                            <option value="turtle-like">Turtle-like</option>
                            <option value="shark-like">Shark-like</option>
                            <option value="dolphin-like">Dolphin-like</option>
                            <option value="dugong-like">Dugong-like</option>
                            <option value="whale-like">Whale-like</option>
                            <option value="ray-like">Ray-like</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input v-model="identifyForm.dangerToHumans" type="checkbox" class="mr-2" />
                            Danger to Humans
                        </label>
                    </div>
                    <div>
                        <label class="block text-gray-700">Category</label>
                        <select v-model="identifyForm.category" class="mt-1 block w-full px-3 py-2 border rounded">
                            <option value="" disabled>Select category</option>
                            <option v-for="category in props.categories" :key="category" :value="category">{{ category }}</option>
                        </select>
                    </div>
                    <div class="flex justify-between">
                        <button type="button" @click="resetIdentifyForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Reset</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Show</button>
                    </div>
                </form>
            </div>
        </Modal>
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
