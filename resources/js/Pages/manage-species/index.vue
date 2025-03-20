<script setup>
import Modal from '@/Components/Modal.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const props = defineProps({
    species: Object,
    categories: Array,
    success: String,
    colors: Array,
    userRole: String,
});

onMounted(() => {
    console.log(props.species.map(species => species.colors));
});

const selectedCategory = ref('all'); // Default filter
const showActive = ref(true); // Default to showing active species

// State for the search query and selected category
const searchQuery = ref('');

// State for the Identify Species modal
const showIdentifyModal = ref(false);
const identifyForm = ref({
    colors: [],
    shape: '',
    dangerToHumans: false,
    category: '',
});
const identifiedSpecies = ref([]);
const identifyStatus = ref(false);

// Filter species based on the selected category, search query, and active/inactive status
const filteredSpecies = computed(() => {
    let speciesList = props.species.filter(species => species.is_active === showActive.value);

    if (selectedCategory.value !== 'all') {
        speciesList = speciesList.filter((species) => species.category === selectedCategory.value);
    }

    if (searchQuery.value.trim() !== '') {
        speciesList = speciesList.filter((species) => species.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }

    if (identifiedSpecies.value.length > 0 || identifyStatus.value) {
        speciesList = speciesList.filter(species => identifiedSpecies.value.includes(species));
    }

    return speciesList.length > 0 ? speciesList.sort((a, b) => a.name.localeCompare(b.name)) : [];
});

const selectCategory = (category) => {
    selectedCategory.value = category;
    identifiedSpecies.value = [];
    identifyStatus.value = false;
    resetIdentifyForm();

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
    identifyForm.value = { colors: [], shape: '', dangerToHumans: false};
    selectedColors.value = []; // Reset selected colors
    identifiedSpecies.value = []; // Reset identified species when form is reset
    identifyStatus.value = false;
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
    if (selectedColors.value.length === 0 && !identifyForm.value.shape && !identifyForm.value.dangerToHumans) {
        identifiedSpecies.value = []; // Instead of null
        showIdentifyModal.value = false;
        identifyStatus.value = false;
        selectCategory('all');
        return;
    }

    const matches = props.species.filter(species => {
        const matchesColors = identifyForm.value.colors.length === 0 || (species.colors && identifyForm.value.colors.every(color => species.colors.includes(color)));
        const matchesShape = identifyForm.value.shape === '' || species.shape === identifyForm.value.shape;
        const matchesDangerToHumans = identifyForm.value.dangerToHumans === false || species.is_dangerous === identifyForm.value.dangerToHumans;

        identifyStatus.value = true;
        return matchesColors && matchesShape && matchesDangerToHumans;
    });

    identifiedSpecies.value = matches.length > 0 ? matches : [];
    showIdentifyModal.value = false;
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
    router.get(route('bpemo.admin.manage.species.create.page'));
};
const viewSpecies = (id) => {
    router.visit(route('species.view', { id }));
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

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Success message -->
                    <div v-if="props?.success" class="glass-panel mb-6 p-4 border border-green-400/30 text-green-400">
                        <strong class="font-bold">Success! </strong>
                        <span>{{ props?.success }}</span>
                    </div>

                    <!-- Header section -->
                    <div class="mb-6">
                        <h3 class="profile-title-gradient mb-4">
                            Marine Wildlife Species
                        </h3>

                        <!-- Filters and actions -->
                        <div class="flex flex-wrap gap-4 mb-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="selectCategory('all')"
                                    :class="selectedCategory === 'all' ? 'filter-button-active' : 'filter-button'"
                                >
                                    All
                                </button>
                                <button
                                    v-for="category in props.categories"
                                    :key="category"
                                    @click="selectCategory(category)"
                                    :class="selectedCategory === category ? 'filter-button-active' : 'filter-button'"
                                >
                                    {{ categoryText(category) }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-between items-center gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <div v-if="page.props.auth.user.user_role === 'bpemo_admin'" class="flex items-center">
                                    <span class="mr-2 text-white">{{ showActive ? 'Active' : 'Inactive' }}</span>
                                    <div
                                        @click="toggleActiveInactive"
                                        class="w-12 h-6 rounded-full flex items-center p-1 cursor-pointer transition-colors duration-300"
                                        :class="showActive ? 'bg-blue-500/50' : 'bg-gray-500/50'"
                                    >
                                        <div
                                            class="bg-white w-4 h-4 rounded-full shadow-md transition-transform duration-300 ease-in-out"
                                            :class="{ 'translate-x-6': showActive }"
                                        ></div>
                                    </div>
                                </div>

                                <button
                                    @click="openIdentifyModal"
                                    class="identify-button flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Identify
                                </button>

                                <div class="search-container">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search species..."
                                        class="search-input"
                                    />
                                </div>
                            </div>

                            <button
                                v-if="page.props.auth.user.user_role === 'bpemo_admin'"
                                type="button"
                                @click="createSpecies()"
                                class="create-button flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create
                            </button>
                        </div>
                    </div>

                    <!-- Species List -->
                    <div class="space-y-2 sm:space-y-3">
                        <div v-for="species in filteredSpecies"
                             :key="species.id"
                             class="species-card">
                            <div class="p-3 sm:p-4">
                                <!-- Main Content Area -->
                                <div class="sm:flex sm:justify-between sm:items-start">
                                    <!-- Species Info -->
                                    <div class="flex flex-col">
                                        <h4 class="text-sm sm:text-base font-semibold text-white">{{ species.name }}</h4>
                                        <span :class="{
                                            'category-badge': true,
                                            'category-marine-mammals': species.category === 'marine_mammals',
                                            'category-marine-turtles': species.category === 'marine_turtles',
                                            'category-sharks-rays': species.category === 'sharks_rays'
                                        }">
                                            {{ categoryText(species.category) }}
                                        </span>
                                    </div>

                                    <!-- View Button (hidden on mobile, visible on sm+) -->
                                    <button
                                        @click="viewSpecies(species.id)"
                                        class="view-button hidden sm:flex sm:items-center sm:gap-1 sm:mt-0"
                                    >
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </button>
                                </div>

                                <!-- Colors Row (if present) -->
                                <div v-if="species.colors && species.colors.length > 0" class="flex flex-wrap gap-2 mt-3">
                                    <div v-for="color in species.colors" :key="color" class="color-tag">
                                        <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full mr-1" :style="{ backgroundColor: color }"></div>
                                        <span>{{ color }}</span>
                                    </div>
                                </div>

                                <!-- View Button (only on mobile, at bottom) -->
                                <div class="mt-3 sm:hidden">
                                    <button
                                        @click="viewSpecies(species.id)"
                                        class="view-button w-full flex items-center justify-center gap-1"
                                    >
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Species
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Show No Species Message -->
                    <div v-if="filteredSpecies.length === 0"
                         class="glass-panel text-center py-10 px-4">
                        <svg class="mx-auto h-10 w-10 sm:h-16 sm:w-16 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg sm:text-xl font-medium text-white">No species found</h3>
                        <p class="mt-2 text-sm text-white/60">Try adjusting your search or filters.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identify Modal -->
        <Modal :show="showIdentifyModal" @close="closeIdentifyModal">
            <div class="p-6 bg-gradient-to-b from-blue-900/90 to-blue-800/90 text-white rounded-lg">
                <h2 class="text-lg font-semibold mb-4 text-blue-200">Identify Species</h2>
                <form @submit.prevent="submitIdentifyForm" class="space-y-4">
                    <div>
                        <label class="block text-blue-200">Colors</label>
                        <select id="colors" @change="addColor" class="w-full bg-blue-800/50 text-white border border-blue-600/50 rounded-md p-2 focus:ring-blue-400 focus:border-blue-400">
                            <option value="" disabled selected>Choose a color</option>
                            <option v-for="color in props.colors" :key="color.name" :value="color.name">
                                {{ color.name }}
                            </option>
                        </select>
                        <div v-if="selectedColors.length" class="flex flex-wrap gap-2 mt-3">
                            <div v-for="color in selectedColors" :key="color" class="flex items-center space-x-2 bg-blue-800/50 px-3 py-1 rounded-lg">
                                <div :style="{ backgroundColor: color }" class="w-6 h-6 rounded-full"></div>
                                <span>{{ color }}</span>
                                <button @click="removeColor(color)" class="text-red-400 hover:text-red-300 font-bold">X</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-blue-200">Shape</label>
                        <select v-model="identifyForm.shape" class="w-full bg-blue-800/50 text-white border border-blue-600/50 rounded-md p-2 focus:ring-blue-400 focus:border-blue-400">
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
                            <input v-model="identifyForm.dangerToHumans" type="checkbox" class="form-checkbox bg-blue-800/50 border-blue-600/50 text-blue-500 mr-2" />
                            <span class="text-blue-200">Danger to Humans</span>
                        </label>
                    </div>
                    <div class="flex justify-between pt-2">
                        <button type="button" @click="resetIdentifyForm" class="px-4 py-2 bg-gray-700/50 text-gray-200 rounded hover:bg-gray-600/50 transition">Reset</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600/70 text-white rounded hover:bg-blue-500/70 transition">Show</button>
                    </div>
                </form>
            </div>
        </Modal>
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
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
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

.species-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    height: 100%;
}

.species-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.15);
}

/* Buttons */
.filter-button, .filter-button-active, .view-button, .create-button, .identify-button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
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

.identify-button {
    background: rgba(109, 213, 167, 0.2);
    color: #84e4b8;
    border-color: rgba(109, 213, 167, 0.3);
}

.view-button {
    background: rgba(77, 171, 247, 0.2);
    color: #4dabf7;
    border-color: rgba(77, 171, 247, 0.3);
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
}

.view-button:hover {
    background: rgba(77, 171, 247, 0.35);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(77, 171, 247, 0.3);
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

.create-button:hover {
    background: linear-gradient(
        135deg,
        rgba(0, 153, 255, 0.95) 0%,
        rgba(0, 102, 204, 0.85) 100%
    );
}

/* Search input */
.search-container {
    position: relative;
}

.search-input {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}

.search-input:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 153, 255, 0.3);
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

/* Category badges */
.category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: inline-block;
    margin-top: 0.25rem;
    max-width: fit-content;
}

.category-marine-mammals {
    background: rgba(77, 171, 247, 0.25);
    color: #8cd5ff;
    border-color: rgba(77, 171, 247, 0.4);
}

.category-marine-turtles {
    background: rgba(109, 213, 167, 0.25);
    color: #a0ffdc;
    border-color: rgba(109, 213, 167, 0.4);
}

.category-sharks-rays {
    background: rgba(196, 134, 252, 0.25);
    color: #e2c1ff;
    border-color: rgba(196, 134, 252, 0.4);
}

/* Color tags */
.color-tag {
    display: flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 9999px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .filter-button, .filter-button-active, .view-button, .create-button, .identify-button {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .profile-title-gradient {
        font-size: 1.25rem;
    }

    .category-badge {
        padding: 0.25rem 0.625rem;
        font-size: 0.7rem;
    }

    .species-card {
        margin: 0 0 0.5rem 0;
    }
}

@media (max-width: 480px) {
    .species-card {
        margin: 0 0 0.75rem 0;
        border-radius: 0.75rem;
    }

    .color-tag {
        padding: 0.1875rem 0.4375rem;
        font-size: 0.675rem;
    }
}

.filter-button:hover, .create-button:hover, .identify-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

.identify-button:hover {
    background: rgba(109, 213, 167, 0.3);
    color: #ffffff;
}
</style>
