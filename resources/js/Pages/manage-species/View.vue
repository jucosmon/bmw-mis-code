<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import CustomButton from '@/Components/CustomButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    species: {
        type: Object,
        required: true,
    },
    success: String,
});
const showPassword = ref(false);
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
        <div class="relative min-h-screen">
            <!-- Background image -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0"
                 style="background-image: url('/images/landing.jpg');">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main Content -->
            <div class="relative z-10 max-w-6xl mx-auto py-16 px-5">
                <!-- Success Message Container -->
                <div v-if="props?.success"
                     class="bg-blue-100/80 border-l-4 border-blue-500 text-blue-700 px-4 py-3 mb-6 rounded shadow-md backdrop-blur-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <strong class="font-bold mr-1">Success!</strong>
                    <span>{{ props?.success }}</span>
                </div>
                <Link class=" ml-5 md:ml-0 mb-3 flex items-center w-fit" :href="backRoute">
                    <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 text-sm text-white">arrow_back</span>
                    <span class="text-lg font-semibold text-white">Back</span>
                </Link>
                <!-- Title and Category Container -->
                <div class="bg-blue-900/40 backdrop-blur-md p-6 rounded-lg shadow-lg mb-6 border border-blue-800/30">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                        <div>
                            <h2 class="text-3xl font-bold text-white">{{ props.species.common_name }}</h2>
                            <p class="text-sm mt-2 text-blue-200 italic">{{ props.species.scientific_name }}</p>
                        </div>
                        <div class="bg-blue-800/50 px-4 py-2 rounded-lg mt-3 sm:mt-0">
                            <p class="text-blue-100">{{ speciesCategory }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <span class="px-4 py-1 rounded-full text-sm inline-flex items-center mb-3 sm:mb-0"
                            :class="{
                                'bg-green-500/30 text-green-200 border border-green-400/30': props.species.is_active,
                                'bg-red-500/30 text-red-200 border border-red-400/30': !props.species.is_active
                            }">
                            <span class="w-2 h-2 rounded-full mr-2"
                                :class="{
                                    'bg-green-400 animate-pulse': props.species.is_active,
                                    'bg-red-400': !props.species.is_active
                                }"></span>
                            {{ props.species.is_active ? 'Active' : 'Inactive' }}
                        </span>

                        <div v-if="page.props.auth.user.user_role==='bpemo_admin'" class="flex gap-3">
                            <CustomButton v-if="props.species.is_active"
                                :onclick="confirmArchiveSpecies"
                                variant="danger"
                                icon="archive"
                                >
                                Archive
                            </CustomButton>
                            <CustomButton v-else
                                :onclick="confirmArchiveSpecies"
                                variant="danger"
                                icon="unarchive"
                                >
                                Unarchive
                            </CustomButton>
                            <CustomButton
                                icon="edit"
                                :onclick="updateSpecies"
                            >
                                Update
                            </CustomButton>
                        </div>
                    </div>
                </div>

                <!-- Species Details Container -->
                <div class="bg-blue-900/40 backdrop-blur-md p-6 rounded-lg shadow-lg mb-6 border border-blue-800/30">
                    <h3 class="text-xl font-semibold text-white mb-6 border-b border-blue-700/50 pb-2">Species Details</h3>

                    <!-- Main Details -->
                    <div class="space-y-6 text-white">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="space-y-4">
                                <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                    <h4 class="text-blue-300 text-sm mb-2 font-medium">Scientific Name</h4>
                                    <p class="font-medium">{{ props.species.scientific_name }}</p>
                                </div>
                                <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                    <h4 class="text-blue-300 text-sm mb-2 font-medium">Common Name</h4>
                                    <p class="font-medium">{{ props.species.common_name }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                    <h4 class="text-blue-300 text-sm mb-2 font-medium">Local Name</h4>
                                    <p class="font-medium">{{ props.species.local_name }}</p>
                                </div>
                                <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                    <h4 class="text-blue-300 text-sm mb-2 font-medium">Conservation Status</h4>
                                    <p class="font-medium">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': props.species.conservation_status === 'Least Concern',
                                                'bg-yellow-100 text-yellow-800': props.species.conservation_status === 'Near Threatened' || props.species.conservation_status === 'Vulnerable',
                                                'bg-orange-100 text-orange-800': props.species.conservation_status === 'Endangered',
                                                'bg-red-100 text-red-800': props.species.conservation_status === 'Critically Endangered' || props.species.conservation_status === 'Extinct in the Wild',
                                                'bg-gray-100 text-gray-800': props.species.conservation_status === 'Data Deficient' || props.species.conservation_status === 'Not Evaluated'
                                            }">
                                            {{ props.species.conservation_status }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                                <h4 class="text-blue-300 text-sm mb-2 font-medium">Shape</h4>
                                <p class="font-medium">{{ props.species.shape }}</p>
                            </div>
                            <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40 flex items-center">
                                <div>
                                    <h4 class="text-blue-300 text-sm mb-2 font-medium">Dangerous</h4>
                                    <p class="font-medium">{{ props.species.is_dangerous ? 'Yes' : 'No' }}</p>
                                </div>
                                <div v-if="props.species.is_dangerous"
                                     class="ml-auto bg-red-500/30 p-2 rounded-full">
                                    <svg class="w-6 h-6 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                            <h4 class="text-blue-300 text-sm mb-2 font-medium">Description</h4>
                            <p class="font-medium">{{ props.species.description }}</p>
                        </div>

                        <!-- Colors Section -->
                        <div v-if="props.species.speciesColors?.length" class="bg-blue-950/50 p-4 rounded-lg border border-blue-800/40">
                            <h4 class="text-blue-300 text-sm mb-3 font-medium">Colors</h4>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="color in props.species.speciesColors"
                                     :key="color.id"
                                     :style="{ backgroundColor: color.color.name }"
                                     class="w-8 h-8 rounded-full shadow-lg border-2 border-white/30">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Gallery Container -->
                <div class="bg-blue-900/40 backdrop-blur-md p-6 rounded-lg shadow-lg border border-blue-800/30">
                    <h3 class="text-xl font-semibold text-white mb-6 border-b border-blue-700/50 pb-2">Media Gallery</h3>
                    <div v-if="props.species.mediaFiles?.length"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="file in props.species.mediaFiles"
                             :key="file.id"
                             @click="openFileModal(file)"
                             class="cursor-pointer rounded-lg overflow-hidden shadow-md bg-blue-950/50 border border-blue-800/40">
                            <template v-if="file.type.startsWith('image/')">
                                <img :src="file.url"
                                     :alt="`Image of ${props.species.common_name}`"
                                     class="w-full h-48 object-cover" />
                            </template>
                            <template v-else-if="file.type.startsWith('video/')">
                                <div class="w-full h-48 bg-blue-950/70 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p v-else class="text-blue-200 text-center py-4 bg-blue-950/30 rounded-lg mt-4">No media files available</p>
                </div>
            </div>
        </div>

        <!-- Media Preview Modal -->
        <Modal :show="showFileModal" @close="closeFileModal">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-100">Media Preview</h2>
                    <button @click="closeFileModal" class="text-gray-100 hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
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

        <!-- Archive/Unarchive Modal -->
        <Modal :show="showConfirmArchiveModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-2 mr-3">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-100">
                       {{ props.species.is_active ? 'Archive Species' : 'Unarchive Species' }}
                    </h3>
                </div>

                <div class="mt-2">
                    <p class="text-sm text-gray-300">
                        {{ props.species.is_active
                            ? 'Are you sure you want to archive this species? This will make it invisible to regular users.'
                            : 'Are you sure you want to unarchive this species? This will make it visible to all users again.' }}
                    </p>
                </div>

                <div class="mt-4">
                    <label for="admin-password" class="block text-sm font-medium text-gray-400">
                        Confirm by entering your password
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="admin-password"
                            v-model="form.password"
                            class="text-black mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Enter your password"
                        />
                    </div>
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>
                <div class="flex my-4">
                    <Checkbox name="showPassword" v-model:checked="showPassword" />
                    <span class="ms-2 text-sm text-white">Show Password</span>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <DangerButton @click="archiveSpecies">
                        {{ props.species.is_active ? 'Archive' : 'Unarchive' }}
                    </DangerButton>
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
