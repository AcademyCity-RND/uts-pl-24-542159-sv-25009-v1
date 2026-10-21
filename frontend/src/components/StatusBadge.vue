<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String, // 'lost' atau 'found' atau status model
    required: false
  },
  status: {
    type: String, // 'pending', 'approved', 'resolve_requested', 'resolved', 'rejected'
    required: false
  }
})

const badgeConfig = computed(() => {
  if (props.type === 'lost') {
    return { class: 'badge-lost', label: 'Hilang' }
  }
  if (props.type === 'found') {
    return { class: 'badge-found', label: 'Ditemukan' }
  }
  switch (props.status) {
    case 'pending':
      return { class: 'badge-pending', label: 'Menunggu Review' }
    case 'approved':
      return { class: 'badge-approved', label: 'Aktif' }
    case 'resolve_requested':
      return { class: 'badge-resolve_requested', label: 'Menunggu Konfirmasi' }
    case 'resolved':
      return { class: 'badge-resolved', label: 'Selesai' }
    case 'rejected':
      return { class: 'badge-rejected', label: 'Ditolak' }
    default:
      return { class: 'badge-resolved', label: props.status || '-' }
  }
})
</script>

<template>
  <span :class="['badge', badgeConfig.class]">
    <span v-if="props.type === 'lost'" aria-hidden="true">🔴</span>
    <span v-else-if="props.type === 'found'" aria-hidden="true">🟢</span>
    <span>{{ badgeConfig.label }}</span>
  </span>
</template>
