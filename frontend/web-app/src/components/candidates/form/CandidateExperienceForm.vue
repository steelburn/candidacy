<template>
  <div class="form-section">
    <div class="section-header">
        <h3>Work Experience</h3>
        <button type="button" @click="addExperience" class="btn-sm btn-secondary">+ Add Position</button>
    </div>
    
    <div v-for="(exp, index) in localExperience" :key="index" class="dynamic-group">
        <div class="group-header">
            <h4>Position {{ index + 1 }}</h4>
            <button type="button" @click="removeExperience(index)" class="btn-icon text-danger" title="Remove">✖</button>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Job Title</label>
                <input v-model="exp.title" type="text" placeholder="e.g. Senior Developer" @input="update" />
            </div>
            <div class="form-group">
                <label>Company</label>
                <input v-model="exp.company" type="text" placeholder="e.g. Tech Corp" @input="update" />
            </div>
        </div>
        <div class="form-group">
            <label>Duration</label>
            <input v-model="exp.duration" type="text" placeholder="e.g. Jan 2020 - Present" @input="update" />
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea v-model="exp.description" rows="3" placeholder="Key responsibilities and achievements..." @input="update"></textarea>
        </div>
    </div>
    
    <div v-if="localExperience.length === 0" class="empty-state">
        <p>No work experience added.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  experience: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:experience'])

const localExperience = ref([])

// Sync local state with prop
watch(() => props.experience, (newVal) => {
    if (newVal) localExperience.value = newVal
}, { immediate: true, deep: true })

const update = () => {
    emit('update:experience', localExperience.value)
}

const addExperience = () => {
    localExperience.value.push({ title: '', company: '', duration: '', description: '' })
    update()
}

const removeExperience = (index) => {
    localExperience.value.splice(index, 1)
    update()
}
</script>

<style scoped>
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
  font-size: 1.25rem;
  font-weight: 600;
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

.btn-secondary:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
  color: #2d3748;
}

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
  color: #4a5568;
  font-size: 0.95rem;
}

input, textarea {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s ease;
  background: #f8fafc;
  color: #2d3748;
}

input:focus, textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
  background: white;
}

.empty-state {
    color: #a0aec0;
    text-align: center;
    padding: 1rem;
    border: 1px dashed #e2e8f0;
    border-radius: 8px;
}
</style>
