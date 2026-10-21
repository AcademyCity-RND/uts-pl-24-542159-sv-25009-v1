<script setup>
import { ref } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('budi@mail.ugm.ac.id')
const password = ref('Password123!')
const errorMessage = ref(route.query.expired ? 'Sesi Anda telah berakhir. Silakan masuk kembali.' : '')
const errors = ref({})

async function handleLogin() {
  errorMessage.value = ''
  errors.value = {}

  const result = await authStore.login(email.value, password.value)

  if (result.success) {
    const redirectPath = route.query.redirect || (result.user.role === 'admin' ? '/admin' : '/')
    router.push(redirectPath)
  } else {
    errorMessage.value = result.error
    if (result.errors) {
      errors.value = result.errors
    }
  }
}
</script>

<template>
  <div class="auth-page container">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Masuk ke JejakBarang</h1>
        <p class="auth-subtitle">Gunakan akun civitas akademika UGM Anda</p>
      </div>

      <!-- Pesan Galat -->
      <div v-if="errorMessage" class="auth-alert-danger" role="alert">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email" class="form-label">Alamat Email (@mail.ugm.ac.id)</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-input"
            :class="{ 'is-invalid': errors.email }"
            placeholder="nama@mail.ugm.ac.id"
            required
            autocomplete="email"
          />
          <span v-if="errors.email" class="form-error">{{ errors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-input"
            :class="{ 'is-invalid': errors.password }"
            placeholder="Minimal 8 karakter..."
            required
            autocomplete="current-password"
          />
          <span v-if="errors.password" class="form-error">{{ errors.password[0] }}</span>
        </div>

        <!-- Tombol Aksi Utama Amber -->
        <button
          type="submit"
          class="btn btn-cta btn-block"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading">Memproses Masuk...</span>
          <span v-else>Masuk Sekarang</span>
        </button>
      </form>

      <div class="auth-footer">
        <p class="auth-footer-text">
          Belum punya akun?
          <RouterLink to="/register" class="auth-link">Daftar Akun Baru</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-top: var(--space-10);
  padding-bottom: var(--space-10);
  min-height: calc(100vh - 160px);
}

.auth-card {
  width: 100%;
  max-width: 440px;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-8);
  box-shadow: var(--shadow-card);
}

.auth-header {
  text-align: center;
  margin-bottom: var(--space-6);
}

.auth-title {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--color-text);
  margin-bottom: var(--space-1);
}

.auth-subtitle {
  font-size: 13px;
  color: var(--color-muted);
}

.auth-alert-danger {
  padding: var(--space-3) var(--space-4);
  background-color: rgba(185, 28, 28, 0.1);
  border: 1px solid var(--color-danger);
  border-radius: var(--radius-md);
  color: var(--color-danger);
  font-size: 13px;
  margin-bottom: var(--space-4);
}

.auth-form {
  display: flex;
  flex-direction: column;
}

.btn-block {
  width: 100%;
  margin-top: var(--space-2);
}

.auth-footer {
  margin-top: var(--space-6);
  text-align: center;
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border);
}

.auth-footer-text {
  font-size: 13px;
  color: var(--color-muted);
}

.auth-link {
  color: var(--color-primary);
  font-weight: 600;
  margin-left: 4px;
}

.auth-link:hover {
  text-decoration: underline;
}
</style>
