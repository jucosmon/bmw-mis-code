<script setup>
import Sidebar from '@/Layouts/Sidebar.vue';
import { supabase } from '@/supabase';
import { Head, Link, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const userMunicipality = ref(null);

const municipalityName = computed(() => userMunicipality.value?.name || 'Loading...');

const map = ref(null);
const markers = ref([]);

// Stats data
const stats = ref({
  totalStrandings: {
    total: 0,
    breakdown: {
      pending: 0,
      verified: 0,
      resolved: 0
    }
  },
  pendingSightings: 0,
  completedReports: {
    total: 0,
    breakdown: {
      stranding: 0,
      sighting: 0
    }
  }
});

const activeReports = ref([]);
const recentAlerts = computed(() => {
  return activeReports.value
    .filter(report => report.type === 'stranded' && report.report_status !== 'resolved')
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .slice(0, 4);
});

// Add new refs for table filtering and pagination
const currentView = ref('all'); // 'all', 'stranding', 'sighting'
const currentPage = ref(1);
const perPage = ref(10);
const totalReports = ref(0);

// Fetch all data
const fetchData = async () => {
  // Ensure municipality data is loaded first
  if (!userMunicipality.value) {
    await fetchMunicipalityData();
  }

  // Only proceed if municipality data is available
  if (!userMunicipality.value) {
    console.error('Municipality data not available');
    return;
  }

  // Fetch stranding stats for user's municipality
  const { data: strandings } = await supabase
    .from('stranded_incidents')
    .select('*')
    .eq('is_active', true)
    .eq('municipality_id', userMunicipality.value.id);

  if (strandings) {
    // Count total unresolved (pending + verified)
    const unresolvedStrandings = strandings.filter(s => s.report_status !== 'resolved');
    stats.value.totalStrandings.total = unresolvedStrandings.length;

    // Breakdown by status
    stats.value.totalStrandings.breakdown = {
      pending: strandings.filter(s => s.report_status === 'pending').length,
      verified: strandings.filter(s => s.report_status === 'verified').length,
      completed: strandings.filter(s => s.report_status === 'completed').length
    };
  }

  // Fetch pending sightings for user's municipality
  const { data: pendingSightings } = await supabase
    .from('sightings')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'pending')
    .eq('municipality_id', userMunicipality.value.id);

  stats.value.pendingSightings = pendingSightings?.length || 0;

  // Fetch completed reports for user's municipality
  const { data: completedStrandings } = await supabase
    .from('stranded_incidents')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'resolved')
    .eq('municipality_id', userMunicipality.value.id);

  const { data: completedSightings } = await supabase
    .from('sightings')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'verified')
    .eq('municipality_id', userMunicipality.value.id);

  stats.value.completedReports = {
    total: (completedStrandings?.length || 0) + (completedSightings?.length || 0),
    breakdown: {
      stranding: completedStrandings?.length || 0,
      sighting: completedSightings?.length || 0
    }
  };

  // Fetch active reports for table and map with location details
  const { data: activeStrandings } = await supabase
    .from('stranded_incidents')
    .select(`
      *,
      barangay:barangay_id(name),
      municipality:municipality_id(name),
      stranded_species (
        *,
        species (*)
      )
    `)
    .eq('is_active', true)
    .eq('municipality_id', userMunicipality.value.id)
    .not('report_status', 'eq', 'resolved');

  const { data: activeSightings } = await supabase
    .from('sightings')
    .select(`
      *,
      barangay:barangay_id(name),
      municipality:municipality_id(name),
      sighted_species (
        *,
        species (*)
      )
    `)
    .eq('is_active', true)
    .eq('report_status', 'pending')
    .eq('municipality_id', userMunicipality.value.id);

  if (activeStrandings || activeSightings) {
    const processedStrandings = activeStrandings?.map(incident => ({
      id: incident.id,
      type: 'stranded',
      species: incident.species_involved || 'Unknown',
      location: `${incident.barangay?.name || 'Unknown'}, ${incident.municipality?.name || 'Unknown'}`,
      date: incident.date,
      status: incident.report_status,
      latitude: incident.latitude,
      longitude: incident.longitude,
      viewUrl: `/stranded-incident/view/${incident.id}`
    })) || [];

    const processedSightings = activeSightings?.map(sighting => ({
      id: sighting.id,
      type: 'sighting',
      species: sighting.sighted_species?.[0]?.species?.name || 'Unknown',
      location: `${sighting.barangay?.name || 'Unknown'}, ${sighting.municipality?.name || 'Unknown'}`,
      date: sighting.date,
      status: sighting.report_status,
      latitude: sighting.latitude,
      longitude: sighting.longitude,
      viewUrl: `/sighting/view/${sighting.id}`
    })) || [];

    activeReports.value = [...processedStrandings, ...processedSightings];
    totalReports.value = activeReports.value.length;
    updateMapMarkers();
  }
};

// Computed property for filtered and paginated reports
const displayedReports = computed(() => {
  let filtered = activeReports.value;

  if (currentView.value === 'stranding') {
    filtered = filtered.filter(report => report.type === 'stranded');
  } else if (currentView.value === 'sighting') {
    filtered = filtered.filter(report => report.type === 'sighting');
  }

  totalReports.value = filtered.length;

  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;

  return filtered.slice(start, end);
});

// Computed property for total pages
const totalPages = computed(() => Math.ceil(totalReports.value / perPage.value));

// Navigation methods
const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const setView = (view) => {
  currentView.value = view;
  currentPage.value = 1; // Reset to first page when changing views
};

// Update map markers
const updateMapMarkers = () => {
  if (!map.value) {
    initializeMap();
    return;
  }

  // Clear existing markers and cluster group
  if (map.value.markerClusterGroup) {
    map.value.markerClusterGroup.clearLayers();
    map.value.removeLayer(map.value.markerClusterGroup);
  }
  markers.value.forEach(marker => marker.remove());
  markers.value = [];

  // Create a new marker cluster group
  const markerCluster = L.markerClusterGroup({
    maxClusterRadius: 30,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    animate: false,
    animateAddingMarkers: false
  });

  map.value.markerClusterGroup = markerCluster;

  // Filter and process reports
  const strandedReports = activeReports.value.filter(report => report.type === 'stranded');

  strandedReports.forEach((report, index) => {
    try {
      if (!report.latitude || !report.longitude) {
        console.warn('Missing coordinates for report:', report.id);
        return;
      }

      const lat = Number(parseFloat(report.latitude).toFixed(6));
      const lng = Number(parseFloat(report.longitude).toFixed(6));

      if (isNaN(lat) || isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
        console.warn('Invalid coordinates for report:', report.id, lat, lng);
        return;
      }

      // Add offset for reports with same coordinates
      const angle = index * (Math.PI * 2) / 8;
      const radius = 0.0001 * Math.floor(index / 8);
      const adjustedLat = lat + Math.cos(angle) * radius;
      const adjustedLng = lng + Math.sin(angle) * radius;

      const marker = L.marker([adjustedLat, adjustedLng], {
        icon: L.icon({
          iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${getMarkerColor(report.status)}.png`,
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        })
      });

      const popupContent = `
        <div class="mb-2 pb-2 border-b border-gray-200">
          <b>${report.species}</b><br>
          ID: ${report.id}<br>
          Status: ${report.status}<br>
          Location: ${report.location}<br>
          Coordinates: ${lat}, ${lng}<br>
          Date: ${formatDate(report.date)}<br>
          <a href="${report.viewUrl}" class="text-blue-600 hover:text-blue-800">View details</a>
        </div>
      `;

      marker.bindPopup(popupContent);
      markerCluster.addLayer(marker);
      markers.value.push(marker);
    } catch (error) {
      console.error('Error adding marker for report:', report.id, error);
    }
  });

  map.value.addLayer(markerCluster);

  if (markerCluster.getBounds().isValid()) {
    try {
      map.value.fitBounds(markerCluster.getBounds(), {
        padding: [50, 50],
        maxZoom: 15,
        animate: false
      });
    } catch (error) {
      console.error('Error fitting bounds:', error);
      map.value.setView([9.8500, 124.1833], 10, { animate: false });
    }
  }
};

// Add this function to fetch municipality data
const fetchMunicipalityData = async () => {
  const { data: municipality } = await supabase
    .from('municipalities')
    .select('*')
    .eq('id', user.value.municipality_id)
    .single();

  if (municipality) {
    userMunicipality.value = municipality;
  }
};

// Initialize map
const initializeMap = () => {
  if (map.value) {
    map.value.remove();
  }

  try {
    map.value = L.map('map', {
      center: [9.8500, 124.1833],
      zoom: 10,
      minZoom: 2,
      maxZoom: 18,
      zoomAnimation: false,
      markerZoomAnimation: false
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
    }).addTo(map.value);
  } catch (error) {
    console.error('Error initializing map:', error);
  }
};

// Add cleanup function ref
const cleanup = ref(null);

// Update onMounted
onMounted(async () => {
  try {
    // Fetch municipality data first
    await fetchMunicipalityData();

    // Initialize map without immediate marker update
    nextTick(() => {
      initializeMap();

      // Set up subscriptions
      const strandings = supabase.channel('public:stranded_incidents')
        .on('postgres_changes',
          { event: '*', schema: 'public', table: 'stranded_incidents' },
          async () => {
            await fetchData();
          }
        )
        .subscribe();

      const sightings = supabase.channel('public:sightings')
        .on('postgres_changes',
          { event: '*', schema: 'public', table: 'sightings' },
          async () => {
            await fetchData();
          }
        )
        .subscribe();

      cleanup.value = () => {
        supabase.removeChannel(strandings);
        supabase.removeChannel(sightings);
        if (map.value) {
          if (map.value.markerClusterGroup) {
            map.value.markerClusterGroup.clearLayers();
          }
          map.value.remove();
        }
      };

      // Initial data fetch
      fetchData().then(() => {
        nextTick(() => updateMapMarkers());
      }).catch(error => {
        console.error('Error fetching initial data:', error);
      });
    });
  } catch (error) {
    console.error('Error in onMounted:', error);
  }
});

// Update onBeforeUnmount
onBeforeUnmount(() => {
  if (cleanup.value) {
    cleanup.value();
  }
});

// Add watch for activeReports
watch(activeReports, () => {
  nextTick(() => updateMapMarkers());
}, { deep: true });

// Helper functions
const getStatusColor = (status) => {
  const colors = {
    'pending': 'text-red-600 bg-red-100',
    'verified': 'text-yellow-600 bg-yellow-100',
    'resolved': 'text-green-600 bg-green-100'
  };
  return colors[status] || 'text-gray-600 bg-gray-100';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getMarkerColor = (status) => {
  switch (status) {
    case 'pending': return 'red';
    case 'verified': return 'yellow';
    case 'completed': return 'green';
    default: return 'gray';
  }
};
</script>

<template>
  <Head title="Dashboard" />

  <Sidebar>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Dashboard
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
            LGU Responder - {{ municipalityName }} Marine Wildlife Monitoring Dashboard
          </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 lg:grid-cols-3 mb-6">
          <!-- Unresolved Strandings in Municipality -->
          <div class="bg-white overflow-hidden rounded-lg shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                  <i class="fas fa-life-ring text-red-600 text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Active Cases in {{ municipalityName }}</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.totalStrandings.total }}</div>
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
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Sightings in {{ municipalityName }}</dt>
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
                    <dt class="text-sm font-medium text-gray-500 truncate">Completed Reports in {{ municipalityName }}</dt>
                    <dd>
                      <div class="text-lg font-semibold text-gray-900">{{ stats.completedReports.total }}</div>
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
              <h3 class="text-lg font-medium text-gray-900">Active Cases in {{ municipalityName }}</h3>
            </div>
            <div class="p-6">
              <!-- Map placeholder - In a real implementation, this would be replaced with a map component -->
              <div id="map" class="bg-gray-100 rounded-lg h-96 relative overflow-hidden z-0"></div>
              <div class="mt-4 flex justify-start text-sm gap-5">
                <div class="flex items-center gap-2">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <span>Pending</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                  <span>Verified</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                  <span>Completed</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Alerts Section - Takes up 1/3 of the width on large screens -->
          <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Urgent Cases in {{ municipalityName }}</h3>
              <Link
                :href="route('stranded.incident.index')"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                View All
              </Link>
            </div>
            <div class="divide-y divide-gray-200">
              <div v-for="alert in recentAlerts" :key="alert.id" class="p-4 hover:bg-gray-50">
                <div class="flex items-start">
                  <div :class="getStatusColor(alert.status)" class="flex-shrink-0 mt-1">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                  </div>
                  <div class="ml-3 w-0 flex-1">
                    <div class="flex justify-between items-center mb-1">
                      <h4 class="text-sm font-medium text-gray-900">
                        {{ alert.species }} ({{ alert.type }})
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
                      <p>Reported: {{ formatDate(alert.date) }}</p>
                    </div>
                    <div class="mt-2">
                      <a :href="alert.viewUrl" class="text-sm font-medium text-blue-600 hover:text-blue-800">
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
            <h3 class="text-lg font-medium text-gray-900">Active Reports in {{ municipalityName }}</h3>
            <div class="flex space-x-2">
                <button
                @click="setView('all')"
                :class="['inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md',
                  currentView === 'all'
                    ? 'border-transparent text-white bg-blue-600 hover:bg-blue-700'
                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50']"
              >
                All
              </button>
              <button
                @click="setView('stranding')"
                :class="['inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md',
                  currentView === 'stranding'
                    ? 'border-transparent text-white bg-blue-600 hover:bg-blue-700'
                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50']"
              >
                All Strandings
              </button>
              <button
                @click="setView('sighting')"
                :class="['inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md',
                  currentView === 'sighting'
                    ? 'border-transparent text-white bg-blue-600 hover:bg-blue-700'
                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50']"
              >
                All Sightings
              </button>
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
                <tr v-for="report in displayedReports" :key="report.id" class="hover:bg-gray-50">
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
                    {{ formatDate(report.date) }}
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
                    <Link :href="report.viewUrl" class="text-blue-600 hover:text-blue-900">
                      View
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            <span class="text-sm text-gray-500">
              Showing {{ ((currentPage - 1) * perPage) + 1 }} to
              {{ Math.min(currentPage * perPage, totalReports) }}
              of {{ totalReports }} reports
            </span>
            <div class="flex space-x-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                :class="['px-3 py-1 border text-sm font-medium rounded-md',
                  currentPage === 1
                    ? 'border-gray-200 text-gray-400 bg-gray-50'
                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50']"
              >
                Previous
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage >= totalPages"
                :class="['px-3 py-1 border text-sm font-medium rounded-md',
                  currentPage >= totalPages
                    ? 'border-gray-200 text-gray-400 bg-gray-50'
                    : 'border-transparent text-white bg-blue-600 hover:bg-blue-700']"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Sidebar>
</template>
