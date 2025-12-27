<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <h3>Generate Applicant Portal Link</h3>
      <div class="form-group">
        <label>Link to Vacancy (Optional)</label>
        <select v-model="selectedVacancyId">
            <option :value="null">General Profile Update</option>
            <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
        </select>
        <p class="hint">Linking to a vacancy allows the applicant to answer specific screening questions.</p>
      </div>
      
      <div v-if="generatedLink" class="generated-result">
        <label>Portal Link:</label>
        <div class="copy-box">
            <input type="text" readonly :value="generatedLink" ref="linkInput" />
            <button @click="copyLink" class="btn-secondary">Copy</button>
        </div>
        <p class="success-msg" v-if="copied">Copied to clipboard!</p>
      </div>

      <div class="modal-actions">
        <button v-if="!generatedLink" @click="generate" :disabled="loading" class="btn-primary">
            {{ loading ? 'Generating...' : 'Generate Link' }}
        </button>
        <button v-else @click="$emit('close')" class="btn-primary">Done</button>
        <button @click="$emit('close')" class="btn-text">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  vacancies: {
    type: Array,
    default: () => []
  },
  generatedLink: {
    type: String,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'generate'])

const selectedVacancyId = ref(null)
const copied = ref(false)

const generate = () => {
    emit('generate', selectedVacancyId.value)
}

const copyLink = () => {
    if (!props.generatedLink) return
    navigator.clipboard.writeText(props.generatedLink)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
}

// Reset state when modal is opened/closed
watch(() => props.show, (newVal) => {
    if (!newVal) {
        copied.value = false
        selectedVacancyId.value = null
    }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.hint {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.generated-result {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.copy-box {
    display: flex;
    gap: 10px;
}

.copy-box input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

.success-msg {
    color: #27ae60;
    font-size: 0.9em;
    margin-top: 5px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-primary {
  background: #3498db;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-text {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
}

.btn-secondary {
    background: #e0e0e0;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
</style>
