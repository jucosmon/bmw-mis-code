<script setup>
import Modal from '@/Components/Modal.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';

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

const municipalityData = ref([]);
const barangayData = ref([]);

const municipalityName = computed(() => {
    if (!user.municipality_id) return 'Unknown Municipality';
    const municipality = municipalityData.value.find(
        (m) => m.id === user.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    if (!user.barangay_id) return 'Unknown Barangay';
    const barangay = barangayData.value.find(
        (b) => b.id === user.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

const municipalityId = ref(null);
const barangays = ref([]);

const fetchBarangays = async () => {
  try {
    if (!municipalityId.value) {
      throw new Error('Municipality ID is not set');
    }
    const response = await axios.get(`/barangays?municipality_id=${municipalityId.value}`);
    barangays.value = response.data;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

onMounted(async () => {
    try {
        const municipalityResponse = await axios.get('/municipalities');
        municipalityData.value = municipalityResponse.data;

        municipalityId.value = user.municipality_id;

        if (municipalityId.value) {
            fetchBarangays();
        } else {
            console.warn('Municipality ID is not set');
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    }
});

const updateUser = ()=> {
    Inertia.visit(route('profile.edit'));
}

//update password user modal

const showUpdatePasswordModal = ref(false);

const confirmDisableUser = () => {
    showUpdatePasswordModal.value = true;
}
const closeModal = () => {
    showUpdatePasswordModal.value = false;
}

const successMessage = ref('');

const handleCancel = () => {
    showUpdatePasswordModal.value = false;
};

const handleSuccess = () => {
    showUpdatePasswordModal.value = false;
    successMessage.value = 'You have successfully updated your password!'; // Show the success message
};


// Initialize the form
const form = useForm({
    is_active: true, // or any other default values you need
    password: '',
});
</script>

<template>
    <Head title="My Profile"/>
    <Sidebar>

        <div class="container mx-auto px-6 pb-6 relative max-w-5xl">
            <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded relative" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ successMessage }}</span>
            </div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-6 rounded-lg shadow-lg relative z-10 mb-8">
                <h1 class="text-3xl font-bold">My Profile</h1>
                <p class="text-sm mt-2">({{ userRole }})</p>
            </div>

            <!-- User Info Section -->
            <div class="space-y-6">
                <div class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 mr-2 text-indigo-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path d="M10 10a3 3 0 100-6 3 3 0 000 6zm-6 8a6 6 0 1112 0H4z" />
                        </svg>
                        Personal Information
                    </h2>
                    <div class="space-y-2">
                        <p><strong>ID:</strong> {{ user.id }}</p>
                        <p>
                            <strong>Name:</strong> {{ user.first_name }}
                            {{ user.last_name }}
                        </p>
                        <p><strong>Email:</strong> {{ user.email }}</p>
                        <p><strong>Phone:</strong> {{ user.contact_number }}</p>
                        <p><strong>Sex:</strong> {{ user.sex }}</p>
                        <p v-if="user.user_role!=='public_user' && user.position"><strong>Position:</strong> {{ user.position }}</p>
                        <p>
                            <strong>Birthdate:</strong>
                            {{ new Date(user.birthdate).toLocaleDateString() }}
                        </p>
                        <p v-if="user.user_role!=='public_user' && user.municipality_id && user.barangay_id"><strong>Address:</strong> {{ barangayName }}, {{ municipalityName }}</p>

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
                        Account Details
                    </h2>
                    <div class="space-y-2">
                        <p><strong>Role:</strong> {{ userRole }}</p>
                        <p><strong>Status:</strong> {{ user.is_active ? 'Active' : 'Inactive' }}</p>
                        <p>
                            <strong>Created At:</strong>
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-4 mt-8">
                    <button
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-red-700 hover:scale-105 hover:shadow-lg transition"
                        @click="confirmDisableUser"
                        v-if="user.is_active===true"
                    >
                        Change Password
                    </button>
                    <Modal :show="showUpdatePasswordModal" @close="closeModal" v-if="user.is_active === true">
                        <div class="p-6">

                            <UpdatePasswordForm
                            :onCancel="handleCancel"
                            :onSuccess="handleSuccess"
                            />
                        </div>
                    </Modal>


                    <button
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg  hover:bg-red-700 hover:scale-105 hover:shadow-lg transition"
                        @click="updateUser()"

                    >
                        Update Profile
                    </button>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

