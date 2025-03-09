<template>
  <div class="max-w-7xl mx-auto p-6 bg-gray-900 text-white">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">Nginx Virtual Hosts</h2>
      <button 
        class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg shadow-md"
        @click="openCreateModal">
        + Add New Vhost
      </button>
    </div>

    <div class="overflow-x-auto bg-gray-800 shadow-md rounded-lg">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-700 text-gray-200 uppercase text-sm">
            <th class="p-3">Vhost Name</th>
            <th class="p-3">Ports</th>
            <th class="p-3">PHP Version</th>
            <th class="p-3">Log Type</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="vhost in vhosts" :key="vhost.id" class="border-b border-gray-600 hover:bg-gray-700">
            <td class="p-3">{{ vhost.server_name }}</td>
            <td class="p-3">{{ vhost.http_port }}{{ vhost.ssl_port ? ' / ' + vhost.ssl_port : '' }}</td>
            <td class="p-3">{{ vhost.php_version }}</td>
            <td class="p-3">{{ vhost.log_type }}</td>
            <td class="p-3 flex justify-center gap-2">
              <button class="px-3 py-1 bg-blue-500 hover:bg-blue-400 text-white rounded-md" @click="editVhost(vhost)">Edit</button>
              <button class="px-3 py-1 bg-red-500 hover:bg-red-400 text-white rounded-md" @click="deleteVhost(vhost.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Overlay -->
    <div 
      v-if="showCreateModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
      @click.self="showCreateModal = false">
      <div class="bg-gray-800 p-6 rounded-lg w-full max-w-md shadow-lg">
        <h3 class="text-xl font-bold mb-4">Create New Virtual Host</h3>
        <form @submit.prevent="createVhost">
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">File Name</label>
            <input
              v-model="newVhost.file" 
              type="text" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" 
              placeholder="example.com">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Server Name</label>
            <input 
              v-model="newVhost.server_name" 
              type="text" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg" 
              placeholder="example.com">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">IPv4</label>
            <select 
              v-model="newVhost.ipv4" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option value="none">None</option>
              <option 
                v-for="ip in ips.ipv4" 
                :key="ip.ip_address" 
                :value="ip.ip_address">
                {{ ip.ip_address }} - {{ ip.description }}
              </option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">IPv6</label>
            <select 
              v-model="newVhost.ipv6" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option value="none">None</option>
              <option 
                v-for="ip in ips.ipv6" 
                :key="ip.ip_address" 
                :value="ip.ip_address">
                {{ ip.ip_address }} - {{ ip.description }}
              </option>
            </select>
          </div>

          <!-- HTTP Port and SSL Port in one line -->
          <div class="mb-4 flex gap-4">
            <div class="w-1/2">
              <label class="block text-sm font-semibold mb-1">HTTP Port</label>
              <select 
                v-model="newVhost.http_port" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
                <option value="none">None</option>
                <option 
                  v-for="port in ports.http" 
                  :key="port.port_number" 
                  :value="port.port_number">
                  {{ port.port_number }} - {{ port.description }}
                </option>
              </select>
            </div>
            <div class="w-1/2">
              <label class="block text-sm font-semibold mb-1">SSL Port</label>
              <select 
                v-model="newVhost.ssl_port" 
                class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
                <option value="none">None</option>
                <option 
                  v-for="port in ports.ssl" 
                  :key="port.port_number" 
                  :value="port.port_number">
                  {{ port.port_number }} - {{ port.description }}
                </option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">PHP Version</label>
            <select 
              v-model="newVhost.php_version" 
              class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option value="none">None</option>
              <option 
                v-for="php in phpVersions" 
                :key="php.version" 
                :value="php.version">
                {{ php.version }} - {{ php.listen }}
              </option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Log Type</label>
            <select v-model="newVhost.log_type" class="w-full p-2 bg-gray-700 border border-gray-600 rounded-lg">
              <option v-for="log in logFormats" :key="log" :value="log">{{ log }}</option>
              <option value="off">Off (access logs disabled)</option>
            </select>
          </div>
          <div class="flex justify-between">
            <button 
              type="button" 
              class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg"
              @click="showCreateModal = false">
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
              Create
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
import { useRouter } from 'vue-router';

const { showAlert } = useAlert();
const router = useRouter();
const vhosts = ref([]);
const showCreateModal = ref(false);
const newVhost = ref({
  file: '',
  server_name: '',
  ipv4: '',
  ipv6: 'none',
  http_port: '',
  ssl_port: '',
  php_version: 'none',
  log_type: ''
});

const ports = ref({
  http: [],
  ssl: []
});

const ips = ref({
  ipv4: [],
  ipv6: []
});

const phpVersions = ref([]);
const logFormats = ref([]);

const fetchVhosts = async () => {
  try {
    const response = await axios.get('/api/list_hosts');
    vhosts.value = response.data;
  } catch (error) {
    console.error('Error fetching vhosts:', error);
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

const openCreateModal = () => {
  showCreateModal.value = true;
  // Auto-select the first available option for IPv4, IPv6, HTTP port, SSL port, PHP version, and log type
  newVhost.value.ipv4 = ips.value.ipv4.length > 0 ? ips.value.ipv4[0].ip_address : 'none';
  newVhost.value.ipv6 = ips.value.ipv6.length > 0 ? ips.value.ipv6[0].ip_address : 'none';
  newVhost.value.http_port = ports.value.http.length > 0 ? ports.value.http[0].port_number : 'none';
  newVhost.value.ssl_port = ports.value.ssl.length > 0 ? ports.value.ssl[0].port_number : 'none';
  newVhost.value.php_version = phpVersions.value.length > 0 ? phpVersions.value[0].version : 'none';
  newVhost.value.log_type = logFormats.value.length > 0 ? logFormats.value[0] : 'none';
};

const createVhost = async () => {
  try {
    const response = await axios.post('/api/vhosts', newVhost.value);
    vhosts.value.push(response.data.vhost);
    showCreateModal.value = false;
    showAlert('Vhost created successfully', 'success');
    resetNewVhost();
  } catch (error) {
    console.error('Error creating vhost:', error);
    showAlert('Error creating vhost', 'error');
  }
};

const resetNewVhost = () => {
  newVhost.value = {
    file: '',
    server_name: '',
    ipv4: '',
    ipv6: 'none',
    http_port: '',
    ssl_port: '',
    php_version: 'none',
    log_type: ''
  };
};

const editVhost = (vhost: any) => {
  router.push({ name: 'vhosts.edit', params: { id: vhost.id } });
};

const deleteVhost = async (id: number) => {
  try {
    await axios.delete(`/api/vhosts/${id}`);
    vhosts.value = vhosts.value.filter((v: any) => v.id !== id);
    showAlert('Vhost deleted successfully', 'success');
  } catch (error) {
    console.error('Error deleting vhost:', error);
    showAlert('Error deleting vhost', 'error');
  }
};

onMounted(() => {
  fetchVhosts();
  fetchPorts();
  fetchIPs();
  fetchPhpVersions();
  fetchLogFormats();
});
</script>
