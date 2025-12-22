<template>
  <div class="form-group">
    <label v-if="label" :for="textareaId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    
    <textarea
      :id="textareaId"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      :maxlength="maxLength"
      :class="['form-input', { 'error': error }]"
      @input="handleInput"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    ></textarea>
    
    <div v-if="showCharCount && maxLength" class="text-sm text-gray-500 mt-xs text-right">
      {{ characterCount }} / {{ maxLength }}
    </div>
    
    <p v-if="error" class="form-error">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-gray-500 mt-xs">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
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
  rows: {
    type: Number,
    default: 4
  },
  maxLength: {
    type: Number,
    default: null
  },
  showCharCount: {
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

const emit = defineEmits(['update:modelValue', 'blur', 'focus'])

const textareaId = computed(() => {
  return `textarea-${Math.random().toString(36).substr(2, 9)}`
})

const characterCount = computed(() => {
  return props.modelValue ? props.modelValue.length : 0
})

const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
}
</script>
