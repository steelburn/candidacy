<template>
  <div class="ai-assistance-section">
    <h3>✨ AI Generation Assistance</h3>
    <div class="form-row">
      <div class="form-group">
        <label>Keywords (comma-separated)</label>
        <input v-model="keywords" type="text" placeholder="e.g. backend, microservices, cloud" />
      </div>
      
      <div class="form-group">
        <label>Expectations/Skills (comma-separated)</label>
        <input v-model="skills" type="text" placeholder="e.g. 5+ years, Python, Docker" />
      </div>
    </div>
    <button type="button" @click="generateDescription" class="btn-ai" :disabled="!vacancy.title || generating">
      {{ generating ? '⏳ Generating...' : '✨ Generate Description with AI' }}
    </button>
    <div v-if="error" class="error-msg">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { aiAPI } from '../../../services/api'

const props = defineProps({
  vacancy: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['generated'])

const keywords = ref('')
const skills = ref('')
const generating = ref(false)
const error = ref('')

const generateDescription = async () => {
  if (!props.vacancy.title) {
    alert('Please enter a job title first')
    return
  }
  
  generating.value = true
  error.value = ''
  
  try {
    const keywordList = keywords.value 
      ? keywords.value.split(',').map(k => k.trim()).filter(k => k)
      : []
    
    const skillList = skills.value
      ? skills.value.split(',').map(s => s.trim()).filter(s => s)
      : []
    
    // Call AI service
    const response = await aiAPI.generateJD({
      title: props.vacancy.title,
      department: props.vacancy.department || 'General',
      location: props.vacancy.location,
      work_mode: props.vacancy.work_mode,
      type: props.vacancy.employment_type,
      level: props.vacancy.experience_level,
      skills: [...keywordList, ...skillList] 
    })
    
    emit('generated', response.data.job_description)
  } catch (err) {
    console.error('AI generation error:', err)
    error.value = 'Failed to generate job description. Please try again.'
  } finally {
    generating.value = false
  }
}
</script>

<style scoped>
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #4a5568;
}

input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #c7d2fe;
  border-radius: 6px;
  font-size: 1rem;
}

input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.btn-ai {
  width: 100%;
  margin-top: 0.5rem;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.2s;
}

.btn-ai:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-msg {
    color: #c33;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}
</style>
