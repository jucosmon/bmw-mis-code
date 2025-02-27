<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Inertia } from '@inertiajs/inertia';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const state = reactive({
  sidebarOpen: false,
  activeDropdown: null,
  profileDropdownOpen: false,
  notificationsDropdownOpen: false,
  isMobileView: false,
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
});

// Watch for changes to sidebarOpen and save to local storage
watch(() => state.sidebarOpen, (newValue) => {
  localStorage.setItem('sidebarOpen', JSON.stringify(newValue));
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

const page = usePage();
const user = computed(() => {
    if (!page || !page.props || !page.props.auth || !page.props.auth.user) {
        console.error('Page object or user is null');
        return null;
    }
    return page.props.auth.user;
});
console.log(user);

// Sidebar and resize handling
const handleResize = () => {
  state.isMobileView = window.innerWidth < 768;
};

const toggleSidebar = (event) => {
  if (event) {
    event.stopPropagation();
  }
  state.sidebarOpen = !state.sidebarOpen;
};

const closeSidebar = () => {
  state.sidebarOpen = false;
};

// Only close sidebar when clicking outside if it's open
const handleClickOutside = (event) => {
  if (state.sidebarOpen &&
      !event.target.closest('.sidebar') &&
      !event.target.closest('.hamburger-btn')) {
    closeSidebar();
  }
};

// Combined lifecycle hooks
onMounted(() => {
  // Load saved state
  const savedSidebarState = localStorage.getItem('sidebarOpen');
  if (savedSidebarState !== null) {
    state.sidebarOpen = JSON.parse(savedSidebarState);
  }

  // Add event listeners
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('click', closeSidebarDropdown);
  window.addEventListener('resize', handleResize);

  // Initial resize check
  handleResize();
});

onBeforeUnmount(() => {
  // Remove event listeners
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('click', closeSidebarDropdown);
  window.removeEventListener('resize', handleResize);
});

</script>

<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div
      class="sidebar flex flex-col flex-shrink-0 text-indigo-700 bg-white dark:text-indigo-200 dark:bg-indigo-900 h-screen fixed md:sticky top-0 transition-all duration-300 overflow-hidden"
      :class="{
        'w-64 opacity-100 visible': state.sidebarOpen,
        'w-0 opacity-0 invisible': !state.sidebarOpen
      }"
    >
      <!-- Sidebar header with close button -->
      <div class="flex items-center justify-between p-4">
        <Link
          href="#"
          class="text-lg font-semibold tracking-widest text-indigo-900 uppercase dark:text-white"
          :class="{ 'hidden': !state.sidebarOpen }"
        >
          BMW-MIS
        </Link>
        <!-- Close button -->
        <button
          class="rounded-lg focus:outline-none focus:shadow-outline"
          :class="{ 'hidden': !state.sidebarOpen }"
          @click="closeSidebar"
        >
          <span class="material-icons text-2xl">close</span>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-grow px-4 pb-4 overflow-y-auto">
        <!-- Dashboard -->
        <Link :href="route('dashboard')"
            class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">home</span>
            <span class="ml-2">Dashboard</span>
        </Link>
        <!-- Manage Stranded Incident -->
        <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
        :href="route('stranded.incident.index')"
        @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">medication</span>
            <span class="ml-2">Stranded Incident</span>
        </Link>

        <!-- Manage Sightings -->
        <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          :href="route('sighting.index')"
          @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">visibility</span>
            <span class="ml-2">Sightings</span>
        </Link>

        <!-- Manage Species  -->
         <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
         :href="route('species.index')"
         @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">manage_search</span>
            <span class="ml-2">Explore Species</span>
        </Link>

        <!-- Manage Guidelines Dropdown -->
        <div v-if="user.user_role==='bpemo_admin'" class="relative">
          <button
            @click="toggleDropdown('manageGuidelines', $event)"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span class="material-icons text-lg mr-2 leading-none">article</span>
            <span class="ml-2">Manage Guidelines</span>
            <svg v-show="state.sidebarOpen"
              fill="currentColor"
              viewBox="0 0 20 20"
              :class="{ 'rotate-180': state.activeDropdown === 'manageGuidelines', 'rotate-0': state.activeDropdown !== 'manageGuidelines' }"
              class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform"
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
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">LGU Responder</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('manage.guideline.index', {user_role: 'barangay_official', archived: false})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Barangay Official</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('manage.guideline.index', {user_role: 'public_user', archived: false})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Public User</span>
            </Link>
          </div>
        </div>

        <!-- View guidelines for specific user roles -->
        <Link
          v-if="user.user_role==='lgu_responder' || user.user_role==='barangay_official' || user.user_role==='public_user'"
          class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          :href="route('guideline.index')"
          @click="closeSidebar">
          <span class="material-icons text-lg mr-2 leading-none">article</span>
          <span class="ml-2">Guidelines</span>
        </Link>

        <!-- Generate Report Dropdown -->
        <div v-if="user.user_role==='bpemo_admin' || user.user_role==='bpemo_staff'" class="relative">
          <button
            @click="toggleDropdown('generateReport', $event)"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span class="material-icons text-lg mr-2 leading-none">assessment</span>
            <span class="ml-2">Generate Report</span>
            <svg v-show="state.sidebarOpen"
              fill="currentColor"
              viewBox="0 0 20 20"
              :class="{ 'rotate-180': state.activeDropdown === 'generateReport', 'rotate-0': state.activeDropdown !== 'generateReport' }"
              class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform"
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
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">map</span>
              <span class="ml-2">Cluster Map</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('generate.report.summary.report')"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Summary Report</span>
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
            <span class="ml-2">Manage Account</span>
            <svg v-show="state.sidebarOpen"
              fill="currentColor"
              viewBox="0 0 20 20"
              :class="{ 'rotate-180': state.activeDropdown === 'manageAccount', 'rotate-0': state.activeDropdown !== 'manageAccount' }"
              class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform"
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
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">BPEMO Administrator</span>
            </DropdownLink>
            <DropdownLink
              v-if="user.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_staff'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">BPEMO Staff</span>
            </DropdownLink>
            <DropdownLink
              v-if="user.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', { type: 'lgu_responder'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">LGU Responder</span>
            </DropdownLink>
            <DropdownLink
              v-if="user.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'barangay_official'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Barangay Official</span>
            </DropdownLink>
            <DropdownLink
              v-if="user.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'public_user'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Public User</span>
            </DropdownLink>

            <!-- Manage Barangay Official Accounts for LGU Responder user -->
            <DropdownLink
              v-if="user.user_role === 'lgu_responder'"
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('lgu.responder.manage.account.index', {type: 'barangay_official'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Barangay Official</span>
            </DropdownLink>
          </div>
        </div>
      </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-h-screen w-full">
      <!-- Header with hamburger button -->
      <header class="sticky top-0 bg-white shadow-md z-30">
        <div class="mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
          <div class="flex items-center gap-5">
            <!-- Hamburger button -->
            <button
              class="hamburger-btn rounded-lg focus:outline-none focus:shadow-outline hover:bg-gray-100 p-2"
              @click="toggleSidebar"
            >
              <span class="material-icons text-2xl">menu</span>
            </button>
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

      <main class="flex-1 overflow-y-auto">
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

/* Add this to ensure dropdown text behaves correctly */
.dropdown-menu-text {
  transition: opacity 0.3s ease;
}

@media (min-width: 768px) {
  .dropdown-menu-text.hidden {
    display: none !important;
  }
}

/* Update styles to handle fixed positioning and scrolling */
.sidebar-text {
  transition: opacity 0.3s ease;
}

/* Ensure proper z-index for fixed sidebar */
.fixed {
  z-index: 40;
}

/* Handle mobile view */
@media (max-width: 768px) {
  .sidebar-text {
    display: inline !important;
  }

  .hidden.sidebar-text {
    display: none !important;
  }

  /* Adjust sidebar for mobile */
  .fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
  }
}

/* Handle dropdown positioning */
.dropdown-container {
  position: absolute;
  z-index: 60;
}

/* Add transition for sidebar */
.fixed, .sticky {
  transition: all 0.3s ease;
}

.fixed {
  z-index: 40;
  transition: transform 0.3s ease;
}

/* Simplified mobile styles */
@media (max-width: 768px) {
  .w-0 {
    width: 0;
    visibility: hidden;
  }

  .w-64 {
    width: 16rem;
    visibility: visible;
  }
}

/* Ensure dropdowns stay on top */
.dropdown-container {
  z-index: 50;
}

.sidebar {
  transition: all 0.3s ease-in-out;
}

.sidebar.invisible {
  pointer-events: none;
}
</style>
