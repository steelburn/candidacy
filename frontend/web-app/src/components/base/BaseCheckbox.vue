<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    
    <div v-if="options.length" class="checkbox-group">
      <label
        v-for="option in normalizedOptions"
        :key="option.value"
        class="checkbox-label"
      >
        <input
          type="checkbox"
          :value="option.value"
          :checked="isChecked(option.value)"
          :disabled="disabled"
          @change="handleChange(option.value, $event.target.checked)"
        />
        <span>{{ option.label }}</span>
      </label>
    </div>
    
    <label v-else class="checkbox-label">
      <input
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <span v-if="checkboxLabel">{{ checkboxLabel }}</span>
    </label>
    
    <p v-if="error" class="form-error">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-gray-500 mt-xs">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [Boolean, Array],
    default: false
  },
  label: {
    type: String,
    default: ''
  },
  checkboxLabel: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const normalizedOptions = computed(() => {
  return props.options.map(option => {
    if (typeof option === 'string' || typeof option === 'number') {
      return { value: option, label: option }
    }
    return option
  })
})

const isChecked = (value) => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.includes(value)
  }
  return false
}

const handleChange = (value, checked) => {
  if (!Array.isArray(props.modelValue)) {
    emit('update:modelValue', checked ? [value] : [])
    return
  }
  
  const newValue = checked
    ? [...props.modelValue, value]
    : props.modelValue.filter(v => v !== value)
  
  emit('update:modelValue', newValue)
}
</script>

<style scoped>
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  cursor: pointer;
  user-select: none;
}

.checkbox-label input[type="checkbox"] {
  cursor: pointer;
}

.checkbox-label:has(input:disabled) {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
