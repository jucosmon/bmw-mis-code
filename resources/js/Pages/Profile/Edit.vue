<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    municipalities: {
        type: Array,
        required: true
    },
    barangays: {
        type: Array,
        required: true
    }
});

const page = usePage();
const barangaysList = ref(props.barangays);

// Filter barangays based on selected municipality
const fetchBarangays = (municipalityId) => {
    if (!municipalityId) return;
    barangaysList.value = props.barangays.filter(
        barangay => barangay.municipality_id === parseInt(municipalityId)
    );
};

const userRole = computed(() => {
    switch (page.props.auth.user.user_role) {
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

// Initialize form with existing user data for updating
const form = useForm({
    first_name: page.props.auth.user.first_name || '',
    last_name: page.props.auth.user.last_name || '',
    email: page.props.auth.user.email || '',
    contact_number: page.props.auth.user.contact_number || '',
    birthdate: page.props.auth.user.birthdate || '',
    sex: page.props.auth.user.sex || '',
    position: page.props.auth.user.position || '',
    municipality_id: page.props.auth.user.municipality_id || '',
    barangay_id: page.props.auth.user.barangay_id || ''
});

const formErrors = ref(null);
const submit = () => {
    if (form.contact_number.length < 11) {
        alert("Contact number must be at least 11 digits long.");
        return; // Prevent form submission
    }
    // Check for changes in the form data compared to page.props.auth.user
    const hasChanges = Object.keys(form.data()).some((key) => {
        return form.data()[key] !== page.props.auth.user[key];
    });

    // Alert based on whether changes were detected
    if (hasChanges) {
        form.patch(route('profile.update'), {
        onSuccess: () => {
            formErrors.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
    } else {
        alert('No changes detected in the form.');
    }
};

const allowOnlyNumbers = (event) => {
    const key = event.key;
    const isNumber = /^[0-9]$/.test(key);
    const isControlKey = ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(key);

    if (!isNumber && !isControlKey) {
        event.preventDefault(); // Prevent the default action if the key is not a number or control key
    }
};

</script>

<template>
    <Head title="Update Profile" />
    <Sidebar>
        <template #header>
            <div class="p-4">
                <button class="inline-flex items-center px-4 py-2 bg-transparent border border-white/20 rounded-lg shadow-sm hover:bg-[#003366] hover:text-white focus:ring-2 focus:ring-[#003366]/40 focus:outline-none transition-all duration-200 ease-in-out text-white">
                    <Link :href="route('profile.view')" class="flex items-center space-x-2">
                        <span>← Back</span>
                    </Link>
                </button>
            </div>
        </template>

        <div class="relative min-h-screen bg-image">
            <!-- Background gradient overlay -->
            <div class="absolute inset-0 bg-gradient-overlay"></div>

            <!-- Content -->
            <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h2 class="profile-title-gradient text-center mb-8">Update Your Profile</h2>

                <div class="max-w-4xl mx-auto oceanic-container p-4 sm:p-6 lg:p-8 rounded-xl">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Error Messages -->
                        <div v-if="formErrors" class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg text-red-700">
                            <ul class="list-disc ml-4 space-y-1">
                                <li v-for="(error, index) in formErrors" :key="index" class="text-sm">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Form Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div class="space-y-2">
                                <InputLabel for="first_name" value="First Name" class="text-gray-700" />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    v-model="form.first_name"
                                    required
                                    autocomplete="first_name"
                                    class="w-full transition duration-150 ease-in-out"
                                />
                                <InputError class="mt-1" :message="form.errors.first_name" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="last_name" value="Last Name" class="text-gray-700" />
                                <TextInput
                                    id="last_name"
                                    type="text"
                                    v-model="form.last_name"
                                    required
                                    autocomplete="last_name"
                                    class="w-full transition duration-150 ease-in-out"
                                />
                                <InputError class="mt-1" :message="form.errors.last_name" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="email" value="Email" class="text-gray-700" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="email"
                                    class="w-full transition duration-150 ease-in-out"
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="contact_number" value="Contact Number" class="text-gray-700" />
                                <TextInput
                                    id="contact_number"
                                    type="text"
                                    v-model="form.contact_number"
                                    @keydown="allowOnlyNumbers"
                                    class="w-full transition duration-150 ease-in-out"
                                />
                                <InputError class="mt-1" :message="form.errors.contact_number" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="birthdate" value="Birth Date" class="text-gray-700" />
                                <TextInput
                                    id="birthdate"
                                    type="date"
                                    v-model="form.birthdate"
                                    required
                                    class="w-full transition duration-150 ease-in-out"
                                />
                                <InputError class="mt-1" :message="form.errors.birthdate" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="sex" value="Sex" class="text-gray-700" />
                                <select
                                    id="sex"
                                    v-model="form.sex"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition duration-150 ease-in-out"
                                >
                                    <option value="" disabled>Select user's sex</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Prefer Not to Say</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.sex" />
                            </div>

                            <div v-if="page.props.auth.user.user_role!=='public_user'" class="md:col-span-2">
                                <div class="space-y-2">
                                    <InputLabel for="position" value="User's Position" class="text-gray-700" />
                                    <TextInput
                                        id="position"
                                        type="text"
                                        v-model="form.position"
                                        required
                                        class="w-full transition duration-150 ease-in-out"
                                    />
                                    <InputError class="mt-1" :message="form.errors.position" />
                                </div>
                            </div>

                            <div v-if="page.props.auth.user.user_role!=='public_user'" class="space-y-2">
                                <InputLabel for="municipality_id" value="Municipality" class="text-gray-700" />
                                <select
                                    id="municipality_id"
                                    v-model="form.municipality_id"
                                    @change="fetchBarangays(form.municipality_id)"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition duration-150 ease-in-out"
                                >
                                    <option value="" disabled>Select a municipality</option>
                                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                        {{ municipality.name }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.municipality_id" />
                            </div>

                            <div v-if="page.props.auth.user.user_role!=='public_user'" class="space-y-2">
                                <InputLabel for="barangay_id" value="Barangay" class="text-gray-700" />
                                <select
                                    id="barangay_id"
                                    v-model="form.barangay_id"
                                    required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition duration-150 ease-in-out"
                                >
                                    <option value="" disabled>Select a barangay</option>
                                    <option v-for="barangay in barangaysList" :key="barangay.id" :value="barangay.id">
                                        {{ barangay.name }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.barangay_id" />
                            </div>
                        </div>

                        <!-- Submit and Cancel Buttons -->
                        <div class="flex items-center justify-between mt-8">
                            <Link
                                :href="route('profile.view')"
                                class="cancel-gradient-text font-medium text-base"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                                class="oceanic-button"
                            >
                                Update Account
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
.container {
    min-height: calc(100vh - 4rem);
}

.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.oceanic-container {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

/* Form element styles */
:deep(.form-input),
:deep(.form-select),
:deep(input),
:deep(select) {
    @apply bg-white/10 border-white/20 text-white placeholder-white/60;
    @apply focus:border-[#003366] focus:ring-[#003366];
}

:deep(label) {
    @apply text-white;
}

:deep(.primary-button) {
    @apply bg-[#003366] hover:bg-[#004488] text-white;
    @apply focus:ring-2 focus:ring-offset-2 focus:ring-[#003366];
}

/* Update text colors for better visibility */
:deep(input),
:deep(select),
:deep(option) {
    color: white;
}

:deep(option) {
    background-color: #003366;
}

/* Error styling */
:deep(.text-red-700) {
    @apply text-red-300;
}

:deep(.bg-red-50) {
    @apply bg-red-900/20 border-red-400;
}

.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 1px;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

@media (min-width: 768px) {
    .profile-title-gradient {
        font-size: 2.5rem;
    }
}

.bg-image {
    background-image: url('/images/landing.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

/* Updated button styling to match sign-in buttons */
.oceanic-button {
    @apply px-6 py-2.5 rounded-lg font-medium text-base tracking-wide;
    background: linear-gradient(
        to right,
        rgba(0, 51, 102, 0.9),
        rgba(0, 128, 255, 0.9)
    );
    border: none;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.oceanic-button:hover {
    background: linear-gradient(
        to right,
        rgba(0, 77, 153, 0.9),
        rgba(51, 153, 255, 0.9)
    );
    transform: translateY(-1px);
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
}

.oceanic-button:active {
    transform: translateY(1px);
}

/* Remove old gradient button styles */
.gradient-button {
    display: none;
}

/* Add cancel button gradient text styling */
.cancel-gradient-text {
    background: linear-gradient(to right, #ffffff, #003366);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-decoration: underline;
    transition: all 0.3s ease;
}

.cancel-gradient-text:hover {
    background: linear-gradient(to right, #ffffff, #004c99);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}
</style>
