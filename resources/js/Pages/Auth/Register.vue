<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    terms_accepted: false,
});

const maxDate = new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().split('T')[0];

// State for the "Show Password" checkbox and terms modal
const showPassword = ref(false);
const showTermsModal = ref(false);

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

const submit = () => {
    // Check if contact number is at least 11 characters
    if (form.contact_number && (form.contact_number.length !== 10 || form.contact_number[0] !== '9')) {
        alert("Contact number must be 10 digits long and start with '9'.");
        return; // Prevent form submission
    }

    // Check if terms are accepted
    if (!form.terms_accepted) {
        alert("You must accept the Terms and Conditions to register.");
        return; // Prevent form submission
    }

    // Check password complexity
    if (!passwordLength.value) {
        alert("Password must be at least 8 characters long.");
        return;
    }

    if (!passwordHasUppercase.value || !passwordHasLowercase.value || !passwordHasNumber.value) {
        alert("Password must contain at least one uppercase letter, one lowercase letter, and one number.");
        return;
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

const toggleTermsModal = () => {
    showTermsModal.value = !showTermsModal.value;
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

        <!-- Logo container -->
        <div class="relative z-20 p-2">
            <Link href="/" class="flex items-center">
                <img src="/images/white_on_trans.png" style="height: 70px;" alt="Marine Wildlife Logo">
            </Link>
        </div>

        <!-- Register Form Container -->
        <div class="relative z-20 flex flex-col items-center justify-center min-h-[calc(100vh-80px)]">
            <div class="login-container">
                <h2 class="title-gradient mb-6">Create Account</h2>

                <form @submit.prevent="submit" class="space-y-3">
                    <!-- Two Column Layout for Names -->
                    <div class="grid grid-cols-7 gap-3">
                        <div class="form-group col-span-4">
                            <InputLabel for="first_name" value="First Name" class="form-label" />
                            <TextInput
                                id="first_name"
                                type="text"
                                v-model="form.first_name"
                                required
                                minlength="2"
                                class="input-field"
                                @keypress="(e) => {
                                    if (!/^[a-zA-Z\s]$/.test(e.key)) {
                                        e.preventDefault();
                                    }
                                }"
                            />
                            <InputError :message="form.errors.first_name" />
                            <span v-if="form.first_name.length > 0 && form.first_name.length < 2" class="text-xs text-red-400">
                                First name must be at least 2 characters
                            </span>
                        </div>

                        <div class="form-group col-span-3">
                            <InputLabel for="last_name" value="Last Name" class="form-label" />
                            <TextInput
                                id="last_name"
                                type="text"
                                v-model="form.last_name"
                                required
                                minlength="2"
                                class="input-field"
                                @keypress="(e) => {
                                    if (!/^[a-zA-Z\s]$/.test(e.key)) {
                                        e.preventDefault();
                                    }
                                }"
                            />
                            <InputError :message="form.errors.last_name" />
                            <span v-if="form.last_name.length > 0 && form.last_name.length < 2" class="text-xs text-red-400">
                                Last name must be at least 2 characters
                            </span>
                        </div>
                    </div>

                    <!-- Single Column Fields -->
                    <div class="form-group">
                        <InputLabel for="email" value="Email" class="form-label" />
                        <TextInput id="email" type="email" v-model="form.email" required class="input-field" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="contact_number" value="Contact Number" class="form-label" />
                        <div class="flex items-center w-full">
                            <span class="text-gray-100 pr-2 pt-2">+63</span>
                            <TextInput
                                id="contact_number"
                                type="text"
                                v-model="form.contact_number"
                                @keydown="allowOnlyNumbers"
                                maxlength="10"
                                class="input-field flex-1"
                                placeholder="9XXXXXXXXX"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.contact_number" />
                    </div>

                    <!-- Two Column Layout for Date and Sex -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <InputLabel for="birthdate" value="Birth Date" class="form-label" />
                            <TextInput id="birthdate" type="date" :max="maxDate" v-model="form.birthdate" required class="input-field" />
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

                        <!-- Password strength indicators -->
                        <div class="password-requirements mt-2">
                            <div class="password-strength" v-if="form.password">
                                <span class="text-sm">Strength: </span>
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
                        <InputLabel for="password_confirmation" value="Confirm Password" class="form-label" />
                        <TextInput id="password_confirmation" :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" required class="input-field" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex my-4">
                        <Checkbox name="showPassword" v-model:checked="showPassword" />
                        <span class="ms-2 text-sm text-white">Show Password</span>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-center mt-4">
                        <Checkbox name="terms" v-model:checked="form.terms_accepted" />
                        <span class="ms-2 text-sm text-white">
                            I agree to the
                            <button type="button" @click="toggleTermsModal" class="text-blue-400 hover:underline">Terms and Conditions</button>
                        </span>
                    </div>
                    <InputError :message="form.errors.terms_accepted" />

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

    <!-- Terms and Conditions Modal -->
    <div v-if="showTermsModal" class="terms-modal-container">
        <div class="terms-modal">
            <div class="terms-header">
                <h3 class="terms-title">Terms and Conditions</h3>
                <button @click="toggleTermsModal" class="close-button">&times;</button>
            </div>
            <div class="terms-content">
                <h4>1. Introduction</h4>
                <p>Welcome to BMWMIS. By registering for an account, you agree to comply with and be bound by the following terms and conditions.</p>

                <h4>2. Use of Service</h4>
                <p>You agree to use our service only for lawful purposes and in accordance with these Terms. You are responsible for maintaining the confidentiality of your account information.</p>

                <h4>3. User Content</h4>
                <p>Any content you submit through our platform may be used by Marine Wildlife for promotion, research, or educational purposes. We respect your privacy and will handle your data according to our Privacy Policy.</p>

                <h4>4. Restrictions</h4>
                <p>You may not use our services to post harmful, offensive, or illegal material, or to engage in activities that disrupt our services or harm marine wildlife.</p>

                <h4>5. Data Protection</h4>
                <p>We collect and process personal data as described in our Privacy Policy. By using our service, you consent to such processing and warrant that all data provided by you is accurate.</p>

                <h4>6. Termination</h4>
                <p>We reserve the right to terminate or suspend your account at our sole discretion, without notice, for conduct that we believe violates these Terms or is harmful to other users, us, or third parties, or for any other reason.</p>

                <h4>7. Changes to Terms</h4>
                <p>We may revise these Terms at any time by updating this page. You are expected to check this page from time to time to take notice of any changes we made.</p>

                <h4>8. Contact</h4>
                <p>If you have any questions about these Terms, please contact us at bmwmis.application@gmail.com</p>
            </div>
            <div class="terms-footer">
                <button @click="toggleTermsModal" class="blue-button">Close</button>
                <button @click="form.terms_accepted = true; toggleTermsModal();" class="green-button">Accept</button>
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
    position: relative;
    z-index: 30;
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

/* Password strength indicator styles */
.password-requirements {
    color: white;
    opacity: 0.8;
}

/* Terms and conditions modal styles */
.terms-modal-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.75);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 50;
    padding: 1rem;
}

.terms-modal {
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.95), rgba(0, 64, 128, 0.95));
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.terms-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.terms-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.close-button:hover {
    opacity: 1;
}

.terms-content {
    padding: 1.5rem;
    color: white;
    overflow-y: auto;
    flex: 1;
}

.terms-content h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    color: #00ccff;
}

.terms-content p {
    margin-bottom: 1rem;
    line-height: 1.5;
    opacity: 0.9;
    font-size: 0.95rem;
}

.terms-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    gap: 1rem;
}

.blue-button, .green-button {
    padding: 0.5rem 1.25rem;
    border-radius: 50px;
    font-weight: 500;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.blue-button {
    background: rgba(0, 153, 255, 0.2);
    color: #00ccff;
    border: 1px solid rgba(0, 204, 255, 0.3);
}

.blue-button:hover {
    background: rgba(0, 153, 255, 0.3);
}

.green-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
}

.green-button:hover {
    box-shadow: 0 0 15px rgba(0, 204, 255, 0.5);
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

    .terms-modal {
        max-height: 90vh;
    }
}

@media (max-width: 480px) {
    .login-container {
        padding: 1.5rem;
        margin: 0.5rem;
        margin-top: 0;
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

    .form-group {
        margin-bottom: 0.5rem;
    }

    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

@media (max-height: 700px) {
    .login-container {
        margin: 1rem auto;
        padding: 1.25rem;
    }

    .space-y-3 > * + * {
        margin-top: 0.4rem;
    }
}
</style>
