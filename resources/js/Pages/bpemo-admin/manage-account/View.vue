<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
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
</script>

<template>
    <Head title= "View User Account" />

    <Sidebar>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                {{userRole}}'s Account Information
            </h2>
        </template>
        <div class="container mx-auto px-7 py-8">
            <h2 class="text-xl font-semibold text-center">Account</h2>
            <!-- Add max width and center the form -->
            <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">

            </div>
        </div>
    </Sidebar>
</template>
