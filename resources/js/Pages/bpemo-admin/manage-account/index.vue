<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { usePage } from '@inertiajs/inertia-vue3';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    type: String,
    users: Array,
});

const userRole = computed(() => {
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
});

const title = computed(() => `Manage Accounts (${userRole.value})`);

const PER_PAGE = 10; // Number of items per page

const currentPage = ref(parseInt(page.props.pagination?.current_page, 10) || 1);

const paginatedUsers = computed(() => {
  const startIndex = (currentPage.value - 1) * PER_PAGE;
  const endIndex = startIndex + PER_PAGE;
  return props.users.slice(startIndex, endIndex);
});

const hasMorePages = computed(() => {
  return props.users.length > PER_PAGE * currentPage.value;
});

const totalPages = computed(() => {
  return Math.ceil(props.users.length / PER_PAGE);
});
</script>

<template>
    <Head title="BPEMO Admin Accounts" />
    <Sidebar>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <!-- Table Container -->
        <div class="container mx-auto px-7 py-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-center">{{ userRole }}s List</h2>
                <button
                    type="button"
                    class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    Create
                </button>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left border border-gray-300">ID</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Name</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Email</th>
                            <th class="px-4 py-2 text-left border border-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in paginatedUsers" :key="user.id">
                            <td class="px-4 py-2 border border-gray-300">{{ user.id }}</td>
                            <td class="px-4 py-2 border border-gray-300">{{ user.first_name }} {{ user.last_name }}</td>
                            <td class="px-4 py-2 border border-gray-300">{{ user.email }}</td>
                            <td class="px-4 py-2 border border-gray-300 flex justify-center items-center">
                                <button class="px-4 py-2 bg-indigo-700 text-white rounded hover:bg-indigo-900">
                                    View
                                </button>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-center items-center">
                <button
                    v-if="currentPage > 1"
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 mr-2"
                    @click="currentPage--"
                >
                    Previous
                </button>

                <span v-for="page in totalPages" :key="page" class="mx-2">
                    <button
                        class="px-4 py-2 rounded-full"
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
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 ml-2"
                    @click="currentPage++"
                >
                    Next
                </button>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Ensure the table scrolls horizontally on smaller screens */
@media (max-width: 640px) {
  .overflow-x-auto {
    overflow-x: auto;
  }

  table {
    width: 100%;
    min-width: 800px; /* You can adjust this according to your data */
  }

  th, td {
    padding: 0.75rem;
    text-align: center;
  }

  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
