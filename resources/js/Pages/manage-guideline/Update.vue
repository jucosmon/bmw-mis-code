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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Guideline</h2>

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
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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

                <!-- Existing Media Files -->
                <div v-if="item.existingMediaFiles.length">
                    <h4 class="mt-2">Existing Media Files:</h4>
                    <div class="flex space-x-2">
                        <div v-for="(file, fileIndex) in item.existingMediaFiles" :key="fileIndex" class="relative">
                            <img :src="`/storage/${file.path}`" class="w-20 h-20 object-cover" />
                            <button @click.prevent="removeExistingFile(index, fileIndex)" class="absolute top-0 right-0 text-red-500">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- New Media Files -->
                <InputLabel :for="'mediaFiles' + index" value="Upload New Media Files" />
                <input type="file" multiple @change="(event) => handleFileChange(event, index)" class="file-input" />

                <div v-if="item.previewFiles.length">
                    <h4 class="mt-2">Preview:</h4>
                    <div class="flex space-x-2">
                        <div v-for="(file, imgIndex) in item.previewFiles" :key="imgIndex" class="relative">
                            <img :src="file" class="w-20 h-20 object-cover" />
                            <button @click.prevent="removeNewFile(index, imgIndex)" class="absolute top-0 right-0 text-red-500">Remove</button>
                        </div>
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
              <PrimaryButton type="submit" class="bg-indigo-900">Update Guideline</PrimaryButton>
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
  width: 100%;
  height: 40px;
  color: white;
}

.file-input::-webkit-file-upload-button {
  visibility: hidden;
}

.file-input::before {
  content: "Choose Files";
  display: inline-block;
  background-color: indigo;
  color: white;
  padding: 10px;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
}
</style>
