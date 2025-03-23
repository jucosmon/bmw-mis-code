<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    type: String,
    users: Array,
    success: String,
});

const userRole = computed(() => {
    if(page.props.auth?.user?.user_role ==='lgu_responder'){
        return 'Barangay Official';
    } else {
        switch (props.type) {
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
const title = computed(() => `Manage Accounts (${userRole.value})`);

//pagination of 123 next
const PER_PAGE = 5; // Number of items per page

const currentPage = ref(parseInt(page.props.pagination?.current_page, 10) || 1);

const activeFilter = ref('active'); // Default filter

// Filtered Users
const filteredUsers = computed(() => {
    if (activeFilter.value === 'active') {
        return props.users.filter((user) => user.is_active);
    } else if (activeFilter.value === 'inactive') {
        return props.users.filter((user) => !user.is_active);
    }
    return props.users; // All users
});

// Paginated Users
const paginatedUsers = computed(() => {
    const startIndex = (currentPage.value - 1) * PER_PAGE;
    const endIndex = startIndex + PER_PAGE;
    return filteredUsers.value.slice(startIndex, endIndex);
});

// Update pagination calculations
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / PER_PAGE));

const hasMorePages = computed(() => filteredUsers.value.length > PER_PAGE * currentPage.value);

//button routes
const createUser = () => {
    if(page.props.auth?.user?.user_role==='lgu_responder'){
        router.get(route('lgu.responder.manage.account.create.page'));
    }else {
        router.get(route('bpemo.admin.manage.account.create.page', { type: props.type }));
    }
}
const viewUser = (user_id)=> {
    if(page.props.auth?.user?.user_role==='lgu_responder'){
        router.visit(route('lgu.responder.manage.account.view', {user_id: user_id}));
    }else{
        router.visit(route('bpemo.admin.manage.account.view', {user_id: user_id}));
    }
}
</script>

<template>
    <Head title="BPEMO Admin Accounts" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Success message -->
                    <div v-if="props?.success" class="glass-panel mb-6 p-4 border border-green-400/30 text-green-400">
                        <strong class="font-bold">Success! </strong>
                        <span>{{ props?.success}}</span>
                    </div>

                    <!-- Header section -->
                    <div class="mb-6">
                        <h3 class="profile-title-gradient mb-2 text-center">
                            {{ userRole }}s List
                        </h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                v-model="activeFilter"
                                class="filter-select"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <button v-if="type !== 'public_user'"
                                type="button"
                                @click="createUser"
                                class="create-button flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create
                            </button>
                        </div>
                    </div>

                    <!-- User Cards -->
                    <div class="space-y-2 sm:space-y-3">
                        <p v-if="filteredUsers.length < 1" class="text-gradient text-center mt-5">No users found</p>
                        <div v-for="user in paginatedUsers" :key="user.id" class="incident-card">
                            <div class="p-3 sm:p-4">
                                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center justify-between">
                                    <!-- ID and Name -->
                                    <div class="flex flex-row items-center gap-2 sm:gap-3 min-w-0">
                                        <span class="text-xs sm:text-sm font-medium text-white/60 flex-shrink-0">#{{ user.id }}</span>
                                        <h4 class="text-sm sm:text-base font-semibold text-white truncate">{{ user.first_name }} {{ user.last_name }}</h4>
                                        <span :class="{
                                            'status-badge': true,
                                            'status-active': user.is_active,
                                            'status-inactive': !user.is_active
                                        }">
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>

                                    <!-- View Button -->
                                    <button
                                        @click="viewUser(user.id)"
                                        class="view-button hidden sm:flex sm:items-center sm:gap-1"
                                    >
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </button>
                                </div>

                                <!-- Info Row -->
                                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 items-start sm:items-center mt-2 sm:mt-3 text-xs sm:text-sm text-white/70">
                                    <!-- Email -->
                                    <span class="flex items-center min-w-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="truncate">{{ user.email }}</span>
                                    </span>

                                    <!-- Position -->
                                    <span v-if="type !== 'public_user'" class="flex items-center min-w-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="truncate">{{ user.position }}</span>
                                    </span>
                                </div>

                                <!-- View Button (mobile only) -->
                                <div class="mt-3 sm:hidden">
                                    <button
                                        @click="viewUser(user.id)"
                                        class="view-button w-full flex items-center justify-center gap-1"
                                    >
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6 flex justify-center items-center gap-2">
                        <button
                            v-if="currentPage > 1"
                            class="pagination-button"
                            @click="currentPage--"
                        >
                            Previous
                        </button>

                        <span v-for="page in totalPages" :key="page" class="mx-1">
                            <button
                                class="pagination-button"
                                :class="{
                                    'bg-blue-500 text-white': currentPage === page,
                                    'bg-gray-200 hover:bg-gray-300': currentPage !== page,
                                }"
                                @click="currentPage = page"
                            >
                                {{ page }}
                            </button>
                        </span>

                        <button
                            v-if="currentPage < totalPages"
                            class="pagination-button"
                            @click="currentPage++"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Ocean theme styling */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.text-gradient{
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Glass panels */
.glass-panel {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.incident-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.incident-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.12);
}

/* Buttons */
.filter-select, .view-button, .create-button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filter-select {
    background: rgba(0, 51, 102, 0.5);
    color: white;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.view-button {
    background: rgba(77, 171, 247, 0.2);
    color: #4dabf7;
    border-color: rgba(77, 171, 247, 0.3);
}

.create-button {
    background: linear-gradient(
        135deg,
        rgba(0, 102, 204, 0.9) 0%,
        rgba(0, 153, 255, 0.8) 100%
    );
    color: white;
    border-color: rgba(0, 153, 255, 0.3);
}

.view-button:hover, .create-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

.view-button:hover {
    background: rgba(77, 171, 247, 0.3);
}

.create-button:hover {
    background: linear-gradient(
        135deg,
        rgba(0, 153, 255, 0.95) 0%,
        rgba(0, 102, 204, 0.85) 100%
    );
}

/* Status badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-active {
    background: rgba(109, 213, 167, 0.15);
    color: #84e4b8;
    border-color: rgba(109, 213, 167, 0.3);
}

.status-inactive {
    background: rgba(255, 107, 107, 0.15);
    color: #ff8f8f;
    border-color: rgba(255, 107, 107, 0.3);
}

/* Pagination buttons */
.pagination-button {
    background: rgba(0, 51, 102, 0.5);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.pagination-button:hover {
    background: rgba(0, 71, 142, 0.6);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .filter-select, .view-button, .create-button, .pagination-button {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .profile-title-gradient {
        font-size: 1.25rem;
    }

    .status-badge {
        padding: 0.125rem 0.5rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .incident-card {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>
