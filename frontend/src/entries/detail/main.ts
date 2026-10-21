import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ItemDetailView from '@/views/ItemDetailView.vue'
import Navbar from '@/components/Navbar.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, ItemDetailView, SiteFooter },
  template: `
    <div class="app-layout">
      <Navbar currentPage="detail" />
      <main class="main-content">
        <ItemDetailView />
      </main>
      <SiteFooter context="Detail barang dan proses resolusi" />
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
