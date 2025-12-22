<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const vacancyId = route.params.id

const form = ref({
  name: '',
  email: '',
  phone: '',
  cv_file: null
})

const loading = ref(false)
const error = ref('')

const handleFileChange = (event) => {
  form.value.cv_file = event.target.files[0]
}

const submitApplication = async () => {
  loading.value = true
  error.value = ''

  try {
    // 1. Create Candidate
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('email', form.value.email)
    formData.append('phone', form.value.phone)
    if (form.value.cv_file) {
      formData.append('cv_file', form.value.cv_file)
    }

    let candidateId = null

    try {
        const candidateResponse = await api.post('/candidates', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        candidateId = candidateResponse.data.id
    } catch (err) {
        if (err.response && err.response.status === 422 && err.response.data.errors.email) {
             error.value = "An account with this email already exists. We cannot process duplicate applications currently."
             // In a real app, we would offer login or magic link here.
             loading.value = false
             return
        }
        throw err
    }

    if (!candidateId) throw new Error("Failed to create candidate profile")

    // 2. Apply for Vacancy
    await api.post('/matches/apply', {
        candidate_id: candidateId,
        vacancy_id: vacancyId
    })

    // Success
    alert('Application submitted successfully!')
    router.push({ name: 'home' })

  } catch (err) {
    console.error(err)
    error.value = 'Failed to submit application. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container">
    <div class="form-card card">
      <h2>Apply for Position</h2>
      <p class="subtitle">Please fill in your details to apply.</p>

      <form @submit.prevent="submitApplication" class="apply-form">
        <div class="form-group">
          <label>Full Name</label>
          <input v-model="form.name" type="text" required placeholder="John Doe" />
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input v-model="form.email" type="email" required placeholder="john@example.com" />
        </div>

        <div class="form-group">
          <label>Phone Number</label>
          <input v-model="form.phone" type="tel" placeholder="+1 234 567 890" />
        </div>

        <div class="form-group">
          <label>Resume (PDF/Doc)</label>
          <input @change="handleFileChange" type="file" required accept=".pdf,.doc,.docx" />
        </div>

        <div v-if="error" class="error-msg">
            {{ error }}
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Submitting...' : 'Submit Application' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.form-card {
  max-width: 600px;
  margin: 4rem auto;
}

.subtitle {
  color: #94a3b8;
  margin-bottom: 2rem;
}

.apply-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #cbd5e1;
}

.form-group input {
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  color: white;
  font-size: 1rem;
}

.form-group input:focus {
  outline: 2px solid var(--primary-color);
  border-color: transparent;
}

.error-msg {
  color: var(--error-color);
  background: rgba(239, 68, 68, 0.1);
  padding: 1rem;
  border-radius: 0.5rem;
}
</style>
