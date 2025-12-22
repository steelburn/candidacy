<template>
  <div class="candidate-form">
    <h1>{{ isEdit ? 'Edit Candidate' : 'Add New Candidate' }}</h1>
    
    <form @submit.prevent="handleSubmit">
      <div v-if="!isEdit" class="auto-fill-section">
        <h3>Upload Resume</h3>
        <div class="file-upload-box">
          <input 
            type="file" 
            id="resume-upload" 
            accept=".pdf,.doc,.docx" 
            @change="handleFileSelect"
          />
          <label v-if="!cvFile" for="resume-upload" class="upload-label">
            <span>Select Resume (PDF/DOCX)</span>
          </label>
        </div>
        
        <div v-if="previewUrl && fileType === 'pdf'" class="pdf-preview">
            <h4>Resume Preview</h4>
            <iframe :src="previewUrl" width="100%" height="500px"></iframe>
            <div class="file-actions">
              <button 
                type="button" 
                @click="processResume" 
                class="btn-secondary"
                :disabled="processingResume || resumeProcessed"
              >
                {{ processingResume ? 'Processing...' : resumeProcessed ? 'Processed ✓' : 'Process Resume' }}
              </button>
              <button type="button" @click="clearFile" class="btn-text">Remove File</button>
            </div>
        </div>
        <div v-else-if="cvFile" class="file-info">
            <p>Selected: {{ cvFile.name }}</p>
            <div class="file-actions">
              <button 
                type="button" 
                @click="processResume" 
                class="btn-secondary"
                :disabled="processingResume || resumeProcessed"
              >
                {{ processingResume ? 'Processing...' : resumeProcessed ? 'Processed ✓' : 'Process Resume' }}
              </button>
              <button type="button" @click="clearFile" class="btn-text">Remove File</button>
            </div>
        </div>

        <p v-if="!cvFile" class="hint">Upload a resume and click "Process Resume" to automatically fill details below.</p>
        <div v-if="parseError" class="parse-error">{{ parseError }}</div>
      </div>
      
      <div v-if="isEdit && !cvFile" class="current-cv-status">
        <!-- In edit mode, if no new file selected, we don't show much unless we want to replace -->
        <h3>Update Resume</h3>
        <div class="file-upload-box">
             <input 
                type="file" 
                id="resume-upload-edit" 
                accept=".pdf,.doc,.docx" 
                @change="handleFileSelect"
              />
              <label for="resume-upload-edit" class="upload-label">
                <span>Upload New Resume to Replace</span>
              </label>
        </div>
      </div>

      <div v-if="isEdit && cvFile" class="pdf-preview">
        <h4>New Resume Preview</h4>
        <iframe v-if="previewUrl && fileType === 'pdf'" :src="previewUrl" width="100%" height="500px"></iframe>
        <div v-else class="file-info">
          <p>Selected: {{ cvFile.name }}</p>
        </div>
        <div class="file-actions">
          <button 
            type="button" 
            @click="processResume" 
            class="btn-secondary"
            :disabled="processingResume || resumeProcessed"
          >
            {{ processingResume ? 'Processing...' : resumeProcessed ? 'Processed ✓' : 'Process Resume' }}
          </button>
          <button type="button" @click="clearFile" class="btn-text">Remove File</button>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Name *</label>
          <input v-model="form.name" type="text" required />
        </div>
        
        <div class="form-group">
          <label>Email *</label>
          <input v-model="form.email" type="email" required />
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input v-model="form.phone" type="tel" />
        </div>
        
        <div class="form-group">
          <label>Years of Experience</label>
          <input v-model.number="form.years_of_experience" type="number" min="0" step="0.5" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>LinkedIn URL</label>
          <input v-model="form.linkedin_url" type="url" />
        </div>
        
        <div class="form-group">
          <label>GitHub URL</label>
          <input v-model="form.github_url" type="url" />
        </div>
      </div>

      <div class="form-group">
        <label>Skills (Comma separated)</label>
        <input 
          v-model="form.skills" 
          type="text" 
          placeholder="PHP, Laravel, Vue.js, MySQL" 
        />
      </div>

      <div class="form-group">
        <label>Professional Summary</label>
        <textarea v-model="form.summary" rows="4" placeholder="Brief professional summary..."></textarea>
      </div>

      <!-- Extended Details (from Auto-fill) -->
      <div v-if="parsedExperience || parsedEducation" class="extended-details">
        <div v-if="parsedExperience" class="detail-block">
          <h4>Parsed Experience</h4>
          <pre>{{ parsedExperience }}</pre>
        </div>
        <div v-if="parsedEducation" class="detail-block">
          <h4>Parsed Education</h4>
          <pre>{{ parsedEducation }}</pre>
        </div>
      </div>
      
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
import api from 'axios' // For direct API calls

const router = useRouter()
const route = useRoute()

const isEdit = ref(false)
const loading = ref(false)
const error = ref('')
const cvFile = ref(null)

const cvInput = ref(null)
const processingResume = ref(false)
const resumeProcessed = ref(false)
const parseError = ref('')
const previewUrl = ref('')
const fileType = ref('')

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
  experience: null,
  education: null
})

// Visual feedback only for complex data
const parsedExperience = ref('')
const parsedEducation = ref('')

// Step 1: Handle file selection (preview only, no API call)
const handleFileSelect = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Create preview for PDF
  if (file.type === 'application/pdf') {
      previewUrl.value = URL.createObjectURL(file)
      fileType.value = 'pdf'
  } else {
      previewUrl.value = ''
      fileType.value = 'doc'
  }
  
  // Set file for later processing
  cvFile.value = file
  // Reset processed state when new file is selected
  resumeProcessed.value = false
  parseError.value = ''
}

// Step 2: Process the resume (upload + parse)
const processResume = async () => {
  if (!cvFile.value) return
  
  processingResume.value = true
  parseError.value = ''

  try {
    const formData = new FormData()
    formData.append('file', cvFile.value)
    
    const response = await candidateAPI.parseCv(formData)
    const data = response.data
    
    // Auto-fill fields
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

    // Store complex data
    if (data.experience) {
        form.value.experience = data.experience
        parsedExperience.value = JSON.stringify(data.experience, null, 2)
    }
    if (data.education) {
        form.value.education = data.education
        parsedEducation.value = JSON.stringify(data.education, null, 2)
    }
    
    resumeProcessed.value = true
    
  } catch (err) {
    console.error('Resume processing error:', err)
    parseError.value = 'Failed to process resume. Please try again or fill details manually.'
  } finally {
    processingResume.value = false
  }
}

const clearFile = () => {
    cvFile.value = null
    previewUrl.value = ''
    fileType.value = ''
    form.value.experience = null
    form.value.education = null
    parsedExperience.value = ''
    parsedEducation.value = ''
    
    const input = document.getElementById('resume-upload')
    if (input) input.value = ''
    const inputEdit = document.getElementById('resume-upload-edit')
    if (inputEdit) inputEdit.value = ''
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const formData = new FormData()
    
    // Always include required fields
    formData.append('name', form.value.name || '')
    formData.append('email', form.value.email || '')
    
    // Include optional fields only if they have values
    if (form.value.phone) formData.append('phone', form.value.phone)
    if (form.value.linkedin_url) formData.append('linkedin_url', form.value.linkedin_url)
    if (form.value.github_url) formData.append('github_url', form.value.github_url)
    if (form.value.portfolio_url) formData.append('portfolio_url', form.value.portfolio_url)
    if (form.value.summary) formData.append('summary', form.value.summary)
    if (form.value.years_of_experience) formData.append('years_of_experience', form.value.years_of_experience)
    if (form.value.skills) formData.append('skills', form.value.skills)

    // Include complex JSON fields - ALWAYS append if they exist, even if empty arrays
    // Convert arrays to JSON strings for the backend
    if (form.value.experience !== null && form.value.experience !== undefined) {
        const expValue = Array.isArray(form.value.experience) 
            ? JSON.stringify(form.value.experience)
            : form.value.experience
        formData.append('experience', expValue)
        console.log('Appending experience:', expValue)
    }
    if (form.value.education !== null && form.value.education !== undefined) {
        const eduValue = Array.isArray(form.value.education)
            ? JSON.stringify(form.value.education)
            : form.value.education
        formData.append('education', eduValue)
        console.log('Appending education:', eduValue)
    }

    if (cvFile.value) formData.append('cv_file', cvFile.value)
    
    if (isEdit.value) {
      // Laravel doesn't parse FormData properly in PUT requests
      // Use POST with _method=PUT (method spoofing)
      formData.append('_method', 'PUT')
      await api.post(`/api/candidates/${route.params.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
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
      
      // Helper to parse JSON string or return as-is
      const parseJsonField = (field) => {
        if (!field) return null
        if (typeof field === 'string') {
          try {
            return JSON.parse(field)
          } catch {
            return field
          }
        }
        return field
      }
      
      // Parse skills to comma-separated string for input field
      let skillsValue = ''
      const parsedSkills = parseJsonField(data.skills)
      if (Array.isArray(parsedSkills)) {
        skillsValue = parsedSkills.join(', ')
      } else if (typeof parsedSkills === 'string') {
        skillsValue = parsedSkills
      }
      
      form.value = {
          name: data.name,
          email: data.email,
          phone: data.phone,
          linkedin_url: data.linkedin_url,
          github_url: data.github_url,
          portfolio_url: data.portfolio_url,
          summary: data.summary,
          years_of_experience: data.years_of_experience,
          skills: skillsValue,
          experience: parseJsonField(data.experience),
          education: parseJsonField(data.education)
      }
    } catch (err) {
      error.value = 'Failed to load candidate'
    }
  }
})
</script>

<style scoped>
.candidate-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  max-width: 800px;
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
  color: #333;
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
  font-size: 1rem;
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

.auto-fill-section {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  border: 1px dashed #667eea;
}

.auto-fill-section h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #667eea;
}

.file-upload-box {
  position: relative;
}

.file-upload-box input[type="file"] {
  display: none;
}

.upload-label {
  display: inline-block;
  background: white;
  border: 1px solid #667eea;
  color: #667eea;
  padding: 0.5rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.upload-label:hover {
  background: #667eea;
  color: white;
}

.hint {
  font-size: 0.9rem;
  color: #666;
  margin-top: 0.5rem;
}

.parse-error {
  color: #c33;
  margin-top: 0.5rem;
  font-size: 0.9rem;
}

.file-name {
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #28a745;
}

.extended-details {
  margin: 2rem 0;
  border-top: 1px solid #eee;
  padding-top: 1rem;
}

.detail-block {
  margin-bottom: 1.5rem;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
}

.detail-block h4 {
  margin-top: 0;
  margin-bottom: 0.5rem;
  font-size: 1rem;
  color: #495057;
}

.detail-block pre {
  white-space: pre-wrap;
  font-family: inherit;
  font-size: 0.9rem;
  color: #333;
  margin: 0;
  background: transparent;
  border: none;
  padding: 0;
}

.pdf-preview {
    margin-top: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 1rem;
    background: #fff;
}

.pdf-preview h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: #495057;
}

.file-info {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #e9ecef;
    border-radius: 4px;
}

.file-info p {
    margin: 0;
    color: #495057;
}

.file-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn-text {
    background: none;
    border: none;
    color: #c33;
    cursor: pointer;
    font-size: 0.9rem;
    text-decoration: underline;
    padding: 0;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-secondary:disabled {
    background: #adb5bd;
    cursor: not-allowed;
}

.current-cv-status {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid #dee2e6;
}

.current-cv-status h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #495057;
}
</style>
