import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Vhosts from '../components/Vhosts.vue';
import EditVhost from '../components/EditVhost.vue';
import IPs from '../components/IPs.vue';
import Ports from '../components/Ports.vue';
import Php from '../components/Php.vue';
import Panel_Settings from '../components/Panel_Settings.vue';
import Nginx_Settings from '../components/Nginx_Settings.vue';
import Components from '../components/Components.vue';

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/vhosts', component: Vhosts },
  { path: '/vhosts/:id/edit', name: 'vhosts.edit', component: EditVhost, props: true },
  { path: '/ips', component: IPs },
  { path: '/ports', component: Ports },
  { path: '/php', component: Php },
  { path: '/panel-settings', component: Panel_Settings },
  { path: '/nginx-settings', component: Nginx_Settings },
  { path: '/components', component: Components },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;