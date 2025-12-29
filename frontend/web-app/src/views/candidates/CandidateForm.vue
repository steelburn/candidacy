<template>
  <div class="candidate-form">
    <h1>{{ isEdit ? 'Edit Candidate' : 'Add New Candidate' }}</h1>
    
    <form @submit.prevent="handleSubmit">
      <CandidateResumeUpload 
        ref="resumeUploadRef"
        :is-edit="isEdit"
        :existing-cv-url="existingCvUrl"
        @file-selected="handleFileSelected"
        @parsed-data="applyParsedData"
        @clear-file="handleClearFile"
      />

      <CandidatePersonalForm 
        :form="form" 
        :is-edit="isEdit" 
      />
      
      <CandidateExperienceForm 
        :experience="form.experience || []" 
        @update:experience="val => form.experience = val" 
      />
      
      <CandidateEducationForm 
        :education="form.education || []" 
        @update:education="val => form.education = val" 
      />
      
      <div v-if="error" class="error">{{ error }}</div>
      
      <div class="form-actions">
        <button type="submit" :disabled="loading" class="btn-primary">
          {{ loading ? 'Saving...' : isEdit ? 'Update' : 'Create' }}
        </button>
        <router-link to="/candidates" class="btn-secondary">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { candidateAPI } from '../../services/api'
import CandidateResumeUpload from '../../components/candidates/form/CandidateResumeUpload.vue'
import CandidatePersonalForm from '../../components/candidates/form/CandidatePersonalForm.vue'
import CandidateExperienceForm from '../../components/candidates/form/CandidateExperienceForm.vue'
import CandidateEducationForm from '../../components/candidates/form/CandidateEducationForm.vue'

const router = useRouter()
const route = useRoute()

const isEdit = ref(false)
const loading = ref(false)
const error = ref('')
const cvFile = ref(null)
const existingCvUrl = ref('')
const resumeUploadRef = ref(null)

const form = ref({
  name: '',
  email: '',
  phone: '',
  linkedin_url: '',
  github_url: '',
  portfolio_url: '',
  summary: '',
  skills: '',
  years_of_experience: null,
  experience: [],
  education: [],
  status: 'new'
})

// File Handling
const handleFileSelected = (file) => {
    cvFile.value = file
}

const handleClearFile = () => {
    cvFile.value = null
    // Also clear auto-filled fields if user wants to remove file?
    // Usually yes, if it was auto-filled. But maybe user edited manually.
    // Let's reset complex fields but keep basic ones? Or reset all?
    // Original code reset experience/education.
    form.value.experience = []
    form.value.education = []
}

// parsing result from child component
const applyParsedData = (data) => {
  if (data.name) form.value.name = data.name
  if (data.email) form.value.email = data.email
  if (data.phone) form.value.phone = data.phone
  if (data.linkedin_url) form.value.linkedin_url = data.linkedin_url
  if (data.github_url) form.value.github_url = data.github_url
  if (data.portfolio_url) form.value.portfolio_url = data.portfolio_url
  if (data.summary) form.value.summary = data.summary
  if (data.years_of_experience) form.value.years_of_experience = data.years_of_experience
  
  if (data.skills) {
    if (Array.isArray(data.skills)) {
      form.value.skills = data.skills.join(', ')
    } else if (typeof data.skills === 'string') {
      form.value.skills = data.skills
    }
  }

  if (data.experience) {
    form.value.experience = data.experience
  }
  if (data.education) {
    form.value.education = data.education
  }
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const formData = new FormData()
    
    formData.append('name', form.value.name || '')
    formData.append('email', form.value.email || '')
    
    if (form.value.phone) formData.append('phone', form.value.phone)
    if (form.value.linkedin_url) formData.append('linkedin_url', form.value.linkedin_url)
    if (form.value.github_url) formData.append('github_url', form.value.github_url)
    if (form.value.portfolio_url) formData.append('portfolio_url', form.value.portfolio_url)
    if (form.value.summary) formData.append('summary', form.value.summary)
    if (form.value.years_of_experience) formData.append('years_of_experience', form.value.years_of_experience)
    if (form.value.skills) formData.append('skills', form.value.skills)
    if (form.value.status) formData.append('status', form.value.status)

    // Handle JSON arrays
    if (form.value.experience && form.value.experience.length > 0) {
        formData.append('experience', JSON.stringify(form.value.experience))
    }
    if (form.value.education && form.value.education.length > 0) {
        formData.append('education', JSON.stringify(form.value.education))
    }

    if (cvFile.value) formData.append('cv_file', cvFile.value)
    
    if (isEdit.value) {
      await candidateAPI.update(route.params.id, formData)
    } else {
      await candidateAPI.create(formData)
    }
    
    router.push('/candidates')
  } catch (err) {
    console.error('Error saving candidate:', err)
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Failed to save candidate'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const response = await candidateAPI.get(route.params.id)
      const data = response.data
      
      const parseJsonField = (field) => {
        if (!field) return []
        if (typeof field === 'string') {
          try {
            const parsed = JSON.parse(field)
            return Array.isArray(parsed) ? parsed : []
          } catch {
            return []
          }
        }
        return Array.isArray(field) ? field : []
      }
      
      let skillsValue = ''
      // Skills handling (some APIs return string, some JSON array)
      if (data.skills) {
          try {
             // try parse if it looks like JSON array
             if (typeof data.skills === 'string' && data.skills.startsWith('[')) {
                 const parsed = JSON.parse(data.skills)
                 skillsValue = parsed.join(', ')
             } else {
                 skillsValue = data.skills
             }
          } catch {
             skillsValue = data.skills
          }
      }
      
      form.value = {
          name: data.name,
          email: data.email,
          phone: data.phone,
          linkedin_url: data.linkedin_url,
          github_url: data.github_url,
          portfolio_url: data.portfolio_url,
          summary: data.summary,
          status: data.status || 'new',
          years_of_experience: data.years_of_experience,
          skills: skillsValue,
          experience: parseJsonField(data.experience),
          education: parseJsonField(data.education)
      }

      if (data.cv_files && data.cv_files.length > 0) {
          existingCvUrl.value = `http://localhost:8080/api/candidates/${route.params.id}/cv/download`
      }
    } catch (err) {
      error.value = 'Failed to load candidate'
    }
  }
})
</script>

<style scoped>
.candidate-form {
  background: rgba(255, 255, 255, 0.95);
  padding: 2.5rem;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
  max-width: 900px;
  margin: 0 auto;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 2rem;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-align: center;
}

.error {
    color: #e53e3e;
    margin: 1rem 0;
    padding: 0.75rem;
    background: #fff5f5;
    border-radius: 8px;
    border: 1px solid #fed7d7;
}

.form-actions {
  display: flex;
  gap: 1.5rem;
  margin-top: 3rem;
  align-items: center;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.875rem 2.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #4a5568;
  padding: 0.875rem 2.5rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.btn-secondary:hover {
  background: #f7fafc;
  color: #2d3748;
  border-color: #cbd5e0;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  text-decoration: none;
}
</style>
