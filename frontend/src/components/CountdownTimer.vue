<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  remainingSeconds: {
    type: Number,
    required: true
  }
})

const currentSeconds = ref(Math.max(0, props.remainingSeconds))
let timerId = null

onMounted(() => {
  timerId = setInterval(() => {
    if (currentSeconds.value > 0) {
      currentSeconds.value--
    } else {
      clearInterval(timerId)
    }
  }, 1000)
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
})

// Format jam:menit:detik
const formattedTime = computed(() => {
  if (currentSeconds.value <= 0) return 'Kedaluwarsa'
  const hours = Math.floor(currentSeconds.value / 3600)
  const minutes = Math.floor((currentSeconds.value % 3600) / 60)
  const seconds = currentSeconds.value % 60
  return `${hours}j ${minutes}m ${seconds}d`
})

// Skema warna semantik sesuai Design Guideline
const timerClass = computed(() => {
  if (currentSeconds.value <= 0) return 'timer-expired'
  const hours = currentSeconds.value / 3600
  if (hours < 12) return 'timer-danger'
  if (hours < 24) return 'timer-warning'
  return 'timer-neutral'
})
</script>

<template>
  <div :class="['countdown-chip', timerClass, 'tabular-nums']">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="10"></circle>
      <polyline points="12 6 12 12 16 14"></polyline>
    </svg>
    <span>{{ formattedTime }}</span>
  </div>
</template>

<style scoped>
.countdown-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.2;
}

.timer-neutral {
  background-color: var(--color-surface-soft);
  color: var(--color-muted);
}

.timer-warning {
  background-color: rgba(180, 83, 9, 0.12);
  color: var(--color-warning);
}

.timer-danger {
  background-color: rgba(185, 28, 28, 0.12);
  color: var(--color-danger);
}

.timer-expired {
  background-color: var(--color-surface-soft);
  color: var(--color-muted);
}
</style>
