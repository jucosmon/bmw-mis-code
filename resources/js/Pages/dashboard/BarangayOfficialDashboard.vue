<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Dashboard" />

    <Sidebar>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Barangay Official Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <p class="text-center">Welcome {{ user.first_name  }} {{ user.last_name }}, you've successfully logged in</p>
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>
