<script setup>
const props = defineProps({
    shape: {
        type: String,
        required: true
    }
});
</script>

<template>
    <div class="w-8 h-8">
        <svg v-if="shape.toLowerCase() === 'round'" viewBox="0 0 24 24" class="text-blue-300">
            <circle cx="12" cy="12" r="10" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
        </svg>
        
        <svg v-else-if="shape.toLowerCase() === 'elongated'" viewBox="0 0 24 12" class="text-blue-300">
            <rect x="2" y="2" width="20" height="8" rx="4" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
        </svg>
        
        <svg v-else-if="shape.toLowerCase() === 'flat'" viewBox="0 0 24 12" class="text-blue-300">
            <ellipse cx="12" cy="6" rx="10" ry="4" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
        </svg>
        
        <!-- Add more shape variants as needed -->
        <svg v-else viewBox="0 0 24 24" class="text-blue-300">
            <path d="M12 2L2 22h20L12 2z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
        </svg>
    </div>
</template>
