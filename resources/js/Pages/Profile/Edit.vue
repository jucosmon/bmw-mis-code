<script setup>
import CustomButton from '@/Components/CustomButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
const maxDate = new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().split('T')[0];

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

const positions = computed(() => {
    switch (page.props.auth.user.user_role) {
        case 'bpemo_admin':
            return ['BPEMO CRM Division Head', 'BPEMO Head'];
        case 'bpemo_staff':
            return ['BPEMO CRM Staff', 'BPEMO CRM Coordinator'];
        case 'lgu_responder':
            return ['LGU Official', 'LGU Staff', 'LGU MAO Staff', 'LGU MAO Fisheries Technician'];
        case 'barangay_official':
            return ['Barangay Captain', 'Barangay Kagawad', 'Barangay Secretary', 'Barangay Treasurer'];
        default:
            return ['Invalid'];
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
    if (form.contact_number && (form.contact_number.length !== 10 || form.contact_number[0] !== '9')) {
        alert("Contact number must be 10 digits long and start with '9'.");
        return; // Prevent form submission
    }
    const hasChanges = Object.keys(form.data())
    .filter(key => {
        // Exclude fields not applicable to public users
        if (page.props.auth.user.user_role === 'public_user') {
            return ['first_name', 'last_name', 'email', 'contact_number', 'birthdate', 'sex'].includes(key);
        }
        return true;
    })
    .some((key) => {
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
        <div class="relative min-h-screen bg-image">
            <!-- Background gradient overlay -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-16">
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
                                <InputLabel for="email" value="Email"/>
                                <TextInput
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="email"
                                    class="w-full disabled:opacity-75 disabled:bg-gray-700/50 disabled:border-gray-600 disabled:cursor-not-allowed"
                                    disabled
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="contact_number" value="Contact Number" class="text-gray-700"/>
                                <div class="flex items-center w-full">
                                    <span class="text-gray-100 pr-2 pt-2">+63</span>
                                    <TextInput
                                        id="contact_number"
                                        type="text"
                                        v-model="form.contact_number"
                                        @keydown="allowOnlyNumbers"
                                        maxlength="10"
                                        class="w-full transition duration-150 ease-in-out flex-1"
                                        placeholder="9XXXXXXXXX"
                                    />
                                </div>
                                <InputError class="mt-2" :message="form.errors.contact_number" />
                            </div>

                            <div class="space-y-2">
                                <InputLabel for="birthdate" value="Birth Date" class="text-gray-700" />
                                <TextInput
                                    id="birthdate"
                                    type="date"
                                    :max="maxDate"
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
                                    <InputLabel for="position" value="User's Position" class="text-gray-700"/>
                                    <select id="position" v-model="form.position" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition duration-150 ease-in-out">
                                        <option value="" disabled>Select user's position</option>
                                        <option v-for="position in positions" :key="position" :value="position">
                                            {{ position }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.position" />
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
                        <div class="flex items-center justify-end gap-4 sm:justify-between mt-8">
                            <CustomButton
                                :onClick="route('profile.view')"
                                variant="secondary"
                                icon="cancel"
                            >
                                Cancel
                            </CustomButton>
                            <CustomButton
                                icon="save"
                                type="submit"
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                            >
                                Save
                            </CustomButton>
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
:deep(input:not([disabled])),
:deep(select) {
    @apply bg-white/10 border-white/20 text-white placeholder-white/60;
    @apply focus:border-[#003366] focus:ring-[#003366];
}

/* Add specific styles for disabled inputs */
:deep(input:disabled) {
    @apply bg-gray-700/50 border-gray-600 text-gray-400;
    @apply cursor-not-allowed;
}

:deep(label) {
    @apply text-white;
}

:deep(.primary-button) {
    @apply bg-[#003366] hover:bg-[#004488] text-white;
    @apply focus:ring-2 focus:ring-offset-2 focus:ring-[#003366];
}

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
