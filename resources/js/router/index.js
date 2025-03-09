import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Vhosts from '../components/Vhosts.vue';
import EditVhost from '../components/EditVhost.vue';
import IPs from '../components/IPs.vue';
import Ports from '../components/Ports.vue';
import Php from '../components/Php.vue';
import Logs from '../components/Logs.vue';
import Settings from '../components/Settings.vue';
import LogStreamer from '../components/LogStreamer.vue';

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/vhosts', component: Vhosts },
  { path: '/vhosts/:id/edit', name: 'vhosts.edit', component: EditVhost, props: true },
  { path: '/ips', component: IPs },
  { path: '/ports', component: Ports },
  { path: '/php', component: Php },
  { path: '/logs', component: Logs },
  { path: '/settings', component: Settings },
  { path: '/log-streamer', component: LogStreamer },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;