<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onMounted, ref } from 'vue';

const page = usePage();
const props = defineProps({
    strandedIncident: {
        type: Object,
        required: true,
        default: () => ({ id: null, report_status: '' }), // Default value
    },
    respondActions:{
        type: Array,
        default: () => [],

        },
    userRespondStatus: {
        type:String,
        default: '',
    },
    strandedSpecies:{
        type: Array,
        default: () => [],
    },
    success: String,
});
const comments = ref(props.strandedIncident.comments || []);
const editingCommentId = ref(null);
const newCommentText = ref('');
const respondActions = ref(props.respondActions || []); // wala magamit
const isPublicUser  = computed(() => page && page.props.auth.user && page.props.auth.user.user_role === 'public_user');
const isBpemoAdmin = computed(() => page && page.props.auth.user && page.props.auth.user.user_role === 'bpemo_admin');
const isBpemoStaff = computed(() => page && page.props.auth.user && page.props.auth.user.user_role === 'bpemo_staff');
const isLguResponder = computed(() => page && page.props.auth.user && page.props.auth.user.user_role === 'lgu_responder');
const isBarangayOfficial = computed(() => page && page.props.auth.user && page.props.auth.user.user_role === 'barangay_official');

// form defaults
const form = useForm({
    is_active: true,
    password: '',
    text: '',
    stranded_incident_id: props.strandedIncident.id,
});

// Computed property to filter active stranded species
const activeStrandedSpecies = computed(() => {
    return props.strandedSpecies.filter(species => species.is_active);
});

//routes
const backRoute = computed(() => {
    if (props.strandedIncident.report_status === 'resolved' || props.strandedIncident.report_status === 'false') {
        return route('resolved.incidents.index');
    } else {
        return route('stranded.incident.index');
    }
});


const updateRoute = computed(() => {
    const isResponder = isBarangayOfficial.value || isBpemoAdmin.value || isBpemoStaff.value || isLguResponder.value;
    const isResponderEligible = isResponder &&
        (props.strandedIncident.report_status === 'pending' || props.strandedIncident.report_status === 'verified'  || props.strandedIncident.report_status === 'completed'
            || props.strandedIncident.report_status === 'false'
        );

    if (isPublicUser.value) {
        return route('stranded.incident.update.page', { id: props.strandedIncident.id });
    } else if (isResponderEligible) {
        return route('stranded.incident.responder.update.page', { id: props.strandedIncident.id });
    } else {
        return null; // Explicitly return null if no conditions are met
    }
});


const archiveRoute = computed(() => {
    return route('stranded.incident.archive', {
        id: props.strandedIncident.id,
        category: props.strandedIncident.category,
    });
});

const unarchiveRoute = computed(() => {
    return route('stranded.incident.unarchive', {
        id: props.strandedIncident.id
    });
});

// main methods with consecutive modals
const updateIncident = () => {
    router.visit(updateRoute.value);
};

const showConfirmArchiveModal = ref(false);

const confirmArchiveIncident = () => {
    showConfirmArchiveModal.value = true;
};

const closeModal = () => {
    showConfirmArchiveModal.value = false;
};

// Archiving For public users only
const archiveButtonStatus = computed(() => {
    return (isPublicUser.value && props.strandedIncident.report_status === 'pending');
});

const archiveIncident = () => {
    if(props.strandedIncident.is_active){
        form.patch(archiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    } else {
        form.patch(unarchiveRoute.value, {
        onSuccess: () => {
            closeModal();
            form.reset('password');
            },
        onError: (errors) => {
            console.error(errors);
            },
        });
    }

};

// Update button validation for Public users only
const updateButtonStatusPublic = computed(() => {
    return isPublicUser.value && props.strandedIncident.report_status === 'pending';
});


// Update button validation for responders
const updateButtonStatusResponder = computed(() => {
    if (isPublicUser.value) {
        return false;
    }

    if(props.strandedIncident.report_status==='completed' && isBarangayOfficial.value){
        return false;
    }
    return props.userRespondStatus==='ongoing' ||
            props.userRespondStatus==='onsite' ||
           props.strandedIncident.report_status === 'verified' ||
           props.strandedIncident.report_status === 'completed' ||
           props.strandedIncident.report_status === 'false';
});

// Respond Actions
const respondButtonStatus = computed(() => {
    return props.strandedIncident.report_status === 'pending' &&
           (props.userRespondStatus!=='ongoing' && props.userRespondStatus!=='onsite')  &&
           (isBarangayOfficial.value || isBpemoAdmin.value ||
           isBpemoStaff.value || isLguResponder.value) &&
           !isPublicUser.value;
});
const respondModalVisible = ref(false);
const showRespondModal = () => {
    respondModalVisible.value = true;
};
const handleRespondAction = (response) => {
    let status = '';

    switch (response) {
        case 'yes':
            status = 'ongoing';
            break;
        case 'no':
            status = 'unavailable';
            break;
        case 'onsite':
            status = 'onsite';
            break;
        default:
            console.error('Invalid response');
            return;
    }

    router.post(
        route('stranded.incident.respond'),
        { status, id: props.strandedIncident.id },
        {

            onSuccess: () => {
                respondModalVisible.value = false;
                if(response === 'onsite'){
                    router.visit(route('stranded.incident.responder.update.page', props.strandedIncident.id));
                }
            },
            onError: (errors) => {
                console.error(errors);
            },
        }
    );

};

// completed button
const completeButtonStatus = computed(() => {
    const hasActiveSpecies = activeStrandedSpecies.value.length > 0;
    return props.strandedIncident.report_status === 'verified' &&
           (isBpemoAdmin.value || isBpemoStaff.value || isLguResponder.value) &&
           hasActiveSpecies;
});

const completeModalVisible = ref(false);

const showCompleteModal = () => {
    if (activeStrandedSpecies.value.length === 0) {
        errors.value.species_forms = 'Cannot mark as complete. At least one detailed species form is required.';
        return;
    }
    completeModalVisible.value = true;
};

const handleCompleteAction = (response) => {
    if (response === 'yes') {
        console.log('Stranded Incident ID:', props.strandedIncident.id); // Check the ID value
        router.patch(
            route('stranded.incident.complete', { id: props.strandedIncident.id }), // Pass the ID here
            {},
            {
                onSuccess: () => {
                    completeModalVisible.value = false;
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    } else {
        completeModalVisible.value = false;
    }
};

// resolved button
const resolveButtonStatus = computed(() => {
    const hasActiveSpecies = activeStrandedSpecies.value.length > 0;
    return props.strandedIncident.report_status === 'completed' &&
           (isBpemoAdmin.value || isBpemoStaff.value) &&
           hasActiveSpecies;
});

const resolveModalVisible = ref(false);

const showResolveModal = () => {
    if (activeStrandedSpecies.value.length === 0) {
        errors.value.species_forms = 'Cannot mark as resolved. At least one detailed species form is required.';
        return;
    }
    resolveModalVisible.value = true;
};

const handleResolveAction = (response) => {
    if (response === 'yes') {
        router.patch(
            route('stranded.incident.resolve', { id: props.strandedIncident.id }), // Pass the ID here
            {},
            {
                onSuccess: () => {
                    resolveModalVisible.value = false;
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    } else {
        resolveModalVisible.value = false;
    }
};

//unresolve button
const unresolveButtonStatus = computed(() => {
    return props.strandedIncident.report_status === 'resolved' &&
           (isBpemoAdmin.value || isBpemoStaff.value);
});

const unresolveModalVisible = ref(false);

const showUnresolveModal = () => {
    unresolveModalVisible.value = true;
};

const handleUnresolveAction = (response) => {
    if (response === 'yes') {
        router.patch(
            route('stranded.incident.unresolve', { id: props.strandedIncident.id }), // Pass the ID here
            {},
            {
                onSuccess: () => {
                    unresolveModalVisible.value = false;
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    } else {
        unresolveModalVisible.value = false;
    }
};

//COMMENTS
const submitComment = () => {
    if (!form.text.trim()) {
        form.errors.text = 'Comment cannot be empty.';
        return;
    }
    router.post(route('comment.create'), {
        text: form.text,
        stranded_incident_id: props.strandedIncident.id // Ensure this is included
    }, {
        onSuccess: (response) => {
            // Clear the form after successful submission
            const newComment = { id: response.id, text: form.text }; // Assuming the response contains the new comment ID
            form.text = ''; // Reset the text field
            comments.value.push(newComment); // Add the new comment to the local state
            scrollToNewComment(newComment.id); // Scroll to the new comment
        },
        onError: (errors) => {
            // Handle errors (e.g., server validation issues)
            console.error(errors);
            if (errors.stranded_incident_id) {
                form.errors.stranded_incident_id = errors.stranded_incident_id[0];
            }
            if (errors.text) {
                form.errors.text = errors.text[0]; // Capture any text errors
            }
        },
    });
};

const scrollToNewComment = (commentId) => {
    const newCommentElement = document.getElementById(`comment-${commentId}`); // Ensure this ID matches your comment element
    if (newCommentElement) {
        newCommentElement.scrollIntoView({ behavior: 'smooth' });
    }
};

const activeComments = computed(() => {
    return comments.value.filter(comment => comment.is_active);
});

const startEditComment = (comment) => {
    editingCommentId.value = comment.id;
    newCommentText.value = comment.text;
    comment.showOptions = false;
};

const cancelEditComment = () => {
    editingCommentId.value = null;
    newCommentText.value = '';
};

const submitEditComment = (commentId) => {
    router.patch(route('comment.update', commentId), { text: newCommentText.value }, {
        onSuccess: () => {
            // Find the updated comment and update its text
            const updatedComment = comments.value.find(comment => comment.id === commentId);
            if (updatedComment) {
                updatedComment.text = newCommentText.value; // Update the comment text
            }
            cancelEditComment(); // Reset the editing state
        },
        onError: (errors) => {
            console.error(errors); // Handle any errors if needed
        }
    });
};

const archiveComment = (commentId) => {
    router.patch(route('comment.archive', commentId), {}, {
        onSuccess: () => {
            // Remove the archived comment from the local state
            comments.value = comments.value.filter(comment => comment.id !== commentId);
            scrollToCommentsSection(); // Scroll to the comments section
        },
        onError: (errors) => {
            console.error(errors); // Handle any errors if needed
        }
    });
};

const scrollToCommentsSection = () => {
    const commentsSection = document.getElementById('comments-section'); // Ensure this ID matches your comments section
    if (commentsSection) {
        commentsSection.scrollIntoView({ behavior: 'smooth' });
    }
};

// detailed species forms
const createSpeciesForm = () => {
    router.get(route('stranded.species.createPage', {id: props.strandedIncident.id}));
}

// view species form
const handleSpeciesClick = ($id) => {
    router.get(route('stranded.species.view', {id: $id}));
}

// location data
const municipalityData = ref([]);
const barangayData = ref([]);

const municipalityName = computed(() => {
    const municipality = municipalityData.value.find(
        (m) => m.id === props.strandedIncident.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = barangayData.value.find(
        (b) => b.id === props.strandedIncident.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
});

onMounted(async () => {
    try {
        const municipalityResponse = await axios.get('/municipalities');
        municipalityData.value = municipalityResponse.data;

        const barangayResponse = await axios.get(
            `/barangays?municipality_id=${props.strandedIncident.municipality_id}`
        );
        barangayData.value = barangayResponse.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
});

// Map references
const map = ref(null);
const marker = ref(null);

// Add helper function for formatting
const formatValue = (value, defaultText = 'Not specified') => {
    if (value === null || value === undefined || value === '') {
        return defaultText;
    }
    return value;
};

// Update map initialization
onMounted(() => {
  nextTick(() => {
    // Only initialize map if coordinates exist
    if (props.strandedIncident.latitude && props.strandedIncident.longitude) {
      initializeMap();
    }
  });
});

const initializeMap = () => {
    map.value = L.map('map', {
      dragging: false,
      scrollWheelZoom: false,
      touchZoom: false,
      doubleClickZoom: false,
      boxZoom: false,
    }).setView([props.strandedIncident.latitude, props.strandedIncident.longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    marker.value = L.marker([props.strandedIncident.latitude, props.strandedIncident.longitude]).addTo(map.value);
};

// Add error state
const errors = ref({});
</script>

<template>
    <Head title="View Stranded Incident" />
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

        <div class="container mx-auto px-6 pb-6 max-w-5xl">
            <div v-if="props?.success"
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm"
                 role="alert">
                <p class="font-bold">Success!</p>
                <p>{{ props?.success }}</p>
            </div>
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-8 rounded-xl shadow-xl mb-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Stranded Incident #{{ props.strandedIncident.id }}</h1>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                                Status: {{ props.strandedIncident.report_status }}
                            </span>
                            <span v-if="userRespondStatus" class="px-3 py-1 bg-white/20 rounded-full text-sm">
                                Response: {{ userRespondStatus }}
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4">
                    <button
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition"
                        @click="confirmArchiveIncident"
                        v-if="props.strandedIncident.is_active && archiveButtonStatus"
                    >
                        Cancel Report
                    </button>
                    <button
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
                        @click="confirmArchiveIncident"
                        v-if="props.strandedIncident.is_active===false && isPublicUser"
                    >
                        Unarchive Incident
                    </button>

                    <Modal :show="showConfirmArchiveModal" @close="closeModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                               {{ props.strandedIncident.is_active ? 'Are you sure you want to archive this stranded incident report?' : 'Are you sure you want to unarchive this stranded incident report?'}}
                            </h2>
                            <div class="mt-4">
                                <label for="admin-password" class="text-sm text-gray-500">
                                    Confirm by entering your password
                                </label>
                                <input
                                    type="password"
                                    id="admin-password"
                                    v-model="form.password"
                                    class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                                <DangerButton @click="archiveIncident">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <button
                        v-if="updateButtonStatusPublic"
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="updateIncident"
                    >
                        Update Report
                    </button>
                    <button
                        v-if="updateButtonStatusResponder"
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="updateIncident"
                    >
                    {{ (userRespondStatus === 'ongoing' || userRespondStatus === 'onsite' )&&
                    (props.strandedIncident.report_status==='pending' || props.strandedIncident.report_status==='false') && !isPublicUser
                        ? 'Verify Incident' : 'Update Incident' }}
                    </button>
                    <button
                         v-if="respondButtonStatus"
                        class="bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-800 transition"
                        @click="showRespondModal"
                    >
                        Respond
                    </button>

                    <Modal :show="respondModalVisible" @close="respondModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Can you go to the incident location now?
                            </h2>
                            <div class="mt-4 flex justify-between space-x-2">
                                <button
                                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-800 transition sm:min-w-40"
                                    @click="handleRespondAction('yes')"
                                >
                                    Yes, Going
                                </button>
                                <button
                                    class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition sm:min-w-40"
                                    @click="handleRespondAction('no')"
                                >
                                    Not Available
                                </button>
                                <button
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-800 transition sm:min-w-40"
                                    @click="handleRespondAction('onsite')"
                                >
                                    On-Site
                                </button>
                            </div>
                        </div>
                    </Modal>

                    <!-- Complete Button -->
                    <button
                        v-if="completeButtonStatus"
                        class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 transition"
                        @click="showCompleteModal"
                    >
                        Mark as Complete
                    </button>

                    <Modal :show="completeModalVisible" @close="completeModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Are you sure the response is finished and all species forms are complete?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="completeModalVisible = false">No</SecondaryButton>
                                <DangerButton @click="handleCompleteAction('yes')">Yes</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <!-- Resolve Button -->
                    <button
                        v-if="resolveButtonStatus"
                        class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 transition"
                        @click="showResolveModal"
                    >
                        Mark as Resolved
                    </button>

                    <Modal :show="resolveModalVisible" @close="resolveModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Do you confirm to resolve the incident?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="resolveModalVisible = false">Cancel</SecondaryButton>
                                <DangerButton @click="handleResolveAction('yes')">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>
                    <button
                        v-if="unresolveButtonStatus"
                        class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 transition"
                        @click="showUnresolveModal"
                    >
                        Unresolve Incident
                    </button>
                    <Modal :show="unresolveModalVisible" @close="unresolveModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Do you confirm to unresolve the incident?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="unresolveModalVisible = false">Cancel</SecondaryButton>
                                <DangerButton @click="handleUnresolveAction('yes')">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>
                </div>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Role-specific warnings -->
                <div v-if="((props.strandedIncident.report_status === 'verified' && (isBpemoAdmin || isBpemoStaff || isLguResponder)) ||
                            (props.strandedIncident.report_status === 'completed' && (isBpemoAdmin || isBpemoStaff))) &&
                            !activeStrandedSpecies.length"
                     class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Please complete at least one detailed species form to mark this incident as
                                {{ props.strandedIncident.report_status === 'verified' ? 'complete' : 'resolved' }}.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else-if="((props.strandedIncident.report_status === 'verified' && (isBpemoAdmin || isBpemoStaff || isLguResponder)) ||
                                 (props.strandedIncident.report_status === 'completed' && (isBpemoAdmin || isBpemoStaff))) &&
                                 activeStrandedSpecies.length > 0"
                     class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                You can now mark this incident as {{ props.strandedIncident.report_status === 'verified' ? 'complete' : 'resolved' }}.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Main details card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white rounded-xl shadow-xl p-6 hover:shadow-2xl transition-shadow">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Incident Details
                        </h2>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Date</p>
                                    <p class="font-medium">{{ formatValue(props.strandedIncident.date) }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Time</p>
                                    <p class="font-medium">{{ formatValue(props.strandedIncident.time) }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Species Involved</p>
                                <p class="font-medium">{{ formatValue(props.strandedIncident.species_involved) }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Quantity</p>
                                    <p class="font-medium">{{ formatValue(props.strandedIncident.quantity) }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Condition</p>
                                    <p class="font-medium capitalize">{{ formatValue(props.strandedIncident.condition) }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Sea State</p>
                                    <p class="font-medium capitalize">{{ formatValue(props.strandedIncident.sea_state) }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Weather</p>
                                    <p class="font-medium capitalize">{{ formatValue(props.strandedIncident.weather) }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-500">Beach Type</p>
                                    <p class="font-medium capitalize">{{ formatValue(props.strandedIncident.beach_type) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="bg-white rounded-xl shadow-xl p-6 hover:shadow-2xl transition-shadow">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Location Details
                        </h2>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium">{{ formatValue(barangayName) }}, {{ formatValue(municipalityName) }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Detailed Location</p>
                                <p class="font-medium">{{ formatValue(props.strandedIncident.detailed_location, 'No detailed location provided') }}</p>
                            </div>

                            <!-- Only show map if coordinates exist -->
                            <template v-if="props.strandedIncident.latitude && props.strandedIncident.longitude">
                                <div id="map" class="h-[300px] rounded-lg shadow-inner"></div>
                                <p class="text-sm text-gray-500 text-center">
                                    {{ props.strandedIncident.latitude }}° N, {{ props.strandedIncident.longitude }}° E
                                </p>
                            </template>
                            <p v-else class="text-gray-500 italic text-center py-4">
                                No GPS coordinates available
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white rounded-xl shadow-xl p-6 hover:shadow-2xl transition-shadow">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Additional Information
                    </h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="whitespace-pre-wrap">{{ formatValue(props.strandedIncident.more_information, 'No additional information provided') }}</p>
                    </div>
                </div>

                <!-- Rest of the existing sections with enhanced styling -->
                <!--Media Files section -->
                <div class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 mr-2 text-indigo-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path d="M4.75 4A2.75 2.75 0 002 6.75v6.5A2.75 2.75 0 004.75 16h10.5A2.75 2.75 0 0018 13.25v-6.5A2.75 2.75 0 0015.25 4H4.75zM9.5 8.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H10.5a.75.75 0 01-.75-.75zm-3.25 4.25a.75.75 0 110-1.5h7.5a.75.75 0 110 1.5H6.25z" />
                        </svg>
                        Media Files
                    </h2>
                    <div v-if="props.strandedIncident.mediaFiles && props.strandedIncident.mediaFiles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="file in props.strandedIncident.mediaFiles"
                            :key="file.id"
                            class="bg-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition"
                        >
                            <img
                                :src="file.url"
                                :alt="`Image of ${props.strandedIncident.name}`"
                                class="w-full h-48 object-cover"
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No media files available</p>
                </div>
                <!--Detailed Species Form section -->
                <div v-if="isBpemoAdmin || isBpemoStaff || isLguResponder" class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <div v-if="errors.species_forms &&
                               ((props.strandedIncident.report_status === 'verified' && (isBpemoAdmin || isBpemoStaff || isLguResponder)) ||
                                (props.strandedIncident.report_status === 'completed' && (isBpemoAdmin || isBpemoStaff)))"
                         class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4 rounded"
                         role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    {{ errors.species_forms }}
                                </p>
                            </div>
                        </div>
                    </div>



                    <div class="flex justify-between">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-indigo-10" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4.75 4A2.75 2.75 0 002 6.75v6.5A2.75 2.75 0 004.75 16h10.5A2.75 2.75 0 0018 13.25v-6.5A2.75 2.75 0 0015.25 4H4.75zM9.5 8.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5H10.5a.75.75 0 01-.75-.75zm-3.25 4.25a.75.75 0 110-1.5h7.5a.75.75 0 110 1.5H6.25z" />
                            </svg>
                            Detailed Species Forms
                        </h2>
                        <button class="bg-indigo-900 text-white px-3 m-1 rounded" @click="createSpeciesForm">+</button>
                    </div>

                    <div v-if="activeStrandedSpecies && activeStrandedSpecies.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                        <div v-for="(strandedSpecies, index) in activeStrandedSpecies" :key="strandedSpecies.id" class="bg-gray-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer">
                            <button @click="handleSpeciesClick(strandedSpecies.id)" class="w-full h-full text-left p-4">
                                <p class="font-semibold text-md text-indigo-950">Species {{ index + 1 }}</p>
                                <p class="text-sm">{{ strandedSpecies.species_name }}</p>
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No detailed species form created</p>
                </div>

                <!-- Comments Section-->
                <div v-if="props.strandedIncident.report_status!=='resolved'" id="comments-section" class="bg-white shadow-lg rounded-xl p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">Comments</h2>
                    <div class="mt-4">
                        <label for="text" class="text-sm text-gray-500 hidden">Create Comment</label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="text"
                                v-model="form.text"
                                class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Comment here"
                            />
                            <button class="bg-indigo-900 text-white px-4 rounded" @click="submitComment">Submit</button>
                        </div>
                        <p v-if="form.errors.text" class="text-sm text-red-500 mt-1">{{ form.errors.text }}</p>
                    </div>

                    <div v-if="comments.length > 0" class="mt-4">
                        <div v-for="comment in activeComments" :id="`comment-${comment.id}`" :key="comment.id" class="comment-item bg-gray-100 shadow-md p-4 rounded-md mb-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <span class="text-gray-500 text-sm">{{ comment.created_at }}</span>
                                    <p class="font-semibold">{{ comment.user.first_name }} {{ comment.user.last_name }}</p>
                                    <p v-if="editingCommentId !== comment.id" class="text-gray-700">{{ comment.text }}</p>
                                    <input
                                        v-else
                                        type="text"
                                        v-model="newCommentText"
                                        class="border rounded-md p-2 w-full mt-1 focus:ring focus:ring-indigo-300"
                                        placeholder="Edit your comment..."
                                    />
                                </div>
                                <div v-if="comment.user_id === page.props.auth.user.id" class="relative">
                                    <button class="text-gray-500 hover:text-gray-700" @click="comment.showOptions = !comment.showOptions">&hellip;</button>
                                    <div v-if="comment.showOptions" class="absolute right-0 mt-2 bg-white shadow-md rounded-md z-10">
                                        <button class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-200" @click="startEditComment(comment)">Update</button>
                                        <button class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-200" @click="archiveComment(comment.id)">Archive</button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="editingCommentId === comment.id" class="mt-2 flex justify-end">
                                <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400" @click="cancelEditComment">Cancel</button>
                                <button class="ml-2 bg-indigo-900 text-white px-4 py-2 rounded-md hover:bg-indigo-600" @click="submitEditComment(comment.id)">Update</button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No comments</p>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
.from-indigo-700 {
    background-image: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
}

/* Add smooth transitions */
.transition-shadow {
    transition: all 0.3s ease;
}

/* Enhance card hover effects */
.hover\:shadow-2xl:hover {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: translateY(-2px);
}

#map {
    height: 400px;
    width: 100%;
    border-radius: 0.5rem;
}

/* Add responsive padding */
@media (max-width: 640px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>
