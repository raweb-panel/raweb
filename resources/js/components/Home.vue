<template>
  <div class="p-4 space-y-4">
    <!-- Top Row for Live Metrics -->
    <div class="flex flex-wrap -mx-2">
      <!-- RAM Usage Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[150px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-lg font-bold">RAM Usage</h4>
            </div>
            <div class="px-4 pb-4 flex-grow flex items-center justify-center">
              <span class="text-2xl font-semibold">{{ ramUsage }}</span>
            </div>
          </div>
        </div>
      </div>
      <!-- CPU Usage Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[150px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-lg font-bold">CPU Usage</h4>
            </div>
            <div class="px-4 pb-4 flex-grow flex items-center justify-center">
              <span class="text-2xl font-semibold">{{ cpuUsage }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Existing Cards -->
    <div class="flex flex-wrap -mx-2">
      <!-- Current Statistics Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[350px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-xl font-bold">Current Statistics</h4>
            </div>
            <div class="px-4 pb-4 flex-grow">
              <div class="space-y-3">
                <div class="flex justify-between border-b border-gray-700 py-2">
                  <span>Active Connections</span>
                  <span>{{ status.active_connections }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-700 py-2">
                  <span>Accepted Connections</span>
                  <span>{{ status.accepts }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-700 py-2">
                  <span>Handled Connections</span>
                  <span>{{ status.handled }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-700 py-2">
                  <span>Total Requests</span>
                  <span>{{ status.requests }}</span>
                </div>
                <!-- Single line for Reading, Writing, Waiting -->
                <div class="flex justify-around py-2">
                  <div class="flex flex-col items-center">
                    <span class="font-medium">Reading</span>
                    <span>{{ status.reading }}</span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="font-medium">Writing</span>
                    <span>{{ status.writing }}</span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="font-medium">Waiting</span>
                    <span>{{ status.waiting }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Active Connections Chart Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[350px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-xl font-bold">Active Connections Over Time</h4>
            </div>
            <div class="px-4 pb-4 flex-grow flex items-center justify-center">
              <canvas ref="connectionsChart" class="w-full" style="max-height: 250px;"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Row for Network Usage and Top IPs Side by Side -->
    <div class="flex flex-wrap -mx-2">
      <!-- Network Mb/s In/Out Chart Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[350px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-xl font-bold">Network Mb/s In/Out Over Time</h4>
            </div>
            <div class="px-4 pb-4 flex-grow flex items-center justify-center">
              <canvas ref="networkChart" class="w-full" style="max-height: 250px;"></canvas>
            </div>
          </div>
        </div>
      </div>
      <!-- Top 10 IPs (Connections) Card -->
      <div class="w-full md:w-1/2 px-2 mb-4">
        <div class="h-[350px]">
          <div class="bg-gray-800 text-white rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col h-full">
            <div class="px-4 py-4 border-b border-gray-700">
              <h4 class="text-xl font-bold">Incoming Connections</h4>
            </div>
            <div class="px-4 pb-4 flex-grow overflow-y-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="text-left">IP</th>
                    <th class="text-right">Connections</th>
                    <th class="text-right">Type</th>
                    <th class="text-right">Ports</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(data, ip) in topIps" :key="ip">
                    <td>{{ ip }}</td>
                    <td class="text-right">{{ data.connections }}</td>
                    <td class="text-right">{{ data.type }}</td>
                    <td class="text-right">{{ data.ports.join(', ') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns';

// Reactive status values.
const status = ref({
  active_connections: "Loading...",
  accepts: "Loading...",
  handled: "Loading...",
  requests: "Loading...",
  reading: "Loading...",
  writing: "Loading...",
  waiting: "Loading..."
});

// Reactive live metrics.
const ramUsage = ref("Loading...");
const cpuUsage = ref("Loading...");

// References to canvas elements.
const connectionsChart = ref<HTMLCanvasElement | null>(null);
const networkChart = ref<HTMLCanvasElement | null>(null);

let chartInstance: Chart | null = null;
let networkChartInstance: Chart | null = null;

// Chart.js configuration for Active Connections.
const chartData = {
  labels: [] as Date[],
  datasets: [{
    label: 'Active Connections',
    data: [] as number[],
    backgroundColor: 'rgba(54, 162, 235, 0.2)',
    borderColor: 'rgba(54, 162, 235, 1)',
    borderWidth: 1,
    fill: true
  }]
};

// Chart.js configuration for Network Usage.
const networkChartData = {
  labels: [] as Date[],
  datasets: [
    {
      label: 'Mb/s In',
      data: [] as number[],
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      borderColor: 'rgba(75, 192, 192, 1)',
      borderWidth: 1,
      fill: true
    },
    {
      label: 'Mb/s Out',
      data: [] as number[],
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      borderColor: 'rgba(255, 99, 132, 1)',
      borderWidth: 1,
      fill: true
    }
  ]
};

// Reactive variable for top IPs.
const topIps = ref<Record<string, { connections: number; type: string; ports: string[] }>>({});

const apiUrl = window.apiNginxStatus || '/api/nginx-status';
const topIpsUrl = window.apiTopIps || '/api/top-ips';
let intervalId: number | null = null;

async function fetchNginxStatus() {
  try {
    const response = await fetch(apiUrl);
    if (!response.ok) throw new Error("Network response was not okay");
    const data = await response.json();

    // Update statistics.
    status.value.active_connections = data.active_connections;
    status.value.accepts = data.accepts;
    status.value.handled = data.handled;
    status.value.requests = data.requests;
    status.value.reading = data.reading;
    status.value.writing = data.writing;
    status.value.waiting = data.waiting;

    // Update live metrics.
    if (data.ram_usage !== undefined) ramUsage.value = data.ram_usage;
    if (data.cpu_usage !== undefined) cpuUsage.value = data.cpu_usage;

    // Update Active Connections chart.
    chartData.labels.push(new Date());
    chartData.datasets[0].data.push(data.active_connections);
    if (chartData.labels.length > 30) {
      chartData.labels.shift();
      chartData.datasets[0].data.shift();
    }
    if (chartInstance) {
      chartInstance.update();
    }

    // Update Network Usage chart.
    if (data.bits_in !== undefined && data.bits_out !== undefined) {
      networkChartData.labels.push(new Date());
      networkChartData.datasets[0].data.push(data.bits_in);
      networkChartData.datasets[1].data.push(data.bits_out);
      if (networkChartData.labels.length > 30) {
        networkChartData.labels.shift();
        networkChartData.datasets[0].data.shift();
        networkChartData.datasets[1].data.shift();
      }
      if (networkChartInstance) {
        networkChartInstance.update();
      }
    }
  } catch (error) {
    console.error("Failed to fetch Nginx status", error);
  }
}

async function fetchTopIpsData() {
  try {
    const response = await fetch(topIpsUrl);
    if (!response.ok) throw new Error("Network response was not okay");
    const data = await response.json();
    topIps.value = data;
  } catch (error) {
    console.error("Failed to fetch Top IPs data", error);
  }
}

onMounted(() => {
  if (connectionsChart.value) {
    chartInstance = new Chart(connectionsChart.value, {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            type: 'time',
            time: {
              displayFormats: { second: 'h:mm:ss a' },
              tooltipFormat: 'MMM d, h:mm:ss a'
            }
          },
          y: { beginAtZero: true }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  }

  if (networkChart.value) {
    networkChartInstance = new Chart(networkChart.value, {
      type: 'line',
      data: networkChartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            type: 'time',
            time: {
              displayFormats: { second: 'h:mm:ss a' },
              tooltipFormat: 'MMM d, h:mm:ss a'
            }
          },
          y: { beginAtZero: true }
        }
      }
    });
  }

  // Begin polling for both endpoints.
  fetchNginxStatus();
  fetchTopIpsData();
  intervalId = setInterval(() => {
    fetchNginxStatus();
    fetchTopIpsData();
  }, 2000);
});

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }
});
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
