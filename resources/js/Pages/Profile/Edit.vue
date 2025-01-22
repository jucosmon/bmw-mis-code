<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const municipalities = ref([]);
const barangays = ref([]);

onMounted(async () => {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    // Fetch barangays for the user's municipality
    if (page.props.auth.user.municipality_id) {
        await fetchBarangays(); // This will populate the barangays
    }
});

// Fetch barangays based on selected municipality
const fetchBarangays = async (municipalityId = page.props.auth.user.municipality_id) => {
    if (!municipalityId) return; // Prevent unnecessary fetch
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
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
            <div>
                <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-900 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                    <Link :href="route('profile.view')" class="flex items-center">
                        Back
                    </Link>
                </button>
            </div>
        </template>

        <div class="container mx-auto px-4 py-8">
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Your Profile</h2>

            <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
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
                            <TextInput id="first_name" type="text" v-model="form.first_name" required autocomplete="first_name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.first_name" />
                        </div>

                        <div>
                            <InputLabel for="last_name" value="Last Name" />
                            <TextInput id="last_name" type="text" v-model="form.last_name" required autocomplete="last_name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.last_name" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" type="email" v-model="form.email" required autocomplete="email" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="contact_number" value="Contact Number" />
                            <TextInput id="contact_number" type="text" v-model="form.contact_number" @keydown="allowOnlyNumbers" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.contact_number" />
                        </div>

                        <div>
                            <InputLabel for="birthdate" value="Birth Date" />
                            <TextInput id="birthdate" type="date" v-model="form.birthdate" required class="w-full" />
                            <InputError class="mt-2" :message="form.errors.birthdate" />
                        </div>

                        <div>
                            <InputLabel for="sex" value="Sex" />
                            <select id="sex" v-model="form.sex" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select user's sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Prefer Not to Say</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.sex" />
                        </div>
                        <div class="col-span-2" v-if="page.props.auth.user.user_role!=='public_user'">
                            <div>
                                <InputLabel for="position" value="User's Position" />
                                <TextInput id="position" type="text" v-model="form.position" required class="w-full" />
                                <InputError class="mt-2" :message="form.errors.position" />
                            </div>
                        </div>
                        <div v-if="page.props.auth.user.user_role!=='public_user'">
                            <InputLabel for="municipality_id" value="Municipality" />
                            <select id="municipality_id" v-model="form.municipality_id" @change="fetchBarangays(form.municipality_id)" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select a municipality</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                    {{ municipality.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.municipality_id" />
                        </div>

                        <div v-if="page.props.auth.user.user_role!=='public_user'">
                            <InputLabel for="barangay_id" value="Barangay" />
                            <select id="barangay_id" v-model="form.barangay_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select a barangay</option>
                                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
                                    {{ barangay.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.barangay_id" />
                        </div>

                    </div>

                    <!-- Submit and Cancel Buttons -->
                    <div class="flex items-center justify-between mt-6">
                        <Link :href="route('profile.view')" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900">
                            Update Account
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Sidebar>
</template>
