<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content modal-large" @click.stop>
      <h3>Import Configuration</h3>
      <p>Paste your configuration JSON below:</p>
      <textarea 
        v-model="importData" 
        class="import-textarea"
        placeholder='{"settings": [...]}'
        rows="15"
      ></textarea>
      <div class="modal-actions">
        <button @click="handleImport" class="btn-primary">Import</button>
        <button @click="$emit('close')" class="btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  show: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'import'])

const importData = ref('')

const handleImport = () => {
  emit('import', importData.value)
  importData.value = ''
}
</script>

<style scoped>
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
.modal-large { max-width: 800px; }
.modal-content h3 {
  margin: 0 0 1rem 0;
  color: #333;
}
.modal-content p {
  color: #666;
  margin-bottom: 1rem;
}
.import-textarea {
  width: 100%;
  font-family: monospace;
  padding: 1rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  resize: vertical;
}
.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}
.btn-primary {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 6px;
  cursor: pointer;
}
.btn-primary:hover {
  background: #5a6fd6;
}
.btn-secondary {
  background: white;
  border: 1px solid #e0e0e0;
  color: #444;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
}
.btn-secondary:hover {
  background: #f8f9fa;
}
</style>
