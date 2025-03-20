<script setup>
import Modal from '@/Components/Modal.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
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
    router.get(route('guideline.index'));
};
</script>

<template>
    <Head title="View Guideline" />
    <Sidebar>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative container mx-auto px-4 py-16 max-w-4xl">
                <!-- Success Message -->
                <div v-if="props?.success"
                     class="notification success-notification"
                     role="alert">
                    <div class="flex-1 flex items-center">
                        <span class="material-icons material-icons-round text-2xl mr-3">check_circle</span>
                        <p class="notification-text">{{ props?.success }}</p>
                    </div>
                </div>

                <!-- Guideline Card -->
                <div class="profile-card">
                    <div class="guideline-header">
                        <div class="flex flex-col">
                            <h1 class="guideline-title-gradient">{{ props.guideline.title }}</h1>
                            <div class="category-badge">
                                <span class="material-icons material-icons-round text-sm mr-2">category</span>
                                <p>{{ props.guideline.category }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Guideline Content -->
                    <div class="p-6 space-y-6">
                        <!-- Description Section -->
                        <div class="space-y-4">
                            <h2 class="text-lg font-semibold border-b pb-2 flex items-center text-white border-opacity-20">
                                <span class="material-icons material-icons-round mr-2">description</span>
                                Description
                            </h2>
                            <p class="text-white/90">{{ props.guideline.description }}</p>
                        </div>

                        <!-- Guidelines Section -->
                        <div class="space-y-4">
                            <h2 class="text-lg font-semibold border-b pb-2 flex items-center text-white border-opacity-20">
                                <span class="material-icons material-icons-round mr-2">list</span>
                                Guidelines
                            </h2>
                            <div class="space-y-4">
                                <div v-for="(item, index) in sortedItems" :key="item.id"
                                     class="info-row group p-4 rounded-lg">
                                    <h3 class="step-title">
                                        <span class="material-icons material-icons-round mr-2">article</span>
                                        Step {{ index + 1 }}
                                    </h3>
                                    <p class="text-white/90 ml-8">{{ item.text }}</p>

                                    <!-- Media Files Grid -->
                                    <div v-if="item.mediaFiles.length > 0" class="mt-4 ml-8">
                                        <h4 class="text-sm font-medium text-white/80 mb-2">Media Files</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                            <div v-for="mediaFile in item.mediaFiles" :key="mediaFile.id"
                                                 class="media-item group/media"
                                                 @click="openFileModal(mediaFile)">
                                                <template v-if="mediaFile.type.startsWith('image/')">
                                                    <img :src="`/storage/${mediaFile.path}`"
                                                         alt="Media"
                                                         class="media-preview" />
                                                </template>
                                                <template v-else-if="mediaFile.type.startsWith('video/')">
                                                    <div class="media-preview flex items-center justify-center bg-black/30">
                                                        <span class="material-icons material-icons-round text-3xl">play_circle</span>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <div class="media-preview flex flex-col items-center justify-center bg-black/30">
                                                        <span class="material-icons material-icons-round text-3xl">description</span>
                                                        <span class="text-xs mt-1 text-center px-2">Document</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-4">
                        <button
                            class="action-button-gradient primary"
                            @click="backRoute"
                        >
                            <span class="material-icons material-icons-round mr-2 group-hover:rotate-12">arrow_back</span>
                            Back to Guidelines
                        </button>
                    </div>
                </div>

                <!-- Media Preview Modal -->
                <Modal :show="showFileModal" @close="closeFileModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Media Preview</h2>
                        <div class="mt-4" v-if="currentMediaFile">
                            <template v-if="currentMediaFile.type.startsWith('image/')">
                                <img :src="`/storage/${currentMediaFile.path}`"
                                     alt="Preview"
                                     class="w-full h-auto rounded-lg" />
                            </template>
                            <template v-else-if="currentMediaFile.type.startsWith('video/')">
                                <video controls class="w-full h-auto rounded-lg">
                                    <source :src="`/storage/${currentMediaFile.path}`" :type="currentMediaFile.type" />
                                    Your browser does not support the video tag.
                                </video>
                            </template>
                            <template v-else>
                                <div class="text-center p-8 bg-gray-50 rounded-lg">
                                    <span class="material-icons material-icons-round text-4xl text-gray-400 mb-2">description</span>
                                    <p class="text-gray-600">This is a document file.</p>
                                    <a :href="`/storage/${currentMediaFile.path}`"
                                       class="text-indigo-600 hover:text-indigo-800 underline mt-2 inline-block"
                                       target="_blank">
                                       Download Document
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Oceanic Theme */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.95) 0%,
        rgba(0, 64, 128, 0.85) 50%,
        rgba(0, 31, 63, 0.95) 100%
    );
}

/* Guideline Card */
.profile-card {
    @apply rounded-xl shadow-lg overflow-hidden mb-6;
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.guideline-header {
    @apply px-8 py-6;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.category-badge {
    @apply flex items-center text-indigo-200 mt-2;
}

/* Text Styling */
.guideline-title-gradient {
    font-size: 2.25rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.step-title {
    @apply text-lg font-semibold mb-2 flex items-center text-white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Information Row Styling */
.info-row {
    @apply transition-all duration-200 p-6;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 1rem;
    border-radius: 12px;
}

.info-row:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

/* Media Items */
.media-item {
    @apply relative rounded-xl overflow-hidden cursor-pointer transition-all duration-200;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.media-preview {
    @apply w-full aspect-square object-cover;
    background: rgba(0, 0, 0, 0.2);
}

.media-item:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

/* Button Gradients */
.action-button-gradient {
    @apply px-6 py-2.5 rounded-xl flex items-center transition-all duration-300;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.action-button-gradient.primary {
    background: linear-gradient(135deg, #4f46e5, #3730a3) !important;
}

.action-button-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    filter: brightness(110%);
}

.action-button-gradient:active {
    transform: translateY(0);
}

/* Background Styles */
.bg-gray-50 {
    background: rgba(0, 51, 102, 0.2) !important;
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Material Icons */
.material-icons-round {
    color: rgba(0, 204, 255, 0.9) !important;
}

/* Group hover effects */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .guideline-title-gradient {
        font-size: 1.5rem;
    }
    .container {
        padding: 1rem;
    }
    .profile-card {
        margin: 0.5rem;
    }
}

/* Notifications */
.notification {
    @apply flex items-center justify-between mb-6 px-6 py-4 rounded-xl backdrop-blur-md;
    animation: slideIn 0.3s ease-out;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.success-notification {
    background: rgba(16, 185, 129, 0.15);
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15);
}

.notification-text {
    @apply text-white text-base font-medium;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.success-notification .material-icons-round {
    color: rgb(16, 185, 129) !important;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
