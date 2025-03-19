<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props
const props = defineProps({
    onCancel: {
        type: Function,
        default: () => {}, // Default no-op function
    },
    onSuccess: {
        type: Function,
        required: true, // Parent must provide this function
    },
});

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();  // Reset the form first
            props.onSuccess(); // Call the onSuccess function passed from the parent
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="modal-wrapper">
        <div class="password-update-container">
            <header>
                <h2 class="title-gradient mb-2">
                    Update Password
                </h2>

                <p class="text-sm text-gray-300 text-center">
                    Ensure your account is using a long, random password to stay secure.
                </p>
            </header>

            <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
                <div class="form-group">
                    <InputLabel for="current_password" value="Current Password" class="text-white" />
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="input-field"
                        autocomplete="current-password"
                    />
                    <InputError :message="form.errors.current_password" class="mt-2" />
                </div>

                <div class="form-group">
                    <InputLabel for="password" value="New Password" class="text-white" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="input-field"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="form-group">
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-white" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="input-field"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>

                <div class="flex items-center justify-between gap-4">
                    <button
                        type="button"
                        class="text-sm text-gray-300 hover:text-white transition-colors duration-200"
                        @click="onCancel"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="form.processing" class="nav-button login-btn">
                        Save
                    </PrimaryButton>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-green-400">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </div>
    </section>
</template>

<style scoped>
.modal-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100%;
    width: 100%;
    padding: 0;
}

.password-update-container {
    background: transparent;
    padding: 2rem;
    border-radius: 16px;
    width: 100%;
    max-width: 550px;
    box-shadow: none;
    border: none;
    margin: 0 auto;
}

.title-gradient {
    font-size: 2.2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #0077be);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

.input-field {
    @apply mt-2 block w-full rounded-xl border-0 shadow-sm;
    background: rgba(0, 95, 175, 0.15);
    backdrop-filter: blur(4px);
    color: white;
    padding: 0.75rem 1rem;
    height: 2.75rem;
    transition: all 0.3s ease;
}

.input-field:focus {
    @apply ring-1 ring-blue-500;
    background: rgba(0, 95, 175, 0.25);
    transform: translateY(-1px);
}

.nav-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-btn {
    background: linear-gradient(135deg, #005f9f, #0077be);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 119, 190, 0.3);
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 119, 190, 0.4);
}
</style>
