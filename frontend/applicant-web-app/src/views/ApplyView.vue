<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import QuestionRenderer from '../components/QuestionRenderer.vue'

const route = useRoute()
const router = useRouter()
const vacancyId = route.params.id

const form = ref({
  name: '',
  email: '',
  phone: '',
  cv_file: null
})

const questions = ref([])
const answers = ref({})
const loading = ref(false)
const fetchingQuestions = ref(true)
const error = ref('')

onMounted(async () => {
    try {
        const res = await api.get(`/vacancies/${vacancyId}`)
        // Check structure, might be res.data or res.data.data
        const vacancy = res.data.data || res.data
        if (vacancy.questions) {
            questions.value = vacancy.questions
        }
    } catch (e) {
        console.error("Failed to load vacancy details", e)
    } finally {
        fetchingQuestions.value = false
    }
})

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
             loading.value = false
             return
        }
        throw err
    }

    if (!candidateId) throw new Error("Failed to create candidate profile")

    // 2. Apply for Vacancy with Answers
    // Format answers
    const formattedAnswers = Object.keys(answers.value).map(qid => ({
        question_id: parseInt(qid),
        answer: answers.value[qid]
    }))

    await api.post('/matches/apply', {
        candidate_id: candidateId,
        vacancy_id: vacancyId,
        answers: formattedAnswers
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
  <div class="container animate-in">
    <div class="form-card card">
      <h2 class="text-gradient">Apply for Position</h2>
      <p class="subtitle">Please fill in your details to apply.</p>

      <form @submit.prevent="submitApplication" class="apply-form">
        <!-- Personal Details -->
        <div class="section">
            <h3>Personal Information</h3>
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
        </div>

        <!-- Screening Questions -->
        <div v-if="questions.length > 0" class="section">
            <h3>Screening Questions</h3>
            <QuestionRenderer :questions="questions" v-model="answers" />
        </div>

        <div v-if="error" class="error-msg">
            {{ error }}
        </div>

        <div class="actions">
             <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Submitting...' : 'Submit Application' }}
            </button>
        </div>
       
      </form>
    </div>
  </div>
</template>

<style scoped>
.form-card {
  max-width: 800px;
  margin: 4rem auto;
}

.subtitle {
  color: var(--text-muted);
  margin-bottom: 2rem;
}

.apply-form {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.section h3 {
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
    color: var(--primary-start);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.form-group label {
  font-weight: 500;
  color: #cbd5e1;
}

.error-msg {
  color: var(--error-color);
  background: rgba(239, 68, 68, 0.1);
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid var(--error-color);
}

.actions {
    margin-top: 1rem;
    text-align: right;
}
</style>

