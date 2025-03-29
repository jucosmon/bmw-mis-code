<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const formErrors = ref(null);

const props = defineProps({
    type: String,
    municipalities: {
        type: Array,
        required: true
    },
    barangays: {
        type: Array,
        required: true
    },
});

const maxDate = new Date().toISOString().split('T')[0];

onMounted(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        const userMunicipalityId = page.props.auth.user.municipality_id;
        form.municipality_id = userMunicipalityId;
    }
});

const filteredBarangays = computed(() => {
    if (!form.municipality_id) return [];
    return props.barangays.filter(b => b.municipality_id === form.municipality_id);
});

const userRole = computed(() => {
  switch (props.type) {
    case 'bpemo_admin':
      return 'BPEMO Administrator';
    case 'bpemo_staff':
      return 'BPEMO Staff';
    case 'lgu_responder':
      return 'LGU Responder';
    case 'barangay_official':
      return 'Barangay Official';
    case 'public_user':
      return 'Public User';
    default:
      return 'Unknown User';
  }
});

// defined routes for different current user type
const backRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.index', { type: 'barangay_official' });
    } else {
        return route('bpemo.admin.manage.account.index', { type: props.type });
    }
});

const createRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.create', { type: 'barangay_official' });
    } else {
        return route('bpemo.admin.manage.account.create', { type: props.type });
    }
});
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    contact_number:'',
    birthdate: '',
    sex:'',
    position:'',
    municipality_id: '',
    barangay_id: ''
});

const submit = () => {
    // Check if contact number is at least 11 characters
    if (form.contact_number && form.contact_number.length < 11) {
        // Optionally, set an error message or handle it as needed
        alert("Contact number must be at least 11 digits long.");
        return; // Prevent form submission
    }

    form.post(createRoute.value, {
        onSuccess: () => {
            formErrors.value = null; // Clear errors on successful submission
        },
        onError: (errors) => {
            formErrors.value = errors; // Set errors on failed submission
        },
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
<Sidebar>

    <Head title="Create Account" />

    <div class="min-h-screen relative">
        <!-- Background Image with Overlay -->
        <div class="fixed inset-0">
            <img src="/images/landing.jpg" alt="Background" class="w-full h-full object-cover">
            <div class="bg-gradient-overlay absolute inset-0"></div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10">
            <div class="container mx-auto px-4 py-16">
                <h2 class="title-gradient mb-6">Create an Account ({{ userRole }})</h2>

                <div class="create-container mx-auto">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Error Messages -->
                        <div v-if="formErrors" class="p-4 bg-red-100 border border-red-400 rounded-lg text-red-600">
                            <ul class="list-disc ml-4">
                                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Form Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="first_name" value="First Name" />
                                <TextInput id="first_name" type="text" v-model="form.first_name" required autocomplete="first_name" class="input-field" />
                                <InputError class="mt-2" :message="form.errors.first_name" />
                            </div>

                            <div>
                                <InputLabel for="last_name" value="Last Name" />
                                <TextInput id="last_name" type="text" v-model="form.last_name" required autocomplete="last_name" class="input-field" />
                                <InputError class="mt-2" :message="form.errors.last_name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" type="email" v-model="form.email" required autocomplete="email" class="input-field" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="contact_number" value="Contact Number" />
                                <TextInput id="contact_number" type="text" v-model="form.contact_number" @keydown="allowOnlyNumbers" class="input-field" />
                                <InputError class="mt-2" :message="form.errors.contact_number" />
                            </div>

                            <div>
                                <InputLabel for="birthdate" value="Birth Date" />
                                <TextInput id="birthdate" type="date" :max="maxDate" v-model="form.birthdate" required class="input-field" />
                                <InputError class="mt-2" :message="form.errors.birthdate" />
                            </div>

                            <div>
                                <InputLabel for="sex" value="Sex" />
                                <select id="sex" v-model="form.sex" required class="input-field">
                                    <option value="" disabled>Select user's sex</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Prefer Not to Say</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.sex" />
                            </div>
                        </div>

                        <!-- Position and Location -->
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="position" value="User's Position" />
                                <TextInput id="position" type="text" v-model="form.position" required class="input-field" />
                                <InputError class="mt-2" :message="form.errors.position" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="municipality_id" value="Municipality" />
                                    <select id="municipality_id" v-model="form.municipality_id"
                                            :disabled="page.props.auth?.user?.user_role === 'lgu_responder'"
                                            required class="input-field">
                                        <option value="" disabled>Select a municipality</option>
                                        <option v-for="municipality in props.municipalities" :key="municipality.id" :value="municipality.id">
                                            {{ municipality.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.municipality_id" />
                                </div>
                                <div>
                                    <InputLabel for="barangay_id" value="Barangay" />
                                    <select id="barangay_id" v-model="form.barangay_id" required class="input-field">
                                        <option value="" disabled>Select a barangay</option>
                                        <option v-for="barangay in filteredBarangays" :key="barangay.id" :value="barangay.id">
                                            {{ barangay.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.barangay_id" />
                                </div>
                            </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="flex items-center justify-between mt-6">
                            <Link :href="backRoute" class="text-sm text-white hover:text-gray-200 underline">Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="oceanic-button"
                                :class="{ 'opacity-25': form.processing }"
                            >
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</Sidebar>
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

.create-container {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    padding: 2.5rem;
    border-radius: 16px;
    width: 100%;
    max-width: 1024px; /* Adjusted for create form */
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

.input-field::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.input-field option {
    background: #003366;
    color: white;
}

/* Add these new styles */
:deep(label) {
    color: white;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

:deep(.text-gray-500),
:deep(.text-gray-700) {
    color: rgba(255, 255, 255, 0.8);
}

:deep(.text-gray-500:hover),
:deep(.text-gray-700:hover) {
    color: white;
}

/* Error message styling */
:deep(.text-red-600) {
    color: #ff9999;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.bg-red-100 {
    background: rgba(255, 0, 0, 0.1);
    border-color: rgba(255, 0, 0, 0.2);
}

/* Oceanic Button Style */
.oceanic-button {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 100%
    ) !important;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
}

.oceanic-button:hover:not(:disabled) {
    background: linear-gradient(
        135deg,
        rgba(0, 64, 128, 0.95) 0%,
        rgba(0, 51, 102, 0.85) 100%
    ) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    border-color: rgba(255, 255, 255, 0.3);
}

.oceanic-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 51, 102, 0.2);
}

.oceanic-button:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

/* Add responsive styles */
@media (max-width: 1024px) {
    .create-container {
        max-width: 90%;
        padding: 2rem;
    }
}

@media (max-width: 768px) {
    .create-container {
        padding: 1.75rem;
        margin: 1rem;
    }

    .title-gradient {
        font-size: 1.8rem;
    }
}

@media (max-width: 640px) {
    .create-container {
        padding: 1.5rem;
        margin: 0.5rem;
    }

    .title-gradient {
        font-size: 1.5rem;
    }
}
</style>


