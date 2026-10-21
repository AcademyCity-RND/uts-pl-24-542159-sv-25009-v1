<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api, { buildImageUrl, type ApiErrorDetail } from '../services/api'
import StatusBadge from '../components/StatusBadge.vue'
import CountdownTimer from '../components/CountdownTimer.vue'
import BaseModal from '../components/BaseModal.vue'
import { useAuthStore } from '../stores/auth'
import type { LostItem } from '../types/api'

const authStore = useAuthStore()

const urlParams = new URLSearchParams(window.location.search)
const itemId = urlParams.get('id')

const item = ref<LostItem | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const actionSuccessMessage = ref('')

// Modal Klaim Penemuan (Report Pihak Ketiga)
const isReportModalOpen = ref(false)
const claimContact = ref('')
const claimMessage = ref('')
const claimLoading = ref(false)
const claimError = ref('')

const isOwner = computed(() => {
  return authStore.currentUser && item.value?.user_id === authStore.currentUser.id
})

async function fetchItemDetail() {
  if (!itemId) {
    errorMessage.value = 'ID barang tidak ditentukan.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get(`/items/${itemId}`)
    item.value = response.data.data
  } catch (error: any) {
    const err = error as ApiErrorDetail
    if (err.status === 404) {
      errorMessage.value = 'Data barang tidak ditemukan atau telah dihapus.'
    } else {
      errorMessage.value = err.message || 'Gagal memuat detail barang.'
    }
  } finally {
    loading.value = false
  }
}

// Submit Laporan Pihak Ketiga
async function submitClaimReport() {
  if (!item.value) return
  claimLoading.value = true
  claimError.value = ''

  try {
    await api.post(`/items/${item.value.id}/report`, {
      reporter_contact: claimContact.value,
      message: claimMessage.value
    })

    actionSuccessMessage.value = 'Laporan informasi penemuan berhasil dikirim ke Admin untuk ditinjau.'
    isReportModalOpen.value = false
    claimContact.value = ''
    claimMessage.value = ''
    fetchItemDetail()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    if (err.status === 409) {
      claimError.value = err.message || 'Konflik status: Anda sudah memiliki laporan aktif yang sedang ditinjau.'
    } else {
      claimError.value = err.message || 'Gagal mengirim laporan penemuan.'
    }
  } finally {
    claimLoading.value = false
  }
}

// Konfirmasi Resolusi oleh Owner
async function handleResolveItem() {
  if (!item.value) return
  if (!confirm('Apakah Anda yakin barang ini sudah berhasil ditemukan/dikembalikan?')) return
  try {
    await api.patch(`/items/${item.value.id}/status`, { status: 'resolved' })
    actionSuccessMessage.value = 'Barang berhasil diselesaikan (Resolved)!'
    fetchItemDetail()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    alert(err.message || 'Gagal mengubah status barang.')
  }
}

// Repost Barang oleh Owner
async function handleRepostItem() {
  if (!item.value) return
  try {
    await api.post(`/items/${item.value.id}/repost`)
    actionSuccessMessage.value = 'Barang berhasil di-repost! Masa tayang diperpanjang 72 jam ke depan.'
    fetchItemDetail()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    alert(err.message || 'Gagal me-repost barang.')
  }
}

onMounted(() => {
  fetchItemDetail()
})
</script>

<template>
  <div class="detail-page container">
    <div v-if="loading" class="detail-loading">
      <p>Memuat informasi detail barang...</p>
    </div>

    <div v-else-if="errorMessage" class="detail-error">
      <h2>Pemberitahuan</h2>
      <p>{{ errorMessage }}</p>
      <a href="/" class="btn btn-secondary">Kembali ke Beranda</a>
    </div>

    <div v-else-if="item" class="detail-layout">
      <!-- Pesan Aksi Sukses -->
      <div v-if="actionSuccessMessage" class="alert-success-banner">
        {{ actionSuccessMessage }}
      </div>

      <div class="detail-grid">
        <!-- Kolom Foto -->
        <div class="detail-media">
          <div class="media-card">
            <img
              v-if="item.image_path"
              :src="buildImageUrl(item.image_path) || ''"
              :alt="item.item_name"
              class="item-full-image"
            />
            <div v-else class="media-placeholder">
              <p>Tidak ada foto yang diunggah</p>
            </div>
          </div>
        </div>

        <!-- Kolom Informasi Lengkap -->
        <div class="detail-info">
          <div class="detail-header-badges">
            <StatusBadge :type="item.category" />
            <StatusBadge :status="item.status" />
          </div>

          <h1 class="item-title">{{ item.item_name }}</h1>
          <p class="item-category-tag">{{ item.category === 'lost' ? 'Barang Hilang' : 'Barang Ditemukan' }}</p>

          <!-- Countdown Timer jika approved -->
          <div v-if="item.status === 'approved' && item.remaining_seconds !== undefined" class="timer-wrapper">
            <span class="timer-label">Sisa Waktu Tayang:</span>
            <CountdownTimer :remaining-seconds="item.remaining_seconds" />
          </div>

          <!-- Tabel Spesifikasi Detail -->
          <div class="spec-table">
            <div class="spec-row">
              <span class="spec-label">Lokasi Terakhir / Ditemukan</span>
              <span class="spec-val">{{ item.location }}</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">Lokasi Penyimpanan Saat Ini</span>
              <span class="spec-val">{{ item.current_storage }}</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">Kronologi / Ciri Khusus</span>
              <span class="spec-val">{{ item.chronology }}</span>
            </div>

            <!-- Kontak (Hanya tampil untuk Owner atau Admin) -->
            <div v-if="item.contact_info" class="spec-row contact-highlight">
              <span class="spec-label">Kontak Pribadi (Pemilik/Pelapor)</span>
              <span class="spec-val-bold">{{ item.contact_info }}</span>
            </div>
            <div v-if="item.reporter_contact_info" class="spec-row contact-highlight">
              <span class="spec-label">Kontak Medsos / Alternatif</span>
              <span class="spec-val-bold">{{ item.reporter_contact_info }}</span>
            </div>
          </div>

          <!-- Aksi Resolusi & Repost -->
          <div class="detail-actions">
            <!-- Jika User Login dan Bukan Owner: Tombol Lapor Admin / Klaim -->
            <button
              v-if="authStore.isAuthenticated && !isOwner && item.status === 'approved'"
              type="button"
              class="btn btn-cta"
              @click="isReportModalOpen = true"
            >
              🤝 Saya Menemukan / Klaim Barang Ini
            </button>

            <!-- Aksi Khusus Pemilik Barang -->
            <template v-if="isOwner">
              <!-- Jika status resolve_requested atau approved, bisa tandai selesai -->
              <button
                v-if="['approved', 'resolve_requested'].includes(item.status)"
                type="button"
                class="btn btn-primary"
                @click="handleResolveItem"
              >
                ✓ Tandai Sudah Selesai (Resolved)
              </button>

              <!-- Jika barang expired atau resolved, tampilkan opsi Repost -->
              <button
                v-if="['resolved'].includes(item.status) || item.is_expired || ((item.remaining_seconds ?? 1) <= 0)"
                type="button"
                class="btn btn-secondary"
                @click="handleRepostItem"
              >
                🔄 Repost Barang (Perpanjang 72 Jam)
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Lapor / Klaim Penemuan -->
    <BaseModal
      :is-open="isReportModalOpen"
      title="Laporkan Informasi Penemuan"
      @close="isReportModalOpen = false"
    >
      <div v-if="claimError" class="alert-danger-banner">
        {{ claimError }}
      </div>

      <p class="modal-instruction">
        Informasi yang Anda kirimkan akan ditinjau oleh Administrator kampus terlebih dahulu sebelum diteruskan ke pemilik barang.
      </p>

      <form @submit.prevent="submitClaimReport">
        <div class="form-group">
          <label class="form-label">Nomor WhatsApp / Kontak Anda</label>
          <input
            v-model="claimContact"
            type="text"
            class="form-input"
            placeholder="08123456789"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Pesan / Bukti Ciri Penemuan</label>
          <textarea
            v-model="claimMessage"
            class="form-textarea"
            rows="3"
            placeholder="Jelaskan di mana barang Anda amankan dan ciri spesifiknya..."
            required
          ></textarea>
        </div>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-secondary"
            @click="isReportModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="btn btn-cta"
            :disabled="claimLoading"
          >
            <span v-if="claimLoading">Mengirim...</span>
            <span v-else>Kirim Laporan</span>
          </button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<style scoped>
.detail-page {
  padding-top: var(--space-8);
  padding-bottom: var(--space-10);
}

.detail-layout {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-8);
}

@media (min-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.media-card {
  width: 100%;
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background-color: var(--color-card);
  box-shadow: var(--shadow-card);
}

.item-full-image {
  width: 100%;
  max-height: 420px;
  object-fit: cover;
}

.media-placeholder {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-muted);
  background-color: var(--color-surface-soft);
}

.detail-info {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.detail-header-badges {
  display: flex;
  gap: var(--space-2);
}

.item-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text);
  line-height: 1.25;
}

.item-category-tag {
  font-size: 13px;
  color: var(--color-muted);
  font-weight: 500;
}

.timer-wrapper {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-top: var(--space-2);
}

.timer-label {
  font-size: 13px;
  color: var(--color-muted);
}

.spec-table {
  display: flex;
  flex-direction: column;
  border-top: 1px solid var(--color-border);
  margin-top: var(--space-4);
  padding-top: var(--space-4);
  gap: var(--space-3);
}

.spec-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.spec-label {
  font-size: 12px;
  color: var(--color-muted);
  font-weight: 500;
}

.spec-val {
  font-size: 14px;
  color: var(--color-text);
}

.spec-val-bold {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-primary);
}

.contact-highlight {
  background-color: var(--color-surface-soft);
  padding: var(--space-3);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.detail-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: var(--space-6);
}

.alert-success-banner {
  padding: var(--space-4);
  background-color: rgba(21, 128, 61, 0.1);
  border: 1px solid var(--color-success);
  border-radius: var(--radius-md);
  color: var(--color-success);
  font-weight: 500;
}

.alert-danger-banner {
  padding: var(--space-3);
  background-color: rgba(185, 28, 28, 0.1);
  border: 1px solid var(--color-danger);
  border-radius: var(--radius-md);
  color: var(--color-danger);
  font-size: 13px;
  margin-bottom: var(--space-4);
}

.modal-instruction {
  font-size: 13px;
  color: var(--color-muted);
  margin-bottom: var(--space-4);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  margin-top: var(--space-4);
}
</style>
