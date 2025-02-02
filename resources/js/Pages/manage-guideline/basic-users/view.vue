<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    guideline: { type: Object, required: true },
    success: String,
});

// Sort items by count when the component is initialized
const sortedItems = computed(() => {
    return [...props.guideline.items].sort((a, b) => a.count - b.count);
});

// Open file modal
const showFileModal = ref(false);
const currentMediaFile = ref(null);

const openFileModal = (mediaFile) => {
    currentMediaFile.value = mediaFile;
    showFileModal.value = true;
};

const closeFileModal = () => {
    showFileModal.value = false;
    currentMediaFile.value = null;
};

const backRoute = () => {
    Inertia.get(route('guideline.index'));
};
</script>

<template>
    <Head title="View Guideline" />
    <Sidebar>
        <template #header>
            <div class="flex justify-between items-center">
                <SecondaryButton @click="backRoute">
                    Back
                </SecondaryButton>
            </div>
        </template>

        <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow-lg">
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-4">{{ props.guideline.title }}</h1>
            <p class="text-md text-gray-700 mb-4 text-center">{{ props.guideline.description }}</p>
            <div class="space-y-4">
                <div v-for="(step, index) in sortedItems" :key="index" class="p-4 bg-gray-50 rounded-lg shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Step {{ index + 1 }}</h2>
                    <p class="text-gray-600 mb-2">{{ step.text }}</p>
                    <div v-if="step.mediaFiles && step.mediaFiles.length > 0" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div v-for="mediaFile in step .mediaFiles" :key="mediaFile.id" class="cursor-pointer" @click="openFileModal(mediaFile)">
                            <template v-if="mediaFile.type.startsWith('image/')">
                                <img :src="`/storage/${mediaFile.path}`" alt="Media" class="w-full h-32 object-cover rounded-lg shadow-md" />
                            </template>
                            <template v-else-if="mediaFile.type.startsWith('video/')">
                                <div class="flex items-center justify-center h-32 bg-gray-200 rounded-lg shadow-md">
                                    <span class="text-gray-600">🎥 Video</span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-center justify-center h-32 bg-gray-200 rounded-lg shadow-md">
                                    <span class="text-gray-600">📄 Document</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showFileModal" @close="closeFileModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800">Preview Media File</h2>
                <div class="mt-4" v-if="currentMediaFile">
                    <template v-if="currentMediaFile.type.startsWith('image/')">
                        <img :src="`/storage/${currentMediaFile.path}`" alt="Preview" class="w-full h-auto rounded-lg" />
                    </template>
                    <template v-else-if="currentMediaFile.type.startsWith('video/')">
                        <video controls class="w-full h-auto rounded-lg">
                            <source :src="`/storage/${currentMediaFile.path}`" :type="currentMediaFile.type" />
                            Your browser does not support the video tag.
                        </video>
                    </template>
                    <template v-else>
                        <p class="text-gray-600">This is a document file. You can download it <a :href="`/storage/${currentMediaFile.path}`" class="text-indigo-600 underline" target="_blank">here</a>.</p>
                    </template>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>

<style scoped>
/* Add any necessary styles here */
</style>
