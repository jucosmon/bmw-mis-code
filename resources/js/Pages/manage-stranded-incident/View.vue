<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

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
    municipalities: {
        type: Array,
        default: () => [],
    },
    barangays: {
        type: Array,
        default: () => [],
    },
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

    const commentData = {
        text: form.text,
        stranded_incident_id: props.strandedIncident.id
    };

    router.post(route('stranded.incident.comment.create'), commentData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Safely get updated data
            router.reload({
                only: ['strandedIncident'],
                preserveScroll: true,
                onSuccess: () => {
                    // Update comments with the latest server data
                    if (props.strandedIncident && props.strandedIncident.comments) {
                        comments.value = props.strandedIncident.comments;
                    }
                    // Clear the form
                    form.text = '';
                    // Scroll to comments section
                    nextTick(() => {
                        scrollToCommentsSection();
                    });
                }
            });
        },
        onError: (errors) => {
            console.error('Comment submission error:', errors);
            if (errors.stranded_incident_id) {
                form.errors.stranded_incident_id = errors.stranded_incident_id[0];
            }
            if (errors.text) {
                form.errors.text = errors.text[0];
            }
        },
    });
};


const scrollToNewComment = (commentId) => {
    nextTick(() => {
        const newCommentElement = document.getElementById(`comment-${commentId}`);
        if (newCommentElement) {
            newCommentElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            // Add a highlight effect to the new comment
            newCommentElement.classList.add('comment-highlight');
            setTimeout(() => {
                newCommentElement.classList.remove('comment-highlight');
            }, 2000);
        }
    });
};



const activeComments = computed(() => {
    if (!comments.value) return [];

    return comments.value
        .filter(comment => comment && comment.is_active)
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
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
    if (!commentId) {
        console.error('Invalid comment ID');
        return;
    }

    router.patch(route('stranded.incident.comment.update', commentId),
        { text: newCommentText.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Reload to get fresh data
                router.reload({
                    only: ['strandedIncident'],
                    preserveScroll: true,
                    onSuccess: () => {
                        if (props.strandedIncident && props.strandedIncident.comments) {
                            comments.value = props.strandedIncident.comments;
                        }
                        cancelEditComment();
                    }
                });
            },
            onError: (errors) => {
                console.error('Comment update error:', errors);
            }
        }
    );
};

const archiveComment = (commentId) => {
    if (!commentId) {
        console.error('Invalid comment ID');
        return;
    }

    router.patch(route('stranded.incident.comment.archive', commentId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({
                only: ['strandedIncident'],
                preserveScroll: true,
                onSuccess: () => {
                    if (props.strandedIncident && props.strandedIncident.comments) {
                        comments.value = props.strandedIncident.comments;
                    }
                    scrollToCommentsSection();
                }
            });
        },
        onError: (errors) => {
            console.error('Comment archive error:', errors);
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
const municipalityName = computed(() => {
    const municipality = props.municipalities.find(
        (m) => m.id === props.strandedIncident.municipality_id
    );
    return municipality ? municipality.name : 'Unknown Municipality';
});

const barangayName = computed(() => {
    const barangay = props.barangays.find(
        (b) => b.id === props.strandedIncident.barangay_id
    );
    return barangay ? barangay.name : 'Unknown Barangay';
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

    // Add event listener for click outside
    document.addEventListener('click', clickOutsideHandler);
  });
});

const initializeMap = () => {
    // Fix for Leaflet default icon
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

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

// Media preview modal
const showFileModal = ref(false);
const currentMediaFile = ref(null);

const openFileModal = (mediaFile) => {
    currentMediaFile.value = mediaFile;
    showFileModal.value = true;
};

const closeFileModal = () => {
    showFileModal.value = false;
    currentMediaFile.value = null;
};

// Add this to the script setup section, right after the errors ref
const clickOutsideHandler = (e) => {
    // Check if any comment has showOptions open
    const openComment = comments.value.find(comment => comment.showOptions);
    if (openComment) {
        // Check if the click was outside the menu
        const menu = document.getElementById(`comment-menu-${openComment.id}`);
        const trigger = document.getElementById(`comment-trigger-${openComment.id}`);
        if (menu && !menu.contains(e.target) && !trigger.contains(e.target)) {
            openComment.showOptions = false;
        }
    }
};

// Function to position the comment menu
const getMenuPosition = (triggerId) => {
    // Get the trigger element
    const trigger = document.getElementById(triggerId);
    if (!trigger) return { top: '0px', right: '0px' };

    // Get the position of the trigger
    const rect = trigger.getBoundingClientRect();

    // Position the menu below and to the right of the trigger
    return {
        top: `${rect.bottom + 5}px`,
        left: `${rect.left - 120}px` // Offset to the left to show the menu properly
    };
};

// Clean up event listener when component is unmounted
onUnmounted(() => {
  document.removeEventListener('click', clickOutsideHandler);
});

</script>

<template>
    <Head title="View Stranded Incident">
        <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Round" rel="stylesheet" />
    </Head>
    <Sidebar>
        <div class="relative min-h-screen">
            <!-- Background -->
            <div class="absolute inset-0">
                <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-overlay"></div>
            </div>

            <!-- Content -->
            <div class="relative container mx-auto px-4 py-16 max-w-5xl">
                <!-- Success Message -->
                <div v-if="props?.success" class="success-notification" role="alert">
                    <div class="flex-1 flex items-center">
                        <span class="material-icons material-icons-round text-2xl mr-3">check_circle</span>
                        <p class="notification-text">{{ props?.success }}</p>
                    </div>
                </div>

                <!-- Stranded Incident Header Card -->
                <div class="profile-card mb-6">

                    <div class="profile-header">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-col">
                                <h1 class="profile-title-gradient">Stranded Incident #{{ props.strandedIncident.id }}</h1>
                                <div class="flex flex-wrap items-center text-indigo-200 mt-2">
                                    <div class="flex items-center mr-4 mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">event</span>
                                        <p class="text-xs text-gray-100 italic">{{ props.strandedIncident.date }}</p>
                                    </div>
                                    <div class="flex items-center mr-4 mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">schedule</span>
                                        <p class="text-xs text-gray-100 italic">{{ props.strandedIncident.time }}</p>
                                    </div>
                                    <div class="flex items-center mr-4 mb-1">
                                        <span class="material-icons material-icons-round text-sm mr-1">verified</span>
                                        <p class="text-xs">{{ props.strandedIncident.is_active ? 'Active' : 'Inactive' }} ({{ props.strandedIncident.report_status }})</p>
                                    </div>
                                    <div class="flex items-center mb-1" v-if="userRespondStatus">
                                        <span class="material-icons material-icons-round text-sm mr-1">how_to_reg</span>
                                        <p class="text-xs">Response: {{ userRespondStatus }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-start sm:justify-end space-x-2 mt-4 sm:mt-0">
                                <!-- Original action buttons styled with new classes -->
                                <button
                                    class="action-button-gradient danger text-sm mb-2"
                                    @click="confirmArchiveIncident"
                                    v-if="props.strandedIncident.is_active && archiveButtonStatus"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">cancel</span>
                                    Cancel Report
                                </button>
                                <button
                                    class="action-button-gradient success text-sm mb-2"
                                    @click="confirmArchiveIncident"
                                    v-if="props.strandedIncident.is_active===false && isPublicUser"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">restore</span>
                                    Unarchive
                                </button>

                                <button
                                    v-if="updateButtonStatusPublic"
                                    class="action-button-gradient primary text-sm mb-2"
                                    @click="updateIncident"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">edit</span>
                                    Update Report
                                </button>
                                <button
                                    v-if="updateButtonStatusResponder"
                                    class="action-button-gradient primary text-sm mb-2"
                                    @click="updateIncident"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">
                                        {{ (userRespondStatus === 'ongoing' || userRespondStatus === 'onsite') &&
                                        (props.strandedIncident.report_status==='pending' || props.strandedIncident.report_status==='false') && !isPublicUser
                                            ? 'verified' : 'edit' }}
                                    </span>
                                    {{ ((userRespondStatus === 'ongoing' || userRespondStatus === 'onsite') &&
                                    (props.strandedIncident.report_status==='pending')) || (props.strandedIncident.report_status==='false') && !isPublicUser
                                        ? 'Verify Incident' : 'Update' }}
                                </button>
                                <button
                                    v-if="respondButtonStatus"
                                    class="action-button-gradient primary text-sm mb-2"
                                    @click="showRespondModal"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">assignment_turned_in</span>
                                    Respond
                                </button>
                                <button
                                    v-if="completeButtonStatus"
                                    class="action-button-gradient success text-sm mb-2"
                                    @click="showCompleteModal"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">check_circle</span>
                                    Mark as Complete
                                </button>
                                <button
                                    v-if="resolveButtonStatus"
                                    class="action-button-gradient success text-sm mb-2"
                                    @click="showResolveModal"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">task_alt</span>
                                    Mark as Resolved
                                </button>
                                <button
                                    v-if="unresolveButtonStatus"
                                    class="action-button-gradient warning text-sm mb-2"
                                    @click="showUnresolveModal"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1 group-hover:rotate-12">restart_alt</span>
                                    Unresolve Incident
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Role-specific warnings (preserved from original) -->
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

                    <!-- Reporter Information -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">person</span>
                                Reporter Information
                            </h2>
                        </div>
                        <div class="p-6 pt-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">account_circle</span>
                                    <span class="ml-3 text-sm">{{ props.strandedIncident.user ? `${props.strandedIncident.user.first_name} ${props.strandedIncident.user.last_name}` : 'Unknown Reporter' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">phone</span>
                                    <span class="ml-3 text-sm">{{ props.strandedIncident.user && props.strandedIncident.user.contact_number ? props.strandedIncident.user.contact_number : 'No contact number available' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">email</span>
                                    <span class="ml-3 text-sm">{{ props.strandedIncident.user ? props.strandedIncident.user.email : 'No email available' }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">badge</span>
                                    <span class="ml-3 text-sm">{{ props.strandedIncident.user ? props.strandedIncident.user.user_role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown role' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Incident Details -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">info</span>
                                Incident Details
                            </h2>
                        </div>
                        <div class="p-6 pt-5 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">pets</span>
                                        <div>
                                            <h3 class="info-card-title">Species Involved</h3>
                                            <p class="info-card-content">{{ formatValue(props.strandedIncident.species_involved) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">pin_drop</span>
                                        <div>
                                            <h3 class="info-card-title">Quantity</h3>
                                            <p class="info-card-content">{{ formatValue(props.strandedIncident.quantity) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">waves</span>
                                        <div>
                                            <h3 class="info-card-title">Sea State</h3>
                                            <p class="info-card-content capitalize">{{ formatValue(props.strandedIncident.sea_state) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">wb_sunny</span>
                                        <div>
                                            <h3 class="info-card-title">Weather</h3>
                                            <p class="info-card-content capitalize">{{ formatValue(props.strandedIncident.weather) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">landscape</span>
                                        <div>
                                            <h3 class="info-card-title">Beach Type</h3>
                                            <p class="info-card-content capitalize">{{ formatValue(props.strandedIncident.beach_type) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="flex items-start">
                                    <span class="material-icons material-icons-round text-xl mr-3 mt-1">heart_broken</span>
                                    <div>
                                        <h3 class="info-card-title">Condition</h3>
                                        <p class="info-card-content capitalize">{{ formatValue(props.strandedIncident.condition) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="flex items-start">
                                    <span class="material-icons material-icons-round text-xl mr-3 mt-1">notes</span>
                                    <div>
                                        <h3 class="info-card-title">Additional Information</h3>
                                        <p class="info-card-content whitespace-pre-wrap">{{ formatValue(props.strandedIncident.more_information, 'No additional information provided') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">location_on</span>
                                Incident Location
                            </h2>
                        </div>
                        <div class="p-6 pt-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">place</span>
                                    <span class="ml-3 text-sm">{{ formatValue(barangayName) }}, {{ formatValue(municipalityName) }}</span>
                                </div>
                                <div class="info-row group">
                                    <span class="material-icons material-icons-round">info</span>
                                    <span class="ml-3 text-sm">{{ formatValue(props.strandedIncident.detailed_location, 'No detailed location provided') }}</span>
                                </div>
                            </div>

                            <!-- Only show map if coordinates exist -->
                            <template v-if="props.strandedIncident.latitude && props.strandedIncident.longitude">
                                <div id="map" class="rounded-lg overflow-hidden shadow-md z-0" style="height: 350px; width: 100%;"></div>
                                <p class="text-center text-xs text-indigo-200 flex items-center justify-center mt-2">
                                    <span class="material-icons material-icons-round text-sm mr-1">my_location</span>
                                    <span>{{ props.strandedIncident.latitude }} lat | {{ props.strandedIncident.longitude }} long</span>
                                </p>
                            </template>
                            <p v-else class="text-gray-300 italic text-center py-4">
                                No GPS coordinates available
                            </p>
                        </div>
                    </div>

                    <!-- Media Files -->
                    <div class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">perm_media</span>
                                Media Files
                            </h2>
                        </div>
                        <div class="p-6 pt-5">
                            <div v-if="props.strandedIncident.mediaFiles && props.strandedIncident.mediaFiles.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div
                                    v-for="file in props.strandedIncident.mediaFiles"
                                    :key="file.id"
                                    class="media-item"
                                    @click="openFileModal(file)"
                                >
                                    <template v-if="file.type.startsWith('image/')">
                                        <img :src="file.url" :alt="`Image of ${props.strandedIncident.name}`" class="media-preview" />
                                    </template>
                                    <template v-else-if="file.type.startsWith('video/')">
                                        <div class="media-preview flex items-center justify-center">
                                            <span class="material-icons material-icons-round text-3xl">play_circle</span>
                                        </div>
                                    </template>
                                    <div class="media-overlay">
                                        <span class="material-icons material-icons-round">visibility</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-gray-300 text-center py-4 italic">No media files available</p>
                        </div>
                    </div>

                    <!-- Detailed Species Forms -->
                    <div v-if="isBpemoAdmin || isBpemoStaff || isLguResponder" class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title flex justify-between items-center w-full">
                                <div class="flex items-center">
                                    <span class="material-icons material-icons-round mr-3">pets</span>
                                    Detailed Species Forms
                                </div>
                                <button
                                    class="action-button-gradient primary text-sm"
                                    @click="createSpeciesForm"
                                >
                                    <span class="material-icons material-icons-round text-sm mr-1">add</span>
                                    Add Form
                                </button>
                            </h2>
                        </div>

                        <div v-if="errors.species_forms &&
                                ((props.strandedIncident.report_status === 'verified' && (isBpemoAdmin || isBpemoStaff || isLguResponder)) ||
                                (props.strandedIncident.report_status === 'completed' && (isBpemoAdmin || isBpemoStaff)))"
                            class="mx-6 mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded"
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

                        <div class="p-6">
                            <div v-if="activeStrandedSpecies && activeStrandedSpecies.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                <div
                                    v-for="(strandedSpecies, index) in activeStrandedSpecies"
                                    :key="strandedSpecies.id"
                                    class="species-card cursor-pointer"
                                    @click="handleSpeciesClick(strandedSpecies.id)"
                                >
                                    <div class="flex items-start">
                                        <span class="material-icons material-icons-round text-xl mr-3 mt-1">water</span>
                                        <div>
                                            <h4 class="species-name">Species {{ index + 1 }}</h4>
                                            <p class="species-description">{{ strandedSpecies.species_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-gray-300 text-center py-6 italic">No detailed species form created</p>
                        </div>
                    </div>

                    <!-- Comments Section-->
                    <div v-if="props.strandedIncident.report_status!=='resolved'" id="comments-section" class="profile-card">
                        <div class="section-header py-5">
                            <h2 class="section-title">
                                <span class="material-icons material-icons-round mr-3">comment</span>
                                Comments
                            </h2>
                        </div>
                        <div class="p-6 pt-5">
                            <div class="bg-opacity-10 bg-white p-4 rounded-xl backdrop-blur-sm mb-6">
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        id="text"
                                        v-model="form.text"
                                        class="block w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/60 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                                        placeholder="Add a comment..."
                                    />
                                    <button
                                        class="action-button-gradient primary"
                                        @click="submitComment"
                                    >
                                        <span class="material-icons material-icons-round text-sm mr-1">send</span>
                                        Submit
                                    </button>
                                </div>
                                <p v-if="form.errors.text" class="text-sm text-red-300 mt-1">{{ form.errors.text }}</p>
                            </div>

                            <div v-if="comments.length > 0" class="space-y-4">
                                <div v-for="comment in activeComments" :id="`comment-${comment.id}`" :key="comment.id" class="bg-white/5 p-4 rounded-xl backdrop-blur-sm">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-1">
                                                <span class="material-icons material-icons-round text-sm mr-1">account_circle</span>
                                                <p class="text-sm font-medium text-white">{{ comment.user.first_name }} {{ comment.user.last_name }}</p>
                                                <span class="mx-2 text-white/40">•</span>
                                                <span class="text-xs text-white/60">{{ comment.created_at }}</span>
                                            </div>
                                            <p v-if="editingCommentId !== comment.id" class="text-white/90 ml-6">{{ comment.text }}</p>
                                            <div v-else class="ml-6 mt-2">
                                                <input
                                                    type="text"
                                                    v-model="newCommentText"
                                                    class="block w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/60 focus:ring-2 focus:ring-indigo-400 focus:outline-none mb-2"
                                                    placeholder="Edit your comment..."
                                                />
                                            </div>
                                        </div>
                                        <div v-if="comment.user_id === page.props.auth.user.id" class="relative">
                                            <button
                                                class="text-white/50 hover:text-white"
                                                @click.stop="comment.showOptions = !comment.showOptions; $event.stopPropagation();"
                                                :id="`comment-trigger-${comment.id}`"
                                            >
                                                <span class="material-icons material-icons-round">more_vert</span>
                                            </button>
                                            <teleport to="body">
                                                <div
                                                    v-if="comment.showOptions"
                                                    class="fixed comment-options-menu"
                                                    style="min-width: 150px; background: #1f2937; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -4px rgba(0, 0, 0, 0.5); overflow: hidden; z-index: 999999;"
                                                    :id="`comment-menu-${comment.id}`"
                                                    :style="getMenuPosition(`comment-trigger-${comment.id}`)"
                                                >
                                                    <button class="block w-full px-4 py-2 text-sm text-white hover:bg-gray-700 text-left" @click.stop="startEditComment(comment)">
                                                        <span class="material-icons material-icons-round text-sm mr-1 align-text-bottom">edit</span>
                                                        Update
                                                    </button>
                                                    <button class="block w-full px-4 py-2 text-sm text-red-400 hover:bg-gray-700 text-left" @click.stop="archiveComment(comment.id)">
                                                        <span class="material-icons material-icons-round text-sm mr-1 align-text-bottom">delete</span>
                                                        Archive
                                                    </button>
                                                </div>
                                            </teleport>
                                        </div>
                                    </div>

                                    <div v-if="editingCommentId === comment.id" class="mt-2 flex justify-end space-x-2">
                                        <button class="action-button-gradient danger text-xs" @click="cancelEditComment">
                                            <span class="material-icons material-icons-round text-xs mr-1">close</span>
                                            Cancel
                                        </button>
                                        <button class="action-button-gradient primary text-xs" @click="submitEditComment(comment.id)">
                                            <span class="material-icons material-icons-round text-xs mr-1">save</span>
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-gray-300 text-center py-4 italic">No comments yet</p>
                        </div>
                    </div>

                    <!-- Modals -->
                    <Modal :show="showConfirmArchiveModal" @close="closeModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-100">
                               {{ props.strandedIncident.is_active ? 'Are you sure you want to archive this stranded incident report?' : 'Are you sure you want to unarchive this stranded incident report?'}}
                            </h2>
                            <div class="mt-4">
                                <label for="admin-password" class="text-sm text-gray-250">
                                    Confirm by entering your password
                                </label>
                                <input
                                    type="password"
                                    id="admin-password"
                                    v-model="form.password"
                                    class="text-black mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton class="text-white" @click="closeModal">Cancel</SecondaryButton>
                                <DangerButton @click="archiveIncident">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <Modal :show="respondModalVisible" @close="respondModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-100">
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

                    <Modal :show="completeModalVisible" @close="completeModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-100">
                                Do you confirm that the incident response is finished and all needed data are complete?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton class="text-white" @click="completeModalVisible = false">No</SecondaryButton>
                                <DangerButton @click="handleCompleteAction('yes')">Yes</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <Modal :show="resolveModalVisible" @close="resolveModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-100">
                                Do you confirm to resolve the incident?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton class="text-white" @click="resolveModalVisible = false">Cancel</SecondaryButton>
                                <DangerButton @click="handleResolveAction('yes')">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <Modal :show="unresolveModalVisible" @close="unresolveModalVisible = false">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-100">
                                Do you confirm to unresolve the incident?
                            </h2>
                            <div class="mt-6 flex justify-end space-x-4">
                                <SecondaryButton @click="unresolveModalVisible = false">Cancel</SecondaryButton>
                                <DangerButton @click="handleUnresolveAction('yes')">Confirm</DangerButton>
                            </div>
                        </div>
                    </Modal>

                    <!-- Media Preview Modal -->
                    <Modal :show="showFileModal" @close="closeFileModal">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-200 mb-4">Media Preview</h2>
                            <div class="mt-4" v-if="currentMediaFile">
                                <template v-if="currentMediaFile.type.startsWith('image/')">
                                    <img :src="currentMediaFile.url" alt="Preview" class="w-full h-auto rounded-lg" />
                                </template>
                                <template v-else-if="currentMediaFile.type.startsWith('video/')">
                                    <video controls class="w-full h-auto rounded-lg">
                                        <source :src="currentMediaFile.url" :type="currentMediaFile.type" />
                                        Your browser does not support the video tag.
                                    </video>
                                </template>
                            </div>
                        </div>
                    </Modal>
                </div>
            </div>
        </div>
    </Sidebar>
</template>

<style scoped>
/* Oceanic Theme */
.bg-gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(0, 51, 102, 0.9) 0%,
        rgba(0, 64, 128, 0.8) 50%,
        rgba(0, 31, 63, 0.9) 100%
    );
}

/* Profile Card */
.profile-card {
    @apply rounded-xl shadow-lg overflow-hidden mb-6;
    background: rgba(0, 51, 102, 0.25);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.profile-header {
    @apply px-6 py-6;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.25rem 1.5rem;
}

.section-title {
    @apply text-lg font-semibold flex items-center text-white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Text Styling */
.profile-title-gradient {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(to right, #ffffff, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

/* Information Row Styling */
.info-row {
    @apply flex items-center p-3 rounded-lg transition-all duration-200 my-2;
    color: rgba(255, 255, 255, 0.9);
}

.info-row span {
    color: rgba(255, 255, 255, 0.9) !important;
}

.info-row:hover {
    transform: translateX(4px);
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(4px);
}

.info-row .material-icons-round {
    transition: color 0.3s ease;
}

.info-row:hover .material-icons-round {
    color: #00ccff !important;
}

/* Info Card */
.info-card {
    @apply p-3 rounded-lg;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

.info-card:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.info-card-title {
    @apply text-base font-semibold mb-1 text-white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-card-content {
    @apply text-white/90 text-sm;
}

/* Species Card */
.species-card {
    @apply p-3 rounded-lg transition-all duration-200;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.species-card:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.species-name {
    @apply text-base font-bold mb-0.5;
    color: #00ccff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.species-description {
    @apply text-xs italic text-white/80;
}

/* Media Items */
.media-item {
    @apply relative rounded-lg overflow-hidden cursor-pointer transition-all duration-200;
    border: 1px solid rgba(255, 255, 255, 0.1);
    height: 120px;
}

.media-preview {
    @apply w-full h-full object-cover;
    background: rgba(0, 0, 0, 0.2);
}

.media-overlay {
    @apply absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200;
    background: rgba(0, 51, 102, 0.6);
}

.media-item:hover .media-overlay {
    opacity: 1;
}

.media-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

/* Button Gradients */
.action-button-gradient {
    @apply rounded-lg flex items-center transition-all duration-300;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
}

.action-button-gradient.primary {
    background: linear-gradient(135deg, #4f46e5, #3730a3) !important;
}

.action-button-gradient.danger {
    background: linear-gradient(135deg, #dc2626, #991b1b) !important;
}

.action-button-gradient.success {
    background: linear-gradient(135deg, #059669, #065f46) !important;
}

.action-button-gradient.warning {
    background: linear-gradient(135deg, #d97706, #92400e) !important;
}

.action-button-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
    filter: brightness(110%);
}

.action-button-gradient:active {
    transform: translateY(0);
}

/* Background Styles */
.bg-gray-50 {
    background: rgba(0, 51, 102, 0.2) !important;
    backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Material Icons */
.material-icons-round {
    color: rgba(0, 204, 255, 0.9) !important;
}

/* Group hover effects */
.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
    color: #00ccff !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-title-gradient {
        font-size: 1.5rem;
    }
    .container {
        padding: 1rem;
    }
    .profile-card {
        margin: 0.5rem;
    }
    .media-item {
        height: 100px;
    }
}

@media (max-width: 640px) {
    .media-item {
        height: 90px;
    }
}

/* Notifications */
.success-notification {
    @apply flex items-center justify-between mb-6 px-6 py-4 rounded-xl backdrop-blur-md;
    animation: slideIn 0.3s ease-out;
    background: rgba(16, 185, 129, 0.15);
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.notification-text {
    @apply text-white text-base font-medium;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Map Styling */
#map {
    height: 350px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    #map {
        height: 250px;
    }
}

/* From original Stranded Incident View */
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

/* Preserve any necessary original map styles */
#map {
    height: 350px;
    width: 100%;
    border-radius: 0.5rem;
}

/* Comment Options Menu */
.comment-options-menu {
    position: fixed !important;
    z-index: 999999 !important;
    transform: translateZ(0);
    will-change: transform;
    filter: drop-shadow(0 25px 25px rgb(0 0 0 / 0.6));
}

.comment-highlight {
    animation: highlightComment 2s ease-out;
}

@keyframes highlightComment {
    0% {
        background-color: rgba(255, 255, 255, 0.2);
    }
    100% {
        background-color: transparent;
    }
}
</style>
