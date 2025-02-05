<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
    Inertia.visit(updateRoute.value);
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
</script>

<template>
    <Head title="View Species" />
    <Sidebar>
        <template #header>
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-700 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                <Link :href="backRoute" class="flex items-center">
                    Back
                </Link>
            </button>
        </template>

        <div class="container mx-auto px-6 pb-6 max-w-5xl">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ props?.success}}</span>
            </div>
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-6 rounded-lg shadow-lg mb-8">
                <h1 class="text-3xl font-bold">Species Information</h1>
                <p class="text-sm mt-2">({{ speciesCategory }})</p>
            </div>

            <div class="space-y-6">
                <div class="bg-white shadow-lg rounded-xl p-6">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-9-4h2v2H9V6zm0 4h2v6H9v-6z" />
                        </svg>
                        Species Details
                    </h2>
                    <div class="space-y-2">
                        <p><strong>Name:</strong> {{ props.species.name }}</p>
                        <p><strong>Scientific Name:</strong> {{ props.species.scientific_name }}</p>
                        <p><strong>Common Name:</strong> {{ props.species.common_name }}</p>
                        <p><strong>Local Name:</strong> {{ props.species.local_name }}</p>
                        <p><strong>Description:</strong> {{ props.species.description }}</p>
                        <p><strong>Conservation Status:</strong> {{ props.species.conservation_status }}</p>
                        <p><strong>Maximum Size:</strong> {{ props.species.max_size }} cm</p>
                        <p><strong>Shape:</strong> {{ props.species.shape }} </p>
                        <p><strong>Dangerous:</strong> {{ props.species.is_dangerous ? 'Yes' : 'No' }}</p>
                        <p><strong>Status:</strong> {{ props.species.is_active ? 'Active' : 'Inactive' }}</p>
                         <!-- Colors Section -->
                        <div v-if="species.speciesColors.length" class="flex gap-1">
                            <p><strong>Colors:</strong></p>
                            <ul class="flex flex-wrap gap-2">
                                <li
                                    v-for="color in species.speciesColors"
                                    :key="color.id"
                                    :style="{ backgroundColor: color.color.name }"
                                    class="w-6 h-6 rounded-full border border-gray-400"
                                ></li>
                            </ul>
                        </div>

                    </div>
                </div>
                <div class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 mr-2 text-indigo-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path d="M4.75 4A2.75 2.75 0 002 6.75v6.5A2.75 2.75 0 004.75 16h10.5A2.75 2.75 0 0018 13.25v-6.5A2.75 2.75 0 0015.25 4H4.75zM9.5 8.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H10.5a.75.75 0 01-.75-.75zm-3.25 4.25a.75.75 0 110-1.5h7.5a.75.75 0 110 1.5H6.25z" />
                        </svg>
                        Images
                    </h2>
                    <div v-if="props.species.mediaFiles && props.species.mediaFiles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="file in props.species.mediaFiles"
                            :key="file.id"
                            class="bg-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition"
                        >
                            <img
                                :src="file.url"
                                :alt="`Image of ${props.species.name}`"
                                class="w-full h-48 object-cover"
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No images available</p>
                </div>


                <div v-if="page.props.auth.user.user_role==='bpemo_admin'" class="flex justify-end space-x-4 mt-8">
                    <button
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition"
                        @click="confirmArchiveSpecies"
                        v-if="props.species.is_active"
                    >
                        Archive Species
                    </button>
                    <button
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
                        @click="confirmArchiveSpecies"
                        v-if="props.species.is_active===false"
                    >
                        Unarchive Species
                    </button>

                    <Modal :show="showConfirmArchiveModal" @close="closeModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                               {{ props.species.is_active ? 'Are you sure you want to archive this species?' : 'Are you sure you want to unarchive this species?'}}
                            </h2>
                            <div class="mt-4">
                                <label for="admin-password" class="text-sm text-gray-500">
                                    Confirm by entering your password
                                </label>
                                <input
                                    type="password"
                                    id="admin-password"
                                    v-model="form.password"
                                    class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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

                    <button
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="updateSpecies"
                    >
                        Update Species
                    </button>
                </div>
            </div>
        </div>
    </Sidebar>
</template>
