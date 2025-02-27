<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const state = reactive({
  sidebarOpen: false,
  activeDropdown: null,
  sidebarLargeScreenOpen: true,
  profileDropdownOpen: false,
  notificationsDropdownOpen: false,
  isMobileView: false, // Add this new state
});

console.log('Current history state:', window.history.state);

// notifications
const notifications = ref([]);
const displayedNotifications = ref([]);
const limit = ref(8);
const filterType = ref('all'); // Default filter type to 'all'

const hasMoreNotifications = computed(() => notifications.value.length > limit.value);

const toggleNotificationsDropdown = (event) => {
  event.stopPropagation();
  if (state.profileDropdownOpen) {
    state.profileDropdownOpen = false;
  }
  state.notificationsDropdownOpen = !state.notificationsDropdownOpen;

  if (state.notificationsDropdownOpen) {
    fetchNotifications();
  }
};

const fetchNotifications = async () => {
  try {
    const response = await fetch('/notifications'); // Fetch all notifications
    const data = await response.json();
    notifications.value = data; // Store all notifications
    displayedNotifications.value = notifications.value.slice(0, limit.value); // Limit the displayed notifications
  } catch (error) {
    console.error('Error fetching notifications:', error);
  }
};

const filteredNotifications = computed(() => {
  if (filterType.value === 'all') {
    return notifications.value; // Return all notifications
  }
  return notifications.value.filter(notification => notification.type === filterType.value); // Filter by type
});

const showMoreNotifications = () => {
  limit.value += 8;
  displayedNotifications.value = filteredNotifications.value.slice(0, limit.value);
};

const updateFilterType = (newFilterType) => {
  filterType.value = newFilterType; // Update the filter type
  limit.value = 8; // Reset the limit
  displayedNotifications.value = filteredNotifications.value.slice(0, limit.value); // Update displayed notifications
};

//click single notification
// Modal state
const isFalseNotificationModalOpen = ref(false);
const modalContent  = ref('');
// Function to show the modal
const openFalseNotificationModal = (content) => {
    modalContent.value = content;
    isFalseNotificationModalOpen.value = true;
    console.log('Modal opened with content:', content); // Log the content
};

// Function to close the modal
const closeFalseNotificationModal = () => {
  isFalseNotificationModalOpen.value = false;
  modalContent.value = '';
};

const openNotification = async (notification) => {
    const userRole = user.value.user_role; // Adjust this based on how you access the user role

    if (notification.category === 'general' && notification.type === 'stranding') {
        try {
            // Use fetch to call the controller method
            const response = await fetch(`/stranded-incidents/${notification.stranded_incident_id}/status`);

            // Check if the response is OK (status code 200)
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            // Check the status from the response
            if (data.status === 'false' && userRole === 'public_user') {
                // Show the modal instead of navigating
                openFalseNotificationModal(notification.content);
            } else {
                // Navigate to the Inertia route
                Inertia.get(route('stranded.incident.view', { id: notification.stranded_incident_id }));
            }
        } catch (error) {
            console.error('Error fetching stranded incident status:', error);
        }
    } else if (notification.category === 'general' && notification.type === 'sighting') {
        Inertia.get(); // Add your logic here
    } else if (notification.category === 'false' || notification.category === 'warning') {
        openFalseNotificationModal(notification.content);
    }
};

// Load sidebar state from local storage on component mount
onMounted(() => {
  const savedSidebarState = localStorage.getItem('sidebarOpen');
  if (savedSidebarState !== null) {
    state.sidebarOpen = JSON.parse(savedSidebarState);
  }

  const savedSidebarLargeScreenState = localStorage.getItem('sidebarLargeScreenOpen');
  if (savedSidebarLargeScreenState !== null) {
    state.sidebarLargeScreenOpen = JSON.parse(savedSidebarLargeScreenState);
  }
});

// Watch for changes to sidebarOpen and save to local storage
watch(() => state.sidebarOpen, (newValue) => {
  localStorage.setItem('sidebarOpen', JSON.stringify(newValue));
  if (!newValue && state.isMobileView) {
    const elements = document.querySelectorAll('.sidebar-text');
    elements.forEach(el => el.style.display = 'none');
  }
});

// Watch for changes to sidebarLargeScreenOpen and save to local storage
watch(() => state.sidebarLargeScreenOpen, (newValue) => {
  localStorage.setItem('sidebarLargeScreenOpen', JSON.stringify(newValue));

  // Ensure proper text visibility when toggling sidebar
  nextTick(() => {
    const elements = document.querySelectorAll('.sidebar-text');
    elements.forEach(el => {
      el.style.display = newValue ? 'inline' : 'none';
    });
  });
});

// Additional dropdown and close logic remains unchanged
const toggleDropdown = (dropdownName, event) => {
  event.stopPropagation();
  state.activeDropdown = state.activeDropdown === dropdownName ? null : dropdownName;
};

const toggleProfileDropdown = (event) => {
  event.stopPropagation();
  if (state.notificationsDropdownOpen) {
    state.notificationsDropdownOpen = false;
  }
  state.profileDropdownOpen = !state.profileDropdownOpen;
};

const closeSidebarDropdown = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    state.profileDropdownOpen = false;
    state.notificationsDropdownOpen = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeSidebarDropdown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeSidebarDropdown);
});

const page = usePage();
const user = computed(() => {
    if (!page || !page.props || !page.props.auth || !page.props.auth.user) {
        console.error('Page object or user is null');
        return null;
    }
    return page.props.auth.user;
});
console.log(user);

// Add window resize handler
onMounted(() => {
  const handleResize = () => {
    state.isMobileView = window.innerWidth < 768;
    if (!state.isMobileView && !state.sidebarLargeScreenOpen) {
      const elements = document.querySelectorAll('.sidebar-text');
      elements.forEach(el => el.style.display = 'none');
    }
  };

  window.addEventListener('resize', handleResize);
  handleResize(); // Initial check

  // Clean up
  onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
  });
});

</script>

<template>
    <div class="flex-col w-full md:flex md:flex-row md:min-h-screen">
      <!-- Sidebar -->
      <div class="flex flex-col flex-shrink-0 w-full text-indigo-700 bg-white dark:text-indigo-200 dark:bg-indigo-900"
           :class="{
               'md:w-fit': !state.sidebarLargeScreenOpen,
               'md:w-64': state.sidebarLargeScreenOpen
           }">
        <div class="flex flex-row items-center justify-between flex-shrink-0 px-8 py-4">
          <Link
              v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
              href="#"
              class="text-lg font-semibold tracking-widest text-indigo-900 uppercase rounded-lg dark:text-white focus:outline-none focus:shadow-outline sidebar-text"
              :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
          >
            BMW-MIS
          </Link>
          <button
            class="rounded-lg md:hidden focus:outline-none focus:shadow-outline"
            @click="state.sidebarOpen = !state.sidebarOpen"
          >
            <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
              <path
                v-if="!state.sidebarOpen"
                fill-rule="evenodd"
                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z"
                clip-rule="evenodd"
              />
              <path
                v-else
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
          <button
              class="rounded-lg lg:block hidden focus:outline-none focus:shadow-outline"
              @click="state.sidebarLargeScreenOpen = !state.sidebarLargeScreenOpen"
          >
              <span v-if="!state.sidebarLargeScreenOpen"
                  class="material-icons w-6 h-6 text-white">
                  keyboard_double_arrow_right
              </span>
              <span v-else class="material-icons w-6 h-6 text-white">
                  keyboard_double_arrow_left
              </span>
          </button>
        </div>

        <!-- Navigation -->
        <nav
          :class="{ 'block': state.sidebarOpen, 'hidden': !state.sidebarOpen }"
          class="flex-grow px-4 pb-4 md:block md:pb-0 md:overflow-y-auto"
        >
          <!-- Dashboard -->
          <Link :href="route('dashboard')"
              class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline">
              <span class="material-icons text-lg mr-2 leading-none">home</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Dashboard
              </span>
          </Link>

          <!-- Manage Stranded Incident -->
          <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          :href="route('stranded.incident.index')">
              <span class="material-icons text-lg mr-2 leading-none">medication</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Stranded Incident
              </span>
          </Link>

          <!-- Manage Sightings -->
          <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            :href="route('sighting.index')">
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Sightings
              </span>
          </Link>

          <!-- Manage Species  -->
           <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
           :href="route('species.index')">
              <span class="material-icons text-lg mr-2 leading-none">manage_search</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Explore Species
              </span>
          </Link>

          <!-- Manage Guidelines Dropdown -->
          <div v-if="user.user_role==='bpemo_admin'" class="relative">
            <button
              @click="toggleDropdown('manageGuidelines', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">article</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Manage Guidelines
              </span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
                fill="currentColor"
                viewBox="0 0 20 20"
                :class="{ 'rotate-180': state.activeDropdown === 'manageGuidelines', 'rotate-0': state.activeDropdown !== 'manageGuidelines', 'hidden': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform sidebar-text"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>

            <div
              v-if="state.activeDropdown === 'manageGuidelines'"
              class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
            >
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('manage.guideline.index', {user_role: 'lgu_responder', archived: false})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  LGU Responder
                </span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('manage.guideline.index', {user_role: 'barangay_official', archived: false})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Barangay Official
                </span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('manage.guideline.index', {user_role: 'public_user', archived: false})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Public User
                </span>
              </Link>
            </div>
          </div>

          <!-- View guidelines for specific user roles -->
          <Link
            v-if="user.user_role==='lgu_responder' || user.user_role==='barangay_official' || user.user_role==='public_user'"
            class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            :href="route('guideline.index')">
            <span class="material-icons text-lg mr-2 leading-none">article</span>
            <span
              class="sidebar-text ml-2"
              :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
            >
              Guidelines
            </span>
          </Link>

          <!-- Generate Report Dropdown -->
          <div v-if="user.user_role==='bpemo_admin' || user.user_role==='bpemo_staff'" class="relative">
            <button
              @click="toggleDropdown('generateReport', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">assessment</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Generate Report
              </span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
                fill="currentColor"
                viewBox="0 0 20 20"
                :class="{ 'rotate-180': state.activeDropdown === 'generateReport', 'rotate-0': state.activeDropdown !== 'generateReport', 'hidden': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform sidebar-text"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
            <div
              v-if="state.activeDropdown === 'generateReport'"
              class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
            >
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('generate.report.cluster.map')"
              >
                <span class="material-icons text-lg mr-2 leading-none">map</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Cluster Map
                </span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('generate.report.summary.report')"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Summary Report
                </span>
              </Link>
            </div>
          </div>

          <!-- Manage Account Dropdown -->
          <div class="relative" v-if="user.user_role==='bpemo_admin' || user.user_role==='lgu_responder'">
            <button
              @click="toggleDropdown('manageAccount', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">manage_accounts</span>
              <span
                class="sidebar-text ml-2"
                :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
              >
                Manage Account
              </span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
                fill="currentColor"
                viewBox="0 0 20 20"
                :class="{ 'rotate-180': state.activeDropdown === 'manageAccount', 'rotate-0': state.activeDropdown !== 'manageAccount', 'hidden': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform sidebar-text"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>

            <div
              v-if="state.activeDropdown === 'manageAccount'"
              class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
            >
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_admin'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  BPEMO Administrator
                </span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_staff'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  BPEMO Staff
                </span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', { type: 'lgu_responder'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  LGU Responder
                </span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'barangay_official'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Barangay Official
                </span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'public_user'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Public User
                </span>
              </DropdownLink>

              <!-- Manage Barangay Official Accounts for LGU Responder user -->
              <DropdownLink
                v-if="user.user_role === 'lgu_responder'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('lgu.responder.manage.account.index', {type: 'barangay_official'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span
                  class="sidebar-text ml-2"
                  :class="{ 'hidden md:inline': !state.sidebarOpen && !state.sidebarLargeScreenOpen }"
                >
                  Barangay Official
                </span>
              </DropdownLink>
            </div>
          </div>
        </nav>
      </div>

      <!-- Page Content -->
      <div class="flex-grow overflow-y-auto">
        <header class="shadow-md mb-5">
          <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 flex justify-between">
            <div class="flex gap-5">
              <slot name="header" />
            </div>
            <div class="flex gap-5 items-center">
              <!-- Notifications Button -->
              <button @click="toggleNotificationsDropdown($event)" class="relative rounded-lg focus:outline-none focus:shadow-outline">
                <span class="material-icons text-indigo-900">notifications</span>
                <div v-if="state.notificationsDropdownOpen" class="dropdown-container notifications-dropdown absolute right-0 mt-2 md:w-80 bg-white rounded-md shadow-lg z-20">
                    <div class="py-2">
                        <div class="flex justify-between px-4 ">
                            <select v-model="filterType" @change="updateFilterType(filterType)" class="text-sm w-full text-center" @click.stop>
                                <option value="all">All Notifications</option>
                                <option value="stranding">Stranding</option>
                                <option value="sighting">Sightings</option>
                            </select>
                        </div>
                        <div class="max-h-60 overflow-y-auto">
                            <template v-if="displayedNotifications.length > 0">
                                <div v-for="notification in displayedNotifications" :key="notification.id" class="block px-4 py-2 text-sm text-left text-gray-800 hover:bg-gray-100" @click="openNotification(notification)" @click.stop>
                                    {{ notification.content }}
                                </div>
                            </template>
                            <template v-else>
                                <div class="px-4 py-2 text-sm text-gray-500">No notifications available.</div>
                            </template>
                        </div>
                        <div class="px-4 py-2">
                            <button v-if="hasMoreNotifications" @click="showMoreNotifications" class="text-blue-500 text-sm" @click.stop>Show More</button>
                        </div>
                    </div>
                </div>
            </button>

                <!-- Profile Button -->
              <button @click="toggleProfileDropdown($event)" class="relative rounded-lg focus:outline-none focus:shadow-outline">
                <span class="material-icons text-indigo-900">account_circle</span>
                <div v-if="state.profileDropdownOpen" class="dropdown-container absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg z-20 text-center">
                  <div class="py-2">
                    <DropdownLink class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100" :href="route('profile.view')">
                      Profile
                    </DropdownLink>
                    <DropdownLink class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100" :href="route('logout')" method="post" as="button">
                      Logout
                    </DropdownLink>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </header>
        <main>

          <slot />
          <Modal v-if="isFalseNotificationModalOpen" :show="isFalseNotificationModalOpen" @close="closeFalseNotificationModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="p-6 bg-white rounded shadow-lg">
                    <h2 class="text-lg font-semibold text-slate-800">
                        Notification Details
                    </h2>
                    <p>{{ modalContent }}</p>
                    <div class="mt-6 space-x-4 flex justify-end">
                        <PrimaryButton @click="closeFalseNotificationModal">Ok</PrimaryButton>
                    </div>
                </div>
            </Modal>
        </main>
    </div>
    </div>
  </template>

<style scoped>
/* Add these styles to ensure smooth transitions */
.sidebar-text {
  transition: opacity 0.3s ease;
}

@media (max-width: 768px) {
  .sidebar-text {
    display: inline !important; /* Force show text on mobile when menu is open */
  }

  .hidden.sidebar-text {
    display: none !important;
  }
}
</style>
