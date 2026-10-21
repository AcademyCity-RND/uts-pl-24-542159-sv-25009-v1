import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PublicDashboard from '@/views/PublicDashboard.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, PublicDashboard, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="home" />
      <main class="main-content">
        <PublicDashboard />
      </main>
      <SiteFooter context="Sistem Informasi Lost &amp; Found Kampus" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
