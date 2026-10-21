import { createApp } from 'vue'
import { createPinia } from 'pinia'
import RegisterView from '@/views/RegisterView.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, RegisterView, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="register" />
      <main class="main-content">
        <RegisterView />
      </main>
      <SiteFooter context="Pendaftaran akun komunitas kampus" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
