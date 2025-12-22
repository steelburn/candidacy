<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEditMode ? 'Edit Interview' : 'Schedule Interview' }}</h2>
        <button class="close-btn" @click="close">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="context-info" v-if="(candidateName && vacancyTitle) || isEditMode">
          <p v-if="candidateName"><strong>Candidate:</strong> {{ candidateName }}</p>
          <p v-if="vacancyTitle"><strong>Vacancy:</strong> {{ vacancyTitle }}</p>
          <p v-if="isEditMode && !candidateName && !vacancyTitle">
             <!-- If edit mode but names not passed props, we might need helpers or accept just ID display. 
                  However, List/Calendar usually pass resolved names? No, they might pass IDs. 
                  But form selects track IDs. We can just disable inputs. -->
             Editing Interview #{{ interview.id }}
          </p>
        </div>

        <form @submit.prevent="submit">
          <!-- Selection Fields (only if not provided via props AND not edit mode) -->
          <div v-if="!isEditMode && (!candidateId || !vacancyId)" class="form-section">
            <div class="form-group" v-if="!candidateId">
              <label>Candidate *</label>
              <select v-model="form.candidate_id" required :disabled="loadingLists">
                <option value="" disabled>Select Candidate</option>
                <option v-for="c in selectableCandidates" :key="c.id" :value="c.id">
                  {{ c.first_name }} {{ c.last_name }} ({{ c.email }})
                </option>
              </select>
            </div>
            
            <div class="form-group" v-if="!vacancyId">
              <label>Vacancy *</label>
              <select v-model="form.vacancy_id" required :disabled="loadingLists">
                <option value="" disabled>Select Vacancy</option>
                <option v-for="v in selectableVacancies" :key="v.id" :value="v.id">
                  {{ v.title }}
                </option>
              </select>
            </div>
          </div>
          
          <!-- In Edit Mode, we typically lock Candidate/Vacancy. If we want to show who it is, we need to resolve it.
               The user didn't strictly ask to see names in edit modal if they came from list where they saw it. 
               We will just ensure form.candidate_id is set but hidden/disabled. -->
          <div v-if="isEditMode" class="form-section">
             <!-- Potentially display read-only Candidate/Vacancy info if available in lists -->
          </div>

          <div class="form-group">
            <label>Interviewers</label>
            <div class="multi-select-container">
              <div v-for="user in users" :key="user.id" class="checkbox-item">
                <input 
                  type="checkbox" 
                  :id="'user-' + user.id" 
                  :value="user.id" 
                  v-model="form.interviewer_ids"
                >
                <label :for="'user-' + user.id">{{ user.name }} ({{ user.role }})</label>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Date & Time *</label>
            <input v-model="form.scheduled_at" type="datetime-local" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Stage *</label>
              <select v-model="form.stage" required>
                <option value="screening">Screening</option>
                <option value="technical">Technical</option>
                <option value="behavioral">Behavioral</option>
                <option value="final">Final</option>
              </select>
            </div>

            <div class="form-group">
              <label>Duration (minutes)</label>
              <input v-model.number="form.duration_minutes" type="number" min="15" step="15" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Type *</label>
              <select v-model="form.type" required>
                <option value="video">Video Call</option>
                <option value="in_person">In Person</option>
                <option value="phone">Phone</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Location / Link</label>
            <input 
              v-model="form.location" 
              type="text" 
              :placeholder="form.type === 'video' ? 'e.g. Zoom/Meet Link' : 'e.g. Office Address'" 
            />
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="form.notes" rows="3" placeholder="Internal notes..."></textarea>
          </div>

          <div v-if="error" class="error">{{ error }}</div>

          <div class="form-actions">
            <button 
                v-if="isEditMode && interview.status !== 'cancelled'" 
                type="button" 
                class="btn-danger-outline" 
                @click="cancelInterview"
                style="margin-right: auto;"
            >
                Cancel Interview
            </button>
            <button type="button" class="btn-secondary" @click="close">Close</button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Saving...' : (isEditMode ? 'Save Changes' : 'Schedule Interview') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { interviewAPI, candidateAPI, vacancyAPI, userAPI, matchingAPI } from '../../services/api'

const props = defineProps({
  show: Boolean,
  candidateId: [Number, String],
  vacancyId: [Number, String],
  candidateName: String,
  vacancyTitle: String,
  interview: Object
})

const emit = defineEmits(['close', 'created', 'updated'])

const loading = ref(false)
const loadingLists = ref(false)
const error = ref('')
const candidates = ref([])
const vacancies = ref([])
const users = ref([])
const activeMatches = ref([])

const isEditMode = computed(() => !!props.interview)

const form = reactive({
  candidate_id: '',
  vacancy_id: '',
  interviewer_ids: [],
  scheduled_at: '',
  stage: 'screening',
  type: 'video',
  duration_minutes: 60,
  location: '',
  notes: ''
})

// Filter candidates: Only show those with at least one active match
const selectableCandidates = computed(() => {
    return candidates.value.filter(c => {
        return activeMatches.value.some(m => m.candidate_id === c.id)
    })
})

// Filter vacancies: Show active match vacancies for selected candidate (or all active match vacancies if no candidate selected)
const selectableVacancies = computed(() => {
    return vacancies.value.filter(v => {
        return activeMatches.value.some(m => {
            if (m.vacancy_id !== v.id) return false
            if (form.candidate_id && m.candidate_id !== form.candidate_id) return false
            return true
        })
    })
})

const fetchLists = async () => {
  if (users.value.length === 0) {
    try {
      const userRes = await userAPI.list()
      users.value = userRes.data.data || userRes.data
    } catch (e) {
      console.error('Failed to fetch users', e)
    }
  }

  // Edit mode might need lists to display names even if disabled, but mostly we rely on IDs.
  // Actually, if we are in edit mode, we might not need to fetch "active matches" for filtering if we lock the fields.
  // But let's fetch to be safe for display names.
  
  if (candidates.value.length && vacancies.value.length) return
  
  loadingLists.value = true
  try {
    const [candidatesRes, vacanciesRes, matchesRes] = await Promise.all([
      candidateAPI.list({ per_page: 100 }),
      vacancyAPI.list({ per_page: 100, status: 'open' }),
      matchingAPI.list({ per_page: 1000 })
    ])
    candidates.value = candidatesRes.data.data || candidatesRes.data
    vacancies.value = vacanciesRes.data.data || vacanciesRes.data
    
    // Filter out dismissed matches
    const allMatches = matchesRes.data.data || matchesRes.data || []
    activeMatches.value = allMatches.filter(m => m.status !== 'dismissed')
    
  } catch (err) {
    console.error('Failed to fetch lists:', err)
  } finally {
    loadingLists.value = false
  }
}

// Reset/Fill form
watch(() => props.show, async (newVal) => {
  if (newVal) {
    error.value = ''
    
    if (props.interview) {
        // Edit Mode
        const i = props.interview
        form.candidate_id = i.candidate_id
        form.vacancy_id = i.vacancy_id
        form.interviewer_ids = i.interviewer_ids || []
        // scheduled_at comes as ISO string usually, input needs YYYY-MM-DDThh:mm
        form.scheduled_at = i.scheduled_at ? new Date(i.scheduled_at).toISOString().slice(0, 16) : ''
        form.stage = i.stage
        form.type = i.type
        form.duration_minutes = i.duration_minutes
        form.location = i.location
        form.notes = i.notes || ''
    } else {
        // Create Mode
        form.stage = 'screening'
        form.type = 'video'
        form.duration_minutes = 60
        form.location = ''
        form.notes = ''
        form.interviewer_ids = []
        
        form.candidate_id = props.candidateId || ''
        form.vacancy_id = props.vacancyId || ''

        const tomorrow = new Date()
        tomorrow.setDate(tomorrow.getDate() + 1)
        tomorrow.setHours(10, 0, 0, 0)
        const offset = tomorrow.getTimezoneOffset() * 60000
        form.scheduled_at = new Date(tomorrow - offset).toISOString().slice(0, 16)
    }

    await fetchLists()
  }
})

const close = () => {
  emit('close')
}

const submit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const payload = { ...form }
    
    if (isEditMode.value) {
        await interviewAPI.update(props.interview.id, payload)
        emit('updated')
    } else {
        // Ensure props fallback
        payload.candidate_id = form.candidate_id || props.candidateId
        payload.vacancy_id = form.vacancy_id || props.vacancyId
        await interviewAPI.create(payload)
        emit('created')
    }
    close()
  } catch (err) {
    console.error('Failed to save interview:', err)
    error.value = err.response?.data?.message || 'Failed to save interview'
  } finally {
    loading.value = false
  }
}

const cancelInterview = async () => {
    if (!confirm('Are you sure you want to cancel this interview?')) return
    
    loading.value = true
    try {
        await interviewAPI.update(props.interview.id, { status: 'cancelled' })
        emit('updated')
        close()
    } catch (err) {
        console.error('Failed to cancel interview:', err)
        error.value = 'Failed to cancel interview'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
/* ... existing styles ... */
.multi-select-container {
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  max-height: 150px;
  overflow-y: auto;
  background-color: #f8fafc;
}

.checkbox-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.checkbox-item:last-child {
  margin-bottom: 0;
}

.checkbox-item input {
  width: auto;
  margin-right: 0.75rem;
}

.checkbox-item label {
  margin-bottom: 0;
  font-weight: normal;
  cursor: pointer;
}

/* Copy previous styles to ensure full file integrity or just rely on diff block if robust enough */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
/* ... rest of styles truncated for brevity in replace block if possible, but for replace_file_content we need full block if replacing full file or context. Logic above implies replace of entire file or block. Let's do block replace. */
/* Using block replace Strategy */
</style>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

@keyframes slideIn {
  from { transform: translateY(20px) scale(0.95); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #1a202c;
  font-weight: 700;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #94a3b8;
  transition: color 0.2s;
  padding: 0.25rem;
  line-height: 1;
}

.close-btn:hover {
  color: #1a202c;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.context-info {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid #667eea;
}

.context-info p {
  margin: 0.25rem 0;
  color: #4a5568;
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
  font-weight: 600;
  color: #4a5568;
  font-size: 0.9rem;
}

input, select, textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: #f8fafc;
  color: #1a202c;
}

input:focus, select:focus, textarea:focus {
  border-color: #667eea;
  background-color: white;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
  border: 1px solid #fca5a5;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f1f5f9;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #4a5568;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e0;
}

.btn-danger-outline {
    background: white;
    color: #ef4444;
    border: 1px solid #fecaca;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger-outline:hover {
    background: #fef2f2;
    border-color: #ef4444;
}
</style>
