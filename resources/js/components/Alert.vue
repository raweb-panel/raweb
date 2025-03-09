<template>
  <div v-if="alertMessage" :class="`fixed top-4 right-4 p-4 mb-4 text-sm rounded-lg z-50 ${alertType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
    {{ alertMessage }}
  </div>
</template>

<script lang="ts">
import { ref } from 'vue';

const alertMessage = ref<string | null>(null);
const alertType = ref<'success' | 'error'>('success');

export const useAlert = () => {
  const showAlert = (message: string, type: 'success' | 'error') => {
    alertMessage.value = message;
    alertType.value = type;
    setTimeout(() => {
      alertMessage.value = null;
    }, 5000);
  };

  return { alertMessage, alertType, showAlert };
};

export default {
  setup() {
    return { alertMessage, alertType };
  }
};
</script>

<style scoped>
/* Add any additional styles if needed */
</style>
