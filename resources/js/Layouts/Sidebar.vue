<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { supabase } from '@/supabase';
import { Link, router, usePage } from '@inertiajs/vue3';
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
    const userId = user.value.id;
    const userRole = user.value.user_role;
    console.log('User role:', userRole);
    let query = [];

    if (userRole === 'public_user') {
      const strandedRes = await supabase
        .from('stranded_incidents')
        .select('id')
        .eq('user_id', userId);

      const strandedIds = strandedRes.data?.map(s => s.id) || [];

      // Get sightings IDs
      const sightingRes = await supabase
        .from('sightings')
        .select('id')
        .eq('user_id', userId);

      const sightingIds = sightingRes.data?.map(s => s.id) || [];

      // Get comments for user's stranded incidents
      const commentsRes = await supabase
        .from('comments')
        .select('id')
        .in('stranded_incident_id', strandedIds);

      const commentIds = commentsRes.data?.map(c => c.id) || [];

      // Build the notification query with all conditions
      const conditions = [];

      if (strandedIds.length > 0) {
        conditions.push(`and(stranded_incident_id.in.(${strandedIds}),notif_for.eq.all)`);
      }
      if (sightingIds.length > 0) {
        conditions.push(`and(sighting_id.in.(${sightingIds}),notif_for.eq.all)`);
      }
      if (commentIds.length > 0) {
        conditions.push(`and(comment_id.in.(${commentIds}),notif_for.eq.all)`);
      }

      // Always include specific user notifications
      conditions.push(`and(user_id.eq.${userId},notif_for.eq.specific_user)`);
        query = supabase
      .from('notifications')
      .select('*');
      query = query.or(conditions.join(','));

    } else if (userRole === 'barangay_official') {

      const barangayId = user.value.barangay_id;

      const [strandedRes, sightingRes] = await Promise.all([
        supabase.from('stranded_incidents').select('id').eq('barangay_id', barangayId),
        supabase.from('sightings').select('id').eq('barangay_id', barangayId)
      ]);

      const strandedIds = strandedRes.data?.map(s => s.id) || [];
      const sightingIds = sightingRes.data?.map(s => s.id) || [];

      let commentIds = [];
      if (strandedIds.length > 0) {
        const commentsRes = await supabase
          .from('comments')
          .select('id')
          .in('stranded_incident_id', strandedIds);
        commentIds = commentsRes.data?.map(c => c.id) || [];
      }

      const conditions = [];
      if (strandedIds.length > 0) {
        conditions.push(`stranded_incident_id.in.(${strandedIds})`);
      }
      if (sightingIds.length > 0) {
        conditions.push(`sighting_id.in.(${sightingIds})`);
      }
      if (commentIds.length > 0) {
        conditions.push(`comment_id.in.(${commentIds})`);
      }
      if (conditions.length > 0) {
        query = supabase
        .from('notifications')
        .select('*');
        query = query.or(conditions.join(','));
        query = query.neq('notif_for', 'specific_user');
      }

    } else if (userRole === 'lgu_responder') {
      // Similar approach for LGU responders
      const municipalityId = user.value.municipality_id;

      const [strandedRes, sightingRes] = await Promise.all([
        supabase.from('stranded_incidents').select('id').eq('municipality_id', municipalityId),
        supabase.from('sightings').select('id').eq('municipality_id', municipalityId)
      ]);

      const strandedIds = strandedRes.data?.map(s => s.id) || [];
      const sightingIds = sightingRes.data?.map(s => s.id) || [];

      let commentIds = [];
      if (strandedIds.length > 0) {
        const commentsRes = await supabase
          .from('comments')
          .select('id')
          .in('stranded_incident_id', strandedIds);
        commentIds = commentsRes.data?.map(c => c.id) || [];
      }

      const conditions = [];
      if (strandedIds.length > 0) {
        conditions.push(`stranded_incident_id.in.(${strandedIds})`);
      }
      if (sightingIds.length > 0) {
        conditions.push(`sighting_id.in.(${sightingIds})`);
      }
      if (commentIds.length > 0) {
        conditions.push(`comment_id.in.(${commentIds})`);
      }
      if (conditions.length > 0) {
        query = supabase
      .from('notifications')
      .select('*');
        query = query.or(conditions.join(','));
        query = query.neq('notif_for', 'specific_user');
      }

    } else if (userRole === 'bpemo_admin' || userRole === 'bpemo_staff') {
        query = supabase
      .from('notifications')
      .select('*');
      query = query.neq('notif_for', 'specific_user');
    }
    if (query.length === 0) {
      return;
    }
    query = query.order('created_at', { ascending: false });

    const { data, error } = await query;

    if (error) {
      console.error('Supabase query error:', error);
      throw error;
    }

    notifications.value = data;
    displayedNotifications.value = notifications.value.slice(0, limit.value);
  } catch (error) {
    console.error('Error fetching notifications:', error);
  }
};

// Subscribe to real-time notifications
const subscribeToNotifications = () => {
  const notificationsChannel = supabase
    .channel('public:notifications')
    .on('postgres_changes', { event: '*', schema: 'public', table: 'notifications' }, (payload) => {
      console.log('Change received!', payload);
      if (payload.eventType === 'INSERT') {
        // Add new notification to both notifications list and show toast
        const newNotification = payload.new;
        notifications.value = [newNotification, ...notifications.value];
        displayedNotifications.value = notifications.value.slice(0, limit.value);
        showToast(newNotification);

        // Play notification sound (optional)
        const audio = new Audio('/path/to/notification-sound.mp3'); // Add your sound file
        audio.play().catch(e => console.log('Audio play failed:', e));
      }
      fetchNotifications();
    })
    .subscribe((status) => {
      console.log('Notification subscription status:', status);
    });

  onBeforeUnmount(() => {
    supabase.removeChannel(notificationsChannel);
  });
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
                router.get(route('stranded.incident.view', { id: notification.stranded_incident_id }));
            }
        } catch (error) {
            console.error('Error fetching stranded incident status:', error);
        }
    } else if (notification.category === 'general' && notification.type === 'sighting') {
        router.get(); // Add your logic here
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
      !event.target.closest('.hamburger-btn') &&
      !event.target.closest('.dropdown-container')) {
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
  fetchNotifications();
  subscribeToNotifications();
});

onBeforeUnmount(() => {
  // Remove event listeners
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('click', closeSidebarDropdown);
  window.removeEventListener('resize', handleResize);
});

// Add these new refs for toast notifications
const toasts = ref([]);
const newNotifications = ref([]);

// Update the showToast function
const showToast = async (notification) => {
  // Play notification sound
  const audio = new Audio('/sounds/notif2.wav');
  try {
    await audio.play();
  } catch (error) {
    console.log('Audio playback failed:', error);
  }

  const toast = {
    id: Date.now(),
    content: notification.content,
    type: notification.type,
    show: true,
    notification: notification, // Store the full notification object for click handling
  };

  toasts.value.push(toast);

  // Remove toast after 7 seconds (increased from 5 to give more time to interact)
  setTimeout(() => {
    const index = toasts.value.findIndex(t => t.id === toast.id);
    if (index !== -1) {
      toasts.value[index].show = false;
      setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== toast.id);
      }, 300);
    }
  }, 7000);
};

// Add new function to handle toast clicks
const handleToastClick = (toast) => {
  const notification = toast.notification;
  openNotification(notification);
  // Remove the toast after clicking
  toast.show = false;
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== toast.id);
  }, 300);
};
</script>

<template>
  <!-- Change the root div to have a relative position -->
  <div class="relative flex h-screen overflow-hidden">
    <!-- Update sidebar classes -->
    <div
      class="sidebar flex flex-col flex-shrink-0 text-indigo-700 bg-white dark:text-indigo-200 dark:bg-indigo-900 h-screen fixed md:relative top-0 transition-all duration-300 z-40"
      :class="{
        'w-64': state.sidebarOpen,
        'w-0': !state.sidebarOpen
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
    <div class="flex-1 flex flex-col min-h-screen w-full relative">
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

      <!-- Update main content area -->
      <main class="flex-1 overflow-y-auto relative">
        <slot />

        <!-- Move modals and toasts outside the main scrollable area -->
        <Modal v-if="isFalseNotificationModalOpen"
               :show="isFalseNotificationModalOpen"
               @close="closeFalseNotificationModal"
               class="fixed inset-0 z-50">
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

        <!-- Update toast container positioning -->
        <div class="fixed top-4 right-4 z-40 space-y-2 max-w-md w-full pointer-events-none">
          <transition-group name="toast">
            <div v-for="toast in toasts" :key="toast.id"
              v-show="toast.show"
              class="toast-notification pointer-events-auto"
              :class="{
                'bg-blue-50 border-l-4 border-blue-500': toast.type === 'stranding',
                'bg-green-50 border-l-4 border-green-500': toast.type === 'sighting',
                'bg-yellow-50 border-l-4 border-yellow-500': toast.type === 'warning'
              }"
              @click="handleToastClick(toast)"
            >
              <div class="p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3 flex-grow">
                  <div class="flex-shrink-0">
                    <span class="material-icons text-xl"
                      :class="{
                        'text-blue-600': toast.type === 'stranding',
                        'text-green-600': toast.type === 'sighting',
                        'text-yellow-600': toast.type === 'warning'
                      }">
                      {{ toast.type === 'stranding' ? 'warning' :
                         toast.type === 'sighting' ? 'visibility' : 'info' }}
                    </span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold mb-1"
                      :class="{
                        'text-blue-800': toast.type === 'stranding',
                        'text-green-800': toast.type === 'sighting',
                        'text-yellow-800': toast.type === 'warning'
                      }">
                      {{ toast.type.charAt(0).toUpperCase() + toast.type.slice(1) }} Notification
                    </p>
                    <p class="text-sm text-gray-700 line-clamp-2">
                      {{ toast.content }}
                    </p>
                  </div>
                </div>
                <button @click.stop="toast.show = false"
                        class="flex-shrink-0 ml-4 text-gray-500 hover:text-gray-700 focus:outline-none">
                  <span class="material-icons text-sm">close</span>
                </button>
              </div>
            </div>
          </transition-group>
        </div>
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

/* Add Toast Animation Styles */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.toast-notification {
  animation: slideIn 0.3s ease-out;
  max-width: calc(100vw - 2rem);
  word-break: break-word;
  transition: background-color 0.2s ease;
}

.toast-notification:hover {
  transform: translateY(-1px);
  transition: transform 0.2s ease;
}

/* Responsive styles for different screen sizes */
@media (max-width: 640px) {
  .toast-notification {
    width: calc(100vw - 2rem);
    margin-left: 1rem;
    margin-right: 1rem;
  }
}

@media (min-width: 641px) {
  .toast-notification {
    width: 400px;
  }
}

/* Update existing styles */
.sidebar {
  transition: all 0.3s ease-in-out;
  overflow-y: auto;
  overflow-x: hidden;
}

/* Remove any invisible class handling that might interfere with clicking */
.sidebar.w-0 {
  overflow: hidden;
  visibility: hidden;
  opacity: 0;
}

/* Ensure dropdowns are clickable */
.dropdown-container {
  position: absolute;
  z-index: 60;
  pointer-events: auto;
}

/* Update toast container styles */
.toast-notification {
  position: relative;
  background: white;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  animation: slideIn 0.3s ease-out;
  max-width: calc(100vw - 2rem);
  word-break: break-word;
  transition: transform 0.2s ease, background-color 0.2s ease;
}

/* Ensure proper stacking context */
.z-40 {
  z-index: 40;
}

.z-30 {
  z-index: 30;
}

/* Remove any conflicting pointer-events styles */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    pointer-events: auto;
  }

  .sidebar.w-0 {
    pointer-events: none;
  }
}

/* Update toast notification styles */
.toast-notification {
  width: 384px; /* w-96 equivalent */
  transform-origin: top right;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.toast-notification:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Toast animations */
.toast-enter-active {
  animation: toast-in-right 0.3s ease-out forwards;
}

.toast-leave-active {
  animation: toast-out-right 0.3s ease-in forwards;
}

@keyframes toast-in-right {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes toast-out-right {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

/* Responsive toast width */
@media (max-width: 640px) {
  .toast-notification {
    width: calc(100vw - 2rem);
    margin-left: 1rem;
    margin-right: 1rem;
  }
}

/* Add text truncation */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
