<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <h1 class="text-3xl font-bold mb-6">Available Components</h1>
    <div v-if="components.length === 0" class="text-gray-400">No components found.</div>
    <div v-else>
      <table class="w-full border-collapse border border-gray-700">
        <thead>
          <tr class="bg-gray-800">
            <th class="border border-gray-700 px-4 py-2">Name</th>
            <th class="border border-gray-700 px-4 py-2">Version</th>
            <th class="border border-gray-700 px-4 py-2">Installed</th>
            <th class="border border-gray-700 px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="component in components" :key="component.name" class="text-center">
            <td class="border border-gray-700 px-4 py-2">{{ component.name }}</td>
            <td class="border border-gray-700 px-4 py-2">{{ component.version }}</td>
            <td class="border border-gray-700 px-4 py-2">
              <span :class="component.installed ? 'text-green-500' : 'text-red-500'">
                {{ component.installed ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="border border-gray-700 px-4 py-2">
              <button
                v-if="!component.installed"
                @click="startInstallation(component.name)"
                class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg"
              >
                Install
              </button>
              <button
                v-else
                @click="startUninstallation(component.name)"
                class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg"
              >
                Uninstall
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal for Logs -->
    <div v-if="showLogsModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center">
      <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-3/4 max-h-3/4 overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Logs</h2>
        <pre ref="logOutput" class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-y-auto max-h-64">{{ logs }}</pre>
        <div class="mt-4 flex justify-end">
          <button @click="closeLogsModal" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';

const components = ref([]);
const showLogsModal = ref(false);
const logs = ref('');
const logOutput = ref<HTMLElement | null>(null);  // reference to the log <pre> element

// Auto-scroll the log container when logs change
watch(logs, async () => {
  await nextTick();
  if(logOutput.value){
    logOutput.value.scrollTop = logOutput.value.scrollHeight;
  }
});

const fetchComponents = async () => {
  try {
    const response = await axios.get('/api/components');
    components.value = response.data.components;
  } catch (error) {
    console.error('Error fetching components:', error);
  }
};

const startInstallation = async (componentName: string) => {
  logs.value = ''; // Clear previous logs
  showLogsModal.value = true;

  try {
    const encodedName = encodeURIComponent(componentName);
    const eventSource = new EventSource(`/api/components/install/${encodedName}/logs`);

    eventSource.onmessage = (event) => {
      logs.value += event.data + '\n';
    };

    eventSource.onerror = () => {
      eventSource.close();
    };
  } catch (error) {
    console.error(`Error starting installation for ${componentName}:`, error);
  }
};

const startUninstallation = async (componentName: string) => {
  logs.value = ''; // Clear previous logs
  showLogsModal.value = true;

  try {
    const encodedName = encodeURIComponent(componentName);
    const eventSource = new EventSource(`/api/components/uninstall/${encodedName}/logs`);

    eventSource.onmessage = (event) => {
      logs.value += event.data + "\n";
    };

    eventSource.onerror = () => {
      eventSource.close();
    };
  } catch (error) {
    console.error(`Error starting uninstallation for ${componentName}:`, error);
  }
};

const closeLogsModal = () => {
  showLogsModal.value = false;
};

onMounted(() => {
  fetchComponents();
});
</script>

<style scoped>
/* Add any necessary styles here */
</style>
