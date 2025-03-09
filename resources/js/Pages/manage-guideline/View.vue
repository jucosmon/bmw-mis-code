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

        <div class="max-w-4xl mx-auto p-6">
            <div v-if="props?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded">
                <strong>Success! </strong> {{ props?.success }}
            </div>
            <div class="space-x-2 flex mt-4 justify-end items-end">
                <DangerButton v-if="props.guideline.is_active" @click="showArchiveModal()">Archive</DangerButton>
                <DangerButton v-else @click="showArchiveModal()">Unarchive</DangerButton>
                <Modal :show="archiveModal" @close="closeArchiveModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-slate-800">
                                {{ props.guideline.is_active ? 'Are you sure you want to archive this guideline?' : 'Are you sure you want to unarchive this guideline?' }}
                            </h2>

                            <!-- Password Input -->
                            <div class="mt-4">
                                <label for="admin-password" class="text-sm text-gray-500 mt-2">
                                    Please confirm by entering your password
                                </label>
                                <input
                                    type="password"
                                    id="admin-password"
                                    v-model="form.password"
                                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <!-- Actions -->
                            <div class="mt-6 space-x-4 flex justify-end">
                                <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
                                <DangerButton @click="archive()">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>
                <PrimaryButton v-if="props.guideline.is_active" @click="updateButton">Update</PrimaryButton>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg mb-6 ">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ props.guideline.title }}</h1>
                    <p class="text-gray-600 mt-2">{{ props.guideline.description }}</p>
                    <div class="text-sm text-gray-500 mt-2 ">
                        <span class="mr-4">Category: {{ props.guideline.category }}</span>
                        <span> User Role: {{ props.guideline.user_role }}</span>
                    </div>
                    <p class="mt-2 text-sm font-semibold" :class="{'text-green-600': props.guideline.is_active, 'text-red-600': !props.guideline.is_active}">
                        {{ props.guideline.is_active ? 'Active' : 'Inactive' }}
                    </p>
                </div>

            </div>

            <div class="mt-6">
                <h2 class="text-xl font-bold text-gray-800">Guidelines</h2>
                <div v-for="(item, index) in sortedItems" :key="item.id" class="bg-gray-50 p-4 rounded-lg shadow-sm mt-4">
                    <h3 class="text-lg font-semibold">• {{ item.text }}</h3>
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
                    <p v-else class="text-gray-500 mt-2">No media files available.</p>
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
</style>
