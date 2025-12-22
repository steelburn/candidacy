<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="spinner"></span>
    <slot v-else></slot>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value)
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'success'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

defineEmits(['click'])

const buttonClasses = computed(() => {
  return [
    'btn',
    `btn-${props.variant}`,
    `btn-${props.size}`
  ]
})
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--radius-md);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
  gap: var(--spacing-sm);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-sm {
  padding: var(--spacing-xs) var(--spacing-md);
  font-size: var(--font-size-sm);
}

.btn-md {
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: var(--font-size-base);
}

.btn-lg {
  padding: var(--spacing-md) var(--spacing-xl);
  font-size: var(--font-size-lg);
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--color-primary-dark);
}

.btn-secondary {
  background-color: var(--color-gray-200);
  color: var(--color-gray-700);
}

.btn-secondary:hover:not(:disabled) {
  background-color: var(--color-gray-300);
}

.btn-danger {
  background-color: var(--color-danger);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: var(--color-danger-dark);
}

.btn-success {
  background-color: var(--color-success);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background-color: var(--color-success-dark);
}

.spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
