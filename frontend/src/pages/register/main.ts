import { createApp } from 'vue'
import { createPinia } from 'pinia'
import RegisterView from '@/views/RegisterView.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, RegisterView },
  template: `
    <div class="app-layout">
      <Navbar currentPage="register" />
      <main class="main-content">
        <RegisterView />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Pendaftaran Akun.</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
