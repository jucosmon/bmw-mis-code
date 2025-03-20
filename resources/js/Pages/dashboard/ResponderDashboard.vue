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

// First, modify your roleConfig to add debugging
const roleConfig = computed(() => {
  const config = {
    // Determine if user can see specific sections
    canViewTotalUsers: ['bpemo_admin'].includes(user.value.user_role),
    canViewAllData: ['bpemo_admin', 'bpemo_staff'].includes(user.value.user_role),
    // Set filter parameters based on role
    locationFilter: getLocationFilter(),
  };

  // Debug logging
  console.log('User role:', user.value.user_role);
  console.log('Role config:', config);

  return config;
});

// Helper function to determine location filters based on user role
function getLocationFilter() {
  if (['bpemo_admin', 'bpemo_staff'].includes(user.value.user_role)) {
    console.log('Admin/Staff role detected - no location filter');
    return {}; // No location filter for BPEMO roles - they see everything
  } else if (user.value.user_role === 'barangay_official' && user.value.barangay_id) {
    console.log('Barangay official detected - filtering by barangay:', user.value.barangay_id);
    return { barangay_id: user.value.barangay_id }; // Filter by user's barangay
  } else if (user.value.user_role === 'lgu_responder' && user.value.municipality_id) {
    console.log('LGU responder detected - filtering by municipality:', user.value.municipality_id);
    return { municipality_id: user.value.municipality_id }; // Filter by user's municipality
  }
  console.log('No specific role filter applied');
  return {}; // Default case
}


// Role-based titles and descriptions
const dashboardTitle = computed(() => {
  switch(user.value.user_role) {
    case 'bpemo_admin': return 'BPEMO Admin Dashboard';
    case 'bpemo_staff': return 'BPEMO Staff Dashboard';
    case 'barangay_official': return 'Barangay Management Dashboard';
    case 'lgu_responder': return 'LGU Response Dashboard';
    default: return 'Marine Wildlife Monitoring Dashboard';
  }
});

const map = ref(null);
const markers = ref([]);

// Stats data
const stats = ref({
  totalUsers: 0,
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

// Modify your fetchData function to consistently apply location filters
const fetchData = async () => {
  console.log('Fetching data with role:', user.value.user_role);

  // Apply role-based filters
  const locationFilter = roleConfig.value.locationFilter;
  console.log('Using location filter:', locationFilter);

  // Only fetch user count if the role has permission
  if (roleConfig.value.canViewTotalUsers) {
    console.log('Fetching total users count');
    const { count: usersCount } = await supabase
      .from('users')
      .select('*', { count: 'exact', head: true });

    stats.value.totalUsers = usersCount || 0;
  } else {
    console.log('Skipping total users count - no permission');
  }

  // Apply location filters to all queries consistently
  // Fetch stranding stats (count main records, not species)
  let strandingQuery = supabase
    .from('stranded_incidents')
    .select('*')
    .eq('is_active', true);

  // Apply location filters for role-restricted users
  if (locationFilter.barangay_id) {
    console.log('Applying barangay filter to stranding query:', locationFilter.barangay_id);
    strandingQuery = strandingQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    console.log('Applying municipality filter to stranding query:', locationFilter.municipality_id);
    strandingQuery = strandingQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  const { data: strandings } = await strandingQuery;
  console.log('Fetched strandings:', strandings?.length);

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

  // Fetch pending sightings with location filters
  let sightingsQuery = supabase
    .from('sightings')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'pending');

  // Apply location filters to sightings query
  if (locationFilter.barangay_id) {
    console.log('Applying barangay filter to sightings query:', locationFilter.barangay_id);
    sightingsQuery = sightingsQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    console.log('Applying municipality filter to sightings query:', locationFilter.municipality_id);
    sightingsQuery = sightingsQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  const { data: pendingSightings } = await sightingsQuery;
  console.log('Fetched pending sightings:', pendingSightings?.length);

  stats.value.pendingSightings = pendingSightings?.length || 0;

  // Fetch completed reports with location filters
  let completedStrandingsQuery = supabase
    .from('stranded_incidents')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'resolved');

  // Apply location filters to completed strandings
  if (locationFilter.barangay_id) {
    completedStrandingsQuery = completedStrandingsQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    completedStrandingsQuery = completedStrandingsQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  let completedSightingsQuery = supabase
    .from('sightings')
    .select('*')
    .eq('is_active', true)
    .eq('report_status', 'verified');

  // Apply location filters to completed sightings
  if (locationFilter.barangay_id) {
    completedSightingsQuery = completedSightingsQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    completedSightingsQuery = completedSightingsQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  const { data: completedStrandings } = await completedStrandingsQuery;
  const { data: completedSightings } = await completedSightingsQuery;

  console.log('Fetched completed strandings:', completedStrandings?.length);
  console.log('Fetched completed sightings:', completedSightings?.length);

  stats.value.completedReports = {
    total: (completedStrandings?.length || 0) + (completedSightings?.length || 0),
    breakdown: {
      stranding: completedStrandings?.length || 0,
      sighting: completedSightings?.length || 0
    }
  };

  // Fetch active reports for table and map with location details
  let activeStrandingsQuery = supabase
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
    .not('report_status', 'eq', 'resolved');

  // Apply location filters to active strandings
  if (locationFilter.barangay_id) {
    activeStrandingsQuery = activeStrandingsQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    activeStrandingsQuery = activeStrandingsQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  let activeSightingsQuery = supabase
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
    .eq('report_status', 'pending');

  // Apply location filters to active sightings
  if (locationFilter.barangay_id) {
    activeSightingsQuery = activeSightingsQuery.eq('barangay_id', locationFilter.barangay_id);
  } else if (locationFilter.municipality_id) {
    activeSightingsQuery = activeSightingsQuery.eq('municipality_id', locationFilter.municipality_id);
  }

  const { data: activeStrandings } = await activeStrandingsQuery;
  const { data: activeSightings } = await activeSightingsQuery;

  console.log('Fetched active strandings:', activeStrandings?.length);
  console.log('Fetched active sightings:', activeSightings?.length);

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

// Add these functions after your ref declarations
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
      zoomAnimation: false, // Disable zoom animation to prevent race conditions
      markerZoomAnimation: false // Disable marker zoom animation
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
    }).addTo(map.value);
  } catch (error) {
    console.error('Error initializing map:', error);
  }
};

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

  // Create a new marker cluster group with disabled animations
  const markerCluster = L.markerClusterGroup({
    maxClusterRadius: 30,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    animate: false,
    animateAddingMarkers: false
  });

  map.value.markerClusterGroup = markerCluster;

  // Filter and group reports with better logging
  const strandedReports = activeReports.value.filter(report => report.type === 'stranded');
  console.log('Total stranded reports:', strandedReports.length);

  // Instead of grouping by location, we'll create a marker for each report
  strandedReports.forEach((report, index) => {
    try {
      if (!report.latitude || !report.longitude) {
        console.warn('Missing coordinates for report:', report.id);
        return;
      }

      // Round coordinates to 6 decimal places
      const lat = Number(parseFloat(report.latitude).toFixed(6));
      const lng = Number(parseFloat(report.longitude).toFixed(6));

      if (isNaN(lat) || isNaN(lng) ||
          Math.abs(lat) > 90 || Math.abs(lng) > 180) {
        console.warn('Invalid coordinates for report:', report.id, lat, lng);
        return;
      }

      // Add a tiny offset for reports with the same coordinates
      // This will create a small spiral pattern when multiple reports share coordinates
      const angle = index * (Math.PI * 2) / 8; // 8 positions in the spiral
      const radius = 0.0001 * Math.floor(index / 8); // Increase radius every 8 reports
      const adjustedLat = lat + Math.cos(angle) * radius;
      const adjustedLng = lng + Math.sin(angle) * radius;

      console.log('Creating marker for:', {
        id: report.id,
        originalLat: lat,
        originalLng: lng,
        adjustedLat,
        adjustedLng
      });

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

  // Add cluster group to map and fit bounds without animation
  map.value.addLayer(markerCluster);

  if (markerCluster.getBounds().isValid()) {
    try {
      map.value.fitBounds(markerCluster.getBounds(), {
        padding: [50, 50],
        maxZoom: 15,
        animate: false // Disable bounds animation
      });
    } catch (error) {
      console.error('Error fitting bounds:', error);
      map.value.setView([9.8500, 124.1833], 10, { animate: false });
    }
  }
};

// Add cleanup function ref
const cleanup = ref(null);

// Update onMounted and move cleanup logic
onMounted(() => {
  try {
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

      // Store cleanup function
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

// Register cleanup outside of async context
onBeforeUnmount(() => {
  if (cleanup.value) {
    cleanup.value();
  }
});

// Update your watch statement
watch(activeReports, () => {
  nextTick(() => updateMapMarkers());
}, { deep: true });

// Helper functions
const getStatusColor = (status) => {
  const colors = {
    'pending': 'text-red-600 bg-red-100',
    'verified': 'text-yellow-600 bg-yellow-100',
    'completed': 'text-green-600 bg-green-100'
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

const displayStats = computed(() => {
  // Base stats that all roles can see
  const baseStats = [
    {
      label: 'Unresolved Strandings',
      value: stats.value.totalStrandings.total,
      icon: 'fas fa-life-ring',
      iconClass: 'bg-red-500/20 text-red-400 p-3 rounded-lg'
    },
    {
      label: 'Pending Sightings',
      value: stats.value.pendingSightings,
      icon: 'fas fa-binoculars',
      iconClass: 'bg-yellow-500/20 text-yellow-400 p-3 rounded-lg'
    },
    {
      label: 'Completed Reports',
      value: stats.value.completedReports.total,
      icon: 'fas fa-check-circle',
      iconClass: 'bg-green-500/20 text-green-400 p-3 rounded-lg'
    }
  ];

  // Add total users card only for bpemo_admin
  if (user.value.user_role === 'bpemo_admin') {
    baseStats.unshift({
      label: 'Total Users',
      value: stats.value.totalUsers,
      icon: 'fas fa-users',
      iconClass: 'bg-blue-500/20 text-blue-400 p-3 rounded-lg'
    });
  }

  return baseStats;
});

const getStatusBadgeClass = (status) => {
  const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium';
  const statusColors = {
    pending: 'bg-red-500/20 text-red-400',
    verified: 'bg-yellow-500/20 text-yellow-400',
    completed: 'bg-green-500/20 text-green-400'
  };
  return `${baseClasses} ${statusColors[status] || 'bg-gray-500/20 text-gray-400'}`;
};
</script>

<template>
  <Head :title="dashboardTitle" />

  <Sidebar>

    <div class="relative min-h-screen">
      <!-- Background -->
      <div class="absolute inset-0">
        <img src="/images/landing.jpg" alt="Ocean Background" class="object-cover w-full h-full">
        <div class="absolute inset-0 bg-gradient-overlay"></div>
      </div>

      <!-- Content -->
      <div class="relative py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <!-- Welcome message -->
          <div class="mb-10 text-center">
            <h3 class="profile-title-gradient">
              Welcome {{ user.first_name }} {{ user.last_name }}
            </h3>
            <p class="text-white text-opacity-80">
              Marine Wildlife Monitoring Dashboard
            </p>
          </div>

          <!-- Stats Cards with dynamic grid -->
          <div :class="[
            'grid gap-4 md:gap-6 mb-8',
            user.user_role === 'bpemo_admin'
              ? 'grid-cols-2 xl:grid-cols-4'
              : 'grid-cols-1 md:grid-cols-3'
          ]">
            <div v-for="(stat, index) in displayStats" :key="index"
                 class="stat-card transform transition-all duration-300 hover:scale-105">
              <div class="p-6 h-full flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div :class="stat.iconClass">
                    <i :class="stat.icon"></i>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-white/70 mb-1">{{ stat.label }}</p>
                  <p class="text-2xl font-bold text-white">{{ stat.value }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Map and Alerts with better spacing -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="glass-panel lg:col-span-2">
              <div class="p-6">
                <h3 class="text-xl font-semibold text-white mb-4">Location Overview</h3>
                <div class="map-container">
                  <div id="map" class="h-[400px] md:h-[500px] rounded-lg"></div>
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                  <div v-for="status in ['Pending', 'Verified', 'Completed']"
                       :key="status"
                       class="status-badge">
                    <span :class="`status-dot ${status.toLowerCase()}`"></span>
                    <span>{{ status }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="glass-panel">
              <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-xl font-semibold text-white">Recent Alerts</h3>
                  <Link :href="route('stranded.incident.index')"
                        class="text-blue-400 hover:text-blue-300 text-sm">
                    View All
                  </Link>
                </div>
                <div class="space-y-4">
                  <div v-for="alert in recentAlerts" :key="alert.id"
                       class="alert-card">
                    <div class="flex items-start space-x-4">
                      <div :class="getStatusColor(alert.status)"
                           class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                      </div>
                      <div class="flex-1">
                        <div class="flex justify-between items-start">
                          <h4 class="text-white font-medium">{{ alert.species }}</h4>
                          <span :class="getStatusBadgeClass(alert.status)">
                            {{ alert.status }}
                          </span>
                        </div>
                        <p class="text-white/70 text-sm mt-1">{{ alert.location }}</p>
                        <p class="text-white/60 text-xs mt-1">{{ formatDate(alert.date) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Reports Table with responsive design -->
          <div class="glass-panel mb-8">
            <div class="p-6">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-semibold text-white">Active Reports</h3>
                <div class="flex flex-wrap gap-2">
                  <button v-for="view in ['all', 'stranding', 'sighting']"
                          :key="view"
                          @click="setView(view)"
                          :class="['view-button', currentView === view ? 'active' : '']">
                    {{ view.charAt(0).toUpperCase() + view.slice(1) }}
                  </button>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                  <thead class="bg-white/5">
                    <tr>
                      <th v-for="header in ['ID', 'Type', 'Species', 'Location', 'Reported', 'Status', 'Actions']"
                          :key="header"
                          class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">
                        {{ header }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-white/10">
                    <tr v-for="report in displayedReports"
                        :key="report.id"
                        class="hover:bg-white/5 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-300">
                        #{{ report.id }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 capitalize">
                        {{ report.type }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ report.species }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ report.location }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
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
      </div>
    </div>
  </Sidebar>
</template>

<style scoped>
/* Ocean theme styling */
.bg-gradient-overlay {
  background: linear-gradient(
    135deg,
    rgba(0, 51, 102, 0.9) 0%,
    rgba(0, 64, 128, 0.8) 50%,
    rgba(0, 31, 63, 0.9) 100%
  );
}

.glass-container {
  background: rgba(0, 51, 102, 0.25);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  border-radius: 0.75rem;
  overflow: hidden;
  transition: all 0.3s ease;
}

.glass-container:hover {
  border-color: rgba(255, 255, 255, 0.12);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.profile-title-gradient {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: 1px;
  background: linear-gradient(to right, #ffffff, #00ccff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

/* Stats cards styling */
.glass-container .flex-shrink-0 {
  background: rgba(255, 255, 255, 0.1) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.glass-container:hover .flex-shrink-0 {
  background: rgba(255, 255, 255, 0.15) !important;
  border-color: rgba(255, 255, 255, 0.25);
  transform: scale(1.05);
}

.glass-container .text-gray-500 {
  color: rgba(255, 255, 255, 0.7) !important;
}

.glass-container .text-gray-900 {
  color: rgba(255, 255, 255, 0.9) !important;
}

/* Table styling */
.glass-container thead {
  background: rgba(255, 255, 255, 0.05);
}

.glass-container tbody tr {
  transition: all 0.2s ease;
}

.glass-container tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(8px);
}

.glass-container th {
  color: rgba(255, 255, 255, 0.7);
}

.glass-container td {
  color: rgba(255, 255, 255, 0.9);
}

/* Button styling */
button {
  background: linear-gradient(
    135deg,
    rgba(0, 51, 102, 0.9) 0%,
    rgba(0, 64, 128, 0.8) 100%
  ) !important;
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  transition: all 0.3s ease;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

button:not(:disabled):hover {
  background: linear-gradient(
    135deg,
    rgba(0, 64, 128, 0.95) 0%,
    rgba(0, 51, 102, 0.85) 100%
  ) !important;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Map container specific styling */
#map {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  filter: saturate(0.8) brightness(0.95);
}

/* Enhance table styling */
.glass-container table {
  background: transparent;
}

.glass-container td {
  color: rgba(255, 255, 255, 0.8) !important;
  transition: color 0.2s ease;
}

.glass-container tr:hover td {
  color: rgba(255, 255, 255, 1) !important;
}

/* Status badges enhancement */
.rounded-full {
  padding: 0.5rem 1rem;
  font-weight: 500;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  font-size: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(4px);
}

/* Status colors with glass effect */
.text-red-600.bg-red-100 {
  background: rgba(255, 107, 107, 0.15) !important;
  color: #ff8f8f !important;
  border-color: rgba(255, 107, 107, 0.3);
}

.text-yellow-600.bg-yellow-100 {
  background: rgba(255, 217, 61, 0.15) !important;
  color: #ffe074 !important;
  border-color: rgba(255, 217, 61, 0.3);
}

.text-green-600.bg-green-100 {
  background: rgba(109, 213, 167, 0.15) !important;
  color: #84e4b8 !important;
  border-color: rgba(109, 213, 167, 0.3);
}

/* Table header and content alignment */
th, td {
  padding: 1rem 1.5rem;
}

/* Pagination enhancement */
.pagination-text {
  color: rgba(255, 255, 255, 0.7);
}

/* Button states */
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Links in table */
.glass-container a {
  color: #4dabf7;
  transition: all 0.2s ease;
  position: relative;
}

.glass-container a:hover {
  color: #00ccff;
  text-shadow: 0 0 8px rgba(0, 204, 255, 0.5);
}

.glass-container a::after {
  content: '';
  position: absolute;
  width: 100%;
  height: 1px;
  bottom: -2px;
  left: 0;
  background: linear-gradient(to right, #4dabf7, #00ccff);
  transform: scaleX(0);
  transition: transform 0.2s ease;
}

.glass-container a:hover::after {
  transform: scaleX(1);
}

/* Map markers enhancement */
.leaflet-marker-icon {
  filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.3));
}

.leaflet-popup-content-wrapper {
  background: rgba(0, 51, 102, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
}

.leaflet-popup-tip {
  background: rgba(0, 51, 102, 0.95);
}

.leaflet-popup-content {
  color: rgba(255, 255, 255, 0.9);
}

.leaflet-popup-content a {
  color: #00ccff;
}

/* Custom scrollbar for table container */
.overflow-x-auto {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

/* Legend items enhancement */
.legend-item {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(4px);
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.2s ease;
}

.legend-item:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.2);
}

.status-indicator {
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

.pending-status {
  background: rgb(255, 93, 18);
  border: 1px solid rgba(255, 107, 107, 1);
}

.verified-status {
  background: rgb(218, 226, 2);
  border: 1px solid rgba(255, 217, 61, 1);
}

.completed-status {
  background: rgb(10, 144, 37);
  border: 1px solid rgba(109, 213, 167, 1);
}

/* Table loading state */
.loading-row {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.1) 50%,
    rgba(255, 255, 255, 0.05) 100%
  );
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Status colors adjustment for better visibility */
.text-red-600 { color: #ff6b6b !important; }
.text-yellow-600 { color: #ffd93d !important; }
.text-green-600 { color: #6dd5a7 !important; }
.text-blue-600 { color: #4dabf7 !important; }

.bg-red-100 { background: rgba(255, 107, 107, 0.2) !important; }
.bg-yellow-100 { background: rgba(255, 217, 61, 0.2) !important; }
.bg-green-100 { background: rgba(109, 213, 167, 0.2) !important; }
.bg-blue-100 { background: rgba(77, 171, 247, 0.2) !important; }

/* Pagination section */
.bg-gray-50 {
  background: rgba(0, 51, 102, 0.2) !important;
  backdrop-filter: blur(8px);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Links */
a {
  color: #00ccff;
  transition: color 0.2s ease;
}

a:hover {
  color: #4dabf7;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .glass-container {
    margin: 0.5rem;
    padding: 1rem;
  }

  .profile-title-gradient {
    font-size: 1.5rem;
  }
}

/* Base Styles */
.glass-panel {
  @apply rounded-xl bg-white/5 backdrop-blur-md border border-white/10;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.stat-card {
  @apply glass-panel overflow-hidden;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
}

.alert-card {
  @apply p-4 rounded-lg bg-white/5 hover:bg-white/10 transition-colors;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Status Indicators */
.status-badge {
  @apply flex items-center space-x-2 px-3 py-1.5 rounded-full bg-white/10 text-sm text-white;
}

.status-dot {
  @apply w-2.5 h-2.5 rounded-full;
}

.status-dot.pending { @apply bg-red-500; }
.status-dot.verified { @apply bg-yellow-500; }
.status-dot.completed { @apply bg-green-500; }

/* Action Buttons */
.view-button {
  @apply px-4 py-2 rounded-lg text-sm font-medium transition-all;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.view-button:hover {
  @apply transform -translate-y-0.5;
  background: rgba(255, 255, 255, 0.15);
}

.view-button.active {
  @apply bg-blue-500 text-white border-blue-400;
}

/* Table Enhancements */
.table-container {
  @apply overflow-x-auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.table-container::-webkit-scrollbar {
  @apply h-1.5;
}

.table-container::-webkit-scrollbar-track {
  @apply bg-transparent;
}

.table-container::-webkit-scrollbar-thumb {
  @apply bg-white/30 rounded-full;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
  .stat-card {
    @apply p-4;
  }

  .table-container {
    @apply -mx-4;
  }

  .view-button {
    @apply px-3 py-1.5 text-xs;
  }
}

@media (max-width: 768px) {
  .map-container {
    @apply h-[300px];
  }
}

/* Dark mode optimizations */
@media (prefers-color-scheme: dark) {
  .glass-panel {
    background: rgba(0, 0, 0, 0.3);
  }
}

/* Animation utilities */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
