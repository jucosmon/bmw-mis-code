<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    guidelines: Array,
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
    switch (page.props.auth.user.user_role) {
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
const title = computed(() => `Guidelines (${guidelinesRole.value})`);

const filterStatus = ref('all'); // Default filter

// Filter guideline based on the selected status
const filteredGuidelines = computed(() => {
    if (filterStatus.value === 'marine_turtles') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_turtles');
    } else if (filterStatus.value === 'marine_mammals') {
        return props.guidelines.filter((guideline) => guideline.category === 'marine_mammals');
    } else if (filterStatus.value === 'sharks_rays') {
        return props.guidelines.filter((guideline) => guideline.category === 'sharks_rays');
    } else {
        return props.guidelines; // 'all'
    }
});

// Button routes
const viewGuideline = (id) => {
    return router.visit(route('guideline.view', { id }));
};
</script>

<template>
    <Head title="Guidelines" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <!-- Container -->
        <div class="container mx-auto px-7 py-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">{{ guidelinesRole }} Guidelines</h2>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 w-auto pr-8"
                >
                    <option value="marine_turtles">Marine Turtles</option>
                    <option value="marine_mammals">Marine Mammals</option>
                    <option value="sharks_rays">Sharks and Rays</option>
                    <option value="all">All</option>
                </select>
            </div>

            <!-- Guidelines List -->
            <div class="space-y-4">
                <div v-for="guideline in filteredGuidelines" :key="guideline.id" class="bg-white shadow-sm rounded-lg p-4 border border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold mb-1">{{ guideline.title }}</h3>
                        <p class="text-xs font-bold text-indigo-600">{{ guidelinesCategory(guideline.category) }}</p>
                    </div>
                    <button
                        class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="viewGuideline(guideline.id)"
                    >
                        View
                    </button>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Ensure the list is responsive */
@media (max-width: 640px) {
  .space-y-4 > * + * {
    margin-top: 1rem;
  }
}
</style>
