<script setup>
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push('/')
}
</script>

<template>
  <header class="navbar">
    <div class="container navbar-inner">
      <!-- Logo Brand -->
      <RouterLink to="/" class="brand-link">
        <div class="brand-logo" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
        </div>
        <span class="brand-name">Jejak<span class="brand-name-sub">Barang</span></span>
      </RouterLink>

      <!-- Navigasi & Aksi -->
      <nav class="nav-links">
        <RouterLink to="/" class="nav-item" active-class="nav-item-active" exact>
          Beranda
        </RouterLink>

        <!-- Admin Link -->
        <RouterLink
          v-if="authStore.isAdmin"
          to="/admin"
          class="nav-item"
          active-class="nav-item-active"
        >
          Admin Panel
        </RouterLink>

        <!-- CTA Utama Amber: Laporkan Barang -->
        <RouterLink to="/report" class="btn btn-cta cta-report">
          <span>+ Laporkan Barang</span>
        </RouterLink>

        <!-- Toggle Theme -->
        <button
          type="button"
          class="theme-toggle-btn"
          :title="themeStore.currentTheme === 'dark' ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
          :aria-label="themeStore.currentTheme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
          @click="themeStore.toggleTheme"
        >
          <svg v-if="themeStore.currentTheme === 'dark'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>

        <!-- Auth State Buttons -->
        <template v-if="authStore.isAuthenticated">
          <span class="user-greeting">
            {{ authStore.currentUser?.name?.split(' ')[0] || 'User' }}
          </span>
          <button type="button" class="btn btn-secondary btn-sm" @click="handleLogout">
            Keluar
          </button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="nav-item">
            Masuk
          </RouterLink>
          <RouterLink to="/register" class="btn btn-secondary btn-sm">
            Daftar
          </RouterLink>
        </template>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background-color: var(--color-card);
  border-bottom: 1px solid var(--color-border);
  backdrop-filter: blur(8px);
}

.navbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.brand-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
}

.brand-name {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: -0.02em;
}

.brand-name-sub {
  color: var(--color-text);
}

.nav-links {
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.nav-item {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-muted);
  transition: color 0.15s ease;
}

.nav-item:hover {
  color: var(--color-text);
}

.nav-item-active {
  color: var(--color-primary);
  font-weight: 700;
}

.theme-toggle-btn {
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.theme-toggle-btn:hover {
  background-color: var(--color-surface-soft);
}

.user-greeting {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

.btn-sm {
  padding: 7px 14px;
  min-height: 36px;
  font-size: 13px;
}

@media (max-width: 640px) {
  .cta-report {
    display: none;
  }
}
</style>
