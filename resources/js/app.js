// resources/js/app.js
import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import './index.css' // Import Tailwind CSS
import App from './App.vue'
import router from './router'
import axios from 'axios' // Import Axios
import EditVhost from './components/EditVhost.vue' // Import EditVhost component

// Get the CSRF token from the meta tag
const token = document.head.querySelector('meta[name="csrf-token"]').content

// Set the CSRF token as a common header for all Axios requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = token

// Create the Vue app instance and use ElementPlus and the router
const app = createApp(App)
app.use(ElementPlus) // Enable ElementPlus
app.use(router)

// Register the EditVhost component
app.component('edit-vhost', EditVhost)

// Mount the Vue app to the #app element
app.mount('#app')
