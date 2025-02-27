<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    years: Array,
});

const emit = defineEmits(['close', 'export']);

const exportFilters = ref({
    year: '',
    category: '',
    eventType: ''
});

const handleExport = () => {
    emit('export', exportFilters.value);
    exportFilters.value = { year: '', category: '', eventType: '' };
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Export Data</h3>
                <div class="space-y-4">
                    <div>
                        <label for="exportYear" class="block text-sm font-medium text-gray-700">Year</label>
                        <select v-model="exportFilters.year" id="exportYear" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All Years</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="exportCategory" class="block text-sm font-medium text-gray-700">Category</label>
                        <select v-model="exportFilters.category" id="exportCategory" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All Categories</option>
                            <option value="marine_mammals">Marine Mammals</option>
                            <option value="marine_turtles">Marine Turtles</option>
                            <option value="sharks_rays">Shark and Rays</option>
                        </select>
                    </div>

                    <div>
                        <label for="exportEventType" class="block text-sm font-medium text-gray-700">Incident Type</label>
                        <select v-model="exportFilters.eventType" id="exportEventType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All Types</option>
                            <option value="Sighting">Sighting</option>
                            <option value="Stranded">Stranded</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button @click="$emit('close')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">
                    Cancel
                </button>
                <button @click="handleExport" class="px-4 py-2 bg-blue-500 text-white rounded-md">
                    Export
                </button>
            </div>
        </div>
    </div>
</template>
