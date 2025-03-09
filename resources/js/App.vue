<template>
  <el-config-provider :theme="theme">
    <div id="app" class="dark">
      <Navbar />
      <Alert />
      <router-view></router-view>
    </div>
  </el-config-provider>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import Navbar from './components/Navbar.vue';
import Alert from './components/Alert.vue';

const theme = reactive({
  dark: true,
});
</script>

<style>
/* Add any global styles if needed */
body {
  @apply bg-gray-900 text-white;
}
</style>