<script setup>
import CustomButton from '@/Components/CustomButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();

const formErrors = ref(null);
const previewImages = ref([]);

const props = defineProps({
    colors: Array,
});
const maxDate = new Date().toISOString().split('T')[0];

const speciesCatagory = (category) => {
    switch (category) {
        case 'marine_turtles':
            return 'Marine Turtles';
        case 'marine_mammals':
            return 'Marine Mammals';
        case 'sharks_rays':
            return 'Sharks and Rays';
        default:
            return 'Unknown Category';
    }
};

// defined routes for different current user type
const backRoute = computed(() => {
    return route('species.index', { category: props.category });
});

const createRoute = computed(() => {
    return route('bpemo.admin.manage.species.create', { category: props.category });
});

const form = useForm({
    name: '',
    scientific_name: '',
    common_name: '',
    local_name: '',
    description: '',
    conservation_status: '',
    shape: '',
    is_dangerous: '',
    mediaFiles: [],
    colors: [], // Add colors to the form
    category: '', // Add category to the form
});

const handleFileChange = (event) => {
    const files = event.target.files;
    form.mediaFiles = Array.from(files); // Store the selected files in form.mediaFiles

    // Generate previews for each selected image
    previewImages.value = Array.from(files).map(file => {
        return URL.createObjectURL(file);
    });
};

// Remove selected preview image
const removeImage = (index) => {
    previewImages.value.splice(index, 1);
    form.mediaFiles.splice(index, 1);
};

const selectedColors = ref([]);

const addColor = (event) => {
    const selectedColor = event.target.value;
    if (selectedColor && !selectedColors.value.includes(selectedColor)) {
        selectedColors.value.push(selectedColor);
        form.colors.push(selectedColor);
        document.getElementById("colors").value = "";
    }
};

const removeColor = (color) => {
    selectedColors.value = selectedColors.value.filter(c => c !== color);
    form.colors = form.colors.filter(c => c !== color); // Remove from form data
};


const submit = () => {
    form.colors = selectedColors.value;
    console.log('Submitting colors:', form.colors);
    form.post(createRoute.value, {
        onSuccess: () => {
            formErrors.value = null;
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};
</script>

<template>
    <Head title="Create Species" />

    <Sidebar>
        <div class="relative min-h-screen">
            <!-- Background image with oceanic overlay -->
            <div class="fixed top-0 left-0 w-full h-full bg-cover bg-center z-0"
                 style="background-image: url('/images/landing.jpg');">
                <!-- Ocean-themed overlay -->
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Main content -->
            <div class="relative z-10">
                <div class="container mx-auto px-4 py-16">
                    <h2 class="title-gradient mb-6">Create New Species</h2>

                    <div class="species-info-container max-w-4xl mx-auto">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Error Messages -->
                            <div v-if="formErrors" class="error-container">
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
                                        class="w-full resize-y"
                                        placeholder="Enter a detailed description"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.description" />
                                </div>
                                <div class="sm:col-span-2 col-span-1">
                                    <InputLabel for="colors" value="Select Colors" />
                                    <select id="colors" @change="addColor" class="w-full">
                                        <option value="" disabled selected>Choose a color</option>
                                        <option v-for="color in props.colors" :key="color.name" :value="color.name">
                                            {{ color.name }}
                                        </option>
                                    </select>
                                    <div v-if="selectedColors.length" class="flex flex-wrap gap-2 mt-3">
                                        <div v-for="color in selectedColors" :key="color" class="flex items-center space-x-2 bg-gray-200/20 backdrop-blur-sm px-3 py-1 rounded-lg">
                                            <div :style="{ backgroundColor: color }" class="w-6 h-6 rounded-full"></div>
                                            <span class="text-white">{{ color }}</span>
                                            <button @click="removeColor(color)" class="text-red-400 hover:text-red-300 font-bold">X</button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="category" value="Marine Wildlife Category" />
                                    <select id="category" v-model="form.category" class="w-full">
                                        <option value="" disabled>Select an option</option>
                                        <option value="marine_turtles">Marine Turtles</option>
                                        <option value="marine_mammals">Marine Mammals</option>
                                        <option value="sharks_rays">Sharks and Rays</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.category" />
                                </div>
                                <div>
                                    <InputLabel for="conservation_status" value="Conservation Status" />
                                    <select id="conservation_status" v-model="form.conservation_status" required class="w-full">
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
                                    <select id="shape" v-model="form.shape" required class="w-full">
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
                                    <InputLabel for="is_dangerous" value="Is this species dangerous?" />
                                    <select id="is_dangerous" v-model="form.is_dangerous" class="w-full">
                                        <option value="" disabled>Select an option</option>
                                        <option :value="true">Yes, it is dangerous</option>
                                        <option :value="false">No, it is not dangerous</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.is_dangerous" />
                                </div>
                                <!-- Image Upload Field -->
                                <div class="sm:col-span-2 col-span-1">
                                    <div>
                                        <InputLabel for="mediaFiles" value="Upload Images" />
                                        <label for="mediaFiles" class="browse-button" tabindex="0" role="button" @keypress.enter="$event.target.click()">
                                            Browse Files
                                        </label>
                                        <input type="file" accept="image/*" id="mediaFiles" @change="handleFileChange" multiple class="hidden" />
                                        <InputError class="mt-2" :message="form.errors.mediaFiles" />
                                    </div>
                                </div>

                                <!-- Image Previews -->
                                <div class="mt-4 sm:col-span-2 col-span-1">
                                    <div v-if="previewImages.length" class="preview-section">
                                        <div v-for="(image, index) in previewImages" :key="index" class="relative">
                                            <img :src="image" alt="Image Preview" class="h-32 w-32 object-cover rounded-md shadow-md"/>
                                            <button
                                                @click="removeImage(index)"
                                                class="absolute top-0 right-0 bg-red-500/80 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit and Cancel Buttons -->
                            <div class="flex items-center justify-end gap-4 mt-6">
                                <CustomButton
                                    :onClick="backRoute"
                                    variant="secondary"
                                    icon="cancel">Cancel</CustomButton>
                                <CustomButton
                                    :disabled="form.processing"
                                    :class="{ 'opacity-25': form.processing }"
                                    icon="send">
                                    Create
                                </CustomButton>
                            </div>
                        </form>
                    </div>
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

.species-info-container {
    background: rgba(0, 51, 102, 0.35);
    backdrop-filter: blur(10px);
    padding: 2.5rem;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 2rem;
}

.error-container {
    padding: 1rem;
    background: rgba(220, 38, 38, 0.2);
    border-left: 4px solid rgba(220, 38, 38, 0.7);
    border-radius: 8px;
    color: #fee2e2;
    margin-bottom: 1.5rem;
}

.preview-section {
    background: rgba(0, 51, 102, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1rem 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
/* Browse button styles */
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


/* Form Input Styles */
input[type="text"],
input[type="date"],
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
input[type="date"]:focus,
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
    background: #0056b3 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' height='24' viewBox='0 0 24 24' width='24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    padding-right: 2.5rem !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
    border-radius: 8px !important;
    transition: all 0.3s ease;
    width: 100% !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    padding: 0.75rem 1rem !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

select:focus {
    background: #0366d6 !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.25), 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    outline: none !important;
}

select option {
    background-color: #003366;
    color: white;
    padding: 8px;
}

/* Button Styles */
.oceanic-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    font-size: 0.875rem;
    color: white;
    background: linear-gradient(135deg, rgba(0, 153, 255, 0.7), rgba(0, 102, 204, 0.7));
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.oceanic-button:hover {
    background: linear-gradient(135deg, rgba(0, 153, 255, 0.8), rgba(0, 102, 204, 0.8));
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.update-button {
    background: #0099ff !important;
    color: white !important;
    font-weight: 500 !important;
    padding: 0.75rem 2rem !important;
    border-radius: 8px !important;
    border: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    transition: all 0.2s ease !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    font-size: 1rem !important;
}

.update-button:hover:not(:disabled) {
    background: #0077cc !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.cancel-button {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    position: relative;
}

.cancel-button::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 1px;
    bottom: -2px;
    left: 0;
    background: linear-gradient(to right, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.1));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.cancel-button:hover {
    color: rgba(255, 255, 255, 0.9);
}

.cancel-button:hover::after {
    transform: scaleX(1);
}


/* Label styling */
label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500 !important;
    margin-bottom: 0.5rem !important;
    display: block !important;
}
</style>
