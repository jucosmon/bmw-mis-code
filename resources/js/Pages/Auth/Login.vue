<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

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

        <!-- Login Form Container -->
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4">
            <div class="login-container">
                <h2 class="title-gradient mb-6">Sign In</h2>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <InputLabel for="email" value="Email" class="text-white" />
                        <TextInput
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            class="input-field"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="form-group mt-4">
                        <InputLabel for="password" value="Password" class="text-white" />
                        <TextInput
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            class="input-field"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-white">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-blue-300 hover:text-blue-200"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="nav-button login-btn" :disabled="form.processing">
                            <span class="button-content">Sign In</span>
                        </button>

                        <p class="text-center text-white text-sm mt-4">
                            Don't have an account?
                        </p>
                        <Link :href="route('register')" class="block text-center mt-2">
                            <span class="title-gradient-medium">Create Account</span>
                        </Link>
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
    padding: 2.5rem;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
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

.input-field {
    @apply mt-2 block w-full rounded-xl border-0 shadow-sm;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    color: white;
    padding: 0.75rem 1rem;
    height: 2.75rem;
    transition: all 0.3s ease;
}

.input-field:focus {
    @apply ring-1 ring-blue-400;
    background: rgba(255, 255, 255, 0.12);
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
    width: 100%;
}

.login-btn {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
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

.title-gradient-small {
    font-weight: 600;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-left: 0.25rem;
    transition: opacity 0.3s ease;
    font-size: 0.95rem;
}

.title-gradient-small:hover {
    opacity: 0.8;
}

.title-gradient-medium {
    font-size: 1.4rem;
    font-weight: 600;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

@media (max-width: 640px) {
    .login-container {
        padding: 2rem;
        margin: 1rem;
        max-width: 95%;
    }

    .title-gradient {
        font-size: 1.8rem;
    }

    .nav-button {
        padding: 0.75rem 1.5rem;
    }
}

@media (max-width: 768px) {
    .login-container {
        padding: 1.75rem;
        margin: 1rem;
        max-width: 90%;
    }

    .title-gradient {
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }

    .input-field {
        height: 2.5rem;
        font-size: 0.9rem;
    }

    .nav-button {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
    }

    .title-gradient-medium {
        font-size: 1.2rem;
    }
}

@media (max-width: 480px) {
    .login-container {
        padding: 1.5rem;
        margin: 0.5rem;
        margin-top: 2rem;
    }

    .title-gradient {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .input-field {
        height: 2.4rem;
        font-size: 0.85rem;
        padding: 0.6rem 0.9rem;
    }

    .nav-button {
        padding: 0.65rem 1.25rem;
        font-size: 0.9rem;
    }

    .logo-container {
        height: 40px;
        padding: 0.5rem;
    }

    .title-gradient-medium {
        font-size: 1rem;
    }

    .form-group {
        margin-bottom: 0.75rem;
    }
}

@media (max-height: 667px) {
    .login-container {
        padding: 1.25rem;
        margin: 1rem auto;
        max-height: 90vh;
        overflow-y: auto;
    }

    .title-gradient {
        font-size: 1.4rem;
        margin-bottom: 0.75rem;
    }

    .form-group {
        margin-bottom: 0.5rem;
    }

    .nav-button {
        padding: 0.6rem 1rem;
    }

    .logo-container {
        height: 35px;
        padding: 0.25rem;
    }
}

@media (orientation: landscape) and (max-height: 500px) {
    .login-container {
        margin: 4rem auto;
    }

    .logo-container {
        display: none;
    }
}

@media (max-height: 600px) {
    .login-container {
        margin: 3rem auto;
    }
}
</style>
