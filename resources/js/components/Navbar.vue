<template>
  <nav class="bg-gray-800 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
      <div class="relative flex items-center justify-between h-16">
        <!-- Mobile menu button -->
        <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
          <button
            @click="toggleMenu"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
            <span class="sr-only">Open main menu</span>
            <svg
              v-if="!isMenuOpen"
              class="block h-6 w-6"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
            <svg
              v-else
              class="block h-6 w-6"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Logo and desktop menu -->
        <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
          <div class="flex-shrink-0">
            <router-link to="/" class="text-white text-xl font-bold">{{ panelTitle }}</router-link>
          </div>
          <div class="hidden sm:block sm:ml-6">
            <div class="flex space-x-4">
              <router-link to="/" class="nav-link">
                <HomeIcon class="h-5 w-5 inline-block mr-1" /> Home
              </router-link>
              <router-link to="/vhosts" class="nav-link">
                <CpuChipIcon class="h-5 w-5 inline-block mr-1" /> Vhosts
              </router-link>

              <!-- Network Menu Wrapper with ref -->
              <div ref="networkRef" class="relative">
                <button @click="toggleNetworkMenu" class="nav-link">
                  <LinkIcon class="h-5 w-5 inline-block mr-1" /> Network
                </button>
                <div
                  v-if="isNetworkMenuOpen"
                  class="absolute left-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg z-10 divide-y divide-gray-700">
                  <router-link to="/ips" class="nav-link block px-3 py-2">IPs</router-link>
                  <router-link to="/ports" class="nav-link block px-3 py-2">Ports</router-link>
                </div>
              </div>

              <!-- Monitoring Menu Wrapper with ref -->
              <div ref="monitoringRef" class="relative">
                <button @click="toggleMonitoringMenu" class="nav-link">
                  <ChartBarIcon class="h-5 w-5 inline-block mr-1" /> Monitoring
                </button>
                <div
                  v-if="isMonitoringMenuOpen"
                  class="absolute left-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg z-10 divide-y divide-gray-700">
                  <router-link to="/logs" class="nav-link block px-3 py-2">Logs</router-link>
                  <router-link to="/log-streamer" class="nav-link block px-3 py-2">Log Streamer</router-link>
                </div>
              </div>

              <router-link to="/php" class="nav-link">
                <CodeBracketIcon class="h-5 w-5 inline-block mr-1" /> Php Pools
              </router-link>

              <router-link to="/settings" class="nav-link">
                <Cog8ToothIcon class="h-5 w-5 inline-block mr-1" /> Settings
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="isMenuOpen" class="sm:hidden bg-gray-900">
      <div class="px-2 pt-2 pb-3 space-y-1">
        <router-link to="/" class="mobile-nav-link" @click="closeMenu">
          <HomeIcon class="h-5 w-5 inline-block mr-1" /> Home
        </router-link>
        <router-link to="/vhosts" class="mobile-nav-link" @click="closeMenu">
          <CpuChipIcon class="h-5 w-5 inline-block mr-1" /> Vhosts
        </router-link>
        <button @click="toggleNetworkMenu" class="mobile-nav-link">
          <LinkIcon class="h-5 w-5 inline-block mr-1" /> Network
        </button>
        <div v-if="isNetworkMenuOpen" class="pl-4">
          <router-link to="/ips" class="mobile-nav-link" @click="closeMenu">IPs</router-link>
          <router-link to="/ports" class="mobile-nav-link" @click="closeMenu">Ports</router-link>
        </div>
        <button @click="toggleMonitoringMenu" class="mobile-nav-link">
          <ChartBarIcon class="h-5 w-5 inline-block mr-1" /> Monitoring
        </button>
        <div v-if="isMonitoringMenuOpen" class="pl-4">
          <router-link to="/logs" class="mobile-nav-link" @click="closeMenu">Logs</router-link>
          <router-link to="/log-streamer" class="mobile-nav-link" @click="closeMenu">Log Streamer</router-link>
        </div>
        <router-link to="/php" class="mobile-nav-link" @click="closeMenu">
          <CodeBracketIcon class="h-5 w-5 inline-block mr-1" /> Php Pools
        </router-link>
        <router-link to="/settings" class="mobile-nav-link" @click="closeMenu">
          <Cog8ToothIcon class="h-5 w-5 inline-block mr-1" /> Settings
        </router-link>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Import Heroicons from the solid set.
import {
  HomeIcon,
  CpuChipIcon,
  LinkIcon,
  ChartBarIcon,
  Cog8ToothIcon,
  CodeBracketIcon
} from '@heroicons/vue/24/solid';

const isMenuOpen = ref(false);
const isNetworkMenuOpen = ref(false);
const isMonitoringMenuOpen = ref(false);
const panelTitle = ref('Nginx Manager');

// Refs for submenu wrappers
const networkRef = ref<HTMLElement | null>(null);
const monitoringRef = ref<HTMLElement | null>(null);

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value;
}

function closeMenu() {
  isMenuOpen.value = false;
  isNetworkMenuOpen.value = false;
  isMonitoringMenuOpen.value = false;
}

function toggleNetworkMenu() {
  isNetworkMenuOpen.value = !isNetworkMenuOpen.value;
}

function toggleMonitoringMenu() {
  isMonitoringMenuOpen.value = !isMonitoringMenuOpen.value;
}

// Global click handler to close submenus when clicking outside
function handleOutsideClick(event: MouseEvent) {
  if (networkRef.value && !networkRef.value.contains(event.target as Node)) {
    isNetworkMenuOpen.value = false;
  }
  if (monitoringRef.value && !monitoringRef.value.contains(event.target as Node)) {
    isMonitoringMenuOpen.value = false;
  }
}

const fetchPanelTitle = async () => {
  try {
    const response = await axios.get('/api/panel-title');
    panelTitle.value = response.data.panel_title || 'Nginx Manager';
  } catch (error) {
    console.error('Error fetching panel title:', error);
  }
};

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  fetchPanelTitle();
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
});
</script>

<style scoped>
.nav-link {
  @apply text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium;
}

.mobile-nav-link {
  @apply block text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-base font-medium;
}
</style>
