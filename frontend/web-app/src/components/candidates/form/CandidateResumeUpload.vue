<template>
  <div>
    <!-- New Upload Section (Visible if not editing, or if editing but replacing) -->
    <div v-if="!isEdit || (isEdit && !hasExistingCv && !cvFile) || (isEdit && cvFile)" class="auto-fill-section" :class="{ 'compact': isEdit }">
      <h3 v-if="!isEdit">Upload Resume</h3>
      <h4 v-else>New Resume</h4>
      
      <!-- Upload Box -->
      <div v-if="!cvFile" class="file-upload-box">
        <input 
          type="file" 
          id="resume-upload" 
          accept=".pdf,.doc,.docx" 
          @change="handleFileSelect"
        />
        <label for="resume-upload" class="upload-label">
          <span>Select Resume (PDF/DOCX)</span>
        </label>
      </div>
      
      <!-- Preview & Actions -->
      <div v-if="cvFile" class="preview-container">
         <div v-if="previewUrl && fileType === 'pdf'" class="pdf-preview">
            <iframe :src="previewUrl" width="100%" height="500px"></iframe>
         </div>
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

      <p v-if="!cvFile && !isEdit" class="hint">Upload a resume and click "Process Resume" to automatically fill details below.</p>
      <div v-if="parseError" class="parse-error">{{ parseError }}</div>
    </div>

    <!-- Existing CV Display (Edit Mode Only) -->
    <div v-if="isEdit && !cvFile && hasExistingCv" class="current-cv-status">
      <h3>Current Resume</h3>
      
      <div class="pdf-preview existing-preview">
          <iframe :src="existingCvUrl" width="100%" height="500px"></iframe>
           <div class="file-actions">
             <a :href="existingCvUrl" target="_blank" class="btn-text">Download / Open in New Tab</a>
          </div>
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { candidateAPI, default as api } from '../../../services/api'

const props = defineProps({
  isEdit: {
    type: Boolean,
    default: false
  },
  existingCvUrl: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['file-selected', 'parsed-data', 'clear-file'])

const cvFile = ref(null)
const previewUrl = ref('')
const fileType = ref('')
const processingResume = ref(false)
const resumeProcessed = ref(false)
const parseError = ref('')

const hasExistingCv = computed(() => !!props.existingCvUrl)

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (file.type === 'application/pdf') {
      previewUrl.value = URL.createObjectURL(file)
      fileType.value = 'pdf'
  } else {
      previewUrl.value = ''
      fileType.value = 'doc'
  }
  
  cvFile.value = file
  resumeProcessed.value = false
  parseError.value = ''
  
  emit('file-selected', file)
}

const clearFile = () => {
    cvFile.value = null
    previewUrl.value = ''
    fileType.value = ''
    resumeProcessed.value = false
    parseError.value = ''
    
    // Reset inputs
    const input = document.getElementById('resume-upload')
    if (input) input.value = ''
    const inputEdit = document.getElementById('resume-upload-edit')
    if (inputEdit) inputEdit.value = ''
    
    emit('clear-file')
}

const processResume = async () => {
  if (!cvFile.value) return
  
  processingResume.value = true
  parseError.value = ''

  try {
    const formData = new FormData()
    formData.append('file', cvFile.value)
    
    // Call parse-cv endpoint
    const response = await candidateAPI.parseCv(formData)
    const data = response.data
    
    if (response.status === 202 && data.job_id) {
      // Poll for results
      await pollForParsingResults(data.job_id)
    } else {
      // Legacy sync response
      emit('parsed-data', data)
      resumeProcessed.value = true
    }
  } catch (err) {
    console.error('Resume processing error:', err)
    parseError.value = 'Failed to process resume. Please try again or fill details manually.'
  } finally {
    processingResume.value = false
  }
}

const pollForParsingResults = async (jobId) => {
  const maxAttempts = 60
  const pollInterval = 2000
  
  for (let attempt = 0; attempt < maxAttempts; attempt++) {
    try {
      const statusResponse = await api.get(`/api/candidates/cv-parsing/${jobId}/status`)
      const status = statusResponse.data.status
      
      if (status === 'completed') {
        const resultResponse = await api.get(`/api/candidates/cv-parsing/${jobId}/result`)
        let parsedData = resultResponse.data.parsed_data
        
        // Fallback parsing logic
        if (!parsedData && resultResponse.data.raw_response) {
          try {
            let jsonString = resultResponse.data.raw_response
            // Basic cleanup
            jsonString = jsonString.replace(/\/\/[^\n]*/g, '')
            jsonString = jsonString.replace(/\/\*.*?\*\//gs, '')
            jsonString = jsonString.replace(/,\s*([}\]])/g, '$1')
            parsedData = JSON.parse(jsonString)
          } catch (e) {
            console.error('Failed to parse raw_response:', e)
          }
        }
        
        if (parsedData) {
          emit('parsed-data', parsedData)
          resumeProcessed.value = true
        } else {
           throw new Error('No valid parsed data received')
        }
        return
      } else if (status === 'failed') {
        throw new Error('AI parsing failed')
      }
      
      await new Promise(resolve => setTimeout(resolve, pollInterval))
    } catch (err) {
      if (err.response?.status === 202) {
        await new Promise(resolve => setTimeout(resolve, pollInterval))
      } else {
        throw err
      }
    }
  }
  throw new Error('Parsing timeout')
}

defineExpose({ clearFile })
</script>

<style scoped>
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

.auto-fill-section.compact {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
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

.file-upload-box {
    position: relative;
    margin: 1rem 0;
}

.file-upload-box input[type="file"] {
    display: none; 
}

.preview-container {
    margin-top: 2rem;
}

.pdf-preview {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1rem;
    background: white;
}

.file-info {
    font-weight: 600;
    color: #4a5568;
    margin: 1rem 0;
}

.file-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1rem;
}

.current-cv-status {
  background: #fff;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
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

.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #cbd5e0;
  color: #2d3748;
}

.btn-text {
    background: none;
    border: none;
    color: #e53e3e;
    cursor: pointer;
    text-decoration: underline;
    font-size: 0.9rem;
}

.hint {
    color: #718096;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.parse-error {
    color: #e53e3e;
    margin-top: 1rem;
    background: #fff5f5;
    padding: 0.5rem;
    border-radius: 4px;
}

.mt-4 { margin-top: 1rem; }
</style>
