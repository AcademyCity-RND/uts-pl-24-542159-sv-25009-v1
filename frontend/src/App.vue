<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Navbar from './components/Navbar.vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()

onMounted(async () => {
  if (authStore.token) {
    await authStore.fetchCurrentUser()
  }
})
</script>

<template>
  <div class="app-layout">
    <Navbar />
    <main class="main-content">
      <RouterView />
    </main>
    <footer class="footer">
      <div class="container footer-inner">
        <p class="footer-text">
          &copy; 2026 <strong>JejakBarang</strong> — Sistem Informasi Lost & Found Kampus Anti-Tenggelam.
        </p>
        <p class="footer-subtext">
          Konstruksi dan Evolusi Perangkat Lunak (KEPL)
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
}

.footer {
  margin-top: var(--space-10);
  border-top: 1px solid var(--color-border);
  padding: var(--space-6) 0;
  background-color: var(--color-card);
}

.footer-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-1);
  text-align: center;
}

.footer-text {
  font-size: 13px;
  color: var(--color-text);
}

.footer-subtext {
  font-size: 12px;
  color: var(--color-muted);
}
</style>
