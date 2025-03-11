<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    guideline: { type: Object, required: true },
    success: String,
});
const form = useForm({
    is_active: true,
    password: '',
});
// Sort items by count when the component is initialized
const sortedItems = computed(() => {
    return [...props.guideline.items].sort((a, b) => a.count - b.count);
});

const backRoute = () => {
    if (props.guideline.is_active) {
        // Navigate to the active guidelines
        router.get(route('manage.guideline.index', { user_role: props.guideline.user_role, archived: false}));
    } else {
        // Navigate to the archived guidelines
        router.get(route('manage.guideline.index', { user_role: props.guideline.user_role, archived: true}));
    }
};

// open file modal
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



// archive/unarchive
const archiveModal = ref(false);

const showArchiveModal = () => {
    archiveModal.value = true;
};

const closeArchiveModal = () => {
    archiveModal.value = false;
};

const archive = ()=> {
    if(props.guideline.is_active){
        form.patch(route('manage.guideline.archive', { id: props.guideline.id }), {
            onSuccess: () => {
                closeArchiveModal();
                form.reset('password');
            },
            onError: (errors) => {
                console.error(errors);
            },
        });
    }else{
        form.patch(route('manage.guideline.unarchive', { id: props.guideline.id }), {
            onSuccess: () => {
                closeArchiveModal();
                form.reset('password');
            },
            onError: (errors) => {
                console.error(errors);
            },
        });
    }
}

// update
const updateButton = () => {
    return router.get(route('manage.guideline.updatePage', { id: props.guideline.id }));
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

        <div class="relative min-h-screen">
            <!-- Background image with oceanic overlay -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>
            
            <!-- Content overlay -->
            <div class="relative z-10 max-w-4xl mx-auto p-6">
                <div v-if="props?.success" class="bg-blue-100/80 border border-blue-400 text-blue-700 px-4 py-3 mb-4 rounded backdrop-blur-sm">
                    <strong>Success! </strong> {{ props?.success }}
                </div>
                <div class="space-x-2 flex mt-4 justify-end items-end">
                    <DangerButton v-if="props.guideline.is_active" 
                        class="action-button-gradient danger" 
                        @click="showArchiveModal()">Archive</DangerButton>
                    <DangerButton v-else 
                        class="action-button-gradient danger" 
                        @click="showArchiveModal()">Unarchive</DangerButton>
                    <PrimaryButton v-if="props.guideline.is_active" 
                        class="action-button-gradient primary" 
                        @click="updateButton">Update</PrimaryButton>
                </div>

                <!-- Main info container -->
                <div class="bg-blue-900/30 backdrop-blur-sm p-6 rounded-lg shadow-lg mb-6 text-white">
                    <div>
                        <h1 class="text-2xl font-bold font-poppins tracking-tight">{{ props.guideline.title }}</h1>
                        <p class="text-blue-50 mt-2 font-inter">{{ props.guideline.description }}</p>
                        <div class="text-sm text-blue-100 mt-2 font-inter">
                            <span class="mr-4">Category: {{ props.guideline.category }}</span>
                            <span> User Role: {{ props.guideline.user_role }}</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold" :class="{'text-green-400': props.guideline.is_active, 'text-red-400': !props.guideline.is_active}">
                            {{ props.guideline.is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-xl font-bold text-white mb-4 font-poppins tracking-tight">Guidelines</h2>
                    <div v-for="(item, index) in sortedItems" :key="item.id" 
                         class="bg-blue-900/30 backdrop-blur-sm p-4 rounded-lg shadow-lg mt-4 text-white hover:bg-blue-800/40 transition-all">
                        <h3 class="text-lg font-semibold text-blue-50 font-inter">• {{ item.text }}</h3>
                        <div v-if="item.mediaFiles.length > 0" class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div v-for="mediaFile in item.mediaFiles" :key="mediaFile.id" class="cursor-pointer" @click="openFileModal(mediaFile)">
                                <template v-if="mediaFile.type.startsWith('image/')">
                                    <img :src="`/storage/${mediaFile.path}`" alt="Media" class="w-full h-32 object-cover rounded-lg shadow-md" />
                                </template>
                                <template v-else>
                                    <div class="flex items-center justify-center h-32 bg-gray-200 rounded-lg shadow-md">
                                        <span class="text-gray-600">{{ mediaFile.type.includes('video') ? '🎥 Video' : '📄 Document' }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <p v-else class="text-blue-200 mt-2">No media files available.</p>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showFileModal" @close="closeFileModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800">Preview Media File</h2>
                <div class="mt-4"  v-if="currentMediaFile">
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
/* Responsive tweaks */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr !important;
    }
}

/* Oceanic Theme */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.backdrop-blur-sm {
    backdrop-filter: blur(6px);
}

/* Add container hover effect */
.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.shadow-lg:hover {
    box-shadow: 0 15px 20px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

/* Button Gradients */
.gradient-primary {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

.gradient-danger {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

/* Font styles */
.font-poppins {
    font-family: 'Poppins', sans-serif;
}

.font-inter {
    font-family: 'Inter', sans-serif;
}

/* Button Gradients */
.action-button-gradient {
    @apply px-6 py-3 rounded-lg flex items-center transition-all duration-300 text-base;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    min-width: 120px;
    justify-content: center;
}

.action-button-gradient.primary {
    background: linear-gradient(135deg, #4f46e5, #3730a3) !important;
    font-size: 1rem;
}

.action-button-gradient.danger {
    background: linear-gradient(135deg, #dc2626, #991b1b) !important;
    font-size: 1rem;
}

/* Updated Button Styles */
button {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 100%
    ) !important;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
}

button:hover {
    background: linear-gradient(
        135deg,
        rgba(0, 64, 128, 0.95) 0%,
        rgba(0, 51, 102, 0.85) 100%
    ) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    border-color: rgba(255, 255, 255, 0.3);
}

button:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 51, 102, 0.2);
}
</style>
