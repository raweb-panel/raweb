<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">IP Addresses</h2>
      <button 
        class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow-md"
        @click="showCreateModal = true">
        + Add New IP
      </button>
    </div>
    <div class="overflow-x-auto bg-gray-800 shadow-md rounded-lg">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-700 text-gray-200 uppercase text-sm">
            <th class="p-3">ID</th>
            <th class="p-3">IP Address</th>
            <th class="p-3">Type</th>
            <th class="p-3">Description</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ip in ips" :key="ip.id" class="border-b border-gray-600 hover:bg-gray-700">
            <td class="p-3">{{ ip.id }}</td>
            <td class="p-3">{{ ip.ip_address }}</td>
            <td class="p-3">{{ ip.type.toUpperCase() }}</td>
            <td class="p-3">{{ ip.description }}</td>
            <td class="p-3 flex justify-center gap-2">
              <button class="px-3 py-1 bg-blue-500 hover:bg-blue-400 text-white rounded-md" @click="editIp(ip)">Edit</button>
              <button class="px-3 py-1 bg-red-500 hover:bg-red-400 text-white rounded-md" @click="deleteIp(ip.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40">
      <div class="bg-gray-800 p-6 rounded-lg w-96 shadow-lg">
        <h3 class="text-xl font-bold mb-4">{{ editingIp ? 'Edit IP' : 'Add New IP' }}</h3>
        <form @submit.prevent="saveIp">
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">IP Address</label>
            <input v-model="newIp.ip_address" type="text" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Type</label>
            <select v-model="newIp.type" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option value="ipv4">IPv4</option>
              <option value="ipv6">IPv6</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Description</label>
            <input v-model="newIp.description" type="text" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
          </div>
          <div class="flex justify-between">
            <button type="button" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg" @click="resetForm">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
              {{ editingIp ? 'Update' : 'Create' }}
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

const ips = ref([]);
const showCreateModal = ref(false);
const editingIp = ref(false);
const newIp = ref({
  id: null,
  ip_address: '',
  type: 'ipv4',
  description: ''
});

// Fetch IPs
const fetchIps = async () => {
  try {
    const response = await axios.get('/api/list_ips'); // Laravel API route
    ips.value = response.data;
  } catch (error) {
    console.error('Error fetching IPs:', error);
  }
};

// Create or Update IP
const saveIp = async () => {
  try {
    let response;
    if (editingIp.value) {
      // Update existing IP
      response = await axios.put(`/api/ips/${newIp.value.id}`, newIp.value);
      const index = ips.value.findIndex(ip => ip.id === newIp.value.id);
      if (index !== -1) ips.value[index] = { ...newIp.value };
    } else {
      // Create new IP
      response = await axios.post('/api/ips', newIp.value);
      ips.value.push(response.data.ip);
    }
    resetForm();
    showAlert(response.data.message, 'success');
  } catch (error) {
    showAlert('Error saving IP: ' + error.response.data.message, 'error');
  }
};

// Edit IP
const editIp = (ip) => {
  newIp.value = { ...ip };
  editingIp.value = true;
  showCreateModal.value = true;
};

// Delete IP
const deleteIp = async (id) => {
  if (window.confirm('Are you sure?')) {
    try {
      const response = await axios.delete(`/api/ips/${id}`);
      ips.value = ips.value.filter(ip => ip.id !== id);
      showAlert(response.data.message, 'success');
    } catch (error) {
      showAlert('Error deleting IP: ' + error.response.data.message, 'error');
    }
  }
};

// Reset form & modal
const resetForm = () => {
  newIp.value = { id: null, ip_address: '', type: 'ipv4', description: '' };
  editingIp.value = false;
  showCreateModal.value = false;
};

// Fetch IPs on mount
onMounted(fetchIps);
</script>

<style scoped>
/* Add any additional styles if needed */
</style>
