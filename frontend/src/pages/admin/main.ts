import { createApp } from 'vue'
import { createPinia } from 'pinia'
import AdminDashboard from '@/views/AdminDashboard.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, AdminDashboard },
  template: `
    <div class="app-layout">
      <Navbar currentPage="admin" />
      <main class="main-content">
        <AdminDashboard />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Dashboard Moderasi Administrator.</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
