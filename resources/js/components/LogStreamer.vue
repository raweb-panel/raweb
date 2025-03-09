<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <h1 class="text-3xl font-bold mb-6">Log Streamer</h1>
    <div class="mb-4">
      <label class="block text-sm font-semibold mb-1">Select Vhost</label>
      <select v-model="selectedVhost" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        <option v-for="vhost in vhosts" :key="vhost.id" :value="vhost.id">{{ vhost.server_name }}</option>
      </select>
    </div>
    <div class="mb-4">
      <label class="block text-sm font-semibold mb-1">Select Log Filters</label>
      <div class="flex flex-wrap gap-2">
        <div v-for="filter in logFilters" :key="filter" class="flex items-center">
          <input type="checkbox" v-model="selectedFilters" :value="filter" class="mr-2">
          <span>{{ filter }}</span>
        </div>
      </div>
    </div>
    <div class="mb-4">
      <button @click="startStreaming" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">Start Streaming</button>
      <button @click="stopStreaming" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg ml-2">Stop Streaming</button>
    </div>
    <div class="bg-gray-800 p-4 rounded-lg shadow-md overflow-y-auto" style="max-height: 400px;">
      <pre>{{ logStream }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const vhosts = ref([]);
const selectedVhost = ref(null);
const logStream = ref('');
const selectedFilters = ref([]);
const logFilters = ref([]);

let eventSource = null;

const fetchVhosts = async () => {
  try {
    const response = await axios.get('/api/list_hosts');
    vhosts.value = response.data;
  } catch (error) {
    console.error('Error fetching vhosts:', error);
  }
};

const fetchLogFilters = async () => {
  try {
    const response = await axios.get('/api/logs_stream_names');
    logFilters.value = response.data.logs;
  } catch (error) {
    console.error('Error fetching log filters:', error);
  }
};

const startStreaming = () => {
  if (!selectedVhost.value) {
    alert('Please select a vhost.');
    return;
  }

  const filters = selectedFilters.value.join(',');
  const url = `/api/logs/stream?vhost=${selectedVhost.value}&filters=${filters}`;

  eventSource = new EventSource(url);
  eventSource.onmessage = (event) => {
    logStream.value += event.data + '\n';
  };
  eventSource.onerror = (error) => {
    console.error('Error streaming logs:', error);
    stopStreaming();
  };
};

const stopStreaming = () => {
  if (eventSource) {
    eventSource.close();
    eventSource = null;
  }
};

onMounted(() => {
  fetchVhosts();
  fetchLogFilters();
});

onUnmounted(() => {
  stopStreaming();
});
</script>

<style scoped>
/* Add any necessary styles here */
</style>