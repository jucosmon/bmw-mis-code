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
  if (!user.value) return;

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

const navigateToRegister = () => {
  router.get(route('register'));
};

const navigateToLogin = () => {
  router.get(route('login'));
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
    const userRole = user.value?.user_role; // Add null check

    if (notification.category === 'general' && notification.type === 'stranding') {
        try {
            // Check if stranded_incident_id exists to prevent errors
            if (!notification.stranded_incident_id) {
                openFalseNotificationModal(notification.content);
                return;
            }

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
                // Check if route exists before navigating
                if (route().has('stranded.incident.view')) {
                    router.get(route('stranded.incident.view', { id: notification.stranded_incident_id }));
                } else {
                    // Fallback if route doesn't exist
                    openFalseNotificationModal('Cannot navigate to this notification.');
                }
            }
        } catch (error) {
            console.error('Error fetching stranded incident status:', error);
            openFalseNotificationModal('Error loading notification details.');
        }
    } else if (notification.category === 'general' && notification.type === 'sighting') {
        // Safer handling for sighting type
        try {
            if (notification.sighting_id && route().has('sighting.view')) {
                router.get(route('sighting.view', { id: notification.sighting_id }));
            } else {
                openFalseNotificationModal(notification.content);
            }
        } catch (error) {
            console.error('Error navigating to sighting:', error);
            openFalseNotificationModal(notification.content);
        }
    } else if (notification.category === 'false' || notification.category === 'warning') {
        openFalseNotificationModal(notification.content);
    } else {
        // Fallback for other notification types
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
    return page.props.auth?.user || null;
});

const hasUserAccess = computed(() => {
    return !!user.value;
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
  <!-- Change the root div to have a relative position without white bg -->
  <div class="relative flex h-screen overflow-hidden bg-transparent">
    <!-- Sidebar with oceanic glass design - increase z-index -->
    <div
      class="sidebar flex flex-col flex-shrink-0 h-screen fixed md:relative top-0 transition-all duration-300 z-[999] oceanic-glass-sidebar"
      :class="{
        'w-64': state.sidebarOpen,
        'w-0 -translate-x-full pointer-events-none': !state.sidebarOpen
      }"
    >
      <!-- Sidebar header with logo -->
      <div class="flex items-center justify-between p-4">
        <Link
          href="#"
          class="text-lg font-semibold tracking-widest text-white uppercase sidebar-logo"
          :class="{ 'hidden': !state.sidebarOpen }"
        >
          <span class="ocean-text-gradient">BMW-MIS</span>
        </Link>
        <!-- Close button -->
        <button
          class="rounded-lg focus:outline-none focus:shadow-outline text-white/80 hover:text-white transition-colors"
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
            class="flex items-center px-4 py-2 mt-2 text-sm font-semibold rounded-lg nav-link"
            :class="$page.url === route('dashboard') ? 'nav-link-active' : 'nav-link-inactive'"
            @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">home</span>
            <span class="ml-2">Dashboard</span>
        </Link>
        <!-- Manage Stranded Incident -->
        <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold rounded-lg nav-link"
        :href="route('stranded.incident.index')"
        :class="$page.url.startsWith(route('stranded.incident.index')) ? 'nav-link-active' : 'nav-link-inactive'"
        @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">medication</span>
            <span class="ml-2">Stranded Incident</span>
        </Link>

        <!-- Manage Sightings -->
        <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold rounded-lg nav-link"
          :href="route('sighting.index')"
          :class="$page.url.startsWith(route('sighting.index')) ? 'nav-link-active' : 'nav-link-inactive'"
          @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">visibility</span>
            <span class="ml-2">Sightings</span>
        </Link>

        <!-- Manage Species  -->
         <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold rounded-lg nav-link"
         :href="route('species.index')"
         :class="$page.url.startsWith(route('species.index')) ? 'nav-link-active' : 'nav-link-inactive'"
         @click="closeSidebar">
            <span class="material-icons text-lg mr-2 leading-none">manage_search</span>
            <span class="ml-2">Explore Species</span>
        </Link>

        <!-- Manage Guidelines Dropdown -->
        <div v-if="hasUserAccess && user?.user_role==='bpemo_admin'" class="relative mt-2">
          <button
            @click="toggleDropdown('manageGuidelines', $event)"
            class="flex flex-row items-center w-full px-4 py-2 text-sm font-semibold text-left rounded-lg nav-link nav-link-inactive"
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
            class="w-full mt-2 dropdown-menu"
          >
            <Link
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('manage.guideline.index', {user_role: 'lgu_responder', archived: false})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">LGU Responder</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('manage.guideline.index', {user_role: 'barangay_official', archived: false})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Barangay Official</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
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
          v-if="!['bpemo_admin', 'bpemo_staff'].includes(user?.user_role)"
          class="flex items-center px-4 py-2 mt-2 text-sm font-semibold rounded-lg nav-link"
          :class="$page.url.startsWith(route('guideline.index')) ? 'nav-link-active' : 'nav-link-inactive'"
          :href="route('guideline.index')"
          @click="closeSidebar">
          <span class="material-icons text-lg mr-2 leading-none">article</span>
          <span class="ml-2">Guidelines</span>
        </Link>

        <!-- Generate Report Dropdown -->
        <div v-if="hasUserAccess && ['bpemo_admin', 'bpemo_staff', 'lgu_responder'].includes(user?.user_role)" class="relative mt-2">
          <button
            @click="toggleDropdown('generateReport', $event)"
            class="flex flex-row items-center w-full px-4 py-2 text-sm font-semibold text-left rounded-lg nav-link nav-link-inactive"
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
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a 1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
          <div
            v-if="state.activeDropdown === 'generateReport'"
            class="w-full mt-2 dropdown-menu"
          >
            <Link
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('generate.report.cluster.map')"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">map</span>
              <span class="ml-2">Cluster Map</span>
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('generate.report.summary.report')"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Summary Report</span>
            </Link>
          </div>
        </div>

        <!-- Manage Account Dropdown -->
        <div class="relative mt-2" v-if="hasUserAccess && ['bpemo_admin', 'lgu_responder'].includes(user?.user_role)">
          <button
            @click="toggleDropdown('manageAccount', $event)"
            class="flex flex-row items-center w-full px-4 py-2 text-sm font-semibold text-left rounded-lg nav-link nav-link-inactive"
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
            class="w-full mt-2 dropdown-menu"
          >
            <DropdownLink
              v-if="user?.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_admin'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">BPEMO Administrator</span>
            </DropdownLink>
            <DropdownLink
              v-if="user?.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_staff'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">BPEMO Staff</span>
            </DropdownLink>
            <DropdownLink
              v-if="user?.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('bpemo.admin.manage.account.index', { type: 'lgu_responder'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">LGU Responder</span>
            </DropdownLink>
            <DropdownLink
              v-if="user?.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('bpemo.admin.manage.account.index', {type: 'barangay_official'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Barangay Official</span>
            </DropdownLink>
            <DropdownLink
              v-if="user?.user_role === 'bpemo_admin'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
              :href="route('bpemo.admin.manage.account.index', {type: 'public_user'})"
              @click="closeSidebar"
            >
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span class="ml-2">Public User</span>
            </DropdownLink>

            <!-- Manage Barangay Official Accounts for LGU Responder user -->
            <DropdownLink
              v-if="user?.user_role === 'lgu_responder'"
              class="block px-4 py-2 text-sm font-semibold text-white rounded-lg dropdown-item"
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

      <!-- Floating action buttons on left side (hamburger and header) -->
      <div class="absolute top-4 left-4 z-50 flex items-center gap-3 pl-1">
        <!-- Hamburger/Menu Button - Floating on left -->
        <button class="oceanic-float-button hamburger-btn" @click="toggleSidebar">
          <span class="material-icons">{{ state.sidebarOpen ? 'menu_open' : 'menu' }}</span>
        </button>
      </div>

      <!-- Floating action buttons on right side -->
      <div class="absolute top-4 right-4 z-50 flex gap-3" v-if="hasUserAccess">
        <!-- Notifications Button - Floating -->
        <button @click="toggleNotificationsDropdown($event)" class="oceanic-float-button relative">
          <span class="material-icons">notifications</span>
          <div v-if="state.notificationsDropdownOpen" class="dropdown-container notifications-dropdown absolute right-0 top-full mt-2 w-60 sm:w-80 oceanic-glass-panel rounded-lg shadow-lg z-20">
              <div class="py-2">
                <div class="flex justify-between px-4">
                    <div class="flex w-full gap-1">
                        <button @click="updateFilterType('all')"
                            class=" flex-1 py-1 text-sm transition-colors duration-200"
                            :class="{
                                'bg-blue-600 text-white': filterType === 'all',
                                'bg-transparent text-blue-300 hover:bg-blue-600/30': filterType !== 'all'
                            }"
                            @click.stop>
                         All
                        </button>
                        <button @click="updateFilterType('stranding')"
                                class="flex-1 py-1 text-sm transition-colors duration-200"
                                :class="{
                                    'bg-blue-600 text-white': filterType === 'stranding',
                                    'bg-transparent text-blue-300 hover:bg-blue-600/30': filterType !== 'stranding'
                                }"
                                @click.stop>
                            Stranding
                        </button>
                        <button @click="updateFilterType('sighting')"
                                class=" flex-1 py-1 text-sm transition-colors duration-200"
                                :class="{
                                    'bg-blue-600 text-white': filterType === 'sighting',
                                    'bg-transparent text-blue-300 hover:bg-blue-600/30': filterType !== 'sighting'
                                }"
                                @click.stop>
                            Sightings
                        </button>
                    </div>
                </div>
                  <div class="max-h-60 overflow-y-auto">
                      <template v-if="displayedNotifications.length > 0">
                          <div v-for="notification in displayedNotifications" :key="notification.id"
                              class="block px-4 py-3 text-sm text-left text-white hover:bg-white/10 transition-all duration-200"
                              @click="openNotification(notification)" @click.stop>
                              {{ notification.content }}
                          </div>
                      </template>
                      <template v-else>
                          <div class="px-4 py-3 text-sm text-white/60">No notifications available.</div>
                      </template>
                  </div>
                  <div class="px-4 py-2">
                      <button v-if="hasMoreNotifications" @click="showMoreNotifications" class="text-blue-300 hover:text-blue-200 text-sm" @click.stop>Show More</button>
                  </div>
              </div>
          </div>
        </button>

        <!-- Profile Button - Floating -->
        <button @click="toggleProfileDropdown($event)" class="oceanic-float-button relative">
          <span class="material-icons">account_circle</span>
          <div v-if="state.profileDropdownOpen" class="dropdown-container absolute right-0 top-full mt-2 w-48 oceanic-glass-panel rounded-lg shadow-lg z-20">
            <div class="py-2">
              <DropdownLink class="block px-4 py-2 text-sm text-white hover:bg-white/10 transition-all duration-200" :href="route('profile.view')">
                Profile
              </DropdownLink>
              <DropdownLink class="block px-4 py-2 text-sm text-white hover:bg-white/10 transition-all duration-200" :href="route('logout')" method="post" as="button">
                Logout
              </DropdownLink>
            </div>
          </div>
        </button>
      </div>

      <div class="absolute top-4 right-4 z-50 flex gap-2" v-else>
        <!-- Profile Button - Floating -->
        <button @click="navigateToLogin()" class="register-button relative">
          <span>Log In</span>
        </button>
        <button @click="navigateToRegister()" class="register-button relative">
          <span>Register</span>
        </button>

      </div>

      <!-- Main content area -->
      <main class="flex-1 overflow-y-auto relative">
          <slot/>


        <div v-if="hasUserAccess">
            <Modal v-if="isFalseNotificationModalOpen"
                :show="isFalseNotificationModalOpen"
                @close="closeFalseNotificationModal"
                class="fixed inset-0 z-50 modal-content">
            <div class="oceanic-glass-panel p-6 rounded-lg shadow-lg">
                <h2 class="text-lg font-semibold text-white/90 ocean-text-gradient">
                Notification Details
                </h2>
                <p class="text-white/80 mt-2">{{ modalContent }}</p>
                <div class="mt-6 space-x-4 flex justify-end">
                <PrimaryButton @click="closeFalseNotificationModal" class="oceanic-button">Ok</PrimaryButton>
                </div>
            </div>
            </Modal>

            <!-- Update toast container positioning - move to bottom -->
            <div class="fixed bottom-4 left-4 right-4 md:right-4 md:left-auto z-40 flex flex-col items-center md:items-end pointer-events-none">
                <transition-group name="toast">
                <div v-for="toast in toasts" :key="toast.id"
                v-show="toast.show"
                class="toast-notification pointer-events-auto mx-auto mb-2"
                :class="{
                    'toast-stranding': toast.type === 'stranding',
                    'toast-sighting': toast.type === 'sighting',
                    'toast-warning': toast.type === 'warning'
                }"
                @click="handleToastClick(toast)"
                >
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-grow">
                    <div class="flex-shrink-0">
                        <span class="material-icons text-xl"
                        :class="{
                            'text-blue-300': toast.type === 'stranding',
                            'text-green-300': toast.type === 'sighting',
                            'text-yellow-300': toast.type === 'warning'
                        }">
                        {{ toast.type === 'stranding' ? 'warning' :
                            toast.type === 'sighting' ? 'visibility' : 'info' }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold mb-1"
                        :class="{
                            'text-blue-300': toast.type === 'stranding',
                            'text-green-300': toast.type === 'sighting',
                            'text-yellow-300': toast.type === 'warning'
                        }">
                        {{ toast.type.charAt(0).toUpperCase() + toast.type.slice(1) }} Notification
                        </p>
                        <p class="text-sm text-white/80 line-clamp-2">
                        {{ toast.content }}
                        </p>
                    </div>
                    </div>
                    <button @click.stop="toast.show = false"
                            class="flex-shrink-0 ml-4 text-white/60 hover:text-white/90 focus:outline-none">
                    <span class="material-icons text-sm">close</span>
                    </button>
                </div>
                </div>
            </transition-group>
            </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Ocean theme styling for sidebar */
.oceanic-glass-sidebar {
  background: rgba(0, 51, 102, 0.9);
  backdrop-filter: blur(12px);
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
}

.oceanic-glass-panel {
  background: rgba(0, 51, 102, 0.90);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Ocean gradient text */
.ocean-text-gradient {
  background: linear-gradient(to right, #ffffff, #4dabf7);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.sidebar-logo {
  font-weight: 700;
  letter-spacing: 1px;
}

/* Floating buttons */
.oceanic-float-button {
  background: rgba(0, 51, 102, 0.85);
  backdrop-filter: blur(8px);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.oceanic-float-button:hover {
  background: rgba(0, 71, 142, 0.95);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
}

/* Floating buttons */
.register-button {
  background: rgba(0, 51, 102, 0.85);
  backdrop-filter: blur(8px);
  color: white;
  width: auto;
  border-radius: 10%;
  height: 42px;
  padding: 10px;
  margin-right: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.register-button:hover {
  background: rgba(0, 71, 142, 0.95);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
}

/* Navigation links */
.nav-link {
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  border: 1px solid transparent;
}

.nav-link-active {
  background: rgba(77, 171, 247, 0.3);
  color: white;
  border-color: rgba(77, 171, 247, 0.5);
  font-weight: 600;
}

.nav-link-inactive {
  color: white;
}

.nav-link-inactive:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
}

/* Dropdown styling */
.dropdown-menu {
  background: rgba(0, 51, 102, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem 0;
}

.dropdown-item {
  transition: all 0.2s ease;
  margin: 0.25rem 0.5rem;
}

.dropdown-item:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* Select styling */
.oceanic-select {
  background: rgba(0, 51, 102, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  border-radius: 0.375rem;
  padding: 0.375rem 2rem 0.375rem 0.75rem;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.oceanic-button {
  background: rgba(77, 171, 247, 0.3);
  color: white;
  border-color: rgba(77, 171, 247, 0.5);
  font-weight: 600;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  transition: all 0.2s ease;
}

.oceanic-button:hover {
  background: rgba(77, 171, 247, 0.5);
}

/* Toast notifications */
.toast-notification {
  backdrop-filter: blur(8px);
  border-radius: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  animation: slideIn 0.3s ease-out;
  margin-bottom: 0.5rem;
  max-width: 400px;
}

.toast-stranding {
  background: rgba(0, 102, 204, 0.7);
  border-left: 4px solid rgba(77, 171, 247, 0.8);
}

.toast-sighting {
  background: rgba(0, 153, 102, 0.7);
  border-left: 4px solid rgba(64, 192, 128, 0.8);
}

.toast-warning {
  background: rgba(204, 153, 0, 0.7);
  border-left: 4px solid rgba(255, 193, 7, 0.8);
}

.toast-notification:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
}

/* Toast animations */
@keyframes slideIn {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* Responsive toast handling */
@media (max-width: 640px) {
  .toast-notification {
    width: calc(100vw - 2rem) !important;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
    left: 0;
    right: 0;
  }

  /* Fix main content spacing on mobile */
  .mt-16 {
    margin-top: 4.5rem !important;
  }
}

@media (min-width: 641px) and (max-width: 1024px) {
  .toast-notification {
    max-width: 90%;
    margin-left: auto;
    margin-right: auto;
  }
}

/* Sidebar handling on mobile */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
  }

  .w-0 {
    transform: translateX(-100%);
    visibility: hidden;
    opacity: 0;
    width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    pointer-events: none !important;
  }

  .w-64 {
    transform: translateX(0);
    visibility: visible;
    opacity: 1;
    width: 16rem;
  }

  /* Update main content area to provide better padding on small screens */
  main .mt-16 {
    padding-left: 1rem;
    padding-right: 1rem;
    margin-top: 4rem !important;
  }
}

/* Text truncation */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.oceanic-header-panel {
  background: rgba(0, 51, 102, 0.85);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 0.5rem 1.25rem;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
  transition: all 0.3s ease;
}

.oceanic-header-panel:hover {
  background: rgba(0, 71, 142, 0.95);
  border-color: rgba(255, 255, 255, 0.25);
}

@media (max-width: 640px) {
  .oceanic-header-panel {
    max-width: calc(100vw - 7rem);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .oceanic-header-panel h2 {
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

/* Modal styling */
.modal-content .oceanic-glass-panel {
  background: rgba(0, 51, 102, 0.95);
  border: 1px solid rgba(77, 171, 247, 0.3);
}

/* Global style overrides to remove white backgrounds */
:deep(body),
:deep(#app),
:deep(.min-h-screen),
:deep(.bg-white),
:deep(header),
:deep(nav) {
  background: transparent !important;
  background-color: transparent !important;
  box-shadow: none !important;
}

/* Make floating elements more visible against the background */
.oceanic-float-button,
.oceanic-header-panel {
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
}

/* Ensure sidebar is topmost */
.sidebar {
  z-index: 999 !important;
}

/* Sidebar base state when closed */
.sidebar.w-0 {
  width: 0 !important;
  min-width: 0 !important;
  max-width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow: hidden;
  pointer-events: none !important;
  transform: translateX(-100%);
  border: none !important;
}
</style>
