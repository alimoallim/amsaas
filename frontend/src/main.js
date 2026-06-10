import { createApp } from 'vue'

import App from './App.vue'

import router from './router'

import { createPinia } from 'pinia'

import './style.css'
import { initTheme } from './utils/theme'

initTheme()

import { useThemeStore } from './stores/theme'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

useThemeStore(pinia).init()

app.mount('#app')