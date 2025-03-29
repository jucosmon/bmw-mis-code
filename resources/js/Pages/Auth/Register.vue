<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const formErrors = ref([]);
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    contact_number:'',
    birthdate: '',
    sex:'',

});

// State for the "Show Password" checkbox
const showPassword = ref(false);

const submit = () => {
    // Check if contact number is at least 11 characters
    if (form.contact_number && form.contact_number.length < 11) {
        // Optionally, set an error message or handle it as needed
        alert("Contact number must be at least 11 digits long.");
        return; // Prevent form submission
    }

    form.post(route('register'), {
        onSuccess: () => {
            formErrors.value = null; // Clear errors on successful submission
        },
        onError: (errors) => {
            formErrors.value = errors; // Set errors on failed submission
        },
        onFinish: () => form.reset('password', 'password_confirmation'), // Reset the entire form
    });

};

const allowOnlyNumbers = (event) => {
    // Allow only numbers (0-9), Backspace, Tab, and Arrow keys
    const key = event.key;
    const isNumber = /^[0-9]$/.test(key);
    const isControlKey = ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(key);

    if (!isNumber && !isControlKey) {
        event.preventDefault(); // Prevent the default action if the key is not a number or control key
    }
};
</script>

<template>
    <Head title="Register" />

    <div class="relative min-h-screen">
        <!-- Background -->
        <div class="absolute inset-0">
            <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
            <div class="absolute inset-0 bg-gradient-overlay"></div>
        </div>

        <!-- Logo -->
        <div class="w-full fixed top-0 left-0 right-0 p-6 z-10">
            <Link href="/" class="logo-container">
                <img src="/images/white_on_trans.png" alt="Marine Wildlife Logo" class="logo-image">
            </Link>
        </div>

        <!-- Register Form Container -->
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4">
            <div class="login-container">
                <h2 class="title-gradient mb-6">Create Account</h2>

                <form @submit.prevent="submit" class="space-y-3">
                    <!-- Two Column Layout for Names -->
                    <div class="grid grid-cols-7 gap-3">
                        <div class="form-group col-span-4">
                            <InputLabel for="first_name" value="First Name" class="form-label" />
                            <TextInput id="first_name" type="text" v-model="form.first_name" required class="input-field" />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="form-group col-span-3">
                            <InputLabel for="last_name" value="Last Name" class="form-label" />
                            <TextInput id="last_name" type="text" v-model="form.last_name" required maxlength="10" class="input-field" />
                            <InputError :message="form.errors.last_name" />
                        </div>
                    </div>

                    <!-- Single Column Fields -->
                    <div class="form-group">
                        <InputLabel for="email" value="Email" class="form-label" />
                        <TextInput id="email" type="email" v-model="form.email" required class="input-field" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="form-group">
                        <InputLabel for="contact_number" value="Contact Number" class="form-label" />
                        <TextInput id="contact_number" type="text" v-model="form.contact_number" @keydown="allowOnlyNumbers" class="input-field" />
                        <InputError :message="form.errors.contact_number" />
                    </div>

                    <!-- Two Column Layout for Date and Sex -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <InputLabel for="birthdate" value="Birth Date" class="form-label" />
                            <TextInput id="birthdate" type="date" v-model="form.birthdate" required class="input-field" />
                            <InputError :message="form.errors.birthdate" />
                        </div>

                        <div class="form-group">
                            <InputLabel for="sex" value="Sex" class="form-label" />
                            <select
                                id="sex"
                                v-model="form.sex"
                                required
                                class="input-field select-field"
                            >
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Prefer Not to Say</option>
                            </select>
                            <InputError :message="form.errors.sex" />
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div class="form-group">
                        <InputLabel for="password" value="Password" class="form-label" />
                        <TextInput id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password" required class="input-field" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="form-group">
                        <InputLabel for="password_confirmation" value="Confirm Password" class="form-label" />
                        <TextInput id="password_confirmation" :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" required class="input-field" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex my-4">
                        <Checkbox name="showPassword" v-model:checked="showPassword" />
                        <span class="ms-2 text-sm text-white">Show Password</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4">
                        <button type="submit" class="nav-button login-btn" :disabled="form.processing">
                            <span class="button-content">Create Account</span>
                        </button>

                        <p class="text-center text-white text-sm mt-3">
                            Already have an account?
                            <Link :href="route('login')" class="signup-link">Sign in</Link>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.75) 0%,
        rgba(0, 64, 128, 0.65) 50%,
        rgba(0, 31, 63, 0.75) 100%
    );
}

.login-container {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    padding: 2rem;
    border-radius: 16px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.title-gradient {
    font-size: 2.2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

.form-label {
    @apply text-white text-sm font-medium mb-1;
}

.input-field {
    height: 2.5rem;
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

.form-group {
    margin-bottom: 0.75rem;
}

.input-field {
    @apply mt-2 block w-full rounded-xl border-0 shadow-sm;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    color: white;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.input-field:focus {
    @apply ring-1 ring-blue-400;
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
}

select.input-field {
    appearance: none;
    background-image: url("data:image/svg+xml,...");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1em;
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
    width: 100%;
    letter-spacing: 0.5px;
}

.login-btn {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.signup-link {
    font-weight: 700;
    color: #00ccff;
    margin-left: 0.25rem;
    transition: all 0.3s ease;
}

.logo-container {
    height: 60px;
    display: flex;
    align-items: center;
    margin-left: 1rem;
}

.logo-image {
    height: 100%;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
    transition: transform 0.3s ease;
}

.select-field {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2399ccff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1em;
    padding-right: 2.5rem;
}

.select-field option {
    background-color: rgb(0, 51, 102);
    color: white;
    padding: 0.5rem;
}

/* Remove the gender-specific styles */
.gender-options,
.gender-option,
.gender-button {
    display: none;
}

@media (max-width: 480px) {
    .gender-options {
        flex-direction: column;
        gap: 0.5rem;
    }

    .gender-option {
        width: 100%;
    }

    .gender-button {
        width: 100%;
        justify-content: flex-start;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .login-container {
        padding: 1.75rem;
        margin: 1rem;
        max-width: 90%;
    }

    .title-gradient {
        font-size: 1.6rem;
        margin-bottom: 1.25rem;
    }

    .grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .input-field {
        height: 2.5rem;
        font-size: 0.9rem;
    }

    .nav-button {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
    }

    .space-y-3 > * + * {
        margin-top: 0.5rem;
    }
}

@media (max-width: 480px) {
    .login-container {
        padding: 1.5rem;
        margin: 0.75rem;
    }

    .title-gradient {
        font-size: 1.5rem;
    }

    .input-field {
        height: 2.4rem;
        font-size: 0.85rem;
        padding: 0.6rem 0.9rem;
    }

    .nav-button {
        padding: 0.7rem 1.25rem;
        font-size: 0.9rem;
    }

    .logo-container {
        height: 45px;
    }

    .form-group {
        margin-bottom: 0.5rem;
    }

    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

@media (max-height: 700px) {
    .login-container {
        margin: 4rem auto;
    }

    .space-y-3 > * + * {
        margin-top: 0.4rem;
    }
}
</style>
