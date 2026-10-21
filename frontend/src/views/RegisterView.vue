<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const phoneNumber = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref('')
const errors = ref<Record<string, string[]>>({})

async function handleRegister() {
  errorMessage.value = ''
  errors.value = {}

  const payload: Record<string, string> = {
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value
  }

  if (phoneNumber.value.trim()) {
    payload.phone_number = phoneNumber.value.trim()
  }

  const result = await authStore.register(payload)

  if (result.success) {
    window.location.href = '/'
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
        <h1 class="auth-title">Daftar Akun JejakBarang</h1>
        <p class="auth-subtitle">Gunakan email aktif Anda untuk verifikasi akun</p>
      </div>

      <!-- Pesan Galat -->
      <div v-if="errorMessage" class="auth-alert-danger" role="alert">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-group">
          <label for="name" class="form-label">Nama Lengkap</label>
          <input
            id="name"
            v-model="name"
            type="text"
            class="form-input"
            :class="{ 'is-invalid': errors.name }"
            placeholder="Contoh: Budi Santoso"
            required
            autocomplete="name"
          />
          <span v-if="errors.name" class="form-error">{{ errors.name[0] }}</span>
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Email (@gmail.com)</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-input"
            :class="{ 'is-invalid': errors.email }"
            placeholder="nama@gmail.com"
            required
            autocomplete="email"
          />
          <span v-if="errors.email" class="form-error">{{ errors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="phone_number" class="form-label">Nomor WhatsApp / HP (Opsional)</label>
          <input
            id="phone_number"
            v-model="phoneNumber"
            type="tel"
            class="form-input"
            :class="{ 'is-invalid': errors.phone_number }"
            placeholder="08123456789"
            autocomplete="tel"
          />
          <span v-if="errors.phone_number" class="form-error">{{ errors.phone_number[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi (Min. 8 karakter & simbol)</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-input"
            :class="{ 'is-invalid': errors.password }"
            placeholder="Password123!"
            required
            autocomplete="new-password"
          />
          <span v-if="errors.password" class="form-error">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            class="form-input"
            :class="{ 'is-invalid': errors.password_confirmation }"
            placeholder="Ketik ulang kata sandi..."
            required
            autocomplete="new-password"
          />
          <span v-if="errors.password_confirmation" class="form-error">{{ errors.password_confirmation[0] }}</span>
        </div>

        <button
          type="submit"
          class="btn btn-cta btn-block"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading">Memproses Pendaftaran...</span>
          <span v-else>Daftar Akun Sekarang</span>
        </button>
      </form>

      <div class="auth-footer">
        <p class="auth-footer-text">
          Sudah punya akun?
          <a href="/login" class="auth-link">Masuk ke Sini</a>
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
  padding-top: var(--space-8);
  padding-bottom: var(--space-10);
  min-height: calc(100vh - 160px);
}

.auth-card {
  width: 100%;
  max-width: 480px;
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
