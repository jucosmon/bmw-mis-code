<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
    }
});
const comments = ref(props.strandedIncident.comments || []);
const editingCommentId = ref(null);
const newCommentText = ref('');
const respondActions = ref(props.respondActions || []); // wala magamit
const isPublicUser  = computed(() => page.props.auth.user.user_role === 'public_user');
const isBpemoAdmin = computed(() => page.props.auth.user.user_role === 'bpemo_admin');
const isBpemoStaff = computed(() => page.props.auth.user.user_role === 'bpemo_staff');
const isLguResponder = computed(() => page.props.auth.user.user_role === 'lgu_responder');
const isBarangayOfficial = computed(() => page.props.auth.user.user_role === 'barangay_official');

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
    if( props.strandedIncident.report_status === 'resolved' || props.strandedIncident.report_status === 'false'){
        return route('resolved.incidents.index');
    }else{
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
    Inertia.visit(updateRoute.value);
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

    Inertia.post(
        route('stranded.incident.respond'),
        { status, id: props.strandedIncident.id },
        {

            onSuccess: () => {
                respondModalVisible.value = false;
            },
            onError: (errors) => {
                console.error(errors);
            },
        }
    );
};

// completed button
const completeButtonStatus = computed(() => {
    return props.strandedIncident.report_status === 'verified' &&
           (isBpemoAdmin.value || isBpemoStaff.value || isLguResponder.value);
});

const completeModalVisible = ref(false);

const showCompleteModal = () => {
    completeModalVisible.value = true;
};

const handleCompleteAction = (response) => {
    if (response === 'yes') {
        console.log('Stranded Incident ID:', props.strandedIncident.id); // Check the ID value
        Inertia.patch(
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
    return props.strandedIncident.report_status === 'completed' &&
           (isBpemoAdmin.value || isBpemoStaff.value);
});

const resolveModalVisible = ref(false);

const showResolveModal = () => {
    resolveModalVisible.value = true;
};

const handleResolveAction = (response) => {
    if (response === 'yes') {
        Inertia.patch(
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
        Inertia.patch(
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
    Inertia.post(route('comment.create'), {
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
    Inertia.patch(route('comment.update', commentId), { text: newCommentText.value }, {
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
    Inertia.patch(route('comment.archive', commentId), {}, {
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
    Inertia.get(route('stranded.species.createPage', {id: props.strandedIncident.id}));
}

// view species form
const handleSpeciesClick = ($id) => {
    Inertia.get(route('stranded.species.view', {id: $id}));
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

// Initialize Leaflet map
onMounted(() => {
  nextTick(() => {
    console.log('Stranded Incident:', props.strandedIncident); // Log the stranded incident for debugging

    // Initialize the map with the latitude and longitude from props
    map.value = L.map('map', {
      dragging: false, // Disable dragging
      scrollWheelZoom: false, // Disable zooming with the mouse wheel
      touchZoom: false, // Disable touch zooming on mobile
      doubleClickZoom: false, // Disable double-click zooming
      boxZoom: false, // Disable box zooming
    }).setView([props.strandedIncident.latitude, props.strandedIncident.longitude], 13);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map.value);

    // Add a marker at the specified location (non-draggable)
    marker.value = L.marker([props.strandedIncident.latitude, props.strandedIncident.longitude]).addTo(map.value);
  });
});
</script>

<template>
    <Head title="View Stranded Incident" />
    <Sidebar>
        <template #header>
            <button class="bg-white border rounded-lg shadow-sm px-4 py-2 hover:bg-indigo-700 hover:text-white focus:ring-2 focus:ring-indigo-400 focus:outline-none transition">
                <Link :href="backRoute" class="flex items-center">
                    Back
                </Link>
            </button>
        </template>

        <div class="container mx-auto px-6 pb-6 max-w-5xl">

            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-6 rounded-lg shadow-lg mb-8">
                <h1 class="text-3xl font-bold">Stranded Incident Information</h1>
                <p class="text-sm mt-2">({{ props.strandedIncident.report_status }}) - Marked as {{ props.userRespondStatus }}</p>
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
                                    On Review
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

            <div class="space-y-6">
                <!-- Text Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white shadow-lg rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-9-4h2v2H9V6zm0 4h2v6H9v-6z" />
                            </svg>
                            Stranded Incident Details
                        </h2>
                        <div class="space-y-2">
                            <p><strong>ID:</strong> {{ props.strandedIncident.id }}</p>
                            <p><strong>Certainty Level:</strong> {{ props.strandedIncident.certainty_level }}</p>
                            <p><strong>Date:</strong> {{ props.strandedIncident.date }}</p>
                            <p><strong>Time:</strong> {{ props.strandedIncident.time }}</p>
                            <p><strong>Species Involved:</strong> {{ props.strandedIncident.species_involved }}</p>
                            <p><strong>Quantity:</strong> {{ props.strandedIncident.quantity }}</p>
                            <p><strong>Condition:</strong> {{ props.strandedIncident.condition }}</p>
                            <p><strong>Sea State:</strong> {{ props.strandedIncident.sea_state }}</p>
                            <p><strong>Weather:</strong> {{ props.strandedIncident.weather }} </p>
                            <p><strong>Beach Type:</strong> {{ props.strandedIncident.beach_type }}</p>
                            <p><strong>More Information:</strong> {{ props.strandedIncident.more_information  }}</p>
                            <p><strong>Active Status:</strong> {{ props.strandedIncident.is_active ? 'Active' : 'Inactive' }}</p>
                        </div>
                    </div>
                    <div class="bg-white shadow-lg rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-4">Incident Location</h2>
                        <p class="mb-1"><strong>Location:</strong> {{ barangayName }}, {{ municipalityName }}</p>
                        <p class="mb-1"><strong>Detailed Location:</strong> {{ props.strandedIncident.detailed_location }}</p>
                        <div id="map" style="height: 400px; width: 100%;" class="mb-3"></div>
                        <p class="mt-2 text-gray-500 text-sm text-center">{{ props.strandedIncident.latitude }} lat. | {{ props.strandedIncident.longitude }} long.</p>

                    </div>
                </div>
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

<style>
#map {
    height: 400px; /* Ensure this is set */
    width: 100%; /* Ensure this is set */
}</style>
