<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const municipalities = ref([]);
const barangays = ref([]);
const props = defineProps({
    user:  {
        type: Object,
        required: true
    }
});

onMounted(async () => {
    const response = await fetch('/municipalities');
    municipalities.value = await response.json();

    // Fetch barangays for the user's municipality
    if (props.user.municipality_id) {
        await fetchBarangays(); // This will populate the barangays
    }
});

// Fetch barangays based on selected municipality
const fetchBarangays = async (municipalityId = props.user.municipality_id) => {
    if (!municipalityId) return; // Prevent unnecessary fetch
    const response = await fetch(`/barangays?municipality_id=${municipalityId}`);
    barangays.value = await response.json();
};


const page = usePage();

const userRole = computed(() => {
    if(page.props.auth.user.user_role ==='lgu_responder'){
        return 'Barangay Official';
    } else {
        switch (props.user.user_role) {
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
    }
});

// Defined Routes for user type
const backRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.view', {user_id: props.user.id});
    } else {
        return route('bpemo.admin.manage.account.view', {user_id: props.user.id});
    }
});

const updateRoute = computed(() => {
    if (page.props.auth?.user?.user_role === 'lgu_responder') {
        return route('lgu.responder.manage.account.update', {
            user_id: props.user.id,
        });
    } else {
        return route('bpemo.admin.manage.account.update', {
            user_id: props.user.id,
            type: props.user.user_role
        });
    }
});
// Initialize form with existing user data for updating
const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    email: props.user.email || '',
    contact_number: props.user.contact_number || '',
    birthdate: props.user.birthdate || '',
    sex: props.user.sex || '',
    position: props.user.position || '',
    is_active: typeof props.user.is_active === 'boolean' ? props.user.is_active : false,
    municipality_id: props.user.municipality_id || '',
    barangay_id: props.user.barangay_id || ''
});

const formErrors = ref(null);
const submit = () => {
    if (form.contact_number.length < 11) {
        alert("Contact number must be at least 11 digits long.");
        return; // Prevent form submission
    }
    // Check for changes in the form data compared to props.user
    const hasChanges = Object.keys(form.data()).some((key) => {
        return form.data()[key] !== props.user[key];
    });

    // Alert based on whether changes were detected
    if (hasChanges) {
        form.put(updateRoute.value, {
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
    <Head title="Update Account" />

    <Sidebar>
        <template #header>
            <div>
                <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-900 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                    <Link :href="backRoute" class="flex items-center">
                        Back
                    </Link>
                </button>
            </div>
        </template>

        <div class="container mx-auto px-4 py-8">
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Account ({{ userRole }})</h2>

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
                            <TextInput id="contact_number" type="text" v-model="form.contact_number" @keydown="allowOnlyNumbers" required class="w-full" />
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

                        <div>
                            <InputLabel for="position" value="User's Position" />
                            <TextInput id="position" type="text" v-model="form.position" required class="w-full" />
                            <InputError class="mt-2" :message="form.errors.position" />
                        </div>
                        <div>
                            <InputLabel for="is_active" :value="props.user.is_active ? 'Active' : 'Inactive'" />
                            <select id="is_active" v-model="form.is_active" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select user's is_active</option>
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>
                        <div>
                            <InputLabel for="municipality_id" value="Municipality" />
                            <select id="municipality_id" v-model="form.municipality_id" @change="fetchBarangays(form.municipality_id)" required
                            :disabled="page.props.auth?.user?.user_role === 'lgu_responder'"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select a municipality</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                    {{ municipality.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.municipality_id" />
                        </div>

                        <div>
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
                        <Link :href="backRoute" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900">
                            Update Account
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Sidebar>
</template>
