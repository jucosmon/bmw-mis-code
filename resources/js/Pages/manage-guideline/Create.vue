<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import { computed, ref } from 'vue';

const formErrors = ref(null);
const previewFiles = ref([]);
const props = defineProps({
    user_role: {
        type: String,
        required: true,
    },
});

const backRoute = computed(() => route('manage.guideline.index', { user_role: props.user_role }));
const createRoute = computed(() => route('manage.guideline.create', { user_role: props.user_role }));

const form = useForm({
    title: '',
    description: '',
    category: '',
    user_role: props.user_role,
    items: [
        {
            count: 1, // Initialize count for the first item
            text: '',
            guideline_id: '',
            mediaFiles: [],
            previewFiles: [],
        },
    ],
});

// File handling for each item
const handleFileChange = (event, index) => {
    const files = event.target.files;
    const newFiles = Array.from(files); // Store the selected files

    // Update the specific item's mediaFiles
    form.items[index].mediaFiles = newFiles;

    // Create preview URLs for the new files
    form.items[index].previewFiles = newFiles.map((file) => URL.createObjectURL(file));
};

const removeImage = (itemIndex, imageIndex) => {
    form.items[itemIndex].previewFiles.splice(imageIndex, 1);
    form.items[itemIndex].mediaFiles.splice(imageIndex, 1);
};

// Submit form
const submit = () => {
    if (!form.items.length) {
        alert("Please add at least one species.");
        return;
    }
    form.post(createRoute.value, {
        onSuccess: () => {
            formErrors.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};

// Add new item form
const addItemEntry = () => {
    const newItemIndex = form.items.length + 1; // Calculate the new count based on the current length
    form.items.push({
        count: newItemIndex, // Set the count based on the current index
        text: '',
        species_id: '',
        mediaFiles: [],
        previewFiles: [],
    });
};

const removeItemEntry = (index) => {
    form.items.splice(index, 1);
    // Update counts for remaining items
    form.items.forEach((item, idx) => {
        item.count = idx + 1; // Reassign counts based on the new index
    });
};

</script>

<template>
  <Head title="Create Guideline" />

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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Create New Guideline</h2>

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
            <div class="sm:col-span-2">
              <InputLabel for="title" value="Title" />
              <TextInput
                id="title"
                v-model="form.title"
                type="text"
                autocomplete="title" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus :border-indigo-500"
              />
              <InputError :message="formErrors?.title" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="description" value="Description" />
              <TextInput
                id="description"
                v-model="form.description"
                type="text"
                autocomplete="description" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
              <InputError :message="formErrors?.description" class="mt-2" />
            </div>

            <div>
            <InputLabel for="category" value="Marine Wildlife Category" />
            <select v-model="form.category" class="w-full" required>
                <option value="" disabled>Select an option</option>
                <option value="marine_turtles">Marine Turtles</option>
                <option value="marine_mammals">Marine Mammals</option>
                <option value="sharks_rays">Sharks and Rays</option>

            </select>
            <InputError class="mt-2" :message="form.errors.category" />
            </div>
            <div>
            <InputLabel for="user_role" value="User Role" />
            <select v-model="form.user_role" class="w-full" required disabled>
                <option value="" disabled>Select an option</option>
                <option value="lgu_responder">LGU Responder</option>
                <option value="barangay_official">Barangay Official</option>
                <option value="public_user">Public User</option>
            </select>
            <InputError class="mt-2" :message="form.errors.user_role" />
            </div>

            <div v-for="(item, index) in form.items" :key="index" class="sm:col-span-2 p-5 rounded-lg shadow-md">
              <h3 class="text-lg font-semibold">Item {{ item.count }}</h3>
              <InputLabel :for="'text' + index" :value="'Text for Item ' + item.count" />
              <TextInput
                :id="'text' + index"
                v-model="item.text"
                type="text" required
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
              <InputError :message="formErrors?.items?.[index]?.text" class="mt-2" />

              <InputLabel :for="'mediaFiles' + index" value="Upload Media Files" />
              <input type="file" multiple @change="(event) => handleFileChange(event, index)" class="file-input" />
              <div v-if="item.previewFiles.length">
                <h4 class="mt-2">Preview:</h4>
                <div class="flex space-x-2">
                  <img v-for="(file, imgIndex) in item.previewFiles" :key="imgIndex" :src="file" class="w-20 h-20 object-cover" />
                  <button @click="removeImage(index, imgIndex)" class="text-red-500">Remove</button>
                </div>
              </div>
              <div class="flex justify-end">
                <button v-if="form.items.length > 1" @click.prevent="removeItemEntry(index)">
                <span class="material-icons text-xl mr-2 leading-none text-red-600">delete</span>
                </button>
                <button @click.prevent="addItemEntry">
                <span class="material-icons text-xl mr-2 leading-none text-indigo-900">add_circle</span>
                </button>
                </div>
            </div>
            <div class="sm:col-span-2 flex justify-center">
              <PrimaryButton type="submit" class="bg-indigo-900">Submit Guideline</PrimaryButton>
            </div>
          </div>
        </form>
      </div>
    </div>
  </Sidebar>
</template>
<style scoped>
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
