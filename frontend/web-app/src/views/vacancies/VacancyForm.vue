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
      
      <!-- AI Assistance Fields -->
      <div class="ai-assistance-section">
        <h3>✨ AI Generation Assistance</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Keywords (comma-separated)</label>
            <input v-model="aiFields.keywords" type="text" placeholder="e.g. backend, microservices, cloud" />
          </div>
          
          <div class="form-group">
            <label>Expectations/Skills (comma-separated)</label>
            <input v-model="aiFields.skills" type="text" placeholder="e.g. 5+ years, Python, Docker" />
          </div>
        </div>
        <button type="button" @click="generateDescription" class="btn-ai" :disabled="!form.title || aiGenerating">
          {{ aiGenerating ? '⏳ Generating...' : '✨ Generate Description with AI' }}
        </button>
      </div>
      
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
      <div v-if="aiGenerating" class="info">AI is generating job description...</div>
      
      <div class="form-actions">
        <button type="submit" :disabled="loading" class="btn-primary">
          {{ loading ? 'Saving...' : isEdit ? 'Update' : 'Create' }}
        </button>
        <router-link to="/vacancies" class="btn-secondary">Cancel</router-link>
      </div>
    </form>

    <!-- Questions Management (only in edit mode) -->
    <div v-if="isEdit" class="questions-section">
      <h3>Screening Questions</h3>
      <div class="question-list" v-if="questions.length > 0">
        <div v-for="q in questions" :key="q.id" class="question-item">
          <p><strong>{{ q.question_text }}</strong> <span class="badge">{{ q.question_type }}</span></p>
        </div>
      </div>
      <p v-else class="text-muted">No screening questions added yet.</p>

      <div class="add-question-form">
        <h4>Add New Question</h4>
        <div class="form-group">
          <label>Question</label>
          <input v-model="newQuestion.text" type="text" placeholder="e.g. Why do you want to join us?" />
        </div>
        <div class="form-group">
            <label>Type</label>
            <select v-model="newQuestion.type">
                <option value="text">Text Answer</option>
                <option value="boolean">Yes/No</option>
                <option value="multiple_choice">Multiple Choice</option>
            </select>
        </div>
        <button type="button" @click="addQuestion" :disabled="addingQuestion || !newQuestion.text" class="btn-secondary">
            {{ addingQuestion ? 'Adding...' : 'Add Question' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { vacancyAPI, aiAPI } from '../../services/api'
import { MdEditor } from 'md-editor-v3'
import 'md-editor-v3/lib/style.css'

const router = useRouter()
const route = useRoute()

const isEdit = ref(false)
const loading = ref(false)
const aiGenerating = ref(false)
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

// AI assistance fields
const aiFields = ref({
  keywords: '',
  skills: ''
})

// Markdown editor toolbar configuration
const toolbars = [
  'bold',
  'underline',
  'italic',
  'strikeThrough',
  '-',
  'title',
  'sub',
  'sup',
  'quote',
  'unorderedList',
  'orderedList',
  'task',
  '-',
  'codeRow',
  'code',
  'link',
  'image',
  'table',
  '-',
  'revoke',
  'next',
  '=',
  'pageFullscreen',
  'fullscreen',
  'preview',
  'catalog'
]

const generateDescription = async () => {
  if (!form.value.title) {
    alert('Please enter a job title first')
    return
  }
  
  aiGenerating.value = true
  error.value = ''
  
  try {
    // Parse keywords and skills from comma-separated strings
    const keywords = aiFields.value.keywords 
      ? aiFields.value.keywords.split(',').map(k => k.trim()).filter(k => k)
      : []
    
    const skills = aiFields.value.skills
      ? aiFields.value.skills.split(',').map(s => s.trim()).filter(s => s)
      : []
    
    // Call AI service with enhanced data
    const response = await aiAPI.generateJD({
      title: form.value.title,
      department: form.value.department || 'General',
      location: form.value.location,
      work_mode: form.value.work_mode,
      type: form.value.employment_type,
      level: form.value.experience_level,
      skills: [...keywords, ...skills] // Combine keywords and skills
    })
    
    form.value.description = response.data.job_description
  } catch (err) {
    console.error('AI generation error:', err)
    error.value = 'Failed to generate job description. Please try again.'
  } finally {
    aiGenerating.value = false
  }
}

// Question Logic
const questions = ref([])
const addingQuestion = ref(false)
const newQuestion = ref({ text: '', type: 'text' })

const loadQuestions = async (id) => {
    try {
        const res = await vacancyAPI.getQuestions(id)
        questions.value = res.data
    } catch (e) {
        console.error("Failed to load questions", e)
    }
}

const addQuestion = async () => {
    if (!newQuestion.value.text) return
    addingQuestion.value = true
    try {
        await vacancyAPI.addQuestion(route.params.id, {
            question_text: newQuestion.value.text,
            question_type: newQuestion.value.type
        })
        newQuestion.value = { text: '', type: 'text' }
        await loadQuestions(route.params.id)
    } catch (e) {
        error.value = "Failed to add question"
    } finally {
        addingQuestion.value = false
    }
}

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
  // Check if we're in edit mode
  if (route.params.id) {
    isEdit.value = true
    loading.value = true
    
    try {
      const response = await vacancyAPI.get(route.params.id)
      // Populate form with loaded data
      Object.assign(form.value, response.data)
      // Ensure work_mode is an array
      if (!Array.isArray(form.value.work_mode)) {
        form.value.work_mode = []
      }
      // Load questions
      await loadQuestions(route.params.id)
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

.btn-ai {
  margin-top: 0.5rem;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.info {
  background: #e3f2fd;
  color: #1976d2;
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

.questions-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.question-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    border: 1px solid #e9ecef;
}

.question-item p { margin: 0; }

.badge {
    display: inline-block;
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #495057;
    margin-left: 0.5rem;
}

.add-question-form {
    margin-top: 1.5rem;
    background: #f1f3f5;
    padding: 1.5rem;
    border-radius: 8px;
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

.ai-assistance-section {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 2px solid #e0e7ff;
}

.ai-assistance-section h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #4c1d95;
  font-size: 1.1rem;
}

.ai-assistance-section .btn-ai {
  width: 100%;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  margin-top: 0;
}

.ai-assistance-section .btn-ai:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.text-muted { color: #6c757d; }
</style>
