<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    type: String,
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

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    contact_number:'',
    password: '',
    password_confirmation: '',
    birthdate: '',
    sex:'',
    position:'',
    municipality_id: '',
    barangay_id: ''
});

// State for the "Show Password" checkbox
const showPassword = ref(false);

const submit = () => {
    // Check if contact number is at least 11 characters
    if (form.contact_number.length < 11) {
        // Optionally, set an error message or handle it as needed
        alert("Contact number must be at least 11 digits long.");
        return; // Prevent form submission
    }

    form.post(route('bpemo.admin.manage.account.create',{type: props.type}), {
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
    <Head title="Create Account" />

    <Sidebar>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Create an Account
            </h2>
        </template>
        <div class="container mx-auto px-7 py-8">
            <h2 class="text-xl font-semibold text-center">{{ userRole }} New Account</h2>
            <!-- Add max width and center the form -->
            <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
                <form @submit.prevent="submit">
                <!-- Display error messages at the top -->
                <div v-if="formErrors" class="mb-4">
                    <div class="text-red-600">
                        <ul>
                            <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>
                </div>
                <div>
                    <InputLabel for="first_name" value="First Name" />

                    <TextInput
                        id="first_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.first_name"
                        required
                        autofocus
                        autocomplete="first_name"
                    />

                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name" />

                    <TextInput
                        id="last_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.last_name"
                        required
                        autocomplete="last_name"
                    />

                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" value="Password" />

                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                    />

                    <TextInput
                        id="password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
                <div class="mt-4 flex items-center mb-3">
                    <input
                        type="checkbox"
                        class="mr-2"
                        id="show-password"
                        v-model="showPassword"
                    />
                    <InputLabel for="show-password" value="Show Password" class="text-gray-700 text-sm" />
                </div>

                <div>
                    <InputLabel for="contact_number" value="Contact Number" />

                    <TextInput
                        id="contact_number"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.contact_number"
                        required
                        autocomplete="contact_number"
                        @keydown="allowOnlyNumbers"
                    />

                    <InputError class="mt-2" :message="form.errors.contact_number" />
                </div>
                <div>
                    <InputLabel for="birthdate" value="Birth Date" />

                    <TextInput
                        id="birthdate"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.birthdate"
                        required
                        autocomplete="birthdate"
                    />

                    <InputError class="mt-2" :message="form.errors.birthdate" />
                </div>
                <div>
                    <InputLabel for="sex" value="Sex" />

                    <select
                        id="sex"
                        class="mt-1 block w-full"
                        v-model="form.sex"
                        required
                    >
                        <option value="" disabled>Select user's sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Prefer Not to Say</option>
                    </select>

                    <InputError class="mt-2" :message="form.errors.sex" />
                </div>

                <div>
                    <InputLabel for="position" value="User's Position" />

                    <TextInput
                        id="position"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.position"
                        required
                        autocomplete="position"
                    />

                    <InputError class="mt-2" :message="form.errors.position" />
                </div>

                    <div class="mt-4 flex items-center justify-end">
                        <Link
                            :href="route('bpemo.admin.manage.account.create.page', {type: props.type})"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Cancel?
                        </Link>

                        <PrimaryButton
                            class="ms-4"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Create
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Sidebar>
</template>
