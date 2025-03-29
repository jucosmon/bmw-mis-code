<template>
    <button
        :class="['custom-btn', variant]"
        :type="type"
        @click="handleClick"
    >
        <span class="material-icons material-icons-rounded text-md block sm:hidden">{{ icon }}</span>
        <span class="button-text">
            <slot />
        </span>
    </button>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        default: 'button'
    },
    variant: {
        type: String,
        default: 'primary'
    },
    onClick: {
        type: [Function, Array, String],
        default: null
    },
    icon: {
        type: String,
        required: true
    }
});

const handleClick = (event) => {
    if (typeof props.onClick === 'function') {
        props.onClick(event);
    } else if (typeof props.onClick === 'string') {
        router.visit(props.onClick);
    }
};
</script>

<style>
.custom-btn {
    @apply px-4 py-2.5 text-sm font-medium inline-flex items-center justify-center gap-2;
    border-radius: 50px;
    min-width: 140px;
    text-align: center;
    transition: all 0.3s ease;
}

.custom-btn.primary {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.custom-btn.secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #374151;
    border: 1px solid #d1d5db;
    color: white;
}

.custom-btn.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.custom-btn:hover {
    transform: translateY(-1px);
}

.custom-btn.primary:hover {
    background: linear-gradient(135deg, #00b3e6, #00d9ff);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.custom-btn.secondary:hover {
    background: rgba(243, 244, 246, 0.1);
    border-color: #9ca3af;
}

.custom-btn.danger:hover {
    background: linear-gradient(135deg, #f87171, #ef4444);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.custom-btn:disabled {
    opacity: 0.25;
    cursor: not-allowed;
    transform: none;
}

@media (max-width: 640px) {
    .custom-btn {
        min-width: auto;
        padding: 0.625rem;
        aspect-ratio: 1;
    }

    .button-text {
        display: none;
    }

    .icon {
        margin: 0;
    }
}
</style>
