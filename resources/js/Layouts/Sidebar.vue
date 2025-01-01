<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue'; // Get the authenticated user

const state = reactive({
  sidebarOpen: false, // Sidebar toggle
  activeDropdown: null, // Tracks which dropdown is open
});

const toggleDropdown = (dropdownName) => {
  state.activeDropdown = state.activeDropdown === dropdownName ? null : dropdownName;
};

const page = usePage();

const user = computed(() => page.props.auth.user);
console.log(user);
</script>

<template>
  <div class="flex-col w-full md:flex md:flex-row md:min-h-screen">
    <!-- Sidebar -->

    <div class="flex flex-col flex-shrink-0 w-full text-indigo-700 bg-white md:w-64 dark:text-indigo-200 dark:bg-indigo-900">
      <!-- Header -->
      <div class="flex flex-row items-center justify-between flex-shrink-0 px-8 py-4">
        <Link
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
      </div>

      <!-- Navigation -->
      <nav
        :class="{ 'block': state.sidebarOpen, 'hidden': !state.sidebarOpen }"
        class="flex-grow px-4 pb-4 md:block md:pb-0 md:overflow-y-auto"
      >
        <!-- Dashboard -->
        <Link :href="route('dashboard')" class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" active>Dashboard</Link>

        <!-- Manage Stranded Incident for all type of users but different capabilities within the page -->
        <Link class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Manage Stranded Incident</Link>

        <!-- Explore Marine Wildlife Species for all type of users -->
        <Link class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Explore Marine Wildlife Species</Link>

        <!-- Manage Species Record Dropdown for BPEMO admin only -->
        <div v-if="user.user_role==='bpemo_admin'" class="relative">
          <button
            @click="toggleDropdown('manageSpeciesRecord')"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span>Manage Species Record</span>
            <svg
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
              href="#"
            >
              Marine Turtles
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Mammals
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Shark and Rays
            </Link>
          </div>
        </div>

        <!-- Manage Guidelines Dropdown for BPEMO admin only -->
        <div v-if="user.user_role==='bpemo_admin'" class="relative">
          <button
            @click="toggleDropdown('manageGuidelines')"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span>Manage Guidelines</span>
            <svg
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
              LGU Responder
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Barangay Official
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Public User
            </Link>
          </div>
        </div>

        <!--  View guidelines for LGU responder, barangay official and public user -->
        <Link
        v-if="user.user_role==='lgu_responder' || user.user_role==='barangay_official' || user.user_role==='public_user'"
        class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
        href="#">
        Guidelines
        </Link>


        <!-- Manage Sightings -->
        <Link class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Manage Sightings</Link>

        <!-- Generate Report Dropdown -->
        <div v-if="user.user_role==='bpemo_admin' || user.user_role==='bpemo_staff'" class="relative">
          <button
            @click="toggleDropdown('generateReport')"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span>Generate Report</span>
            <svg
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
          <!-- For both BPEMO administrator and admin only -->
          <div
            v-if="state.activeDropdown === 'generateReport'"
            class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
          >
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
            Marine Wildlife Cluster Map
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Wildlife Summary Report
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Turtle Categorized Report
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Mammal Categorized Report
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Shark and Rays Categorized Report
            </Link>
            <Link
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Download Marine Wildlife Data
            </Link>
          </div>
        </div>

         <!-- Manage Account Dropdown -->
         <div class="relative">
          <button
            @click="toggleDropdown('manageAccount')"
            class="flex flex-row items-center w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent rounded-lg dark:bg-transparent dark:focus:text-white dark:hover:text-white dark:focus:bg-indigo-700 dark:hover:bg-indigo-700 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline"
          >
            <span>Manage Account</span>
            <svg
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
            <!-- Manage Profile for all user roles -->
            <DropdownLink
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('profile.view')"
            >
              Profile
            </DropdownLink>

            <!-- Manage All Accounts in the system for Administrator accounts -->
            <DropdownLink
            v-if="user.user_role === 'bpemo_admin'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
            :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_admin'})"
            >
              BPEMO Administrator Accounts
            </DropdownLink>
            <DropdownLink
            v-if="user.user_role === 'bpemo_admin'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'bpemo_staff'})"
            >
              BPEMO Staff Accounts
            </DropdownLink>
            <DropdownLink
            v-if="user.user_role === 'bpemo_admin'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'lgu_responder'})"
            >
              LGU Responder Accounts
            </DropdownLink>
            <DropdownLink
            v-if="user.user_role === 'bpemo_admin'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'barangay_official'})"
            >
              Barangay Official Accounts
            </DropdownLink>
            <DropdownLink
            v-if="user.user_role === 'bpemo_admin'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('bpemo.admin.manage.account.index', {type: 'public_user'})"
            >
              Public User Accounts
            </DropdownLink>

            <!-- Manage Barangay Offical Accounts for LGU Responder user-->
            <DropdownLink
            v-if="user.user_role === 'lgu_responder'"
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('lgu.responder.manage.account.index', {type: 'barangay_official'})"
            >
              Barangay Official Accounts
            </DropdownLink>
            <!-- Log out functionality for all users (built in laravel inertia)-->
            <DropdownLink
            class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('logout')"
              method="post" as="button"
            >
              Logout
            </DropdownLink>
          </div>
        </div>

      </nav>
    </div>

    <!-- Page Content -->
    <div class="flex-grow overflow-y-auto">
        <header>
            <!--
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            Hamburger Button for Larger Screens (Tablet, Laptop, Desktop)
            <button
                class="rounded-lg lg:block hidden focus:outline-none focus:shadow-outline"
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
            </div> -->

            <!-- Slot for header content -->
            <div class=" max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>
    <main>
        <slot />
    </main>
    </div>


  </div>
</template>
