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
    const newFiles = Array.from(files).map(file => ({
        file: file,
        type: file.type,
        name: file.name,
        preview: URL.createObjectURL(file)
    }));

    // Store the actual files for form submission
    form.items[index].mediaFiles = [
        ...form.items[index].mediaFiles,
        ...files
    ];

    // Store the preview data
    form.items[index].previewFiles = [
        ...form.items[index].previewFiles,
        ...newFiles
    ];
};

const removeExistingFile = (itemIndex, fileIndex) => {
    const mediaFile = form.items[itemIndex].existingMediaFiles[fileIndex];
    if (mediaFile && mediaFile.id) {
        form.items[itemIndex].deletedFiles.push(mediaFile.id);
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

// Add these helper functions from Create.vue
const isImageFile = (file) => {
    const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return imageTypes.includes(file.type);
};

const isVideoFile = (file) => {
    const videoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    return videoTypes.includes(file.type);
};

const getDocumentIcon = (file) => {
    const fileType = file.type || '';
    if (fileType.includes('pdf')) return 'picture_as_pdf';
    if (fileType.includes('word') || fileType.includes('document')) return 'description';
    if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'table_chart';
    if (fileType.includes('presentation')) return 'slideshow';
    return 'insert_drive_file';
};

const getFileName = (file) => {
    if (file.file) {
        // For new files
        return file.file.name;
    }
    // For existing files
    return file.name || file.path.split('/').pop() || 'Unknown File';
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
        <div class="container mx-auto px-4 py-16">
          <h2 class="title-gradient mb-6">Update Guideline</h2>

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
                    required
                    class="w-full text-lg border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  />
                  <InputError :message="formErrors?.title" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                  <InputLabel for="description" value="Description" />
                  <textarea
                    id="description"
                    v-model="form.description"
                    required
                    class="w-full h-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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
                  <InputError class="mt-2" :message="form.errors.category" />
                </div>

                <div>
                  <InputLabel for="user_role" value="User Role" />
                  <select v-model="form.user_role" id="user_role" class="w-full" required disabled>
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
                <div class="item-header mb-4">
                  <h3 class="text-lg font-semibold">Item {{ item.count }}</h3>
                </div>

                <div class="item-content space-y-4">
                  <div>
                    <InputLabel :for="'text' + index" :value="'Text for Item ' + item.count" />
                    <textarea
                      :id="'text' + index"
                      v-model="item.text"
                      required
                      class="w-full h-24 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                    <InputError :message="formErrors?.items?.[index]?.text" class="mt-2" />
                  </div>

                  <!-- Existing Media Files -->
                  <div v-if="item.existingMediaFiles.length" class="preview-section">
                    <h4 class="preview-title">Existing Media Files</h4>
                    <div class="preview-grid">
                      <div v-for="(file, fileIndex) in item.existingMediaFiles" :key="fileIndex"
                           class="preview-item">
                        <!-- Image Preview -->
                        <img v-if="isImageFile(file)"
                             :src="`/storage/${file.path}`"
                             class="preview-image"
                             :alt="file.name" />

                        <!-- Video Preview -->
                        <video v-else-if="isVideoFile(file)"
                               class="preview-video"
                               controls>
                          <source :src="`/storage/${file.path}`" :type="file.type">
                          Your browser does not support video playback.
                        </video>

                        <!-- Document Preview -->
                        <div v-else class="document-preview">
                          <span class="material-icons document-icon">
                            {{ getDocumentIcon(file) }}
                          </span>
                          <span class="document-name">{{ getFileName(file) }}</span>
                        </div>

                        <button @click.prevent="removeExistingFile(index, fileIndex)"
                                class="remove-button"
                                title="Remove">
                          <span class="material-icons">close</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- New Media Files -->
                  <div class="preview-section">
                    <h4 class="preview-title">Add New Media Files</h4>
                    <label :for="'file-upload-' + index" class="browse-button" tabindex="0" role="button" @keypress.enter="$event.target.click()">
                      Browse Files
                    </label>
                    <input
                      :id="'file-upload-' + index"
                      type="file"
                      multiple
                      @change="(event) => handleFileChange(event, index)"
                      class="hidden"
                      accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                    />
                    <div v-if="item.previewFiles.length" class="preview-grid mt-4">
                      <div v-for="(file, imgIndex) in item.previewFiles" :key="imgIndex"
                           class="preview-item">
                        <!-- Image Preview -->
                        <img v-if="isImageFile(file.file)"
                             :src="file.preview"
                             class="preview-image"
                             :alt="file.name" />

                        <!-- Video Preview -->
                        <video v-else-if="isVideoFile(file.file)"
                               class="preview-video"
                               controls>
                          <source :src="file.preview" :type="file.type">
                          Your browser does not support video playback.
                        </video>

                        <!-- Document Preview -->
                        <div v-else class="document-preview">
                          <span class="material-icons document-icon">
                            {{ getDocumentIcon(file) }}
                          </span>
                          <span class="document-name">{{ getFileName(file) }}</span>
                        </div>

                        <button @click.prevent="removeNewFile(index, imgIndex)"
                                class="remove-button"
                                title="Remove">
                          <span class="material-icons">close</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="item-actions">
                  <div class="flex justify-end gap-2">
                    <button v-if="form.items.length > 1"
                            @click.prevent="removeItemEntry(index)"
                            type="button"
                            class="action-button delete-button">
                      <span class="material-icons">delete</span>
                    </button>
                    <button @click.prevent="addItemEntry"
                            type="button"
                            class="action-button add-button">
                      <span class="material-icons">add_circle</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form buttons -->
            <div class="flex justify-between items-center mt-6">
              <Link :href="backRoute"
                    class="cancel-button">
                Cancel
              </Link>
              <PrimaryButton type="submit"
                            :disabled="form.processing"
                            class="create-button"
                            :class="{ 'opacity-25': form.processing }">
                Update Guideline
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
/* Oceanic Theme Base */
.title-gradient {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
    margin-bottom: 2.5rem;
}

.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.92) 0%,
        rgba(0, 75, 150, 0.9) 50%,
        rgba(0, 51, 102, 0.92) 100%
    );
}

.guideline-info-container {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    padding: 2.5rem;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 2rem;
}

.item-card {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

/* Form Input Styles */
input[type="text"],
textarea,
select {
    background: rgba(255, 255, 255, 0.08) !important;
    backdrop-filter: blur(2px);
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 8px !important;
    transition: all 0.3s ease;
    width: 100% !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    padding: 0.75rem 1rem !important;
}

input[type="text"]:focus,
textarea:focus,
select:focus {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(0, 204, 255, 0.5) !important;
    box-shadow: 0 0 0 2px rgba(0, 204, 255, 0.25) !important;
    outline: none !important;
}

textarea {
    min-height: 8rem !important;
    resize: vertical;
}

select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' height='24' viewBox='0 0 24 24' width='24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem !important;
}

.preview-section {
    background: rgba(0, 51, 102, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.preview-title {
    font-size: 1rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9) !important;
    margin-bottom: 1rem;
    letter-spacing: 0.01em;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    padding: 0.5rem;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-image,
.preview-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: rgba(0, 0, 0, 0.3);
}

.document-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
}

.document-icon {
    font-size: 2.5rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.5rem;
}

.document-name {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.remove-button {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 4px;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.2s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-button:hover {
    background: rgba(0, 0, 0, 0.7);
    transform: scale(1.1);
}

/* Button Styles */
.browse-button {
    display: inline-block;
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.browse-button:hover,
.browse-button:focus {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
    outline: none;
}

.browse-button:focus-visible {
    outline: 2px solid #00ccff;
    outline-offset: 2px;
}

.action-button {
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0.25rem;
    border: none;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.1);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.1);
}

.create-button,
.cancel-button {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 50px;
    min-width: 140px;
    text-align: center;
    transition: all 0.3s ease;
}

.create-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.cancel-button {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
}

.create-button:hover,
.cancel-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.create-button:active,
.cancel-button:active {
    transform: translateY(0);
}

/* Spacing and Layout */
.space-y-8 > * + * {
    margin-top: 2rem;
}

.space-y-4 > * + * {
    margin-top: 1rem;
}

.item-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* Error Container */
.error-container {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid rgba(255, 68, 68, 0.2);
    border-radius: 8px;
    padding: 1.25rem;
    color: #ff4444;
    margin-bottom: 1.5rem;
}

/* Labels */
label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

/* Back Button */
.oceanic-button {
    background: linear-gradient(135deg, #00a3cc, #00ccff);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 204, 255, 0.3);
}

.oceanic-button:hover {
    background: linear-gradient(135deg, #00b3cc, #00d9ff);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0, 204, 255, 0.4);
}

.oceanic-button a {
    color: white !important;
    text-decoration: none;
}

.item-header h3 {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.125rem;
    font-weight: 600;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.01em;
}
</style>
