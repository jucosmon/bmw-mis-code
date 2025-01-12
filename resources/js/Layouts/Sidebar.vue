<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, watch } from 'vue';

const state = reactive({
  sidebarOpen: false,
  activeDropdown: null,
  sidebarLargeScreenOpen: true,
  profileDropdownOpen: false, // For profile dropdown
  notificationsDropdownOpen: false, // For notifications dropdown
});

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
});

// Watch for changes to sidebarLargeScreenOpen and save to local storage
watch(() => state.sidebarLargeScreenOpen, (newValue) => {
  localStorage.setItem('sidebarLargeScreenOpen', JSON.stringify(newValue));
});

const toggleDropdown = (dropdownName, event) => {
    event.stopPropagation(); // Prevent the click event from bubbling up

  state.activeDropdown = state.activeDropdown === dropdownName ? null : dropdownName;
};

const toggleProfileDropdown = (event) => {
    event.stopPropagation(); // Prevent the click event from bubbling up
    if(state.notificationsDropdownOpen){
        state.notificationsDropdownOpen = false;
    }
    state.profileDropdownOpen = !state.profileDropdownOpen;
};

const toggleNotificationsDropdown = (event) => {
    event.stopPropagation(); // Prevent the click event from bubbling up
    if(state.profileDropdownOpen){
        state.profileDropdownOpen = false;
    }
    state.notificationsDropdownOpen = !state.notificationsDropdownOpen;
};

const closeDropdown = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    state.profileDropdownOpen = false;
    state.notificationsDropdownOpen = false;

  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdown);
});


const page = usePage();
const user = computed(() => page.props.auth.user);
console.log(user);
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
              class="text-lg font-semibold tracking-widest text-indigo-900 uppercase rounded-lg dark:text-white focus:outline-none focus:shadow-outline"
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
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Dashboard</span>
          </Link>

          <!-- Manage Stranded Incident -->
          <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          :href="route('stranded.incident.index')">
              <span class="material-icons text-lg mr-2 leading-none">medication</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Stranded Incident</span>
          </Link>

          <!-- Manage Sightings -->
          <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">
              <span class="material-icons text-lg mr-2 leading-none">visibility</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Sightings</span>
          </Link>

          <!-- Explore Marine Wildlife Species -->
          <Link class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">
              <span class="material-icons text-lg mr-2 leading-none">search</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Explore Species</span>
          </Link>

          <!-- Manage Species Record Dropdown -->
          <div v-if="user.user_role==='bpemo_admin'" class="relative">
            <button
              @click="toggleDropdown('manageSpeciesRecord', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">manage_search</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Manage Species</span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
                fill="currentColor"
                viewBox="0 0 20 20"
                :class="{ 'rotate-180': state.activeDropdown === 'manageSpeciesRecord', 'rotate-0': state.activeDropdown !== 'manageSpeciesRecord' }"
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
              v-if="state.activeDropdown === 'manageSpeciesRecord'"
              class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
            >
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.species.index', {category: 'marine_turtles'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml- 2">Marine Turtles</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.species.index', {category: 'marine_mammals'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Marine Mammals</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.species.index', {category: 'sharks_rays'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Sharks and Rays</span>
              </Link>
            </div>
          </div>

          <!-- Manage Guidelines Dropdown -->
          <div v-if="user.user_role==='bpemo_admin'" class="relative">
            <button
              @click="toggleDropdown('manageGuidelines', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">article</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Manage Guidelines</span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
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
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">LGU Responder</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Barangay Official</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Public User</span>
              </Link>
            </div>
          </div>

          <!-- View guidelines for specific user roles -->
          <Link
            v-if="user.user_role==='lgu_responder' || user.user_role==='barangay_official' || user.user_role==='public_user'"
            class="flex items-center px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            href="#">
            <span class="material-icons text-lg mr-2 leading-none">article</span>
            <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Guidelines</span>
          </Link>

          <!-- Generate Report Dropdown -->
          <div v-if="user.user_role==='bpemo_admin' || user.user_role==='bpemo_staff'" class="relative">
            <button
              @click="toggleDropdown('generateReport', $event)"
              class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
            >
              <span class="material-icons text-lg mr-2 leading-none">assessment</span>
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Generate Report</span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
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
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Marine Wildlife Cluster Map</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Marine Wildlife Summary Report</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Marine Turtle Categorized Report</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none ">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Marine Mammal Categorized Report</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Shark and Rays Categorized Report</span>
              </Link>
              <Link
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                href="#"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Download Marine Wildlife Data</span>
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
              <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Manage Account</span>
              <svg v-show="state.sidebarLargeScreenOpen || state.sidebarOpen"
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
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">BPEMO Administrator</span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_staff'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">BPEMO Staff</span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', { type: 'lgu_responder'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">LGU Responder</span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'barangay_official'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Barangay Official</span>
              </DropdownLink>
              <DropdownLink
                v-if="user.user_role === 'bpemo_admin'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('bpemo.admin.manage.account.index', {type: 'public_user'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Public User</span>
              </DropdownLink>

              <!-- Manage Barangay Official Accounts for LGU Responder user -->
              <DropdownLink
                v-if="user.user_role === 'lgu_responder'"
                class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
                :href="route('lgu.responder.manage.account.index', {type: 'barangay_official'})"
              >
                <span class="material-icons text-lg mr-2 leading-none">visibility</span>
                <span v-show="state.sidebarLargeScreenOpen || state.sidebarOpen" class="ml-2">Barangay Official</span>
              </DropdownLink>
            </div>
          </div>
        </nav>
      </div>

      <!-- Page Content -->
      <div class="flex-grow overflow-y-auto">
        <header class="shadow-md">
          <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 flex justify-between">
            <div class="flex gap-5">
              <slot name="header" />
            </div>
            <div class="flex gap-5">
              <!-- Notifications Button -->
              <button @click="toggleNotificationsDropdown($event)" class="relative rounded-lg lg:block hidden focus:outline-none focus:shadow-outline">
                <span class="material-icons text-indigo-900">notifications</span>
                <div v-if="state.notificationsDropdownOpen" class="dropdown-container absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20">
                  <div class="py-2">
                    <Link class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100" href="#">
                      Notification 1
                    </Link>
                    <Link class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100" href="#">
                      Notification 2
                    </Link>
                    <Link class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100" href="#">
                      Notification 3
                    </Link>
                  </div>
                </div>
              </button>

              <!-- Profile Button -->
              <button @click="toggleProfileDropdown($event)" class="relative rounded-lg lg:block hidden focus:outline-none focus:shadow-outline">
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
        </main>
    </div>
    </div>
  </template>
