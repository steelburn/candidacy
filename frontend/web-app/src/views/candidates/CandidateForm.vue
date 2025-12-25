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
        <!-- In edit mode, if no new file selected, show existing if available -->
        <h3>Current Resume</h3>
        
        <div v-if="existingCvUrl" class="pdf-preview existing-preview">
            <iframe :src="existingCvUrl" width="100%" height="500px"></iframe>
             <div class="file-actions">
               <a :href="existingCvUrl" target="_blank" class="btn-text">Download / Open in New Tab</a>
            </div>
        </div>
        <div v-else class="no-cv-message">
            <p>No resume uploaded for this candidate.</p>
        </div>

        <div class="file-upload-box mt-4">
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

      <div v-if="isEdit" class="form-group">
        <label>Status</label>
        <select v-model="form.status" class="form-control">
          <option value="draft">Draft</option>
          <option value="new">New</option>
          <option value="reviewing">Reviewing</option>
          <option value="shortlisted">Shortlisted</option>
          <option value="interviewed">Interviewed</option>
          <option value="offered">Offered</option>
          <option value="hired">Hired</option>
          <option value="rejected">Rejected</option>
        </select>
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

      <!-- Work Experience Section -->
      <div class="form-section">
        <div class="section-header">
            <h3>Work Experience</h3>
            <button type="button" @click="addExperience" class="btn-sm btn-secondary">+ Add Position</button>
        </div>
        
        <div v-for="(exp, index) in form.experience" :key="index" class="dynamic-group">
            <div class="group-header">
                <h4>Position {{ index + 1 }}</h4>
                <button type="button" @click="removeExperience(index)" class="btn-icon text-danger" title="Remove">✖</button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Job Title</label>
                    <input v-model="exp.title" type="text" placeholder="e.g. Senior Developer" />
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input v-model="exp.company" type="text" placeholder="e.g. Tech Corp" />
                </div>
            </div>
            <div class="form-group">
                <label>Duration</label>
                <input v-model="exp.duration" type="text" placeholder="e.g. Jan 2020 - Present" />
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea v-model="exp.description" rows="3" placeholder="Key responsibilities and achievements..."></textarea>
            </div>
        </div>
        <div v-if="!form.experience || form.experience.length === 0" class="empty-state">
            <p>No work experience added.</p>
        </div>
      </div>

      <!-- Education Section -->
      <div class="form-section">
        <div class="section-header">
            <h3>Education</h3>
            <button type="button" @click="addEducation" class="btn-sm btn-secondary">+ Add Education</button>
        </div>
        
        <div v-for="(edu, index) in form.education" :key="index" class="dynamic-group">
            <div class="group-header">
                <h4>Education {{ index + 1 }}</h4>
                <button type="button" @click="removeEducation(index)" class="btn-icon text-danger" title="Remove">✖</button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Degree / Certificate</label>
                    <input v-model="edu.degree" type="text" placeholder="e.g. BSc Computer Science" />
                </div>
                <div class="form-group">
                    <label>Institution</label>
                    <input v-model="edu.institution" type="text" placeholder="e.g. University of Technology" />
                </div>
            </div>
            <div class="form-group">
                <label>Year</label>
                <input v-model="edu.year" type="text" placeholder="e.g. 2018" />
            </div>
        </div>
        <div v-if="!form.education || form.education.length === 0" class="empty-state">
            <p>No education added.</p>
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
import { candidateAPI, default as api } from '../../services/api'
// import api from 'axios' - REMOVED: using configured api instance

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
const existingCvUrl = ref('')
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
  education: null,
  status: 'new'
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
    
    // Call parse-cv endpoint (now returns immediately with job_id)
    const response = await candidateAPI.parseCv(formData)
    const data = response.data
    
    // Check if async response (202 with job_id)
    if (response.status === 202 && data.job_id) {
      // Poll for results
      await pollForParsingResults(data.job_id)
    } else {
      // Legacy sync response (shouldn't happen now, but handle it)
      applyParsedData(data)
      resumeProcessed.value = true
    }
    
  } catch (err) {
    console.error('Resume processing error:', err)
    parseError.value = 'Failed to process resume. Please try again or fill details manually.'
  } finally {
    processingResume.value = false
  }
}

// Dynamic Field Helpers
const addExperience = () => {
    if (!form.value.experience) form.value.experience = []
    form.value.experience.push({ title: '', company: '', duration: '', description: '' })
}

const removeExperience = (index) => {
    form.value.experience.splice(index, 1)
}

const addEducation = () => {
    if (!form.value.education) form.value.education = []
    form.value.education.push({ degree: '', institution: '', year: '' })
}

const removeEducation = (index) => {
    form.value.education.splice(index, 1)
}

// Poll for parsing results
const pollForParsingResults = async (jobId) => {
  const maxAttempts = 60 // 60 attempts
  const pollInterval = 2000 // 2 seconds
  
  for (let attempt = 0; attempt < maxAttempts; attempt++) {
    try {
      const statusResponse = await api.get(`/api/candidates/cv-parsing/${jobId}/status`)
      const status = statusResponse.data.status
      
      if (status === 'completed') {
        // Fetch the result
        const resultResponse = await api.get(`/api/candidates/cv-parsing/${jobId}/result`)
        let parsedData = resultResponse.data.parsed_data
        
        // Fallback: if parsed_data is null but raw_response exists, try to parse it
        if (!parsedData && resultResponse.data.raw_response) {
          try {
            // Strip comments from JSON
            let jsonString = resultResponse.data.raw_response
            jsonString = jsonString.replace(/\/\/[^\n]*/g, '') // Remove // comments
            jsonString = jsonString.replace(/\/\*.*?\*\//gs, '') // Remove /* */ comments
            jsonString = jsonString.replace(/,\s*([}\]])/g, '$1') // Remove trailing commas
            parsedData = JSON.parse(jsonString)
          } catch (e) {
            console.error('Failed to parse raw_response:', e)
          }
        }
        
        if (parsedData) {
          applyParsedData(parsedData)
          resumeProcessed.value = true
        } else {
          throw new Error('No valid parsed data received')
        }
        return
      } else if (status === 'failed') {
        throw new Error('AI parsing failed')
      }
      
      // Still processing, wait and retry
      await new Promise(resolve => setTimeout(resolve, pollInterval))
      
    } catch (err) {
      if (err.response?.status === 202) {
        // Still processing
        await new Promise(resolve => setTimeout(resolve, pollInterval))
      } else {
        throw err
      }
    }
  }
  
  throw new Error('Parsing timeout - please try again')
}

// Apply parsed data to form
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

  // Store complex data
  if (data.experience) {
    form.value.experience = data.experience
    parsedExperience.value = JSON.stringify(data.experience, null, 2)
  }
  if (data.education) {
    form.value.education = data.education
    parsedEducation.value = JSON.stringify(data.education, null, 2)
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
    if (form.value.years_of_experience) formData.append('years_of_experience', form.value.years_of_experience)
    if (form.value.skills) formData.append('skills', form.value.skills)
    if (form.value.status) formData.append('status', form.value.status)

    // Include complex JSON fields - ALWAYS append if they exist, even if empty arrays
    // Convert arrays to JSON strings for the backend
    if (form.value.experience !== null && form.value.experience !== undefined) {
        const expValue = Array.isArray(form.value.experience) 
            ? JSON.stringify(form.value.experience)
            : form.value.experience
        formData.append('experience', expValue)

    }
    if (form.value.education !== null && form.value.education !== undefined) {
        const eduValue = Array.isArray(form.value.education)
            ? JSON.stringify(form.value.education)
            : form.value.education
        formData.append('education', eduValue)

    }

    if (cvFile.value) formData.append('cv_file', cvFile.value)
    
    if (isEdit.value) {
      // Use helper which handles method spoofing for FormData
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
          status: data.status || 'new', // Default to new if null
          years_of_experience: data.years_of_experience,
          skills: skillsValue,
          experience: parseJsonField(data.experience),
          experience: parseJsonField(data.experience),
          education: parseJsonField(data.education)
      }

      // Check for existing CV
      if (data.cv_files && data.cv_files.length > 0) {
          existingCvUrl.value = `http://localhost:8080/api/candidates/${route.params.id}/cv/view`
      }
    } catch (err) {
      error.value = 'Failed to load candidate'
    }
  }
})
</script>

<style scoped>
/* Modern Clean UI with Glassmorphism hints */
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

h3 {
  font-size: 1.25rem;
  color: #2d3748;
  font-weight: 600;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  animation: fadeIn 0.5s ease-out;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #4a5568;
  font-size: 0.95rem;
}

input, select, textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s ease;
  background: #f8fafc;
  color: #2d3748;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
  background: white;
}

/* Section Styling */
.form-section {
  margin-top: 2.5rem;
  padding-top: 2rem;
  border-top: 1px solid #edf2f7;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  background: linear-gradient(135deg, #4a5568 0%, #718096 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Dynamic Cards */
.dynamic-group {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px rgba(0,0,0,0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dynamic-group:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px rgba(0,0,0,0.05);
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid #edf2f7;
}

.group-header h4 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
}

/* Buttons */
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
  border: 1px solid #e2e8f0;
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #cbd5e0;
  color: #2d3748;
}

.btn-icon.text-danger {
  color: #fc8181;
  font-size: 1.25rem;
  padding: 0.25rem;
  border-radius: 50%;
  transition: background 0.2s;
  background: transparent;
  border: none;
  cursor: pointer;
}

.btn-icon.text-danger:hover {
  background: #fff5f5;
  color: #e53e3e;
}

.form-actions {
  display: flex;
  gap: 1.5rem;
  margin-top: 3rem;
  align-items: center;
}

/* File Upload */
.auto-fill-section {
  background: linear-gradient(to bottom right, #f8fafc, #edf2f7);
  padding: 2rem;
  border-radius: 12px;
  border: 2px dashed #cbd5e0;
  margin-bottom: 2.5rem;
  text-align: center;
  transition: border-color 0.2s;
}

.auto-fill-section:hover {
  border-color: #667eea;
}

.upload-label {
  display: inline-block;
  background: white;
  color: #667eea;
  padding: 0.75rem 2rem;
  border-radius: 50px;
  cursor: pointer;
  font-weight: 600;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  transition: all 0.2s;
  border: 2px solid transparent;
}

.upload-label:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 12px rgba(102, 126, 234, 0.2);
}

/* Status & Extras */
.current-cv-status {
  background: #fff;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.error {
  background: #fff5f5;
  color: #c53030;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid #fc8181;
}

/* File Preview */
.pdf-preview {
  margin-top: 2rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.pdf-preview h4 {
  margin-bottom: 1rem;
  color: #2d3748;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: #f8fafc;
  border-radius: 12px;
  color: #a0aec0;
  border: 2px dashed #edf2f7;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Link as button override for styles */
a.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}



.mt-4 {
    margin-top: 1.5rem;
}
</style>
