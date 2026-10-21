import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ItemDetailView from '@/views/ItemDetailView.vue'
import Navbar from '@/components/Navbar.vue'
import '@/assets/styles/main.css'

const App = {
  components: { Navbar, ItemDetailView },
  template: `
    <div class="app-layout">
      <Navbar currentPage="detail" />
      <main class="main-content">
        <ItemDetailView />
      </main>
      <footer class="footer">
        <div class="container footer-inner">
          <p class="footer-text">&copy; 2026 <strong>JejakBarang</strong> — Detail Barang & Resolusi.</p>
        </div>
      </footer>
    </div>
  `
}

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
