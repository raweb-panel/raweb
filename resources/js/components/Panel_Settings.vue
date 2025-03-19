<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <h1 class="text-3xl font-bold mb-6">Panel Settings</h1>
    <form @submit.prevent="updateSettings">
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">Panel Title</label>
        <input
          v-model="settings.panel_title"
          type="text"
          class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
        />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">Panel Description</label>
        <input
          v-model="settings.panel_description"
          type="text"
          class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg"
        />
      </div>
      <div class="flex justify-between">
        <button
          type="submit"
          class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg"
        >
          Save Settings
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAlert } from './Alert.vue';

const { showAlert } = useAlert();
const settings = ref({
  panel_title: '',
  panel_description: ''
});

const fetchSettings = async () => {
  try {
    const response = await axios.get('/api/panel_settings');
    settings.value = response.data.settings;
  } catch (error) {
    console.error('Error fetching settings:', error);
    showAlert('Error fetching settings', 'error');
  }
};

const updateSettings = async () => {
  try {
    await axios.post('/api/panel_settings', {
      ...settings.value
    });
    showAlert('Settings updated successfully', 'success');
  } catch (error) {
    console.error('Error updating settings:', error);
    showAlert('Error updating settings', 'error');
  }
};

onMounted(() => {
  fetchSettings();
});
</script>

<style scoped>
/* Add any necessary styles here */
</style>