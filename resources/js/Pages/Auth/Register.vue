<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    contact_number:'',
    birthdate: '',
    sex:'',

});

const submit = () => {
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
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

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

            <div class="relative">
                <TextInput
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="mt-1 block w-full pr-10"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                >
                    <span v-if="showPassword">👁️</span>
                    <span v-else>🙈</span>
                </button>
            </div>


            <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div class="mt-4">
            <InputLabel
                for="password_confirmation"
                value="Confirm Password"
            />

            <TextInput
                id="password_confirmation"
                type="password"
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
        <div>
            <InputLabel for="contact_number" value="Contact Number" />

            <TextInput
                id="contact_number"
                type="text"
                class="mt-1 block w-full"
                v-model="form.contact_number"
                required
                autocomplete="contact_number"
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
                <option value="" disabled>Select your sex</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="unknown">Prefer Not to Say</option>
            </select>

            <InputError class="mt-2" :message="form.errors.sex" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <Link
                :href="route('login')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Already registered?
            </Link>

            <PrimaryButton
                class="ms-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Register
            </PrimaryButton>
        </div>
    </form>
    </GuestLayout>
</template>
