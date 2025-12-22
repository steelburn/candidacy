<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Offer</h2>
        <button class="close-btn" @click="close">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="context-info">
          <p><strong>Candidate:</strong> {{ candidateName }}</p>
          <p><strong>Vacancy:</strong> {{ vacancyTitle }}</p>
        </div>

        <form @submit.prevent="submit">
          <div class="form-row">
            <div class="form-group">
              <label>Salary Offered *</label>
              <input v-model.number="form.salary_offered" type="number" min="0" required />
            </div>

            <div class="form-group">
              <label>Currency</label>
              <select v-model="form.currency" disabled>
                <option value="MYR">MYR</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Offer Date *</label>
            <input v-model="form.offer_date" type="date" required />
          </div>

          <div class="form-group">
            <label>Start Date</label>
            <input v-model="form.start_date" type="date" />
          </div>

          <div class="form-group">
            <label>Terms / Notes</label>
            <textarea v-model="form.terms" rows="4" placeholder="Contract terms or additional notes..."></textarea>
          </div>

          <div v-if="error" class="error">{{ error }}</div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="close">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Creating...' : 'Create Offer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { offerAPI } from '../../services/api'

const props = defineProps({
  show: Boolean,
  candidateId: [Number, String],
  vacancyId: [Number, String],
  candidateName: String,
  vacancyTitle: String
})

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
const error = ref('')

const form = reactive({
  salary_offered: '',
  currency: 'MYR',
  offer_date: '',
  start_date: '',
  terms: ''
})

// Reset form when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    error.value = ''
    form.salary_offered = ''
    form.currency = 'MYR'
    form.start_date = ''
    form.terms = ''
    // Default offer date to today
    form.offer_date = new Date().toISOString().split('T')[0]
  }
})

const close = () => {
  emit('close')
}

const submit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const payload = {
      candidate_id: props.candidateId,
      vacancy_id: props.vacancyId,
      ...form
    }
    
    await offerAPI.create(payload)
    emit('created')
    close()
  } catch (err) {
    console.error('Failed to create offer:', err)
    error.value = err.response?.data?.message || 'Failed to create offer'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 1.5rem;
}

.context-info {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  border-left: 4px solid #667eea;
}

.context-info p {
  margin: 0.25rem 0;
  color: #555;
  font-size: 0.9rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #444;
  font-size: 0.9rem;
}

input, select, textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

input:focus, select:focus, textarea:focus {
  border-color: #667eea;
  outline: none;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  border: none;
  font-weight: 500;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f3f5;
  color: #495057;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  border: none;
  font-weight: 500;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #e9ecef;
}
</style>
