<script setup>
import DropdownLink from '@/Components/DropdownLink.vue';
import { reactive } from 'vue';

const state = reactive({
  sidebarOpen: false, // Sidebar toggle
  activeDropdown: null, // Tracks which dropdown is open
});

const toggleDropdown = (dropdownName) => {
  state.activeDropdown = state.activeDropdown === dropdownName ? null : dropdownName;
};
</script>

<template>
  <div class="flex-col w-full md:flex md:flex-row md:min-h-screen">
    <!-- Sidebar -->

    <div class="flex flex-col flex-shrink-0 w-full text-indigo-700 bg-white md:w-64 dark:text-indigo-200 dark:bg-indigo-900">
      <!-- Header -->
      <div class="flex flex-row items-center justify-between flex-shrink-0 px-8 py-4">
        <a
          href="#"
          class="text-lg font-semibold tracking-widest text-indigo-900 uppercase rounded-lg dark:text-white focus:outline-none focus:shadow-outline"
        >
          BMW-MIS
        </a>
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
        <a class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-indigo-200 rounded-lg dark:bg-indigo-800 dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Dashboard</a>

        <!-- Manage Stranded Incident -->
        <a class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Manage Stranded Incident</a>

        <!-- Explore Marine Wildlife Species -->
        <a class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Explore Marine Wildlife Species</a>

        <!-- Manage Species Record Dropdown -->
        <div class="relative">
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
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Turtles
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Mammals
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Shark and Rays
            </a>
          </div>
        </div>

        <!-- Manage Guidelines Dropdown -->
        <div class="relative">
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
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              LGU Responder
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Barangay Official
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Public User
            </a>
          </div>
        </div>

        <!-- Manage Sightings -->
        <a class="block px-4 py-2 mt-2 text-sm font-semibold text-indigo-900 bg-transparent rounded-lg dark:bg-transparent dark:hover:bg-indigo-700 dark:focus:bg-indigo-700 dark:focus:text-white dark:hover:text-white dark:text-indigo-200 hover:text-indigo-900 focus:text-indigo-900 hover:bg-indigo-200 focus:bg-indigo-200 focus:outline-none focus:shadow-outline" href="#">Manage Sightings</a>

        <!-- Generate Report Dropdown -->
        <div class="relative">
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

          <div
            v-if="state.activeDropdown === 'generateReport'"
            class="w-full mt-2 bg-white rounded-md shadow-lg dark:bg-indigo-800"
          >
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
            Marine Wildlife Cluster Map
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Wildlife Summary Report
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Turtle Categorized Report
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Marine Mammal Categorized Report
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Shark and Rays Categorized Report
            </a>
            <a
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              Download Marine Wildlife Data
            </a>
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
            <DropdownLink
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              :href="route('profile.edit')"
            >
              Profile
            </DropdownLink>
            <DropdownLink
              class="block px-4 py-2 text-sm font-semibold text-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700 dark:text-white"
              href="#"
            >
              All Accounts
            </DropdownLink>
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
    <div>
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
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>
    <main>
        <slot />
    </main>
    </div>


  </div>
</template>
