import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PublicDashboard from '@/views/PublicDashboard.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, PublicDashboard },
  template: `
    <div class="app-layout">
      <Navbar currentPage="home" />
      <main class="main-content">
        <PublicDashboard />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Sistem Informasi Lost & Found Kampus.</p>
          <p class="footer-subtext">Konstruksi dan Evolusi Perangkat Lunak (KEPL)</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
