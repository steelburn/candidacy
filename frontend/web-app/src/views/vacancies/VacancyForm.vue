<template>
  <div class="vacancy-form">
    <h1>{{ isEdit ? 'Edit Vacancy' : 'Create New Vacancy' }}</h1>
    
    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <label>Job Title *</label>
        <input v-model="form.title" type="text" required />
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Location *</label>
          <input v-model="form.location" type="text" required />
        </div>
        
        <div class="form-group">
          <label>Department</label>
          <input v-model="form.department" type="text" />
        </div>
      </div>
      
      <div class="form-group">
        <label>Work Mode</label>
        <div class="checkbox-group">
          <label class="checkbox-label">
            <input type="checkbox" value="on_site" v-model="form.work_mode" />
            <span>On-site</span>
          </label>
          <label class="checkbox-label">
            <input type="checkbox" value="remote" v-model="form.work_mode" />
            <span>Remote</span>
          </label>
          <label class="checkbox-label">
            <input type="checkbox" value="hybrid" v-model="form.work_mode" />
            <span>Hybrid</span>
          </label>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Employment Type *</label>
          <select v-model="form.employment_type" required>
            <option value="full_time">Full Time</option>
            <option value="part_time">Part Time</option>
            <option value="contract">Contract</option>
            <option value="intern">Intern</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Experience Level *</label>
          <select v-model="form.experience_level" required>
            <option value="entry">Entry</option>
            <option value="mid">Mid</option>
            <option value="senior">Senior</option>
            <option value="lead">Lead</option>
            <option value="executive">Executive</option>
          </select>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Status *</label>
          <select v-model="form.status" required>
            <option value="draft">Draft</option>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
            <option value="on_hold">On Hold</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Positions Available</label>
          <input v-model.number="form.positions_available" type="number" min="1" />
        </div>
      </div>
      
      <!-- AI Assistance Component -->
      <VacancyAiAssistance 
        :vacancy="form" 
        @generated="desc => form.description = desc" 
      />
      
      <!-- Markdown Editor for Description -->
      <div class="form-group">
        <label>Description *</label>
        <MdEditor 
          v-model="form.description" 
          language="en-US"
          :toolbars="toolbars"
          :preview="true"
          style="height: 500px;"
        />
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Min Salary</label>
          <input v-model.number="form.min_salary" type="number" />
        </div>
        
        <div class="form-group">
          <label>Max Salary</label>
          <input v-model.number="form.max_salary" type="number" />
        </div>
      </div>
      
      <div v-if="error" class="error">{{ error }}</div>
      
      <div class="form-actions">
        <button type="submit" :disabled="loading" class="btn-primary">
          {{ loading ? 'Saving...' : isEdit ? 'Update' : 'Create' }}
        </button>
        <router-link to="/vacancies" class="btn-secondary">Cancel</router-link>
      </div>
    </form>

    <!-- Questions Management (only in edit mode) -->
    <VacancyQuestions 
      v-if="isEdit"
      :vacancy-id="route.params.id"
      :vacancy-title="form.title"
      :vacancy-description="form.description"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { vacancyAPI } from '../../services/api'
import { MdEditor } from 'md-editor-v3'
import 'md-editor-v3/lib/style.css'
import VacancyAiAssistance from '../../components/vacancies/form/VacancyAiAssistance.vue'
import VacancyQuestions from '../../components/vacancies/form/VacancyQuestions.vue'

const router = useRouter()
const route = useRoute()

const isEdit = ref(false)
const loading = ref(false)
const error = ref('')

const form = ref({
  title: '',
  location: '',
  department: '',
  work_mode: [],
  employment_type: 'full_time',
  experience_level: 'mid',
  status: 'draft',
  description: '',
  min_salary: null,
  max_salary: null,
  positions_available: 1
})

const toolbars = [
  'bold', 'underline', 'italic', 'strikeThrough', '-',
  'title', 'sub', 'sup', 'quote', 'unorderedList', 'orderedList', 'task', '-',
  'codeRow', 'code', 'link', 'image', 'table', '-',
  'revoke', 'next', '=', 'pageFullscreen', 'fullscreen', 'preview', 'catalog'
]

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    if (isEdit.value) {
      await vacancyAPI.update(route.params.id, form.value)
    } else {
      await vacancyAPI.create(form.value)
    }
    
    router.push('/vacancies')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save vacancy'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (route.params.id) {
    isEdit.value = true
    loading.value = true
    
    try {
      const response = await vacancyAPI.get(route.params.id)
      Object.assign(form.value, response.data)
      if (!Array.isArray(form.value.work_mode)) {
        form.value.work_mode = []
      }
    } catch (err) {
      console.error('Failed to load vacancy:', err)
      error.value = 'Failed to load vacancy data'
    } finally {
      loading.value = false
    }
  }
})
</script>

<style scoped>
.vacancy-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  max-width: 900px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

input, select, textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  text-decoration: none;
  display: inline-block;
}

.checkbox-group {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.5rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
}
</style>
