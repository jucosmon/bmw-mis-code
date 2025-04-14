<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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
const showPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Password validation indicators
const passwordLength = computed(() => form.password.length >= 8);
const passwordHasUppercase = computed(() => /[A-Z]/.test(form.password));
const passwordHasLowercase = computed(() => /[a-z]/.test(form.password));
const passwordHasNumber = computed(() => /[0-9]/.test(form.password));
const passwordHasSpecial = computed(() => /[^A-Za-z0-9]/.test(form.password));

// Overall password strength
const passwordStrength = computed(() => {
    const criteria = [
        passwordLength.value,
        passwordHasUppercase.value,
        passwordHasLowercase.value,
        passwordHasNumber.value,
        passwordHasSpecial.value
    ];

    const metCriteria = criteria.filter(c => c).length;

    if (metCriteria === 0) return { text: "Very Weak", color: "red" };
    if (metCriteria === 1) return { text: "Weak", color: "red" };
    if (metCriteria === 2) return { text: "Fair", color: "orange" };
    if (metCriteria === 3) return { text: "Good", color: "yellow" };
    if (metCriteria === 4) return { text: "Strong", color: "lightgreen" };
    return { text: "Very Strong", color: "green" };
});

const updatePassword = () => {
    // First validate the password complexity
    if (!passwordLength.value) {
        alert("Password must be at least 8 characters long.");
        return;
    }

    if (!passwordHasUppercase.value || !passwordHasLowercase.value || !passwordHasNumber.value) {
        alert("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
        return;
    }

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
                        :type="showPassword ? 'text' : 'password'"
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
                        :type="showPassword ? 'text' : 'password'"
                        class="input-field"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />

                    <!-- Password strength indicators -->
                    <div class="password-requirements mt-2" v-if="form.password">
                        <div class="password-strength">
                            <span class="text-sm text-white">Strength: </span>
                            <span class="text-sm" :style="{color: passwordStrength.color}">{{ passwordStrength.text }}</span>
                        </div>
                        <ul class="text-xs space-y-1 mt-1">
                            <li :class="passwordLength ? 'text-green-400' : 'text-white'">✓ At least 8 characters</li>
                            <li :class="passwordHasUppercase ? 'text-green-400' : 'text-white'">✓ At least one uppercase letter</li>
                            <li :class="passwordHasLowercase ? 'text-green-400' : 'text-white'">✓ At least one lowercase letter</li>
                            <li :class="passwordHasNumber ? 'text-green-400' : 'text-white'">✓ At least one number</li>
                            <li :class="passwordHasSpecial ? 'text-green-400' : 'text-white'">✓ Special character (recommended)</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-white" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="input-field"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>
                <div class="flex my-4">
                    <Checkbox name="showPassword" v-model:checked="showPassword" />
                    <span class="ms-2 text-sm text-white">Show Password</span>
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

/* Password strength indicator styles */
.password-requirements {
    color: white;
    opacity: 0.9;
}

.password-strength {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
</style>
