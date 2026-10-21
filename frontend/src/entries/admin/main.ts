import { createApp } from 'vue'
import { createPinia } from 'pinia'
import AdminDashboard from '@/views/AdminDashboard.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, AdminDashboard, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="admin" />
      <main class="main-content">
        <AdminDashboard />
      </main>
      <SiteFooter context="Dashboard moderasi administrator" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
