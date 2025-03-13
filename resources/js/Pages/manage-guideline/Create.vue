<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    user_role: {
        type: String,
        required: true,
    },
});
const formErrors = ref(null);
const previewFiles = ref([]);


const backRoute = computed(() => route('manage.guideline.index', { user_role: props.user_role, archived: false }));
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
        <button class="oceanic-button">
          <Link :href="backRoute" class="flex items-center">
            Back
          </Link>
        </button>
      </div>
    </template>

    <div class="min-h-screen bg-cover bg-center bg-gradient-overlay" style="background-image: url('/images/landing.jpg')">
      <div class="container mx-auto px-4 py-8">
        <h2 class="title-gradient mb-6">Create New Guideline</h2>

        <form @submit.prevent="submit" class="space-y-8 max-w-4xl mx-auto">
          <!-- Error Messages -->
          <div v-if="formErrors" class="error-container">
            <ul class="list-disc ml-4">
              <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
            </ul>
          </div>

          <!-- Guidelines Information Section -->
          <div class="guideline-info-container">
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
            </div>
          </div>

          <!-- Items Section -->
          <div class="space-y-6">
            <div v-for="(item, index) in form.items" :key="index" 
                 class="item-card">
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
                <div class="action-buttons">
                  <button v-if="form.items.length > 1" @click.prevent="removeItemEntry(index)" class="action-button delete-button">
                    <span class="material-icons text-xl leading-none">delete</span>
                  </button>
                  <button @click.prevent="addItemEntry" class="action-button add-button">
                    <span class="material-icons text-xl leading-none">add_circle</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-center mt-6">
            <PrimaryButton type="submit" class="submit-button">Submit Guideline</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
.bg-gradient-overlay {
    position: relative;
}

.bg-gradient-overlay::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(0, 40, 80, 0.8) 0%,
        rgba(0, 96, 128, 0.75) 50%,
        rgba(0, 48, 96, 0.8) 100%
    );
    pointer-events: none;
}

.bg-gradient-overlay::after {
    content: '';
    position: absolute;
    inset: 0;
    background: 
        linear-gradient(
            rgba(0, 64, 128, 0.2) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(0, 64, 128, 0.2) 1px,
            transparent 1px
        );
    background-size: 32px 32px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 40%, transparent 70%);
}

/* Remove .create-container styles since we're not using it anymore */

.title-gradient {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
}

/* Remove the section-title class since it's no longer needed */
.section-title {
    display: none;
}

.input-field {
    @apply mt-2 block w-full rounded-xl border-0 shadow-sm;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    color: white;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.error-container {
    @apply p-4 rounded-lg;
    background: rgba(255, 59, 48, 0.1);
    border: 1px solid rgba(255, 59, 48, 0.2);
    color: #ffb4b4;
}

.oceanic-button {
    @apply px-4 py-2 rounded-lg transition-all duration-300;
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.9), rgba(0, 64, 128, 0.8));
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
}

.oceanic-button:hover {
    background: linear-gradient(135deg, rgba(0, 64, 128, 0.95), rgba(0, 51, 102, 0.85));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2);
}

/* Responsive styles */
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

/* Preserve your existing file input styles but update colors */
.file-input::before {
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.9), rgba(0, 64, 128, 0.8));
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
}

select,
input[type="text"] {
    @apply bg-white/10 border-blue-600/30 text-white;
    backdrop-filter: blur(4px);
}

:deep(label) {
    font-family: 'Inter', sans-serif;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

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
    content: "Browse";
    display: inline-block;
    @apply px-4 py-2 rounded-lg transition-all duration-300;
    background: linear-gradient(135deg, #0075c4, #0052a2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 6px rgba(0, 82, 162, 0.2);
}

.file-input:hover::before {
    background: linear-gradient(135deg, #0088e0, #0063c4);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 82, 162, 0.3);
}

.oceanic-overlay {
  position: relative;
}

.oceanic-overlay::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(rgba(1, 26, 55, 0.7), rgba(1, 26, 55, 0.7));
  pointer-events: none;
}

/* Make sure content appears above the overlay */
.container {
  position: relative;
  z-index: 1;
  font-family: 'Inter', sans-serif;
}

/* Update input fields for better contrast */
select,
input[type="text"],
.input-field {
  @apply bg-white/10 border-blue-600/30 text-white placeholder-blue-200/60;
}

/* Update text colors for better visibility */
label,
h3,
.text-lg {
  @apply text-white;
}

/* Add this if you want the error messages to be more visible */
.bg-red-100 {
  @apply bg-red-900/20 border-red-500/50 text-red-200;
}

.guideline-info-container {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    padding: 2.5rem;
    border-radius: 16px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.item-card {
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.item-card h3 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
}

.file-input {
    position: relative;
    width: auto;
    height: auto;
    color: transparent;
}

.file-input::-webkit-file-upload-button {
    visibility: hidden;
    width: 0;
}

.file-input::before {
    content: "Browse";
    display: inline-block;
    @apply px-4 py-2 rounded-lg transition-all duration-300;
    background: linear-gradient(135deg, #0075c4, #0052a2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 6px rgba(0, 82, 162, 0.2);
}

.file-input:hover::before {
    background: linear-gradient(135deg, #0088e0, #0063c4);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 82, 162, 0.3);
}

.file-input::after {
    display: none;
}

/* Hide the default text display */
.file-input {
    color: transparent;
}

.file-input:focus {
    outline: none;
}

/* Add new submit button styling */
.submit-button {
    background: linear-gradient(135deg, rgba(0, 51, 102, 0.9), rgba(0, 64, 128, 0.8));
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.submit-button:hover {
    background: linear-gradient(135deg, rgba(0, 64, 128, 0.95), rgba(0, 51, 102, 0.85));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2);
}

/* Update select styling for better readability */
select {
    @apply bg-white/10 border-blue-600/30 text-white;
    backdrop-filter: blur(4px);
}

select option {
    background: rgb(0, 51, 102);
    color: white;
    padding: 8px;
    font-family: 'Inter', sans-serif;
}

select:focus {
    outline: none;
    border-color: rgba(147, 197, 253, 0.5);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.action-button {
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.1);
}

.delete-button:hover {
    background: rgba(255, 68, 68, 0.2);
    transform: translateY(-1px);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.1);
}

.add-button:hover {
    background: rgba(0, 204, 255, 0.2);
    transform: translateY(-1px);
}
</style>
