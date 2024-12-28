<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const userRole = computed(() => {
    switch (props.user.user_role) {
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
    const municipality = municipalityData.value.find(
        (m) => m.id === props.user.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = barangayData.value.find(
        (b) => b.id === props.user.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

onMounted(async () => {
    try {
        const municipalityResponse = await axios.get('/municipalities');
        municipalityData.value = municipalityResponse.data;

        const barangayResponse = await axios.get(
            `/barangays?municipality_id=${props.user.municipality_id}`
        );
        barangayData.value = barangayResponse.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
});
</script>

<template>
    <Sidebar>
        <template #header>
            <div>
                <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-900 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                    <Link :href="route('bpemo.admin.manage.account.index', {type: props.user.user_role})" class="flex items-center">
                        Back
                    </Link>
                </button>
            </div>
        </template>
        <div class="container mx-auto px-6 pb-6 relative max-w-5xl">
            <!-- Background Waves -->
            <div class="absolute inset-0 bg-[url('/images/waves.svg')] bg-cover opacity-10 pointer-events-none"></div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-6 rounded-lg shadow-lg relative z-10 mb-8">
                <h1 class="text-3xl font-bold">User Account Information</h1>
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
                        <p>
                            <strong>Name:</strong> {{ props.user.first_name }}
                            {{ props.user.last_name }}
                        </p>
                        <p><strong>Email:</strong> {{ props.user.email }}</p>
                        <p><strong>Phone:</strong> {{ props.user.contact_number }}</p>
                        <p><strong>Sex:</strong> {{ props.user.sex }}</p>
                        <p v-if="props.user.user_role!=='public_user'"><strong>Position:</strong> {{ props.user.position }}</p>
                        <p>
                            <strong>Birthdate:</strong>
                            {{ new Date(props.user.birthdate).toLocaleDateString() }}
                        </p>
                        <p v-if="props.user.user_role!=='public_user'"><strong>Address:</strong> {{ barangayName }}, {{ municipalityName }}</p>

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
                        <p><strong>Status:</strong> {{ props.user.is_active ? 'Active' : 'Inactive' }}</p>
                        <p>
                            <strong>Created At:</strong>
                            {{ new Date(props.user.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="props.user.user_role!=='bpemo_admin'" class="flex justify-end space-x-4 mt-8">
                    <button
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 hover:scale-105 hover:shadow-lg transition"
                    >
                        Disable Account
                    </button>
                    <button
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:scale-105 hover:shadow-lg transition"
                    >
                        Update Account
                    </button>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

