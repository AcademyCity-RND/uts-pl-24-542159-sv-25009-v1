<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close'])

function handleKeyDown(e) {
  if (e.key === 'Escape' && props.isOpen) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="props.isOpen" class="modal-backdrop" @click="emit('close')">
      <div
        class="modal-card"
        role="dialog"
        aria-modal="true"
        :aria-label="props.title"
        @click.stop
      >
        <!-- Modal Header -->
        <div class="modal-header">
          <h2 class="modal-title">{{ props.title }}</h2>
          <button
            type="button"
            class="modal-close-btn"
            aria-label="Tutup modal"
            @click="emit('close')"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <slot></slot>
        </div>

        <!-- Modal Footer -->
        <div v-if="$slots.footer" class="modal-footer">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(11, 18, 32, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: var(--space-4);
}

.modal-card {
  width: 100%;
  max-width: 520px;
  background-color: var(--color-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-modal);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4) var(--space-6);
  border-bottom: 1px solid var(--color-border);
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-text);
}

.modal-close-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  line-height: 1;
  color: var(--color-muted);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
}

.modal-close-btn:hover {
  color: var(--color-text);
  background-color: var(--color-surface-soft);
}

.modal-body {
  padding: var(--space-6);
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-3);
  padding: var(--space-4) var(--space-6);
  border-top: 1px solid var(--color-border);
  background-color: var(--color-surface-soft);
}
</style>
