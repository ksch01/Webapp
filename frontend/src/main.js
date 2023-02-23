import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import Activated from './Activated.vue'
import Invalid from './Invalid.vue'
import Main from './Main.vue'

import './assets/stylesheet.css'


const routes = [
    { path: '/', component: App},
    { path: '/activated', component: Activated},
    { path: '/invalid', component: Invalid}
]

const router = createRouter({
    history: createWebHashHistory(),
    routes
})

const app = createApp(Main)
app.use(router)
app.mount('#app')