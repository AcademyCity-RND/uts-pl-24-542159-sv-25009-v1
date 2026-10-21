<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

// Form Data State
const type = ref<'lost' | 'found'>('lost')
const itemName = ref('')
const category = ref('Elektronik')
const location = ref('')
const currentStorage = ref('')
const contactInfo = ref('')
const reporterContactInfo = ref('')
const chronology = ref('')
const imageFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)

const categories = ['Elektronik', 'Dokumen / Kartu', 'Aksesoris & Pakaian', 'Kunci', 'Lain-lain']

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const errors = ref<Record<string, string[]>>({})

onMounted(() => {
  // Guard sederhana MPA: Jika belum login, alihkan ke halaman login.
  if (!authStore.isAuthenticated) {
    window.location.href = '/login?redirect=' + encodeURIComponent('/report')
  }
})

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
      errorMessage.value = 'Format file wajib berupa JPG, JPEG, atau PNG.'
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      errorMessage.value = 'Ukuran gambar maksimal adalah 2 MB.'
      return
    }
    imageFile.value = file
    imagePreview.value = URL.createObjectURL(file)
    errorMessage.value = ''
  }
}

async function handleSubmit() {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('type', type.value)
    formData.append('item_name', itemName.value)
    formData.append('category', category.value)
    formData.append('location', location.value)
    formData.append('current_storage', currentStorage.value)
    formData.append('chronology', chronology.value)

    if (contactInfo.value.trim()) {
      formData.append('contact_info', contactInfo.value.trim())
    }
    if (reporterContactInfo.value.trim()) {
      formData.append('reporter_contact_info', reporterContactInfo.value.trim())
    }
    if (imageFile.value) {
      formData.append('image', imageFile.value)
    }

    const response = await api.post('/items', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    successMessage.value = 'Laporan berhasil dibuat! Status barang saat ini adalah Pending (menunggu moderasi admin).'
    setTimeout(() => {
      window.location.href = `/detail?id=${response.data.data.id}`
    }, 1500)
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim laporan barang.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="report-page container">
    <div class="report-card">
      <div class="report-header">
        <h1 class="report-title">Laporkan Barang Hilang atau Ditemukan</h1>
        <p class="report-subtitle">
          Data akan ditinjau oleh Admin sebelum tayang di beranda publik.
        </p>
      </div>

      <!-- Alert Status -->
      <div v-if="successMessage" class="alert alert-success" role="alert">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="alert alert-danger" role="alert">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleSubmit" class="report-form">
        <!-- Tipe Laporan -->
        <div class="form-group">
          <label class="form-label">Jenis Laporan</label>
          <div class="type-toggle-group">
            <label :class="['type-option', { 'type-selected-lost': type === 'lost' }]">
              <input type="radio" v-model="type" value="lost" class="sr-only" />
              <span>🔴 Barang Hilang (Kehilangan)</span>
            </label>
            <label :class="['type-option', { 'type-selected-found': type === 'found' }]">
              <input type="radio" v-model="type" value="found" class="sr-only" />
              <span>🟢 Barang Ditemukan (Temuan)</span>
            </label>
          </div>
        </div>

        <!-- Nama Barang -->
        <div class="form-group">
          <label for="item_name" class="form-label">Nama Barang</label>
          <input
            id="item_name"
            v-model="itemName"
            type="text"
            class="form-input"
            :class="{ 'is-invalid': errors.item_name }"
            placeholder="Contoh: Laptop Asus Vivobook Hitam 14 Inch"
            required
          />
          <span v-if="errors.item_name" class="form-error">{{ errors.item_name[0] }}</span>
        </div>

        <!-- Kategori -->
        <div class="form-group">
          <label for="category" class="form-label">Kategori</label>
          <select
            id="category"
            v-model="category"
            class="form-select"
            :class="{ 'is-invalid': errors.category }"
            required
          >
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <span v-if="errors.category" class="form-error">{{ errors.category[0] }}</span>
        </div>

        <!-- Lokasi -->
        <div class="form-group">
          <label for="location" class="form-label">Lokasi Terakhir / Ditemukan</label>
          <input
            id="location"
            v-model="location"
            type="text"
            class="form-input"
            :class="{ 'is-invalid': errors.location }"
            placeholder="Contoh: Gedung Herman Johannes SV UGM Lantai 2"
            required
          />
          <span v-if="errors.location" class="form-error">{{ errors.location[0] }}</span>
        </div>

        <!-- Tempat Penyimpanan -->
        <div class="form-group">
          <label for="current_storage" class="form-label">Lokasi Penyimpanan Saat Ini</label>
          <input
            id="current_storage"
            v-model="currentStorage"
            type="text"
            class="form-input"
            :class="{ 'is-invalid': errors.current_storage }"
            placeholder="Contoh: Dititipkan di Pos Satpam Utama SV"
            required
          />
          <span v-if="errors.current_storage" class="form-error">{{ errors.current_storage[0] }}</span>
        </div>

        <!-- Kontak Pribadi / Pemilik (Privat) -->
        <div class="form-row">
          <div class="form-group col-half">
            <label for="contact_info" class="form-label">Kontak Pribadi (WhatsApp / HP)</label>
            <input
              id="contact_info"
              v-model="contactInfo"
              type="text"
              class="form-input"
              :class="{ 'is-invalid': errors.contact_info }"
              placeholder="08123456789"
            />
            <span v-if="errors.contact_info" class="form-error">{{ errors.contact_info[0] }}</span>
          </div>

          <div class="form-group col-half">
            <label for="reporter_contact_info" class="form-label">Kontak Alternatif / Medsos</label>
            <input
              id="reporter_contact_info"
              v-model="reporterContactInfo"
              type="text"
              class="form-input"
              :class="{ 'is-invalid': errors.reporter_contact_info }"
              placeholder="@username_ig atau kontak lain"
            />
            <span v-if="errors.reporter_contact_info" class="form-error">{{ errors.reporter_contact_info[0] }}</span>
          </div>
        </div>

        <!-- Kronologi Kejadian -->
        <div class="form-group">
          <label for="chronology" class="form-label">Kronologi / Ciri Khusus</label>
          <textarea
            id="chronology"
            v-model="chronology"
            class="form-textarea"
            :class="{ 'is-invalid': errors.chronology }"
            rows="3"
            placeholder="Jelaskan ciri khusus, stiker, warna, atau detail spesifik lainnya..."
            required
          ></textarea>
          <span v-if="errors.chronology" class="form-error">{{ errors.chronology[0] }}</span>
        </div>

        <!-- Unggah Foto Barang -->
        <div class="form-group">
          <label class="form-label">Foto Barang (Opsional, Maks 2MB, JPG/PNG)</label>
          <input
            type="file"
            accept="image/png, image/jpeg, image/jpg"
            class="form-input file-input"
            @change="handleFileChange"
          />
          <span v-if="errors.image" class="form-error">{{ errors.image[0] }}</span>

          <div v-if="imagePreview" class="preview-box">
            <img :src="imagePreview" alt="Pratinjau Foto Barang" class="preview-img" />
          </div>
        </div>

        <!-- Tombol Aksi Utama Amber -->
        <button
          type="submit"
          class="btn btn-cta btn-block"
          :disabled="loading"
        >
          <span v-if="loading">Mengirim Laporan...</span>
          <span v-else>Kirim Laporan untuk Moderasi</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.report-page {
  padding-top: var(--space-8);
  padding-bottom: var(--space-10);
  display: flex;
  justify-content: center;
}

.report-card {
  width: 100%;
  max-width: 640px;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-8);
  box-shadow: var(--shadow-card);
}

.report-header {
  margin-bottom: var(--space-6);
}

.report-title {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--color-text);
  margin-bottom: var(--space-1);
}

.report-subtitle {
  font-size: 14px;
  color: var(--color-muted);
}

.alert {
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  font-size: 13px;
  margin-bottom: var(--space-4);
}

.alert-success {
  background-color: rgba(21, 128, 61, 0.1);
  border: 1px solid var(--color-success);
  color: var(--color-success);
}

.alert-danger {
  background-color: rgba(185, 28, 28, 0.1);
  border: 1px solid var(--color-danger);
  color: var(--color-danger);
}

.type-toggle-group {
  display: flex;
  gap: var(--space-3);
}

.type-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  background-color: var(--color-surface-soft);
}

.type-selected-lost {
  border-color: var(--color-danger);
  background-color: rgba(185, 28, 28, 0.1);
  color: var(--color-danger);
}

.type-selected-found {
  border-color: var(--color-success);
  background-color: rgba(21, 128, 61, 0.1);
  color: var(--color-success);
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

@media (min-width: 640px) {
  .form-row {
    flex-direction: row;
  }
  .col-half {
    flex: 1;
  }
}

.file-input {
  padding: 8px;
}

.preview-box {
  margin-top: var(--space-3);
  width: 100%;
  max-height: 220px;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.preview-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

.btn-block {
  width: 100%;
  margin-top: var(--space-4);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>
