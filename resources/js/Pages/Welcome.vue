<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Marine Wildlife System" />

    <div class="relative min-h-screen">
        <!-- Background -->
        <div class="absolute inset-0">
            <img src="/images/background.jpg" alt="Ocean Background" class="object-cover w-full h-full">
            <div class="absolute inset-0 bg-gradient-overlay"></div>
        </div>

        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="p-2 pl-2 z-10 flex justify-start absolute top-0 left-0">
                <div class="flex items-center h-[80px]">
                    <img src="/images/white_on_trans.png" class="h-[70px]" alt="Marine Wildlife Logo">
                </div>
            </div>
            <!-- Main Content Area -->
            <div class="text-center max-w-5xl mx-auto mt-16">
                <h1 class="title-gradient">BOHOL MARINE WILDLIFE</h1>
                <h3 class="subtitle-gradient">MANAGEMENT INFORMATION SYSTEM</h3>

                <!-- Auth Buttons Moved Here -->
                <div v-if="canLogin" class="auth-buttons">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')" class="nav-button dashboard-btn">
                            Access Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="nav-button login-btn">
                            <span class="button-content">
                                <i class="fas fa-user mr-2"></i>
                                <span>Sign In</span>
                            </span>
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="nav-button register-btn">
                            <span class="button-content">
                                <i class="fas fa-user-plus mr-2"></i>
                                <span>Create Account</span>
                            </span>
                        </Link>
                    </template>
                </div>
                <template v-if="!$page.props.auth.user">
                    <div class="mt-8 mb-4">
                        <Link :href="route('dashboard')" class="nav-button dashboard-btn pulse-animation mt-3">
                            <span class="button-content text-lg">
                                <i class="fas fa-exclamation-circle mr-3"></i>
                                <span>Report Wildlife Incident</span>
                            </span>
                        </Link>
                        <p class="text-white text-sm mt-2 opacity-80">Quick incident reporting - no login required</p>
                    </div>
                </template>
            </div>

            <!-- Footer Quote -->
            <div class="absolute bottom-0 w-full text-center pb-6">
                <p class="quote-text">
                    "The Ocean is a vast sanctuary, teeming with life to be understood and protected"
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.title-gradient {
    font-size: 5rem; /* Reduced from 6.5rem */
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: 2px; /* Slightly reduced */
    text-transform: uppercase;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
    margin-bottom: 0.5rem;
}

.subtitle-gradient {
    font-size: 2rem; /* Reduced from 2.5rem */
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1.5px; /* Slightly reduced */
    text-transform: uppercase;
    background: linear-gradient(to right, #99ccff, #00a3cc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    margin-bottom: 3rem;
}

.nav-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 2rem;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.5px;
    margin: 0.5rem;
    min-width: 160px;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.button-content {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
    width: 100%;
    text-align: center;
}

.login-btn {
    color: #fff;
    border: 2px solid #00ccff;
    background: transparent;
    backdrop-filter: blur(5px);
}

.login-btn:hover {
    background: rgba(0, 204, 255, 0.15);
    transform: translateY(-3px);
    box-shadow: 0 7px 14px rgba(0, 204, 255, 0.2);
}

.register-btn {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.register-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 7px 20px rgba(0, 204, 255, 0.4);
    background: linear-gradient(135deg, #00ccff, #00a3cc);
}

.register-btn:active,
.login-btn:active {
    transform: translateY(-1px);
}

.dashboard-btn {
    background: linear-gradient(to right, #00a3cc, #00ccff);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.dashboard-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.quote-text {
    font-size: 1rem;
    color: #99ccff;
    font-style: italic;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.auth-buttons {
    margin-top: 1rem;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
    align-items: center;
}

@media (max-width: 1280px) {
    .title-gradient {
        font-size: 4.5rem;
    }
    .subtitle-gradient {
        font-size: 1.9rem;
    }
}

@media (max-width: 1024px) {
    .title-gradient {
        font-size: 4rem;
        letter-spacing: 1.5px;
    }
    .subtitle-gradient {
        font-size: 1.7rem;
        letter-spacing: 1.2px;
    }
    .auth-buttons {
        flex-direction: row;
        gap: 1rem;
    }
}

@media (max-width: 768px) {
    .title-gradient {
        font-size: 3rem;
        letter-spacing: 1px;
    }
    .subtitle-gradient {
        font-size: 1.4rem;
        letter-spacing: 1px;
        margin-bottom: 2rem;
    }
    .auth-buttons {
        flex-direction: column;
        gap: 0.75rem;
    }
    .nav-button {
        width: 100%;
        max-width: 250px;
        padding: 0.5rem 1.5rem;
        margin: 0.25rem 0;
    }
}

@media (max-width: 640px) {
    .title-gradient {
        font-size: 2.5rem;
        margin-bottom: 0.3rem;
    }
    .subtitle-gradient {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
    }
    .quote-text {
        font-size: 0.9rem;
        padding: 0 1.5rem;
    }
    .logo-container {
        height: 50px;
    }
}

@media (max-width: 480px) {
    .title-gradient {
        font-size: 2rem;
    }
    .subtitle-gradient {
        font-size: 1rem;
    }
    .nav-button {
        font-size: 0.85rem;
        padding: 0.45rem 1.25rem;
        max-width: 200px;
    }
    .logo-container {
        height: 40px;
    }
    .quote-text {
        font-size: 0.8rem;
        padding: 0 1rem;
    }
}

@media (max-height: 600px) {
    .title-gradient {
        margin-top: 3rem;
    }
    .quote-text {
        position: relative;
        margin-top: 2rem;
    }
}
</style>
