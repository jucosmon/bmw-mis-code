<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage(); // Ensure page is initialized

const formErrors = ref(null);
const deletedItems = ref([]); // Track deleted items
const props = defineProps({
    guideline: {
        type: Object,
        required: true,
    },
    itemsWithMediaFiles: {
        type: Array,
        required: true,
    },
});

// Add defensive check
if (!page || !page.props) {
    console.error('Page object is null or undefined');
}

console.log('Guideline:', props.guideline);
console.log('Items with Media Files:', props.itemsWithMediaFiles);

// Define routes
const backRoute = computed(() => route('manage.guideline.view', { id: props.guideline.id }));
const updateRoute = computed(() => route('manage.guideline.update', { id: props.guideline.id }));

// Initialize form
const form = useForm({
    title: props.guideline.title || '',
    description: props.guideline.description || '',
    category: props.guideline.category || '',
    user_role: props.guideline.user_role || '',
    items: props.itemsWithMediaFiles.map((item) => {
        console.log(`Processing Item ${item.count}:`, item); // Log entire item
        return {
            id: item.id,
            count: item.count,
            text: item.text || '',
            guideline_id: item.guideline_id || '',
            mediaFiles: [], // New files to be uploaded
            existingMediaFiles: Array.isArray(item.mediaFiles) ? item.mediaFiles : [], // Existing media files
            previewFiles: [], // Preview URLs for new files
            deletedFiles: [], // Track deleted existing files
        };
    }).sort((a, b) => a.count - b.count),
    deletedItems: [],
});

const handleFileChange = (event, index) => {
    const files = event.target.files;
    const newFiles = Array.from(files); // Store the selected files

    // Update the specific item's mediaFiles
    form.items[index].mediaFiles = newFiles;

    // Create preview URLs for the new files
    form.items[index].previewFiles = newFiles.map((file) => URL.createObjectURL(file));
};

const removeExistingFile = (itemIndex, fileIndex) => {
    const mediaFile = form.items[itemIndex].existingMediaFiles[fileIndex];
    if (mediaFile && mediaFile.id) {
        form.items[itemIndex].deletedFiles.push(mediaFile.id); // Track deleted file
    }
    form.items[itemIndex].existingMediaFiles.splice(fileIndex, 1);
};

const removeNewFile = (itemIndex, fileIndex) => {
    form.items[itemIndex].previewFiles.splice(fileIndex, 1);
    form.items[itemIndex].mediaFiles.splice(fileIndex, 1);
};

const sortItems = () => {
    form.items.sort((a, b) => a.count - b.count); // Sort by count
    form.items.forEach((item, idx) => {
        item.count = idx + 1; // Update count to be in ascending order
    });
};

// Add new item form
const addItemEntry = () => {
    const newItem = {
        id: null,
        count: form.items.length + 1, // Set count based on current length
        text: '',
        guideline_id: '',
        mediaFiles: [],
        existingMediaFiles: [],
        previewFiles: [],
        deletedFiles: [],
    };

    form.items.push(newItem);
    sortItems(); // Sort items after adding
};

// Remove item entry
const removeItemEntry = (index) => {
    const removedItem = form.items.splice(index, 1)[0];
    if (removedItem && removedItem.id) {
        form.deletedItems.push(removedItem.id); // Track deleted item
    }
    sortItems(); // Sort items after removing
};

// Check for changes in items
const itemsChanged = computed(() => {
    const sortedFormItems = [...form.items].sort((a, b) => a.count - b.count);
    const sortedOriginalItems = [...props.itemsWithMediaFiles].sort((a, b) => a.count - b.count);

    return sortedFormItems.some((item, index) => {
        const originalItem = sortedOriginalItems[index] || {};
        const changed = (
            item.guideline_id !== originalItem.guideline_id ||
            item.text !== originalItem.text ||
            !mediaFilesEqual(item.existingMediaFiles, originalItem.mediaFiles) || // Deep comparison
            item.mediaFiles.length > 0 ||
            item.deletedFiles.length > 0
        );
        console.log(`Item ${index} changed:`, changed, {
            current: item,
            original: originalItem
        });
        return changed;
    });
});

const mediaFilesEqual = (a, b) => {
    if (a.length !== b.length) return false;
    return a.every((val, index) => val.id === b[index].id && val.path === b[index].path);
};

// Check for changes in the form
const hasChanges = computed(() => {
    const currentData = form.data();
    props.guideline.deletedItems = form.deletedItems || [];

    // Check for changes in the main form fields
    const dataChanged = Object.keys(currentData).some((key) => {
        if (key === 'items') return false; // Skip items
        return normalizeValue(currentData[key]) !== normalizeValue(props.guideline[key]);
    });

    const mediaFilesChanged = form.items.some(item => item.mediaFiles.length > 0);
    const deletedFilesChanged = form.items.some(item => item.deletedFiles.length > 0);
    const newItemsAdded = form.items.length > props.itemsWithMediaFiles.length;
    const itemsRemoved = form.items.length < props.itemsWithMediaFiles.length;
    const hasDeletedItems = form.deletedItems.length > 0; // Check only if original items exist

    console.log('Data changed:', dataChanged);
    console.log('Items changed:', itemsChanged.value);
    console.log('Media files changed:', mediaFilesChanged);
    console.log('Deleted files changed:', deletedFilesChanged);
    console.log('New items added:', newItemsAdded);
    console.log('Items removed:', itemsRemoved);
    console.log('Has deleted items:', hasDeletedItems);

    return (
        dataChanged ||
        mediaFilesChanged ||
        deletedFilesChanged ||
        itemsChanged.value ||
        newItemsAdded ||
        itemsRemoved ||
        hasDeletedItems
    );
});

// Normalize value function
const normalizeValue = (value) => {
    if (value instanceof Date) return value.toISOString().split('T')[0];
    if (typeof value === 'number') return String(value);
    if (value === null || value === undefined) return '';
    return value;
};

const submit = () => {
    console.log('Form items before submit:', form.items);
    if (hasChanges.value) {
        form.post(updateRoute.value, {
            onSuccess: () => {
                formErrors.value = null; // Clear errors on success
                form.deletedItems = []; // Reset deleted items after successful update
            },
            onError: (errors) => {
                formErrors.value = errors; // Set errors on failure
            },
        });
    } else {
        alert('No changes detected in the form.');
    }
};
</script>

<template>
  <Head title="Update Guideline" />
  <Sidebar>
    <div class="relative min-h-screen">
      <!-- Background image with oceanic overlay -->
      <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/images/landing.jpg');">
        <div class="absolute inset-0 bg-gradient-overlay"></div>
      </div>

      <!-- Main content -->
      <div class="relative z-10">
        <!-- Header section -->
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center">
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-900 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
              <Link :href="backRoute" class="flex items-center">Back</Link>
            </button>
          </div>
        </div>

        <!-- Form sections container -->
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
          <h2 class="text-3xl font-bold text-center text-gradient mb-6">Update Guideline</h2>
          
          <!-- Main Details Section -->
          <div class="bg-white/70 backdrop-blur-sm overflow-hidden shadow-xl rounded-lg max-w-2xl mx-auto">
            <div class="p-6">
              <form @submit.prevent="submit" class="space-y-6">
                <!-- Error Messages -->
                <div v-if="formErrors" class="p-4 bg-red-100 border border-red-400 rounded-lg text-red-600">
                  <ul class="list-disc ml-4">
                    <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                  </ul>
                </div>

                <!-- Main Form Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <div class="sm:col-span-2">
                    <InputLabel for="title" value="Title" />
                    <TextInput 
                      id="title" 
                      v-model="form.title" 
                      type="text" 
                      required
                      class="w-full min-w-[300px] text-lg border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-opacity-10 bg-white text-white" 
                    />
                    <InputError :message="formErrors?.title" class="mt-2" />
                  </div>

                  <div class="sm:col-span-2">
                    <InputLabel for="description" value="Description" />
                    <textarea
                      id="description"
                      v-model="form.description"
                      required
                      class="w-full h-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-opacity-10 bg-white text-white"
                    ></textarea>
                    <InputError :message="formErrors?.description" class="mt-2" />
                  </div>

                  <div>
                    <InputLabel for="category" value="Marine Wildlife Category" />
                    <select v-model="form.category" id="category" class="w-full" required>
                      <option value="" disabled>Select an option</option>
                      <option value="marine_turtles">Marine Turtles</option>
                      <option value="marine_mammals">Marine Mammals</option>
                      <option value="sharks_rays">Sharks and Rays</option>
                    </select>
                    <InputError class="mt-2" :message="formErrors?.category" />
                  </div>

                  <div>
                    <InputLabel for="user_role" value="User Role" />
                    <select v-model="form.user_role" id="user_role" class="w-full" required disabled>
                      <option value="" disabled>Select an option</option>
                      <option value="lgu_responder">LGU Responder</option>
                      <option value="barangay_official">Barangay Official</option>
                      <option value="public_user">Public User</option>
                    </select>
                    <InputError class="mt-2" :message="formErrors?.user_role" />
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Items Section -->
          <div class="space-y-4 max-w-2xl mx-auto">
            <h3 class="text-xl font-semibold text-white">Guideline Items</h3>
            <div v-for="(item, index) in form.items" :key="index" 
                 class="bg-white/70 backdrop-blur-sm p-4 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800">Item {{ item.count }}</h4>
                <div class="flex space-x-2">
                  <button v-if="form.items.length > 1" @click.prevent="removeItemEntry(index)"
                          class="text-red-600 hover:text-red-800 transition-colors">
                    <span class="material-icons">delete</span>
                  </button>
                  <button @click.prevent="addItemEntry"
                          class="text-indigo-900 hover:text-indigo-700 transition-colors">
                    <span class="material-icons">add_circle</span>
                  </button>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <InputLabel :for="'text' + index" :value="'Text for Item ' + item.count" />
                  <textarea
                    :id="'text' + index"
                    v-model="item.text"
                    required
                    class="w-full h-24 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-opacity-10 bg-white text-white"
                  ></textarea>
                  <InputError :message="formErrors?.items?.[index]?.text" class="mt-2" />
                </div>

                <!-- Media Files Sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Existing Media Files -->
                  <div v-if="item.existingMediaFiles.length" class="bg-gray-50/80 p-4 rounded-md">
                    <h5 class="font-medium text-gray-700 mb-2">Existing Media Files</h5>
                    <div class="flex flex-wrap gap-2">
                      <div v-for="(file, fileIndex) in item.existingMediaFiles" :key="fileIndex" 
                           class="relative group">
                        <img :src="`/storage/${file.path}`" class="w-20 h-20 object-cover rounded-md" />
                        <button @click.prevent="removeExistingFile(index, fileIndex)" 
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <span class="material-icons text-sm">close</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- New Media Files -->
                  <div>
                    <InputLabel :for="'mediaFiles' + index" value="" />
                    <input type="file" multiple @change="(event) => handleFileChange(event, index)" 
                           class="file-input" />
                  </div>
                </div>

                <!-- Preview Section -->
                <div v-if="item.previewFiles.length" class="bg-gray-50/80 p-4 rounded-md">
                  <h5 class="font-medium text-gray-700 mb-2">Preview</h5>
                  <div class="flex flex-wrap gap-2">
                    <div v-for="(file, imgIndex) in item.previewFiles" :key="imgIndex" 
                         class="relative group">
                      <img :src="file" class="w-20 h-20 object-cover rounded-md" />
                      <button @click.prevent="removeNewFile(index, imgIndex)" 
                              class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="material-icons text-sm">close</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center pt-4">
              <PrimaryButton type="submit" class="gradient-primary px-6 py-2">
                Update Guideline
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
.file-input {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 40px;
  color: transparent; /* Hide default text */
}

.file-input::-webkit-file-upload-button {
  visibility: hidden;
}

.file-input::before {
  content: "Choose Files";
  display: inline-block;
  background: linear-gradient(135deg, #003366 0%, #004080 50%, #001f3f 100%);
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  text-align: center;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.file-input:hover::before {
  background: linear-gradient(135deg, #004080 0%, #005cb8 50%, #003366 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

/* Oceanic Theme */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

.backdrop-blur-sm {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(10px);
}

/* Add container hover effect */
.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.shadow-lg:hover {
    box-shadow: 0 15px 20px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

/* Button Gradients */
.gradient-primary {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

/* Container max widths */
.max-w-3xl {
  max-width: 48rem;
}

.max-w-2xl {
  max-width: 42rem;
}

/* Adjust inner padding for better content display */
.p-4 {
  padding: 1.25rem;
}

/* Enhance spacing between sections */
.space-y-4 > * + * {
  margin-top: 1.25rem;
}

/* Additional container styles */
.max-w-4xl {
    max-width: 56rem;
    margin-left: auto;
    margin-right: auto;
}

/* Adjust spacing for better visual hierarchy */
.space-y-4 > * + * {
    margin-top: 1rem;
}

/* Oceanic Button Style */
button {
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

button:hover {
    background: linear-gradient(
        135deg,
        rgba(0, 64, 128, 0.95) 0%,
        rgba(0, 51, 102, 0.85) 100%
    ) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    border-color: rgba(255, 255, 255, 0.3);
}

button:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 51, 102, 0.2);
}

/* Icon Animation */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

button .material-icons-round {
    color: #00ccff !important;
    transition: all 0.3s ease;
}

/* Container Adjustments */
.bg-white\/70 {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.75) 0%,
        rgba(0, 64, 128, 0.65) 100%
    );
    color: white;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Adjust form backgrounds for better transparency */
.bg-gray-50\/80 {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
}

/* Make inputs slightly more transparent */
input, select {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(2px);
    border-color: rgba(255, 255, 255, 0.15);
    color: white;
}

/* Enhanced container glow */
.shadow-xl {
    box-shadow: 0 0 25px rgba(0, 102, 204, 0.15);
}

/* Adjust blur intensity for the entire container */
.backdrop-blur-sm {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Override text colors for better contrast */
.text-gray-800 {
    color: white;
}

.text-gray-700 {
    color: rgba(255, 255, 255, 0.9);
}

/* Adjust form inputs for dark container */
input, select {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: white;
}

input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

/* Add a subtle glow effect to containers */
.shadow-xl {
    box-shadow: 0 0 20px rgba(0, 102, 204, 0.2);
}

/* Textarea styling */
textarea {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(2px);
  border-color: rgba(255, 255, 255, 0.15);
  color: white;
  resize: vertical;
  min-height: 6rem;
  padding: 0.75rem;
}

textarea:focus {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Add gradient text effect */
.text-gradient {
    background: linear-gradient(
        135deg,
        rgb(255, 255, 255) 0%,
        rgb(147, 197, 253) 100%
    );
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 0 30px rgba(147, 197, 253, 0.5);
}

/* Adjust TextInput width for title */
input[type="text"] {
  width: 100%;
  min-width: 500px;
  font-size: 1.125rem;
  line-height: 1.75;
  padding: 0.75rem 1rem;
}
</style>
