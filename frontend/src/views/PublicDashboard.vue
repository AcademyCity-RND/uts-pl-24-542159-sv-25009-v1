<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api, { type ApiErrorDetail } from '../services/api'
import CardItem from '../components/CardItem.vue'
import type { LostItem } from '../types/api'

const items = ref<LostItem[]>([])
const loading = ref(true)
const errorMessage = ref('')
const isNetworkError = ref(false)

// Filter & Search State
const searchQuery = ref('')
const selectedType = ref('all') // 'all', 'lost', 'found'
const selectedCategory = ref('all')
const categories = ['lost', 'found']

async function fetchPublicItems() {
  loading.value = true
  errorMessage.value = ''
  isNetworkError.value = false

  try {
    const params: Record<string, any> = { per_page: 50 }
    if (selectedCategory.value !== 'all') {
      params.category = selectedCategory.value
    }
    const response = await api.get('/public/items', { params })
    items.value = response.data.data || []
  } catch (error: any) {
    const err = error as ApiErrorDetail
    isNetworkError.value = !!err.isNetworkError
    errorMessage.value = err.message || 'Gagal memuat daftar barang publik.'
  } finally {
    loading.value = false
  }
}

// Client-side search and type filtering
const filteredItems = () => {
  return items.value.filter(item => {
    // Type/Category filter
    if (selectedType.value !== 'all' && item.category !== selectedType.value) {
      return false
    }
    // Search query filter
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const matchName = item.item_name?.toLowerCase().includes(q)
      const matchLoc = item.location?.toLowerCase().includes(q)
      return matchName || matchLoc
    }
    return true
  })
}

onMounted(() => {
  fetchPublicItems()
})

watch(selectedCategory, () => {
  fetchPublicItems()
})
</script>

<template>
  <div class="public-dashboard container">
    <!-- Hero Header -->
    <section class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Papan Informasi Lost &amp; Found Kampus</h1>
        <p class="hero-subtitle">
          Temukan barang yang hilang atau laporkan barang temuan Anda. Informasi terverifikasi anti-tenggelam.
        </p>
      </div>

      <!-- Search & Quick Filters -->
      <div class="search-filter-bar">
        <div class="search-input-wrapper">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon" aria-hidden="true">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Cari nama barang atau lokasi..."
            aria-label="Cari barang"
          />
        </div>

        <!-- Filter Chips (Type) -->
        <div class="filter-chips">
          <button
            type="button"
            :class="['chip', { 'chip-active': selectedType === 'all' }]"
            @click="selectedType = 'all'"
          >
            Semua
          </button>
          <button
            type="button"
            :class="['chip', { 'chip-active': selectedType === 'lost' }]"
            @click="selectedType = 'lost'"
          >
            🔴 Kehilangan
          </button>
          <button
            type="button"
            :class="['chip', { 'chip-active': selectedType === 'found' }]"
            @click="selectedType = 'found'"
          >
            🟢 Ditemukan
          </button>
        </div>
      </div>
    </section>

    <!-- State: Error Jaringan / 5xx -->
    <div v-if="errorMessage" class="error-banner">
      <div class="error-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>{{ errorMessage }}</p>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" @click="fetchPublicItems">
        Coba Lagi
      </button>
    </div>

    <!-- State: Loading Shimmer/Skeleton -->
    <div v-else-if="loading" class="items-grid">
      <div v-for="i in 6" :key="i" class="card-skeleton">
        <div class="skeleton-img"></div>
        <div class="skeleton-content">
          <div class="skeleton-line" style="width: 40%;"></div>
          <div class="skeleton-line" style="width: 80%;"></div>
          <div class="skeleton-line" style="width: 60%;"></div>
        </div>
      </div>
    </div>

    <!-- State: Data Kosong -->
    <div v-else-if="filteredItems().length === 0" class="empty-state">
      <div class="empty-icon" aria-hidden="true">📦</div>
      <h3 class="empty-title">Tidak ada barang yang cocok</h3>
      <p class="empty-desc">
        Belum ada laporan barang yang aktif atau sesuai dengan kriteria pencarian Anda.
      </p>
      <a href="/report" class="btn btn-cta">
        Laporkan Barang Sekarang
      </a>
    </div>

    <!-- State: Grid Barang Aktif -->
    <div v-else class="items-grid">
      <CardItem
        v-for="item in filteredItems()"
        :key="item.id"
        :item="item"
      />
    </div>
  </div>
</template>

<style scoped>
.public-dashboard {
  padding-top: var(--space-8);
  padding-bottom: var(--space-10);
}

.hero-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
  margin-bottom: var(--space-8);
}

.hero-content {
  max-width: 680px;
}

.hero-title {
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--color-text);
  line-height: 1.2;
  margin-bottom: var(--space-2);
}

@media (max-width: 640px) {
  .hero-title {
    font-size: 24px;
  }
}

.hero-subtitle {
  font-size: 15px;
  color: var(--color-muted);
}

.search-filter-bar {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

@media (min-width: 768px) {
  .search-filter-bar {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  max-width: 520px;
}

.search-icon {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: var(--color-muted);
  pointer-events: none;
}

.search-input {
  width: 100%;
  height: 44px;
  padding-left: 42px;
  padding-right: 14px;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
}

.search-input:focus {
  border-color: var(--color-primary);
  box-shadow: var(--ring-focus);
}

.filter-chips {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  overflow-x: auto;
  padding-bottom: 4px;
}

.chip {
  padding: 8px 16px;
  border-radius: var(--radius-full);
  font-size: 13px;
  font-weight: 500;
  border: 1px solid var(--color-border);
  background-color: var(--color-card);
  color: var(--color-text);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.chip:hover {
  background-color: var(--color-surface-soft);
}

.chip-active {
  background-color: var(--color-primary);
  color: #FFFFFF;
  border-color: var(--color-primary);
}

[data-theme="dark"] .chip-active {
  color: #0F172A;
}

/* Responsif Grid: 1 col mobile, 2 col tablet, 3 col desktop */
.items-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-4);
}

@media (min-width: 640px) {
  .items-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .items-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Error Banner */
.error-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4) var(--space-6);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-danger);
  background-color: rgba(185, 28, 28, 0.08);
  color: var(--color-danger);
  margin-bottom: var(--space-6);
}

.error-content {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  font-weight: 500;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: var(--space-10) var(--space-4);
  background-color: var(--color-card);
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-lg);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: var(--space-2);
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: var(--space-1);
}

.empty-desc {
  font-size: 14px;
  color: var(--color-muted);
  max-width: 400px;
  margin-bottom: var(--space-6);
}

/* Shimmer Loading Skeleton */
.card-skeleton {
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  height: 280px;
  display: flex;
  flex-direction: column;
}

.skeleton-img {
  height: 140px;
  background-color: var(--color-surface-soft);
  animation: pulse 1.5s infinite;
}

.skeleton-content {
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.skeleton-line {
  height: 12px;
  background-color: var(--color-surface-soft);
  border-radius: var(--radius-sm);
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
