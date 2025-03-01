<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const formErrors = ref(null);
const page = usePage(); // Ensure page is initialized

const props = defineProps({
    strandedSpecies: {
        type: Object,
        required: true,
    },
    species: {
        type: Array,
        required: true,
    }
});

// Add defensive check
if (!page || !page.props) {
    console.error('Page object is null or undefined');
}

const backRoute = computed(() => route('stranded.species.view', { id: props.strandedSpecies.id }));
const updateRoute = computed(() => route('stranded.species.update', { id: props.strandedSpecies.id }));

const form = useForm({
    condition_code: props.strandedSpecies.condition_code || 1,
    latitude: props.strandedSpecies.latitude || null,
    longitude: props.strandedSpecies.longitude || null,
    sex: props.strandedSpecies.sex || 'unknown',
    length: props.strandedSpecies.length ||  null,
    weight: props.strandedSpecies.weight ||  null,
    girth: props.strandedSpecies.girth || null,
    disposition: props.strandedSpecies.disposition || '',
    disposal_site: props.strandedSpecies.disposal_site || '',
    more_information: props.strandedSpecies.more_information ||  '',
    is_released: props.strandedSpecies.is_released ||  false,
    species_id: props.strandedSpecies.species_id ||  null,
});

const submit = () => {
  form.post(updateRoute.value, {
    onSuccess: () => {
      formErrors.value = null; // Clear errors on successful submission
    },
    onError: (errors) => {
      formErrors.value = errors; // Set errors on failed submission
    },
  });
};

// Map references
const map = ref(null);
const marker = ref(null);

// Initialize Leaflet map
onMounted(() => {
  nextTick(() => {
    console.log('Form object:', form); // Debugging line
    map.value = L.map('map').setView([form.latitude, form.longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    marker.value = L.marker([form.latitude, form.longitude], {
      draggable: true,
    }).addTo(map.value);

    marker.value.on('dragend', (e) => {
      const { lat, lng } = e.target.getLatLng();
      form.latitude = lat;
      form.longitude = lng;
    });
  });
});


// Use current location
const setLocationFromMap = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude } = position.coords;

        // Update the form's latitude and longitude
        form.latitude = latitude; // No .value needed
        form.longitude = longitude; // No .value needed

        // Update the map view and marker position
        map.value.setView([latitude, longitude], 13);
        marker.value.setLatLng([latitude, longitude]);
      },
      () => {
        alert('Failed to fetch current location. Please allow location access.');
      }
    );
  } else {
    alert('Geolocation is not supported by your browser.');
  }
};

// Search and Select for species
const search = ref('');
const isDropdownVisible = ref(false);

// Set the search input to the existing species name if species is defined
onMounted(() => {
    if (props.species && props.species.length) {
        const existingSpecies = props.species.find(species => species.id === form.species_id);
        if (existingSpecies) {
            search.value = existingSpecies.name; // Pre-populate the search input
        }
    }
});
// Filtered species list
const filteredSpecies = computed(() => {
  return props.species.filter((species) =>
    species.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

// Select species handler
const selectSpecies = (species) => {
    search.value = species.name;
    form.species_id = species.id;
    isDropdownVisible.value = false;
};


const closeDropdown = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    isDropdownVisible.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
  // Clean up the event listener when the component is unmounted
  document.removeEventListener('click', closeDropdown);
});



</script>

<template>
  <Head title="Update Species Form" />

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
      <h2 class="text-2xl font-bold text-indigo-900 text-center mb-6">Update Species Form</h2>

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
                <div class="relative dropdown-container">
                    <InputLabel for="species" value="Species Involved" />
                    <input
                        id="species"
                        v-model="search"
                        @focus="isDropdownVisible = true"
                        @input="isDropdownVisible = true"
                        placeholder="Search and select what species is involved..."
                        class="w-full border rounded-lg p-2"
                    />
                    <InputError class="mt-2" :message="form.errors?.species_id" />

                    <!-- Dropdown -->
                    <ul
                        v-if="isDropdownVisible && filteredSpecies.length"
                        class="absolute bg-white border rounded-lg shadow-lg w-full max-h-40 overflow-y-auto z-10 mt-1"
                    >
                        <li
                        v-for="species in filteredSpecies"
                        :key="species.id"
                        @click="selectSpecies(species)"
                        class="px-4 py-2 hover:bg-indigo-100 cursor-pointer"
                        >
                        {{ species.name }}
                        </li>
                    </ul>
                    </div>

            </div>
            <div>
              <InputLabel for="condition_code" value="Condition" />
              <select v-model.number="form.condition_code" class="w-full" required>
                <option value="" disabled>Select an option</option>
                <option value=1>1 = Alive</option>
                <option value=2>2 = Freshly Dead</option>
                <option value=3>3 = Decomposed, but organs are intact</option>
                <option value=4>4 = Advanced Decomposition</option>
                <option value=5>5 = Skeletal/Cartiginous Remains</option>
                <option value=6>6 = Destroyed (slaughtered or burned)</option>
            </select>
              <InputError class="mt-2" :message="form.errors.condition_code" />
            </div>
            <div>
              <InputLabel for="sex" value="Sex" />
              <select v-model="form.sex" class="w-full">
                <option value="" disabled>Select an option</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="unknown">Unknown</option>
              </select>
              <InputError class="mt-2" :message="form.errors.sex" />
            </div>
            <div>
                <InputLabel for="length" value="Length (cm)" />
                <input id="length" type="number" step="0.01" min="0" v-model="form.length" class="w-full" placeholder="Enter the length of the species in cm" />
                <InputError class="mt-2" :message="form.errors.length" />
            </div>
            <div>
                <InputLabel for="weight" value="Weight (kg)" />
                <input id="weight" type="number" step="0.01" min="0" v-model="form.weight" class="w-full" placeholder="Enter the weight of the species in kg" />
                <InputError class="mt-2" :message="form.errors.weight" />
            </div>
            <div>
                <InputLabel for="girth" value="Girth (cm)" />
                <input id="girth" type="number" step="0.01" min="0" v-model="form.girth" class="w-full" placeholder="Enter the girth of the species in cm" />
                <InputError class="mt-2" :message="form.errors.girth" />
            </div>
            <div>
              <InputLabel for="is_released" value="Released?" />
              <select v-model="form.is_released" class="w-full">
                <option value="" disabled>Select an option</option>
                <option :value="true">Yes</option>
                <option :value="false">No</option>
            </select>
              <InputError class="mt-2" :message="form.errors.is_released" />
            </div>
            <!--Map-->
            <div class="mt-4 sm:col-span-2">
                <button
                    @click.prevent="setLocationFromMap"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm w-full"
                >
                    Use Current Location
                </button>
                <p class="text-sm text-gray-600 mt-2">
                    Latitude: {{ form.latitude || 'Not Set' }}, Longitude: {{ form.longitude || 'Not Set' }}
                </p>
                <div
                    id="map"
                    style="height: 400px; width: 100%; margin-top: 10px;"
                    class="rounded-lg border shadow z-0"
                ></div>
            </div>

            <div class="sm:col-span-2">
              <InputLabel for="disposition" value="Disposition" />
              <TextInput id="disposition" v-model="form.disposition" class="w-full" placeholder="e.g. Buried" />
              <InputError class="mt-2" :message="form.errors.disposition" />
            </div>
            <div>
              <InputLabel for="disposal_site" value="Disposal Site" />
              <textarea
                id="disposal_site"
                v-model="form.disposal_site"
                autocomplete="disposal_site"
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                placeholder="Please add more details of the disposal location site"
            ></textarea>
              <InputError class="mt-2" :message="form.errors.disposal_site" />
            </div>
            <div>
              <InputLabel for="more_information" value="More Information of the Incident" />
              <textarea
                id="more_information"
                v-model="form.more_information"
                autocomplete="more_information"
                class="w-full h-15 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                placeholder="Please share more information about the stranded species"
            ></textarea>
              <InputError class="mt-2" :message="form.errors.more_information" />
            </div>

          </div>

          <div class="text-center mt-6">
            <PrimaryButton type="submit">Update</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
 #map {
    height: 400px; /* Ensure this is set */
    width: 100%; /* Ensure this is set */
}

</style>
