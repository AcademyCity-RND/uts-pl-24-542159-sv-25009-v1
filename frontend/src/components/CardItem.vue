<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import StatusBadge from './StatusBadge.vue'
import CountdownTimer from './CountdownTimer.vue'
import { buildImageUrl } from '../services/api'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const imageUrl = computed(() => {
  return buildImageUrl(props.item.image_path)
})
</script>

<template>
  <div class="card-item">
    <!-- Gambar & Badge Tipe -->
    <div class="card-image-wrapper">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="props.item.item_name"
        class="card-image"
        loading="lazy"
      />
      <div v-else class="card-image-placeholder">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <circle cx="8.5" cy="8.5" r="1.5"></circle>
          <polyline points="21 15 16 10 5 21"></polyline>
        </svg>
      </div>

      <!-- Badge Tipe di kiri atas foto -->
      <div class="card-type-badge">
        <StatusBadge :type="props.item.type" />
      </div>
    </div>

    <!-- Isi Kartu -->
    <div class="card-content">
      <div class="card-category">{{ props.item.category }}</div>
      <h3 class="card-title">{{ props.item.item_name }}</h3>

      <div class="card-location">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
          <circle cx="12" cy="10" r="3"></circle>
        </svg>
        <span class="location-text">{{ props.item.location }}</span>
      </div>

      <!-- Footer Kartu: Timer & Link Aksi -->
      <div class="card-footer">
        <CountdownTimer
          v-if="props.item.remaining_seconds !== undefined"
          :remaining-seconds="props.item.remaining_seconds"
        />
        <RouterLink :to="`/items/${props.item.id}`" class="card-link">
          Detail &rarr;
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card-item {
  display: flex;
  flex-direction: column;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-card);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.card-item:hover {
  transform: translateY(-2px);
}

.card-image-wrapper {
  position: relative;
  width: 100%;
  height: 140px;
  background-color: var(--color-surface-soft);
  overflow: hidden;
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-muted);
}

.card-type-badge {
  position: absolute;
  top: var(--space-2);
  left: var(--space-2);
}

.card-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: var(--space-4);
  gap: var(--space-2);
}

.card-category {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-muted);
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--color-text);
  margin-bottom: var(--space-1);
}

.card-location {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  font-size: 13px;
  color: var(--color-muted);
}

.location-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
}

.card-link {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-primary);
  display: inline-flex;
  align-items: center;
}

.card-link:hover {
  text-decoration: underline;
}
</style>
