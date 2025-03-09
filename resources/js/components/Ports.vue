<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">Ports Management</h2>
      <button 
        class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow-md"
        @click="showCreateModal = true">
        + Add New Port
      </button>
    </div>
    <!-- Ports Table -->
    <div class="overflow-x-auto bg-gray-800 shadow-md rounded-lg">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-700 text-gray-200 uppercase text-sm">
            <th class="p-3">Port Number</th>
            <th class="p-3">Description</th>
            <th class="p-3">Type</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="port in ports" 
            :key="port.id" 
            class="border-b border-gray-600 hover:bg-gray-700">
            <td class="p-3">{{ port.port_number }}</td>
            <td class="p-3">{{ port.description }}</td>
            <td class="p-3">{{ port.type.toUpperCase() }}</td>
            <td class="p-3 flex justify-center gap-2">
              <button 
                class="px-3 py-1 bg-blue-500 hover:bg-blue-400 text-white rounded-md"
                @click="editPort(port)">
                Edit
              </button>
              <button 
                class="px-3 py-1 bg-red-500 hover:bg-red-400 text-white rounded-md"
                @click="deletePort(port.id)">
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="ports.length === 0">
            <td colspan="4" class="p-3 text-center text-gray-300">
              No ports found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal for Create/Edit Port -->
    <div 
      v-if="showCreateModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40"
      @click.self="resetForm">
      <div class="bg-gray-800 p-6 rounded-lg w-96 shadow-lg">
        <h3 class="text-xl font-bold mb-4">
          {{ editingPort ? 'Edit Port' : 'Add New Port' }}
        </h3>
        <form @submit.prevent="savePort">
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Port Number</label>
            <input 
              v-model="newPort.port_number" 
              type="text" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" 
              placeholder="e.g. 80">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Description</label>
            <input 
              v-model="newPort.description" 
              type="text" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" 
              placeholder="e.g. HTTP Standard Port">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Type</label>
            <select 
              v-model="newPort.type" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option value="http">HTTP</option>
              <option value="ssl">SSL</option>
            </select>
          </div>
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
              {{ editingPort ? 'Update' : 'Create' }}
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
import { useAlert } from './Alert.vue';

const { showAlert } = useAlert();

interface Port {
  id: number | null;
  port_number: string;
  description: string;
  type: string;
}

const ports = ref<Port[]>([]);
const showCreateModal = ref(false);
const editingPort = ref(false);
const newPort = ref<Port>({
  id: null,
  port_number: '',
  description: '',
  type: 'http'
});

// Fetch ports from the API
const fetchPorts = async () => {
  try {
    const response = await axios.get('/api/list_ports');
    ports.value = response.data;
  } catch (error) {
    console.error('Error fetching ports:', error);
  }
};

const savePort = async () => {
  try {
    let response;
    if (editingPort.value) {
      // Update existing port
      response = await axios.put(`/api/ports/${newPort.value.id}`, newPort.value);
      const index = ports.value.findIndex(p => p.id === newPort.value.id);
      if (index !== -1) {
        ports.value[index] = response.data.port;
      }
      showAlert(response.data.message, 'success');
    } else {
      // Create new port
      response = await axios.post('/api/ports', newPort.value);
      ports.value.push(response.data.port);
      showAlert(response.data.message, 'success');
    }
    resetForm();
  } catch (error) {
    showAlert('Error saving port: ' + error.response.data.message, 'error');
    console.error('Error saving port:', error);
  }
};

const editPort = (port: Port) => {
  newPort.value = { ...port };
  editingPort.value = true;
  showCreateModal.value = true;
};

const deletePort = async (id: number) => {
  if (window.confirm('Delete this port?')) {
    try {
      const response = await axios.delete(`/api/ports/${id}`);
      ports.value = ports.value.filter((p: Port) => p.id !== id);
      showAlert(response.data.message, 'success');
    } catch (error) {
      showAlert('Error deleting port: ' + error.response.data.message, 'error');
      console.error('Error deleting port:', error);
    }
  }
};

const resetForm = () => {
  newPort.value = { id: null, port_number: '', description: '', type: 'http' };
  editingPort.value = false;
  showCreateModal.value = false;
};

onMounted(() => {
  fetchPorts();
});
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
