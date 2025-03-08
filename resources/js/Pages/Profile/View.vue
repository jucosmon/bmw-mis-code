<script setup>
import Modal from '@/Components/Modal.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';

const props = defineProps({
    municipalities: {
        type: Array,
        required: true,
        default: () => []
    },
    barangays: {
        type: Array,
        required: true,
        default: () => []
    }
});

const page = usePage();
const user = page.props.auth.user;

const userRole = computed(() => {
    switch (user.user_role) {
        case 'bpemo_admin':
            return 'BPEMO Administrator';
        case 'bpemo_staff':
            return 'BPEMO Staff';
        case 'lgu_responder':
            return 'LGU Responder';
        case 'barangay_official':
            return 'Barangay Official';
        case 'public_user':
            return 'Public User';
        default:
            return 'Unknown User';
    }
});

const municipalityName = computed(() => {
    if (!user.municipality_id) return null;
    const municipality = props.municipalities.find(m => m.id === user.municipality_id);
    return municipality?.name;
});

const barangayName = computed(() => {
    if (!user.barangay_id) return null;
    const barangay = props.barangays.find(b => b.id === user.barangay_id);
    return barangay?.name;
});

const showUpdatePasswordModal = ref(false);
const successMessage = ref('');

const updateUser = () => router.visit(route('profile.edit'));
const closeModal = () => showUpdatePasswordModal.value = false;
const handleCancel = () => showUpdatePasswordModal.value = false;
const handleSuccess = () => {
    showUpdatePasswordModal.value = false;
    successMessage.value = 'You have successfully updated your password!';
};

// Get user initials for avatar
const userInitials = computed(() => {
    return `${user.first_name[0]}${user.last_name[0]}`.toUpperCase();
});

// Check if user is public
const isPublicUser = computed(() => user.user_role === 'public_user');
</script>

<template>
    <Head title="My Profile" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Profile
            </h2>
        </template>
        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- Success Message -->
            <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded-lg flex items-center" role="alert">
                <span class="material-icons material-icons-round mr-2">check_circle</span>
                <span>{{ successMessage }}</span>
            </div>

            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-8">
                    <div class="flex flex-col sm:flex-row items-center">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center text-2xl font-bold text-indigo-700 shadow-lg transform hover:scale-105 transition-transform duration-200">
                            {{ userInitials }}
                        </div>
                        <div class="ml-0 sm:ml-6 mt-4 sm:mt-0 text-center sm:text-left">
                            <h1 class="text-2xl font-bold text-white">{{ user.first_name }} {{ user.last_name }}</h1>
                            <div class="flex items-center justify-center sm:justify-start text-indigo-200 mt-1">
                                <span class="material-icons material-icons-round text-sm mr-2">badge</span>
                                <p>{{ userRole }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="p-6 grid md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                            <span class="material-icons material-icons-round mr-2 text-indigo-600">person</span>
                            Personal Information
                        </h2>

                        <div class="space-y-3">
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">email</span>
                                <span class="ml-3">{{ user.email }}</span>
                            </div>
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">phone</span>
                                <span class="ml-3">{{ user.contact_number }}</span>
                            </div>
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">cake</span>
                                <span class="ml-3">{{ new Date(user.birthdate).toLocaleDateString() }}</span>
                            </div>
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">{{ user.sex.toLowerCase() === 'male' ? 'male' : 'female' }}</span>
                                <span class="ml-3">{{ user.sex }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Work & Location - Only show for non-public users -->
                    <div v-if="!isPublicUser" class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                            <span class="material-icons material-icons-round mr-2 text-indigo-600">work</span>
                            Work & Location
                        </h2>

                        <div class="space-y-3">
                            <div v-if="user.position" class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">business_center</span>
                                <span class="ml-3">{{ user.position }}</span>
                            </div>
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">location_on</span>
                                <span class="ml-3">
                                    {{ municipalityName || 'No Information' }}
                                    {{ barangayName ? `, ${barangayName}` : '' }}
                                </span>
                            </div>
                            <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <span class="material-icons material-icons-round text-indigo-600">event</span>
                                <span class="ml-3">Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-4">
                    <button
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center group"
                        @click="showUpdatePasswordModal = true"
                    >
                        <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 transition-transform">key</span>
                        Change Password
                    </button>
                    <button
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center group"
                        @click="updateUser"
                    >
                        <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 transition-transform">edit</span>
                        Edit Profile
                    </button>
                </div>
            </div>

            <!-- Password Modal -->
            <Modal :show="showUpdatePasswordModal" @close="closeModal">
                <div class="p-6">
                    <UpdatePasswordForm
                        :onCancel="handleCancel"
                        :onSuccess="handleSuccess"
                    />
                </div>
            </Modal>
        </div>
    </Sidebar>
</template>

