<template>
  <div class="form-group">
    <label v-if="label" :for="selectId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    
    <select
      :id="selectId"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      :class="['form-input', { 'error': error }]"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in normalizedOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    
    <p v-if="error" class="form-error">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-gray-500 mt-xs">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: ''
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

defineEmits(['update:modelValue'])

const selectId = computed(() => {
  return `select-${Math.random().toString(36).substr(2, 9)}`
})

const normalizedOptions = computed(() => {
  return props.options.map(option => {
    if (typeof option === 'string' || typeof option === 'number') {
      return { value: option, label: option }
    }
    return option
  })
})
</script>
