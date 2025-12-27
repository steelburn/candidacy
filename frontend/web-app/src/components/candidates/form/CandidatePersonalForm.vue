<template>
  <div>
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

      <div class="form-row">
          <div class="form-group">
            <label>Portfolio URL</label>
            <input v-model="form.portfolio_url" type="url" />
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
  </div>
</template>

<script setup>
const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
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

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
