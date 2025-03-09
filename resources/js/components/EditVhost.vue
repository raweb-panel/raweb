<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <h2 class="text-3xl font-bold mb-6">Edit Virtual Host</h2>
    <form @submit.prevent="updateVhost">
      <div class="mb-4 flex gap-4">
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">File Name</label>
          <input v-model="vhost.file" type="text" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" disabled>
        </div>
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">Domain Name</label>
          <input v-model="vhost.server_name" type="text" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        </div>
      </div>
      <div class="mb-4 flex gap-4">
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">IPv4</label>
          <select v-model="vhost.ipv4" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        <option value="none">None</option>
        <option v-for="ip in ips.ipv4" :key="ip.ip_address" :value="ip.ip_address">
          {{ ip.ip_address }} - {{ ip.description }}
        </option>
          </select>
        </div>
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">IPv6</label>
          <select v-model="vhost.ipv6" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        <option value="none">None</option>
        <option v-for="ip in ips.ipv6" :key="ip.ip_address" :value="ip.ip_address">
          {{ ip.ip_address }} - {{ ip.description }}
        </option>
          </select>
        </div>
      </div>
      <div class="mb-4 flex gap-4">
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">HTTP Port</label>
          <select v-model="vhost.http_port" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        <option v-for="port in ports.http" :key="port.port_number" :value="port.port_number">
          {{ port.port_number }} - {{ port.description }}
        </option>
          </select>
        </div>
        <div class="w-1/2">
          <label class="block text-sm font-semibold mb-1">SSL Port</label>
          <select v-model="vhost.ssl_port" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
        <option v-for="port in ports.ssl" :key="port.port_number" :value="port.port_number">
          {{ port.port_number }} - {{ port.description }}
        </option>
          </select>
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">PHP Version</label>
        <select v-model="vhost.php_version" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
          <option value="none">None</option>
          <option v-for="php in phpVersions" :key="php.version" :value="php.version">
            {{ php.version }} - {{ php.listen }}
          </option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">Log Type</label>
        <select v-model="vhost.log_type" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
          <option v-for="log in logFormats" :key="log" :value="log">{{ log }}</option>
          <option value="off">Off</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">Nginx Config</label>
        <small class="text-gray-400">Note: Please don't edit <b>this</b> config directly, could be buggy, report issues on github.</small>
        <textarea v-model="nginxConfig" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" rows="10"></textarea>
      </div>
      <div class="flex justify-between">
        <button type="button" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg" @click="cancelEdit">
          Cancel
        </button>
        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
          Update
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { useAlert } from './Alert.vue';

const { showAlert } = useAlert();
const route = useRoute();
const router = useRouter();
const vhost = ref({});
const nginxConfig = ref('');
const ports = ref({ http: [], ssl: [] });
const ips = ref({ ipv4: [], ipv6: [] });
const phpVersions = ref([]);
const logFormats = ref([]);

const fetchVhost = async () => {
  try {
    const response = await axios.get(`/api/vhosts/${route.params.id}`);
    vhost.value = response.data.vhost;
    nginxConfig.value = response.data.nginxConfig;
  } catch (error) {
    console.error('Error fetching vhost:', error);
  }
};

const fetchPorts = async () => {
  try {
    const response = await axios.get('/api/list_ports');
    ports.value.http = response.data.filter((p: any) => p.type === 'http');
    ports.value.ssl = response.data.filter((p: any) => p.type === 'ssl');
  } catch (error) {
    console.error('Error fetching ports:', error);
  }
};

const fetchPhpVersions = async () => {
  try {
    const response = await axios.get('/api/list_fpm_pools');
    phpVersions.value = response.data;
  } catch (error) {
    console.error('Error fetching PHP versions:', error);
  }
};

const fetchIPs = async () => {
  try {
    const response = await axios.get('/api/list_ips');
    ips.value.ipv4 = response.data.filter((p: any) => p.type === 'ipv4');
    ips.value.ipv6 = response.data.filter((p: any) => p.type === 'ipv6');
  } catch (error) {
    console.error('Error fetching IPs:', error);
  }
};

const fetchLogFormats = async () => {
  try {
    const response = await axios.get('/api/logs_names');
    logFormats.value = response.data.logs;
  } catch (error) {
    console.error('Error fetching log formats:', error);
  }
};

const updateVhost = async () => {
  try {
    await axios.put(`/api/vhosts/${route.params.id}`, { ...vhost.value, nginxConfig: nginxConfig.value });
    showAlert('Vhost updated successfully', 'success');
    router.push('/vhosts');
  } catch (error) {
    console.error('Error updating vhost:', error);
    showAlert('Error updating vhost', 'error');
  }
};

const cancelEdit = () => {
  router.push('/');
};

onMounted(() => {
  fetchVhost();
  fetchPorts();
  fetchIPs();
  fetchPhpVersions();
  fetchLogFormats();
});
</script>