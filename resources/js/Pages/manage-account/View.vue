<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    municipalities: Array,
    barangays: Array,
    success: String,
});

// Defined routes based on the current user's role
const backRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.index');
    } else {
        return route('bpemo.admin.manage.account.index', { type: props.user.user_role });
    }
});

const updateRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.update.page', {
            user_id: props.user.id,
            });
    } else {
        return route('bpemo.admin.manage.account.update.page', {
            user_id: props.user.id,
            type: props.user.user_role
            });
    }
});

const disableRoute = computed(()=>{
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.disable', {
            user_id: props.user.id,
            });
    } else {
        return route('bpemo.admin.manage.account.disable', {
            user_id: props.user.id,
            type: props.user.user_role
        });
    }
})

const activateRoute = computed(()=>{
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.activate', {
            user_id: props.user.id,
            });
    } else {
        return route('bpemo.admin.manage.account.activate', {
            user_id: props.user.id,
            type: props.user.user_role
        });
    }
})

const userRole = computed(() => {
    if(page.props.auth.user.user_role ==='lgu_responder'){
        return 'Barangay Official';
    } else {
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
    }
});

const municipalityName = computed(() => {
    const municipality = props.municipalities.find(
        (m) => m.id === props.user.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = props.barangays.find(
        (b) => b.id === props.user.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

const updateUser = ()=> {
    router.visit(updateRoute.value);
}

//disable user modal
const showConfirmDisableUserModal = ref(false);

const confirmDisableUser = () => {
    showConfirmDisableUserModal.value = true;
}
const closeModal = () => {
    showConfirmDisableUserModal.value = false;
}

// Initialize the form
const form = useForm({
    is_active: true,
    password: '',
});

const disableUser = ()=> {
    if(props.user.is_active){
        form.put(disableRoute.value, {
            onSuccess: () => {
                closeModal();
                form.reset('password');
            },
            onError: (errors) => {
                console.error(errors);
            },
        });
    }else{
        form.put(activateRoute.value, {
            onSuccess: () => {
                closeModal();
                form.reset('password');
            },
            onError: (errors) => {
                console.error(errors);
            },
        });
    }
}
</script>

<template>
    <Head title="View User Account"/>
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                View Account
            </h2>
        </template>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative container mx-auto px-4 py-8 max-w-4xl">
                <!-- Success Message -->
                <div v-if="props?.success" class="success-alert">
                    <span class="material-icons material-icons-round mr-2">check_circle</span>
                    <span>{{ props?.success }}</span>
                </div>

                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="flex flex-col sm:flex-row items-center">
                            <div class="avatar">
                                {{ props.user.first_name[0] }}{{ props.user.last_name[0] }}
                            </div>
                            <div class="ml-0 sm:ml-6 mt-4 sm:mt-0 text-center sm:text-left">
                                <h1 class="profile-title-gradient">{{ props.user.first_name }} {{ props.user.last_name }}</h1>
                                <div class="role-badge">
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
                            <h2 class="text-lg font-semibold border-b pb-2 flex items-center text-white border-opacity-20">
                                <span class="material-icons material-icons-round mr-2">person</span>
                                Personal Information
                            </h2>
                            <div class="space-y-3">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">pin</span>
                                    <span class="ml-3">ID: {{ props.user.id }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">email</span>
                                    <span class="ml-3">{{ props.user.email }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">phone</span>
                                    <span class="ml-3">{{ props.user.contact_number }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">{{ props.user.sex.toLowerCase() === 'male' ? 'male' : 'female' }}</span>
                                    <span class="ml-3">{{ props.user.sex }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">cake</span>
                                    <span class="ml-3">{{ new Date(props.user.birthdate).toLocaleDateString() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Work & Location -->
                        <div v-if="props.user.user_role !== 'public_user'" class="space-y-4">
                            <h2 class="text-lg font-semibold border-b pb-2 flex items-center text-white border-opacity-20">
                                <span class="material-icons material-icons-round mr-2">work</span>
                                Work & Location
                            </h2>
                            <div class="space-y-3">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">business_center</span>
                                    <span class="ml-3">{{ props.user.position }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">location_on</span>
                                    <span class="ml-3">{{ barangayName }}, {{ municipalityName }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">event</span>
                                    <span class="ml-3">Joined {{ new Date(props.user.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">verified_user</span>
                                    <span class="ml-3">Status: {{ props.user.is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="props.user.user_role!=='bpemo_admin'" class="px-6 py-4 bg-gray-50 flex justify-end space-x-4">
                        <button
                            v-if="props.user.is_active===true"
                            class="px-4 py-2 bg-red-gradient text-white rounded-lg hover:bg-red-700 transition duration-200 flex items-center group"
                            @click="confirmDisableUser"
                        >
                            <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 transition-transform">block</span>
                            Disable Account
                        </button>
                        <button
                            v-if="props.user.is_active===false"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center group"
                            @click="confirmDisableUser"
                        >
                            <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 transition-transform">check_circle</span>
                            Activate Account
                        </button>
                        <button
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center group"
                            @click="updateUser()"
                        >
                            <span class="material-icons material-icons-round mr-2 group-hover:rotate-12 transition-transform">edit</span>
                            Update Account
                        </button>
                    </div>
                </div>

                <!-- Modal -->
                <Modal :show="showConfirmDisableUserModal" @close="closeModal">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-slate-800">
                            {{ props.user.is_active ? 'Are you sure you want to disable this account?' : 'Are you sure you want to activate this account?' }}
                        </h2>

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

                        <div class="mt-6 space-x-4 flex justify-end">
                            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                            <DangerButton @click="disableUser()">Confirm</DangerButton>
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
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

/* Profile Card */
.profile-card {
    @apply bg-white rounded-xl shadow-lg overflow-hidden mb-6;
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.profile-header {
    @apply px-6 py-8;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.avatar {
    @apply w-24 h-24 rounded-full flex items-center justify-center text-2xl font-bold shadow-lg transform hover:scale-105 transition-transform duration-200;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
}

.role-badge {
    @apply flex items-center justify-center sm:justify-start text-indigo-200 mt-1;
}

.profile-content {
    @apply p-6 grid md:grid-cols-2 gap-6;
}

.info-section {
    @apply space-y-4;
}

.section-title {
    @apply text-lg font-semibold border-b pb-2 flex items-center;
    color: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.15);
}

.info-list {
    @apply space-y-3;
}

.info-item {
    @apply flex items-center p-2 rounded-lg transition-colors;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.9);
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateX(4px);
}

.action-section {
    @apply px-6 py-4 flex justify-end space-x-4;
    background: rgba(0, 51, 102, 0.2);
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.success-alert {
    @apply bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4 rounded-lg flex items-center;
    background: rgba(220, 252, 231, 0.1) !important;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(134, 239, 172, 0.2);
}

/* Text Styling */
.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

/* Button Styling */
button {
    @apply px-4 py-2 rounded-lg flex items-center transition duration-200;
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 100%
    ) !important;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

.material-icons-round {
    color: rgba(0, 204, 255, 0.9) !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-title-gradient {
        font-size: 1.5rem;
    }
    .container {
        padding: 1rem;
    }
    .profile-card {
        margin: 0.5rem;
    }
}

/* Information Row Styling */
.info-row {
    @apply flex items-center p-2 rounded-lg transition-all duration-200;
    color: rgba(255, 255, 255, 0.9);
}

.info-row span {
    color: rgba(255, 255, 255, 0.9) !important;
}

.info-row:hover {
    transform: translateX(4px);
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(4px);
}

.info-row .material-icons-round {
    transition: color 0.3s ease;
}

.info-row:hover .material-icons-round {
    color: #00ccff !important;
}

/* Button Gradients */
.action-button-gradient {
    @apply px-4 py-2 rounded-lg flex items-center transition-all duration-300;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
}

.action-button-gradient.primary {
    background: linear-gradient(135deg, #4f46e5, #3730a3) !important;
}

.action-button-gradient.danger {
    background: linear-gradient(135deg, #dc2626, #991b1b) !important;
}

.action-button-gradient.success {
    background: linear-gradient(135deg, #059669, #065f46) !important;
}

.action-button-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    filter: brightness(110%);
}

.action-button-gradient:active {
    transform: translateY(0);
}

/* Group hover effects */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

/* Updated Button Styles */
.bg-gray-50 {
    background: rgba(0, 51, 102, 0.2) !important;
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

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

/* Icon Animation */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

button .material-icons-round {
    color: #00ccff !important;
    transition: all 0.3s ease;
}

/* Red gradient button */
.bg-red-gradient {
    background: linear-gradient(
        135deg,
        rgb(220, 38, 38),
        rgb(222, 89, 89)
    ) !important;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.bg-red-gradient:hover {
    background: linear-gradient(
        135deg,
        rgb(239, 68, 68),
        rgb(215, 74, 74)
    ) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
    border-color: rgba(255, 255, 255, 0.3);
}
</style>

