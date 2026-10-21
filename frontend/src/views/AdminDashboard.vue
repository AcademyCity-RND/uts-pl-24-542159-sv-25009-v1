<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api, { buildImageUrl, type ApiErrorDetail } from '../services/api'
import StatusBadge from '../components/StatusBadge.vue'
import { useAuthStore } from '../stores/auth'
import type { LostItem, ItemReport } from '../types/api'

const authStore = useAuthStore()

// Tab Active State: 'pending', 'approved', 'reports'
const currentTab = ref<'pending' | 'approved' | 'reports'>('pending')

const pendingItems = ref<LostItem[]>([])
const approvedItems = ref<LostItem[]>([])
const itemReports = ref<ItemReport[]>([])

const loading = ref(true)
const actionMessage = ref('')

onMounted(async () => {
  // Guard Admin MPA: Jika bukan admin, tendang ke login atau beranda
  if (!authStore.isAuthenticated) {
    window.location.href = '/login?redirect=' + encodeURIComponent('/admin')
    return
  }
  if (!authStore.isAdmin) {
    alert('Akses Ditolak: Halaman ini khusus Administrator.')
    window.location.href = '/'
    return
  }

  fetchAdminData()
})

async function fetchAdminData() {
  loading.value = true
  actionMessage.value = ''
  try {
    // 1. Pending Items
    const pendingRes = await api.get('/items', { params: { status: 'pending', per_page: 50 } })
    pendingItems.value = pendingRes.data.data || []

    // 2. Approved Items
    const approvedRes = await api.get('/items', { params: { status: 'approved', per_page: 50 } })
    approvedItems.value = approvedRes.data.data || []

    // 3. Item Reports (Laporan Pihak Ketiga)
    const reportsRes = await api.get('/item-reports', { params: { per_page: 50 } })
    itemReports.value = reportsRes.data.data || []
  } catch (error: any) {
    const err = error as ApiErrorDetail
    actionMessage.value = err.message || 'Gagal memuat data moderasi admin.'
  } finally {
    loading.value = false
  }
}

// Moderasi Item: Setujui (Approve) atau Tolak (Reject)
async function handleItemStatus(itemId: number, newStatus: string) {
  try {
    await api.patch(`/items/${itemId}/status`, { status: newStatus })
    actionMessage.value = `Barang ID #${itemId} berhasil diubah statusnya menjadi ${newStatus}.`
    fetchAdminData()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    alert(err.message || 'Gagal mengubah status barang.')
  }
}

// Hapus Paksa Item oleh Admin
async function handleDeleteItem(itemId: number) {
  if (!confirm(`Hapus permanen laporan barang #${itemId}?`)) return
  try {
    await api.delete(`/items/${itemId}`)
    actionMessage.value = `Barang #${itemId} berhasil dihapus.`
    fetchAdminData()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    alert(err.message || 'Gagal menghapus barang.')
  }
}

// Moderasi Report Pihak Ketiga: Kirim 'accepted' (memicu resolve_requested di backend) atau 'rejected'
async function handleReportStatus(reportId: number, newStatus: string) {
  try {
    await api.patch(`/item-reports/${reportId}/status`, { status: newStatus })
    actionMessage.value = `Laporan pihak ketiga #${reportId} berhasil di-${newStatus}.`
    fetchAdminData()
  } catch (error: any) {
    const err = error as ApiErrorDetail
    alert(err.message || 'Gagal memproses laporan.')
  }
}
</script>

<template>
  <div class="admin-page container">
    <div class="admin-header">
      <h1 class="admin-title">Dashboard Moderasi Admin</h1>
      <p class="admin-subtitle">
        Kelola pengajuan postingan baru, pantau barang aktif, dan tinjau laporan resolusi penemuan.
      </p>
    </div>

    <!-- Alert Notifikasi Aksi -->
    <div v-if="actionMessage" class="alert-info-banner">
      {{ actionMessage }}
    </div>

    <!-- 3 Tab Navigation Admin -->
    <div class="tab-nav">
      <button
        type="button"
        :class="['tab-btn', { 'tab-btn-active': currentTab === 'pending' }]"
        @click="currentTab = 'pending'"
      >
        <span>📥 Tab 1: Pengajuan Pending</span>
        <span class="tab-badge">{{ pendingItems.length }}</span>
      </button>

      <button
        type="button"
        :class="['tab-btn', { 'tab-btn-active': currentTab === 'approved' }]"
        @click="currentTab = 'approved'"
      >
        <span>📢 Tab 2: Laporan Aktif Publik</span>
        <span class="tab-badge">{{ approvedItems.length }}</span>
      </button>

      <button
        type="button"
        :class="['tab-btn', { 'tab-btn-active': currentTab === 'reports' }]"
        @click="currentTab = 'reports'"
      >
        <span>🤝 Tab 3: Pesan Resolusi</span>
        <span class="tab-badge">{{ itemReports.length }}</span>
      </button>
    </div>

    <!-- Tab 1: Pengajuan Pending -->
    <div v-if="currentTab === 'pending'" class="tab-pane">
      <div v-if="pendingItems.length === 0" class="empty-box">
        <p>Tidak ada pengajuan barang baru yang menunggu persetujuan.</p>
      </div>
      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Barang</th>
              <th>Kategori</th>
              <th>Pelapor / Kontak</th>
              <th>Lokasi & Kronologi</th>
              <th>Aksi Moderasi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="it in pendingItems" :key="it.id">
              <td>#{{ it.id }}</td>
              <td>
                <div><strong>{{ it.item_name }}</strong></div>
                <div v-if="it.image_path" class="mt-1">
                  <a :href="buildImageUrl(it.image_path) || '#'" target="_blank" rel="noopener" class="text-xs text-teal-600 hover:underline">
                    🖼️ Lihat Foto
                  </a>
                </div>
              </td>
              <td>
                <StatusBadge :type="it.category" />
              </td>
              <td>
                <div>{{ it.user?.name || 'User #' + it.user_id }}</div>
                <div class="table-subtext">{{ it.contact_info || '-' }}</div>
              </td>
              <td>
                <div>{{ it.location }}</div>
                <div class="table-subtext">{{ it.chronology }}</div>
              </td>
              <td class="action-cell">
                <button
                  type="button"
                  class="btn btn-sm btn-primary"
                  @click="handleItemStatus(it.id, 'approved')"
                >
                  ✓ Setujui (ACC)
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-danger"
                  @click="handleItemStatus(it.id, 'rejected')"
                >
                  ✕ Tolak
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 2: Laporan Aktif Publik -->
    <div v-if="currentTab === 'approved'" class="tab-pane">
      <div v-if="approvedItems.length === 0" class="empty-box">
        <p>Tidak ada barang yang sedang tayang aktif.</p>
      </div>
      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Barang</th>
              <th>Kategori</th>
              <th>Lokasi Penyimpanan</th>
              <th>Kontak Pelapor</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="it in approvedItems" :key="it.id">
              <td>#{{ it.id }}</td>
              <td><strong>{{ it.item_name }}</strong></td>
              <td><StatusBadge :type="it.category" /></td>
              <td>{{ it.current_storage }}</td>
              <td>{{ it.contact_info || it.user?.email || '-' }}</td>
              <td>
                <button
                  type="button"
                  class="btn btn-sm btn-danger"
                  @click="handleDeleteItem(it.id)"
                >
                  🗑 Hapus Tayang
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 3: Pesan Resolusi (Item Reports Pihak Ketiga) -->
    <div v-if="currentTab === 'reports'" class="tab-pane">
      <div v-if="itemReports.length === 0" class="empty-box">
        <p>Belum ada laporan klaim / penemuan pihak ketiga.</p>
      </div>
      <div v-else class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Target Barang</th>
              <th>Penemu / Kontak</th>
              <th>Pesan Bukti Temuan</th>
              <th>Status</th>
              <th>Aksi Admin</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rep in itemReports" :key="rep.id">
              <td>#{{ rep.id }}</td>
              <td>
                <strong>#{{ rep.lost_item_id }}</strong>
                <div class="table-subtext">{{ rep.lost_item?.item_name || 'Item Terkait' }}</div>
              </td>
              <td>
                <div>{{ rep.reporter?.name || 'Pelapor #' + rep.reporter_user_id }}</div>
                <div class="table-subtext">{{ rep.reporter?.email || '-' }}</div>
              </td>
              <td>{{ rep.message }}</td>
              <td>
                <StatusBadge :status="rep.status" />
              </td>
              <td class="action-cell">
                <template v-if="rep.status === 'pending'">
                  <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click="handleReportStatus(rep.id, 'accepted')"
                  >
                    ✓ Terima Laporan
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    @click="handleReportStatus(rep.id, 'rejected')"
                  >
                    ✕ Tolak
                  </button>
                </template>
                <span v-else class="table-subtext">Sudah diproses</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page {
  padding-top: var(--space-8);
  padding-bottom: var(--space-10);
}

.admin-header {
  margin-bottom: var(--space-6);
}

.admin-title {
  font-size: 26px;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.02em;
  margin-bottom: var(--space-1);
}

.admin-subtitle {
  font-size: 14px;
  color: var(--color-muted);
}

.alert-info-banner {
  padding: var(--space-3) var(--space-4);
  background-color: rgba(3, 105, 161, 0.1);
  border: 1px solid var(--color-info);
  border-radius: var(--radius-md);
  color: var(--color-info);
  font-weight: 500;
  margin-bottom: var(--space-4);
}

.tab-nav {
  display: flex;
  gap: var(--space-2);
  border-bottom: 1px solid var(--color-border);
  margin-bottom: var(--space-6);
  overflow-x: auto;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: 12px 18px;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-muted);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: var(--color-text);
}

.tab-btn-active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.tab-badge {
  padding: 2px 8px;
  border-radius: var(--radius-full);
  background-color: var(--color-surface-soft);
  font-size: 11px;
}

.admin-table-wrapper {
  overflow-x: auto;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.admin-table th, .admin-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--color-border);
}

.admin-table th {
  background-color: var(--color-surface-soft);
  font-weight: 600;
  color: var(--color-text);
}

.table-subtext {
  font-size: 12px;
  color: var(--color-muted);
}

.action-cell {
  display: flex;
  gap: var(--space-2);
  white-space: nowrap;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
  min-height: 32px;
}

.empty-box {
  padding: var(--space-10);
  text-align: center;
  background-color: var(--color-card);
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-lg);
  color: var(--color-muted);
}
</style>
