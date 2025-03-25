<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

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

        <!-- Reset Password Form Container -->
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4">
            <div class="login-container">
                <h2 class="title-gradient mb-6">Reset Password</h2>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <InputLabel for="email" value="Email" class="text-white" />
                        <TextInput
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            class="input-field"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="form-group mt-4">
                        <InputLabel for="password" value="New Password" class="text-white" />
                        <TextInput
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            class="input-field"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="form-group mt-4">
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-white" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            required
                            class="input-field"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="nav-button login-btn" :disabled="form.processing">
                            <span class="button-content">
                                <i class="fas fa-key mr-2"></i>
                                Reset Password
                            </span>
                        </button>

                        <p class="text-center text-white text-sm mt-4">
                            Remember your password?
                            <Link :href="route('login')" class="signup-link">
                                Sign in
                            </Link>
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

.form-group {
    margin-bottom: 1rem;
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

@media (max-width: 640px) {
    .login-container {
        padding: 2rem;
        margin: 1rem;
    }

    .title-gradient {
        font-size: 2rem;
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

    .form-group {
        margin-bottom: 1.25rem;
    }
}

@media (max-width: 480px) {
    .login-container {
        padding: 1.5rem;
        margin: 0.75rem;
    }

    .title-gradient {
        font-size: 1.6rem;
        margin-bottom: 1.25rem;
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

    .form-group {
        margin-bottom: 1rem;
    }

    .logo-container {
        height: 45px;
    }
}

@media (max-height: 600px) {
    .login-container {
        margin: 3rem auto;
    }
}
</style>
