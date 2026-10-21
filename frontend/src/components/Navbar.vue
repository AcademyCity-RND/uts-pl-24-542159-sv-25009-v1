<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'

defineProps<{
  currentPage?: 'home' | 'login' | 'register' | 'report' | 'detail' | 'admin'
}>()

const authStore = useAuthStore()
const themeStore = useThemeStore()

onMounted(async () => {
  if (authStore.token) {
    await authStore.fetchCurrentUser()
  }
})

async function handleLogout() {
  await authStore.logout()
  window.location.href = '/'
}
</script>

<template>
  <header class="navbar">
    <div class="container navbar-inner">
      <!-- Logo Brand -->
      <a href="/" class="brand-link">
        <div class="brand-logo" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
        </div>
        <span class="brand-name">Jejak<span class="brand-name-sub">Barang</span></span>
      </a>

      <nav class="nav-links" aria-label="Navigasi utama">
        <a href="/" :class="['nav-item', { 'nav-item-active': currentPage === 'home' }]">
          Beranda
        </a>
      </nav>

      <div class="nav-actions">
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
          <span>{{ themeStore.currentTheme === 'dark' ? 'Terang' : 'Gelap' }}</span>
        </button>

        <span class="nav-divider" aria-hidden="true"></span>

        <template v-if="authStore.isAuthenticated">
          <span class="user-greeting">
            {{ authStore.currentUser?.name?.split(' ')[0] || 'User' }}
          </span>
          <button type="button" class="btn btn-secondary btn-sm" @click="handleLogout">
            Keluar
          </button>
        </template>
        <template v-else>
          <a href="/login" class="nav-item">
            Masuk
          </a>
          <a href="/register" class="btn btn-secondary btn-sm">
            Daftar
          </a>
        </template>
      </div>
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
  gap: var(--space-6);
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
  flex: 1;
  margin-left: var(--space-6);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: var(--space-3);
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
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 36px;
  padding: 7px 11px;
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.theme-toggle-btn:hover {
  background-color: var(--color-surface-soft);
}

.nav-divider {
  width: 1px;
  height: 24px;
  background: var(--color-border);
}

.user-greeting {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
  .navbar-inner {
    gap: var(--space-3);
  }

  .nav-links {
    margin-left: 0;
  }

  .nav-actions {
    gap: var(--space-2);
  }

  .theme-toggle-btn span {
    display: none;
  }

  .theme-toggle-btn {
    padding: 7px 9px;
  }

  .user-greeting {
    display: none;
  }

  .nav-divider {
    display: none;
  }

  .nav-item {
    font-size: 13px;
  }

  .btn-sm {
    padding: 7px 10px;
  }
}

.btn-sm {
  padding: 7px 14px;
  min-height: 36px;
  font-size: 13px;
}

</style>
