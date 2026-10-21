import { createApp } from 'vue'
import { createPinia } from 'pinia'
import LoginView from '@/views/LoginView.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, LoginView },
  template: `
    <div class="app-layout">
      <Navbar currentPage="login" />
      <main class="main-content">
        <LoginView />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Masuk Akun.</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
