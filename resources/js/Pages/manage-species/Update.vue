<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const deletedImages = ref([]);
const previewNewImages = ref([]);

const props = defineProps({
    species:  {
        type: Object,
        required: true
    }
});
const existingImages = ref(props.species.mediaFiles ? props.species.mediaFiles : []);


const speciesCategory = computed(() => {
    switch (props.species.category) {
        case 'marine_turtles':
            return 'Marine Turtles';
        case 'marine_mammals':
            return 'Marine Mammals';
        case 'sharks_rays':
            return 'Sharks and Rays';
        default:
            return 'Unknown Category';
    }
});

const backRoute = computed(() => {
    return route('bpemo.admin.manage.species.view', {id: props.species.id});
});

const updateRoute = computed(() => {
    return route('bpemo.admin.manage.species.update', {
        id: props.species.id,
    });
});

const form = useForm({
    name: props.species.name || '',
    scientific_name: props.species.scientific_name || '',
    common_name: props.species.common_name || '',
    local_name: props.species.local_name || '',
    category: props.species.category || '',
    description: props.species.description || '',
    conservation_status: props.species.conservation_status || null,
    max_size: props.species.max_size || '',
    shape: props.species.shape || '',
    is_dangerous: typeof props.species.is_dangerous === 'boolean' ? props.species.is_dangerous : false,
    is_active: typeof props.species.is_active === 'boolean' ? props.species.is_active : false,
    mediaFiles: [], // This will hold the new files to upload
    deletedImages: [], // Initialize as an empty array
});

const formErrors = ref(null);

const hasChanges = computed(() => {
    const currentData = form.data();

    // Check if basic fields are different
    const dataChanged = Object.keys(currentData).some((key) => {
        // Handle boolean fields carefully
        if (key === 'is_dangerous' || key === 'is_active') {
            return currentData[key] !== !!props.species[key]; // Coerce species[key] to boolean
        }

        // Ignore mediaFiles and deletedImages here as they're checked separately
        if (key === 'mediaFiles' || key === 'deletedImages') {
            return false;
        }

        return currentData[key] !== props.species[key];
    });

    // Check if media files were added
    const mediaFilesChanged = form.mediaFiles.length > 0;

    // Check if existing images were deleted
    const deletedImagesChanged = deletedImages.value.length > 0;

    // Return true if any condition indicates changes
    return dataChanged || mediaFilesChanged || deletedImagesChanged;
});


const handleNewFileChange = (event) => {
    const files = event.target.files;
    // Append new files to the mediaFiles array without resetting the form
    form.mediaFiles.push(...Array.from(files));

    // Generate previews for each selected image
    previewNewImages.value = Array.from(files).map(file => {
        return URL.createObjectURL(file);
    });
};

// Remove selected preview image
const removeNewImage = (index) => {
    previewNewImages.value.splice(index, 1);
    form.mediaFiles.splice(index, 1);
};


const removeExistingImage = (index) => {
    const imageToDelete = existingImages.value[index];
    deletedImages.value.push(imageToDelete.id); // Assuming each image has an `id`
    existingImages.value.splice(index, 1);
};
const submit = () => {
    if (hasChanges.value) {
        form.deletedImages = deletedImages.value;

        // Submit the form via Inertia
        form.post(updateRoute.value, {
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

</script>

<template>
    <Head title="Update Species" />

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
            <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Species ({{ speciesCategory }})</h2>

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
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" type="text" v-model="form.name" required autocomplete="name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="scientific_name" value="Scientific Name" />
                            <TextInput id="scientific_name" type="text" v-model="form.scientific_name" autocomplete="scientific_name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.scientific_name" />
                        </div>
                        <div>
                            <InputLabel for="common_name" value="Common Name" />
                            <TextInput id="common_name" type="text" v-model="form.common_name" autocomplete="common_name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.common_name" />
                        </div>
                        <div>
                            <InputLabel for="local_name" value="Local Name" />
                            <TextInput id="local_name" type="text" v-model="form.local_name" autocomplete="local_name" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.local_name" />
                        </div>
                        <div class="sm:col-span-2 col-span-1">
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                required
                                autocomplete="description"
                                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                                placeholder="Enter a detailed description"
                            ></textarea>
                            <InputError class="mt-2 text-sm text-red-600" :message="form.errors.description" />
                        </div>
                        <div>
                            <InputLabel for="category" value="Marine Wildlife Category" />
                            <select id="category" v-model="form.category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select an option</option>
                                <option value="marine_turtles">Marine Turtles</option>
                                <option value="marine_mammals">Marine Mammals</option>
                                <option value="sharks_rays">Sharks and Rays</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.category" />
                        </div>

                        <div>
                            <InputLabel for="conservation_status" value="Conservation Status" />
                            <select id="conservation_status" v-model="form.conservation_status" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select an option</option>
                                <option value="CR">Critically Endangered (CR)</option>
                                <option value="NT">Near Threatened (NT)</option>
                                <option value="EN">Endangered (EN)</option>
                                <option value="DD">Data Deficient (DD)</option>
                                <option value="VU">Vulnerable (VU)</option>
                                <option value="NA">Not Assessed (NA)</option>
                                <option value="LC">Least Concern (LC)</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.conservation_status" />
                        </div>

                        <div>
                            <InputLabel for="shape" value="Shape" />
                            <select id="shape" v-model="form.shape" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select an option</option>
                                <option value="turtle-like">Turtle-like Shape</option>
                                <option value="shark-like">Shark-like Shape</option>
                                <option value="dolphin-like">Dolphin-like Shape</option>
                                <option value="dugong-like">Dugong-like Shape</option>
                                <option value="whale-like">Whale-like Shape</option>
                                <option value="ray-like">Ray-like Shape</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.shape" />
                        </div>
                        <div>
                            <InputLabel for="max_size" value="Maximum Size (in centimeters)" />
                            <TextInput
                                id="max_size"
                                type="number"
                                step="0.01"
                                v-model="form.max_size"
                                autocomplete="max_size"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter the maximum size"
                            />
                            <InputError class="mt-2 text-sm text-red-600" :message="form.errors.max_size" />
                        </div>
                        <div>
                            <InputLabel for="is_dangerous" value="Is this species dangerous?" />
                            <select id="is_dangerous" v-model="form.is_dangerous" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select an option</option>
                                <option :value="true">Yes, it is dangerous</option>
                                <option :value="false">No, it is not dangerous</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.is_dangerous" />
                        </div>

                        <div>
                            <InputLabel for="is_active" value="Status" />
                            <select id="is_active" v-model="form.is_active" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Select an option</option>
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>
                         <!-- Existing Image Previews -->
                         <div class="mt-4 sm:col-span-2 col-span-1">
                            <InputLabel value="Existing Images" />
                            <div>
                                <div v-if="existingImages.length===0" class="flex flex-wrap gap-2">No existing images</div>
                                <div v-if="existingImages.length" class="flex flex-wrap gap-2">
                                <div v-for="(image, index) in existingImages" :key="index" class="relative">
                                    <img :src="image.url" alt="Image Preview" class="h-32 w-32 object-cover rounded-md"/>
                                    <button
                                        @click.prevent="removeExistingImage(index)"
                                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                    >
                                        &times;
                                    </button>
                                </div>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload Field -->
                        <div class="sm:col-span-2 col-span-1">
                            <InputLabel for="mediaFiles" value="Upload New Images" />
                            <input
                                id="mediaFiles"
                                type="file"
                                accept="image/*"
                                multiple
                                @change="handleNewFileChange"
                                class="file-input w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.mediaFiles" />
                        </div>

                        <!-- Image Previews -->
                        <div class="mt-4 sm:col-span-2 col-span-1">
                            <div>
                                <div v-if="previewNewImages.length" class="flex flex-wrap gap-2">
                                    <div v-for="(image, index) in previewNewImages" :key="index" class="relative">
                                        <img :src="image" alt="Image Preview" class="h-32 w-32 object-cover rounded-md"/>
                                        <button
                                            @click="removeNewImage(index)"
                                            class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Submit and Cancel Buttons -->
                    <div class="flex items-center justify-between mt-6">
                        <Link :href="backRoute" class="text-sm text-gray-500 hover:text-gray-700 underline">Cancel</Link>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }" class="bg-indigo-900">
                            Update Species
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Sidebar>
</template>
<style scoped>
/* Hide the file name (text) but keep the button */
.file-input {
  position: relative;
  overflow: hidden;
  width: 100%; /* Adjust as needed */
  height: 40px; /* Adjust height as needed */
  color: white;


}

/* Hide the file name text after file is selected */
.file-input::-webkit-file-upload-button {
  visibility: hidden; /* Hides the file name */
}

/* Optional: custom styling for the file input button */
.file-input::before {
  content: "Choose Files"; /* Text for the button */
  display: inline-block;
  background-color: indigo; /* Change to your preferred color */
  color: white;
  padding: 10px;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
}

</style>
