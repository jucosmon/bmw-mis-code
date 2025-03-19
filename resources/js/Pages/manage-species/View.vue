<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    species: {
        type: Object,
        required: true,
    },
    success: String,
});

const backRoute = computed(() => {
    return route('species.index');
});

const updateRoute = computed(() => {
    return route('bpemo.admin.manage.species.update.page', { id: props.species.id });
});

const archiveRoute = computed(() => {
    return route('bpemo.admin.manage.species.archive', {
        id: props.species.id,
    });
});

const unarchiveRoute = computed(() => {
    return route('bpemo.admin.manage.species.unarchive', {
        id: props.species.id,
    });
});

const speciesCategory = computed(() => {
    switch (props.species.category) {
        case 'marine_turtles':
            return 'Marine Turtles';
        case 'marine_mammals':
            return 'Marine Mammals';
        case 'sharks_rays':
            return 'Sharks and Rays';
        default:
            return 'Unknown Category';
    }
});

const updateSpecies = () => {
    router.visit(updateRoute.value);
};

const showConfirmArchiveModal = ref(false);

const confirmArchiveSpecies = () => {
    showConfirmArchiveModal.value = true;
};

const closeModal = () => {
    showConfirmArchiveModal.value = false;
};

const form = useForm({
    is_active: true,
    password: '',
});

const archiveSpecies = () => {
    if(props.species.is_active){
        form.patch(archiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    } else {
        form.patch(unarchiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    }

};

// Media preview modal
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
</script>

<template>
    <Head title="View Species" />
    <Sidebar>
        <template #header>
            <div class="flex justify-between items-center">
                <SecondaryButton @click="$router.get(backRoute)">Back</SecondaryButton>
            </div>
        </template>

        <div class="relative min-h-screen">
            <!-- Background image -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0"
                 style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main Content -->
            <div class="relative z-10 max-w-4xl mx-auto p-6">
                <!-- Success Message Container -->
                <div v-if="props?.success"
                     class="bg-blue-100/80 border border-blue-400 text-blue-700 px-4 py-3 mb-4 rounded backdrop-blur-sm">
                    <strong>Success! </strong> {{ props?.success }}
                </div>

                <!-- Title and Category Container -->
                <div class="bg-blue-900/30 backdrop-blur-sm p-6 rounded-lg shadow-lg mb-6">
                    <h2 class="text-3xl font-bold text-white">Species Information</h2>
                    <p class="text-sm mt-2 text-blue-200">({{ speciesCategory }})</p>
                </div>

                <!-- Actions Container -->
                <div v-if="page.props.auth.user.user_role==='bpemo_admin'"
                     class="bg-blue-900/30 backdrop-blur-sm p-6 rounded-lg shadow-lg mb-6">
                    <div class="flex justify-end gap-4">
                        <DangerButton v-if="props.species.is_active"
                            @click="confirmArchiveSpecies">Archive</DangerButton>
                        <DangerButton v-else
                            @click="confirmArchiveSpecies">Unarchive</DangerButton>
                        <PrimaryButton @click="updateSpecies">Update Species</PrimaryButton>
                    </div>
                </div>

                <!-- Species Details Container -->
                <div class="bg-blue-900/30 backdrop-blur-sm p-6 rounded-lg shadow-lg mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-white">Species Details</h3>
                        <span class="px-4 py-1 rounded-full text-sm"
                              :class="{
                                'bg-green-500/20 text-green-300': props.species.is_active,
                                'bg-red-500/20 text-red-300': !props.species.is_active
                              }">
                            {{ props.species.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Main Details -->
                    <div class="space-y-6 text-white">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="space-y-4">
                                <div class="bg-blue-950/30 p-4 rounded-lg">
                                    <h4 class="text-blue-300 text-sm mb-2">Scientific Name</h4>
                                    <p class="font-medium">{{ props.species.scientific_name }}</p>
                                </div>
                                <div class="bg-blue-950/30 p-4 rounded-lg">
                                    <h4 class="text-blue-300 text-sm mb-2">Common Name</h4>
                                    <p class="font-medium">{{ props.species.common_name }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-blue-950/30 p-4 rounded-lg">
                                    <h4 class="text-blue-300 text-sm mb-2">Local Name</h4>
                                    <p class="font-medium">{{ props.species.local_name }}</p>
                                </div>
                                <div class="bg-blue-950/30 p-4 rounded-lg">
                                    <h4 class="text-blue-300 text-sm mb-2">Conservation Status</h4>
                                    <p class="font-medium">{{ props.species.conservation_status }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="bg-blue-950/30 p-4 rounded-lg">
                                <h4 class="text-blue-300 text-sm mb-2">Shape</h4>
                                <p class="font-medium">{{ props.species.shape }}</p>
                            </div>
                            <div class="bg-blue-950/30 p-4 rounded-lg flex items-center">
                                <div>
                                    <h4 class="text-blue-300 text-sm mb-2">Dangerous</h4>
                                    <p class="font-medium">{{ props.species.is_dangerous ? 'Yes' : 'No' }}</p>
                                </div>
                                <div v-if="props.species.is_dangerous"
                                     class="ml-auto bg-red-500/20 p-2 rounded-full">
                                    <svg class="w-6 h-6 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="bg-blue-950/30 p-4 rounded-lg">
                            <h4 class="text-blue-300 text-sm mb-2">Description</h4>
                            <p class="font-medium">{{ props.species.description }}</p>
                        </div>

                        <!-- Colors Section -->
                        <div v-if="species.speciesColors?.length" class="bg-blue-950/30 p-4 rounded-lg">
                            <h4 class="text-blue-300 text-sm mb-3">Colors</h4>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="color in species.speciesColors"
                                     :key="color.id"
                                     :style="{ backgroundColor: color.color.name }"
                                     class="w-8 h-8 rounded-full shadow-lg border-2 border-white/20
                                            transform hover:scale-110 transition-transform">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Gallery Container -->
                <div class="bg-blue-900/30 backdrop-blur-sm p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-white mb-4">Media Gallery</h3>
                    <div v-if="props.species.mediaFiles?.length"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="file in props.species.mediaFiles"
                             :key="file.id"
                             @click="openFileModal(file)"
                             class="cursor-pointer rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                            <template v-if="file.type.startsWith('image/')">
                                <img :src="file.url"
                                     :alt="`Image of ${props.species.name}`"
                                     class="w-full h-48 object-cover" />
                            </template>
                            <template v-else-if="file.type.startsWith('video/')">
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-600">🎥 Video</span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p v-else class="text-blue-200 text-center py-4">No media files available</p>
                </div>
            </div>
        </div>

        <!-- Existing Modals -->
        <Modal :show="showFileModal" @close="closeFileModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray">Preview Media File</h2>
                <div class="mt-4" v-if="currentMediaFile">
                    <template v-if="currentMediaFile.type.startsWith('image/')">
                        <img :src="currentMediaFile.url" alt="Preview" class="w-full h-auto rounded-lg" />
                    </template>
                    <template v-else-if="currentMediaFile.type.startsWith('video/')">
                        <video controls class="w-full h-auto rounded-lg">
                            <source :src="currentMediaFile.url" :type="currentMediaFile.type" />
                            Your browser does not support the video tag.
                        </video>
                    </template>
                </div>
            </div>
        </Modal>

        <Modal :show="showConfirmArchiveModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-100">
                   {{ props.species.is_active ? 'Are you sure you want to archive this species?' : 'Are you sure you want to unarchive this species?'}}
                </h2>
                <div class="mt-4">
                    <label for="admin-password" class="text-sm text-gray-250">
                        Confirm by entering your password
                    </label>
                    <input
                        type="password"
                        id="admin-password"
                        v-model="form.password"
                        class="text-black mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Enter your password"
                    />
                    <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">
                        {{ form.errors.password }}
                    </p>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <DangerButton @click="archiveSpecies">Confirm</DangerButton>
                </div>
            </div>
        </Modal>
    </Sidebar>
</template>

<style scoped>
.bg-gradient-overlay {
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.9) 0%, rgba(0, 64, 128, 0.8) 50%, rgba(0, 31, 63, 0.9) 100%);
}

.backdrop-blur-sm {
    backdrop-filter: blur(6px);
}

/* Basic hover effects */
.shadow-lg {
    transition: all 0.3s ease;
}

.shadow-lg:hover {
    transform: translateY(-2px);
}

/* Responsive styles */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr !important;
    }
}
</style>
