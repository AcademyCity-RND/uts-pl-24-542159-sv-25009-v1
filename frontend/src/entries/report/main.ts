import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ReportItemView from '@/views/ReportItemView.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, ReportItemView, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="report" />
      <main class="main-content">
        <ReportItemView />
      </main>
      <SiteFooter context="Laporkan barang hilang atau ditemukan" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
