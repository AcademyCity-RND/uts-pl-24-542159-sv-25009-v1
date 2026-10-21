import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ReportItemView from '@/views/ReportItemView.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, ReportItemView },
  template: `
    <div class="app-layout">
      <Navbar currentPage="report" />
      <main class="main-content">
        <ReportItemView />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Laporkan Barang.</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
