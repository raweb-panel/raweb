<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">PHP-FPM Pools</h2>
      <button 
        class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow-md"
        @click="showCreateModal = true; generateListenValue();">
        + Add New Pool
      </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-gray-800 shadow-md rounded-lg">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-700 text-gray-200 uppercase text-sm">
            <th class="p-3">Name</th>
            <th class="p-3">Version</th>
            <th class="p-3">Listen</th>
            <th class="p-3">User</th>
            <th class="p-3">Workers</th>
            <th class="p-3">Requests</th>
            <th class="p-3">Memory</th>
            <th class="p-3">Input Vars</th>
            <th class="p-3">Exec Time</th>
            <th class="p-3">Upload</th>
            <th class="p-3">Errors</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="pool in pools" 
            :key="pool.id" 
            class="border-b border-gray-600 hover:bg-gray-700">
            <td class="p-3">{{ pool.name }}</td>
            <td class="p-3">{{ pool.version }}</td>
            <td class="p-3">{{ pool.listen }}</td>
            <td class="p-3">{{ pool.user }}</td>
            <td class="p-3">{{ pool.pm_max_children }}</td>
            <td class="p-3">{{ pool.pm_max_requests }}</td>
            <td class="p-3">{{ pool.ram_limit }}</td>
            <td class="p-3">{{ pool.max_vars }}</td>
            <td class="p-3">{{ pool.max_execution_time }}</td>
            <td class="p-3">{{ pool.max_upload }}</td>
            <td class="p-3">{{ pool.display_errors ? 'on' : 'off' }}</td>
            <td class="p-3 flex justify-center gap-2">
              <button 
                class="px-3 py-1 bg-blue-500 hover:bg-blue-400 text-white rounded-md"
                @click="editPool(pool)">
                Edit
              </button>
              <button 
                class="px-3 py-1 bg-red-500 hover:bg-red-400 text-white rounded-md"
                @click="deletePool(pool.id)">
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="pools.length === 0">
            <td colspan="12" class="p-3 text-center text-gray-300">
              No pools found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal for Create / Edit -->
    <div 
      v-if="showCreateModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
      @click.self="resetForm">
      <div class="bg-gray-800 p-6 rounded-lg w-full max-w-lg shadow-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">
          {{ editingPool ? 'Edit Pool' : 'Add New Pool' }}
        </h3>
        <form @submit.prevent="savePool">
          <!-- Name and Version -->
          <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">Name</label>
              <input 
                v-model="newPool.name" 
                type="text" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="Pool Name">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Version</label>
              <input 
                v-model="newPool.version" 
                type="text" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 8.1">
            </div>
          </div>
          <!-- Type and Listen -->
          <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">Type</label>
              <select 
                v-model="newPool.type" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
                <option value="ip">IP</option>
                <option value="sock">Socket</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Listen (IP:PORT or Sock)</label>
              <input 
                v-model="newPool.listen" 
                type="text" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 127.0.0.1:9000">
            </div>
          </div>
          <!-- Pool System User -->
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Pool System User</label>
            <input 
              v-model="newPool.user" 
              type="text" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
              placeholder="e.g. nginx">
          </div>
          <!-- pm.max_children and pm.max_requests -->
          <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">pm.max_children</label>
              <input 
                v-model="newPool.pm_max_children" 
                type="number" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 50">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">pm.max_requests</label>
              <input 
                v-model="newPool.pm_max_requests" 
                type="number" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 1000">
            </div>
          </div>
          <!-- Memory Limit and Max Input Vars -->
          <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">Memory Limit</label>
              <input 
                v-model="newPool.ram_limit" 
                type="text" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 512M">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Max Input Vars</label>
              <input 
                v-model="newPool.max_vars" 
                type="number" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 10000">
            </div>
          </div>
          <!-- Max Execution Time and Max Upload Size -->
          <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">Max Execution Time (seconds)</label>
              <input 
                v-model="newPool.max_execution_time" 
                type="number" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 90">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Max Upload Size</label>
              <input 
                v-model="newPool.max_upload" 
                type="text" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
                placeholder="e.g. 500M">
            </div>
          </div>
          <!-- Display Errors Checkbox -->
          <div class="mb-4">
            <label class="flex items-center">
              <input type="checkbox" v-model="newPool.display_errors" class="mr-2">
              <span class="text-sm font-semibold">Display Errors</span>
            </label>
          </div>
          <!-- Buttons -->
          <div class="flex justify-between">
            <button 
              type="button" 
              class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg"
              @click="resetForm">
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
              {{ editingPool ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Pool {
  id: number | null;
  name: string;
  version: string;
  type: string;
  listen: string;
  user: string;
  pm_max_children: number;
  pm_max_requests: number;
  ram_limit: string;
  max_vars: number;
  max_execution_time: number;
  max_upload: string;
  display_errors: boolean;
}

const pools = ref<Pool[]>([]);
const showCreateModal = ref(false);
const editingPool = ref(false);
const newPool = ref<Pool>({
  id: null,
  name: '',
  version: '',
  type: 'ip',
  listen: '',
  user: 'nginx',
  pm_max_children: 50,
  pm_max_requests: 1000,
  ram_limit: '512M',
  max_vars: 10000,
  max_execution_time: 90,
  max_upload: '500M',
  display_errors: false
});

const fetchPools = async () => {
  try {
    const response = await axios.get('/api/list_fpm_pools');
    pools.value = response.data;
  } catch (error) {
    console.error('Error fetching pools:', error);
  }
};

const savePool = async () => {
  try {
    let response;
    if (editingPool.value) {
      response = await axios.put(`/api/php/${newPool.value.id}`, newPool.value);
      const index = pools.value.findIndex(pool => pool.id === newPool.value.id);
      if (index !== -1) {
        pools.value[index] = { ...newPool.value };
      }
    } else {
      response = await axios.post('/api/php', newPool.value);
      pools.value.push(response.data.pool);
    }
    resetForm();
    showAlert(response.data.message, 'success');
  } catch (error) {
    showAlert('Error saving pool: ' + error.response.data.message, 'error');
  }
};

const editPool = (pool: Pool) => {
  newPool.value = { ...pool };
  editingPool.value = true;
  showCreateModal.value = true;
};

const deletePool = async (id: number) => {
  if (window.confirm('Are you sure?')) {
    try {
      const response = await axios.delete(`/api/php/${id}`);
      pools.value = pools.value.filter(pool => pool.id !== id);
      showAlert(response.data.message, 'success');
    } catch (error) {
      showAlert('Error deleting pool: ' + error.response.data.message, 'error');
    }
  }
};

const resetForm = () => {
  newPool.value = { 
    id: null,
    name: '',
    version: '',
    type: 'ip',
    listen: '',
    user: 'nginx',
    pm_max_children: 50,
    pm_max_requests: 1000,
    ram_limit: '512M',
    max_vars: 10000,
    max_execution_time: 90,
    max_upload: '500M',
    display_errors: false
  };
  editingPool.value = false;
  showCreateModal.value = false;
};

const generateListenValue = async () => {
  let port;
  let listen;
  let exists = true;
  do {
    port = Math.floor(Math.random() * (9100 - 9000 + 1)) + 9000;
    listen = `127.0.0.1:${port}`;
    const response = await axios.get('/api/fpm_check_aviability', { params: { listen } });
    exists = response.data.exists;
  } while (exists);
  newPool.value.listen = listen;
};

onMounted(() => {
  fetchPools();
});
</script>

<style scoped>
/* Add any additional styles if needed */
</style>
