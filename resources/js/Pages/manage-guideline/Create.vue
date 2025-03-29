<script setup>
import CustomButton from '@/Components/CustomButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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

const isImageFile = (file) => {
    const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return imageTypes.includes(file.type);
};

const isVideoFile = (file) => {
    const videoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    return videoTypes.includes(file.type);
};

const getFileType = (file) => {
    return file.type || 'application/octet-stream';
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
    return file.name || 'Unknown File';
};

</script>

<template>
  <Head title="Create Guideline" />

  <Sidebar>

    <div class="min-h-screen bg-cover bg-center bg-gradient-overlay">
          <!-- Background image with overlay -->
      <div class="absolute inset-0 z-0">
        <img src="/images/landing.jpg" class="w-full h-full object-cover" alt="Background" />
        <div class="absolute inset-0 bg-gradient-to-br from-[rgba(0,40,80,0.85)] to-[rgba(0,96,128,0.8)]"></div>
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 grid-pattern"></div>
      </div>
      <div class="container mx-auto px-4 py-16">
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
              <div class="item-header mb-4">
                <h3 class="text-lg font-semibold">Item {{ item.count }}</h3>
              </div>

              <div class="item-content space-y-4">
                <div>
                  <InputLabel :for="'text' + index" :value="'Text for Item ' + item.count" />
                  <TextInput
                    :id="'text' + index"
                    v-model="item.text"
                    type="text" required
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  />
                  <InputError :message="formErrors?.items?.[index]?.text" class="mt-2" />
                </div>

                <div class="media-section">
                  <InputLabel :for="'mediaFiles' + index" value="Upload Media Files" class="text-lg font-medium" />
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
                </div>

                <!-- Preview Section -->
                <div v-if="item.previewFiles.length" class="preview-section mt-4">
                  <h4 class="preview-title">Media Preview</h4>
                  <div class="preview-grid">
                    <div v-for="(file, imgIndex) in item.previewFiles"
                         :key="imgIndex"
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
                          {{ getDocumentIcon(file.file) }}
                        </span>
                        <span class="document-name">{{ file.name }}</span>
                      </div>

                      <!-- Remove Button - Updated with type="button" -->
                      <button @click.prevent="removeImage(index, imgIndex)"
                              type="button"
                              class="remove-button"
                              title="Remove">
                        <span class="material-icons">close</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons - Updated with type="button" -->
              <div class="item-actions mt-6 pt-4 border-t border-white/10">
                <div class="flex justify-end gap-2">
                  <button v-if="form.items.length > 1"
                          @click.prevent="removeItemEntry(index)"
                          type="button"
                          class="action-button delete-button">
                    <span class="material-icons text-xl leading-none">delete</span>
                  </button>
                  <button @click.prevent="addItemEntry"
                          type="button"
                          class="action-button add-button">
                    <span class="material-icons text-xl leading-none">add_circle</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Form buttons -->
          <div class="flex justify-end gap-4 items-center mt-6 m-3">
            <CustomButton variant="secondary"
                type="button"
                icon="cancel"
                :onClick="backRoute">
              Cancel
            </CustomButton>
            <CustomButton
                variant="primary"
                icon="send"
                type="submit"
                :disabled="form.processing"
                :class="{ 'opacity-25': form.processing }">
              Create
            </CustomButton>
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
        rgba(0, 40, 80, 0.85) 0%,
        rgba(0, 96, 128, 0.8) 50%,
        rgba(0, 48, 96, 0.85) 100%
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
    font-size: 2rem;
    font-weight: 600;
    text-align: center;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
    margin-bottom: 1.5rem;
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
    font-size: 1rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 0.75rem;
    display: block;
    letter-spacing: 0.025em;
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
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    padding: 2rem;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.item-card {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.item-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 1rem;
}

.item-content {
    padding: 1rem 0;
}

.media-section {
    margin-top: 1.5rem;
}

.item-actions {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.action-button {
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.08);
    border: 1px solid rgba(255, 68, 68, 0.2);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.08);
    border: 1px solid rgba(0, 204, 255, 0.2);
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
    @apply px-4 py-2 text-sm font-medium rounded-md;
    background: rgba(255, 255, 255, 0.08);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}

.file-input:hover::before {
    background: rgba(255, 255, 255, 0.12);
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
    padding: 0.35rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(4px);
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.action-button {
    padding: 0.35rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-button {
    color: #ff4444;
    background: rgba(255, 68, 68, 0.08);
}

.add-button {
    color: #00ccff;
    background: rgba(0, 204, 255, 0.08);
}

select,
input[type="text"] {
    @apply bg-white/5 border-white/10 text-white text-sm;
    backdrop-filter: blur(4px);
    height: 2.5rem;
    border-radius: 8px;
    padding: 0.5rem 1rem;
}

:deep(label) {
    font-family: 'Inter', sans-serif;
    font-size: 0.875rem;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.5rem;
    display: block;
    letter-spacing: 0.01em;
}

.input-field:focus,
select:focus,
input[type="text"]:focus {
    @apply ring-1 ring-blue-400;
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
    border-color: rgba(147, 197, 253, 0.5);
}

.preview-section {
    @apply mt-6 p-4 rounded-lg;
    background: rgba(0, 51, 102, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-title {
    @apply text-sm font-medium text-white/90 mb-3;
    letter-spacing: 0.01em;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 120px));
    gap: 0.75rem;
    @apply p-2;
    justify-content: start;
}

.preview-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    @apply rounded-lg overflow-hidden;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    max-width: 120px; /* Limit maximum size */
}

/* Media queries for responsive grid */
@media (max-width: 640px) {
    .preview-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 100px));
        gap: 0.5rem;
    }

    .preview-item {
        max-width: 100px;
    }
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: rgba(0, 0, 0, 0.3);
}

.preview-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: rgba(0, 0, 0, 0.3);
}

.document-preview {
    @apply flex flex-col items-center justify-center h-full p-2;
    background: rgba(255, 255, 255, 0.05);
}

.document-icon {
    @apply text-3xl text-white/80 mb-1;
}

.document-name {
    @apply text-xs text-white/70 text-center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.remove-button {
    position: absolute;
    top: 4px;
    right: 4px;
    @apply p-1 rounded-full bg-black/50 text-white/90
           hover:bg-black/70 transition-colors duration-200;
}

.remove-button .material-icons {
    @apply text-sm;
}

/* Add smooth transitions */
.preview-item {
    transition: all 0.2s ease;
}

.preview-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.remove-button:hover {
    transform: scale(1.1);
}

/* Update browse button styles */
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

/* Remove old file input styles */
.file-input {
    display: none;
}
</style>
