import { createApp } from 'vue'
import { createPinia } from 'pinia'
import LoginView from '@/views/LoginView.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, LoginView, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="login" />
      <main class="main-content">
        <LoginView />
      </main>
      <SiteFooter context="Masuk dan kelola laporan barang" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
