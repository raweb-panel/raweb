<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <h1 class="text-3xl font-bold mb-6">Nginx Log Formats</h1>
    <div class="mb-4">
      <button @click="openCreateModal" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
        + Add New Log Format
      </button>
    </div>
    <div class="overflow-x-auto bg-gray-800 shadow-md rounded-lg">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-700 text-gray-200 uppercase text-sm">
            <th class="p-3">Log Format</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id" class="border-b border-gray-600 hover:bg-gray-700">
            <td class="p-3">{{ log.format }}</td>
            <td class="p-3 flex justify-center gap-2">
              <button @click="editLog(log)" class="px-3 py-1 bg-blue-500 hover:bg-blue-400 text-white rounded-md">Edit</button>
              <button @click="deleteLog(log.id)" class="px-3 py-1 bg-red-500 hover:bg-red-400 text-white rounded-md">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Overlay -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" @click.self="closeModal">
      <div class="bg-gray-800 p-6 rounded-lg w-full max-w-md shadow-lg">
        <h3 class="text-xl font-bold mb-4">{{ isEditing ? 'Edit Log Format' : 'Create New Log Format' }}</h3>
        <form @submit.prevent="isEditing ? updateLog() : createLog()">
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Log Format</label>
            <textarea v-model="logFormat" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" rows="5"></textarea>
          </div>
          <div class="flex justify-between">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">{{ isEditing ? 'Update' : 'Create' }}</button>
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
const logs = ref([]);
const showModal = ref(false);
const isEditing = ref(false);
const logFormat = ref('');
const currentLogId = ref(null);

const fetchLogs = async () => {
  try {
    const response = await axios.get('/api/logs');
    logs.value = response.data.logs;
  } catch (error) {
    console.error('Error fetching logs:', error);
    showAlert('Error fetching logs', 'error');
  }
};

const openCreateModal = () => {
  logFormat.value = '';
  isEditing.value = false;
  showModal.value = true;
};

const editLog = (log) => {
  logFormat.value = log.format;
  currentLogId.value = log.id;
  isEditing.value = true;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const createLog = async () => {
  try {
    await axios.post('/api/logs', { format: logFormat.value });
    fetchLogs();
    closeModal();
    showAlert('Log format created successfully', 'success');
  } catch (error) {
    console.error('Error creating log format:', error);
    showAlert('Error creating log format', 'error');
  }
};

const updateLog = async () => {
  try {
    await axios.put(`/api/logs/${currentLogId.value}`, { format: logFormat.value });
    fetchLogs();
    closeModal();
    showAlert('Log format updated successfully', 'success');
  } catch (error) {
    console.error('Error updating log format:', error);
    showAlert('Error updating log format', 'error');
  }
};

const deleteLog = async (id) => {
  try {
    await axios.delete(`/api/logs/${id}`);
    fetchLogs();
    showAlert('Log format deleted successfully', 'success');
  } catch (error) {
    console.error('Error deleting log format:', error);
    showAlert('Error deleting log format', 'error');
  }
};

onMounted(() => {
  fetchLogs();
});
</script>

<style scoped>
/* Add any necessary styles here */
</style>