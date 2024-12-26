<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <Sidebar>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                BPEMO Staff Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        Successfully Logged In!
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>
</template>
