<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Sample data - would connect to your database in production
const stats = ref({
  totalUsers: 354,
  totalStrandings: 28,
  pendingSightings: 42,
  completedReports: 187
});

// Sample stranding data for the map markers and list
const activeReports = ref([
  { id: 1, type: 'stranding', status: 'pending', species: 'Dolphin', location: 'North Beach', lat: 14.5995, lng: 120.9842, reportedAt: '2025-02-27T08:30:00', urgency: 'high' },
  { id: 2, type: 'stranding', status: 'in-progress', species: 'Sea Turtle', location: 'East Bay', lat: 14.6021, lng: 120.9876, reportedAt: '2025-02-26T15:45:00', urgency: 'medium' },
  { id: 3, type: 'sighting', status: 'pending', species: 'Whale Shark', location: 'Coral Reef', lat: 14.5932, lng: 120.9799, reportedAt: '2025-02-28T09:15:00', urgency: 'low' },
  { id: 4, type: 'stranding', status: 'pending', species: 'Pilot Whale', location: 'West Shore', lat: 14.6081, lng: 120.9921, reportedAt: '2025-02-28T11:05:00', urgency: 'high' },
  { id: 5, type: 'sighting', status: 'in-progress', species: 'Dugong', location: 'Seagrass Bay', lat: 14.5984, lng: 120.9854, reportedAt: '2025-02-27T16:20:00', urgency: 'medium' }
]);

// Sort by urgency and date for urgent alerts
const recentAlerts = computed(() => {
  return [...activeReports.value]
    .sort((a, b) => {
      // First sort by urgency
      const urgencyOrder = { high: 0, medium: 1, low: 2 };
      if (urgencyOrder[a.urgency] !== urgencyOrder[b.urgency]) {
        return urgencyOrder[a.urgency] - urgencyOrder[b.urgency];
      }
      // Then by date (newest first)
      return new Date(b.reportedAt) - new Date(a.reportedAt);
    })
    .slice(0, 4); // Get top 4 alerts
});

// Function to get status color
const getStatusColor = (status) => {
  const colors = {
    'pending': 'text-red-600 bg-red-100',
    'in-progress': 'text-yellow-600 bg-yellow-100',
    'completed': 'text-green-600 bg-green-100'
  };
  return colors[status] || 'text-gray-600 bg-gray-100';
};

// Function to get marker color for map
const getMarkerColor = (status, type) => {
  if (type === 'sighting') return 'text-blue-500';
  if (status === 'pending') return 'text-red-500';
  if (status === 'in-progress') return 'text-yellow-500';
  return 'text-green-500';
};

// Function to format date
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <Head title="Dashboard" />

  <Sidebar>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        BPEMO Admin Dashboard
      </h2>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Welcome message -->
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-700">
            Welcome {{ user.first_name }} {{ user.last_name }}
          </h3>
          <p class="text-sm text-gray-500">
            Marine Wildlife Monitoring Dashboard
          </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
          <!-- Total Users Card -->
          <div class="bg-white overflow-hidden rounded-lg shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                  <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.totalUsers }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Unresolved Strandings -->
          <div class="bg-white overflow-hidden rounded-lg shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                  <i class="fas fa-life-ring text-red-600 text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Unresolved Strandings</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.totalStrandings }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Sightings -->
          <div class="bg-white overflow-hidden rounded-lg shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                  <i class="fas fa-binoculars text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Sightings</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.pendingSightings }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Completed Reports -->
          <div class="bg-white overflow-hidden rounded-lg shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                  <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Completed Reports</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.completedReports }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Map and Alerts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Map Section - Takes up 2/3 of the width on large screens -->
          <div class="bg-white rounded-lg shadow lg:col-span-2">
            <div class="px-6 py-5 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Pending and Ongoing Reports in Map</h3>
            </div>
            <div class="p-6">
              <!-- Map placeholder - In a real implementation, this would be replaced with a map component -->
              <div class="bg-gray-100 rounded-lg h-96 relative overflow-hidden">
                <!-- Map pins with status colors -->
                <div class="absolute top-1/4 left-1/4">
                  <i class="fas fa-map-marker-alt text-2xl text-red-500"></i>
                </div>
                <div class="absolute top-1/3 left-1/2">
                  <i class="fas fa-map-marker-alt text-2xl text-yellow-500"></i>
                </div>
                <div class="absolute top-2/3 left-1/3">
                  <i class="fas fa-map-marker-alt text-2xl text-blue-500"></i>
                </div>
                <div class="absolute top-1/2 left-2/3">
                  <i class="fas fa-map-marker-alt text-2xl text-red-500"></i>
                </div>
                <div class="absolute top-3/4 left-1/4">
                  <i class="fas fa-map-marker-alt text-2xl text-yellow-500"></i>
                </div>
                <!-- Map overlay with informational text -->
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 text-white">
                  <div class="text-center p-4">
                    <p class="font-semibold mb-2">Map Placeholder</p>
                    <p class="text-sm">
                      In production, connect with your preferred mapping library (Leaflet, Google Maps, etc.)
                    </p>
                  </div>
                </div>
              </div>
              <div class="mt-4 flex justify-between text-sm">
                <div class="flex items-center">
                  <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                  <span>Pending Strandings</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-map-marker-alt text-yellow-500 mr-1"></i>
                  <span>In-Progress</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>
                  <span>Sightings</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Alerts Section - Takes up 1/3 of the width on large screens -->
          <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-5 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Urgent Alerts</h3>
            </div>
            <div class="divide-y divide-gray-200">
              <div v-for="alert in recentAlerts" :key="alert.id" class="p-4 hover:bg-gray-50">
                <div class="flex items-start">
                  <div :class="getMarkerColor(alert.status, alert.type)" class="flex-shrink-0 mt-1">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                  </div>
                  <div class="ml-3 w-0 flex-1">
                    <div class="flex justify-between items-center mb-1">
                      <h4 class="text-sm font-medium text-gray-900">
                        {{ alert.species }} {{ alert.type }}
                      </h4>
                      <span
                        :class="getStatusColor(alert.status)"
                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ alert.status }}
                      </span>
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                      <p>Location: {{ alert.location }}</p>
                      <p>Reported: {{ formatDate(alert.reportedAt) }}</p>
                    </div>
                    <div class="mt-2">
                      <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                        View details
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Reports Table -->
        <div class="bg-white rounded-lg shadow mb-6">
          <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Pending and Ongoing Reports</h3>
            <div class="flex space-x-2">
              <a href="#" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                All Strandings
              </a>
              <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                All Sightings
              </a>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    ID
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Species
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Location
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Reported
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="report in activeReports" :key="report.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    #{{ report.id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                    {{ report.type }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ report.species }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ report.location }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(report.reportedAt) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="getStatusColor(report.status)"
                      class="px-2 py-1 rounded-full text-xs font-medium capitalize"
                    >
                      {{ report.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                    <a href="#" class="text-green-600 hover:text-green-900">Update</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            <span class="text-sm text-gray-500">
              Showing 5 of 70 reports
            </span>
            <div class="flex space-x-2">
              <a href="#" class="px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Previous
              </a>
              <a href="#" class="px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Next
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Sidebar>
</template>
