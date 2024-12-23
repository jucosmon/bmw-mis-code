<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 px-4 sm:px-6 lg:px-8"
    >
        <div>
            <Link href="/">
                <div class="text-center py-4">
                    <h1 class="text-3xl font-bold text-gray-800">BMWMIS</h1>
                </div>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg mb-10"
        >
            <slot />
        </div>
    </div>
</template>
