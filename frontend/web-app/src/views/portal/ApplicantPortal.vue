<template>
  <div class="portal-container">
    <div class="portal-header">
      <h2>Role Application Portal</h2>
      <p>Secure Candidate Access</p>
    </div>

    <div v-if="loading" class="loading">Verifying access...</div>
    <div v-else-if="error" class="error-screen">
        <h3>Access Denied</h3>
        <p>{{ error }}</p>
    </div>
    <div v-else class="portal-content">
        <form @submit.prevent="handleSubmit">
            <!-- Profile Section -->
            <section class="portal-section">
                <h3>Your Profile</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input v-model="form.name" type="text" required />
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input v-model="form.phone" type="tel" />
                    </div>
                </div>
                <div class="form-group">
                    <label>Professional Summary</label>
                    <textarea v-model="form.summary" rows="4"></textarea>
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
                    <label>Portfolio URL</label>
                    <input v-model="form.portfolio_url" type="url" />
                </div>
            </section>

            <!-- Questions Section -->
            <section v-if="questions.length > 0" class="portal-section">
                <h3>Screening Questions</h3>
                <p class="section-hint">Please answer the following questions for this role.</p>
                
                <div v-for="q in questions" :key="q.id" class="question-block">
                    <label>{{ q.question_text }} *</label>
                    
                    <div v-if="q.question_type === 'text'">
                        <textarea v-model="answers[q.id]" rows="3" required></textarea>
                    </div>
                    
                    <div v-else-if="q.question_type === 'boolean'">
                        <select v-model="answers[q.id]" required>
                            <option :value="null" disabled>Select an option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div v-else-if="q.question_type === 'multiple_choice'">
                         <input v-model="answers[q.id]" type="text" placeholder="Your Answer" required />
                    </div>
                </div>
            </section>

            <div v-if="submitError" class="error-msg">{{ submitError }}</div>
            <div v-if="submitSuccess" class="success-screen">
                <h3>Application Updated!</h3>
                <p>Thank you for submitting your details. Our team will review them shortly.</p>
            </div>

            <div v-else class="form-actions">
                <button type="submit" :disabled="submitting" class="btn-submit">
                    {{ submitting ? 'Submitting...' : 'Submit Application' }}
                </button>
            </div>
        </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { candidateAPI, vacancyAPI } from '../../services/api'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const submitting = ref(false)
const submitError = ref('')
const submitSuccess = ref(false)

const candidate = ref({})
const questions = ref([])
const answers = ref({})
const vacancyId = ref(null)

const form = ref({
    name: '',
    phone: '',
    summary: '',
    linkedin_url: '',
    github_url: '',
    portfolio_url: ''
})

onMounted(async () => {
    const token = route.params.token
    if (!token) {
        error.value = "Missing access token"
        loading.value = false
        return
    }

    try {
        const res = await candidateAPI.validateToken(token)
        candidate.value = res.data.candidate
        vacancyId.value = res.data.vacancy_id
        
        // Populate profile form
        form.value = {
            name: candidate.value.name || '',
            phone: candidate.value.phone || '',
            summary: candidate.value.summary || '',
            linkedin_url: candidate.value.linkedin_url || '',
            github_url: candidate.value.github_url || '',
            portfolio_url: candidate.value.portfolio_url || ''
        }
        
        // Load existing answers if any
        if (res.data.answers) {
            res.data.answers.forEach(a => {
                answers.value[a.question_id] = a.answer
            })
        }

        // Load Questions if vacancy attached
        if (vacancyId.value) {
            await loadQuestions(vacancyId.value)
        }

    } catch (e) {
        error.value = "Invalid or expired link. Please contact HR."
    } finally {
        loading.value = false
    }
})

const loadQuestions = async (vid) => {
    try {
        const qRes = await vacancyAPI.getQuestions(vid)
        questions.value = qRes.data
    } catch (e) {
        console.error("Failed to load questions", e)
    }
}

const handleSubmit = async () => {
    submitting.value = true
    submitError.value = ''
    
    try {
        // Format answers
        const formattedAnswers = Object.keys(answers.value).map(qid => ({
            question_id: parseInt(qid),
            answer: answers.value[qid]
        }))

        await candidateAPI.submitAnswers(route.params.token, {
            candidate: form.value,
            answers: formattedAnswers
        })
        
        submitSuccess.value = true
    } catch (e) {
        submitError.value = "Failed to submit application. Please try again."
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped>
.portal-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
    font-family: 'Inter', sans-serif;
}

.portal-header {
    text-align: center;
    margin-bottom: 2rem;
}

.portal-header h2 {
    color: #333;
    margin-bottom: 0.5rem;
}

.portal-header p {
    color: #666;
    margin: 0;
}

.portal-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.portal-section h3 {
    margin-top: 0;
    color: #667eea;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #444;
}

input, textarea, select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

input:focus, textarea:focus, select:focus {
    border-color: #667eea;
    outline: none;
}

.question-block {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px dashed #eee;
}

.question-block:last-child {
    border-bottom: none;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 3rem;
    border: none;
    border-radius: 6px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: transform 0.1s;
}

.btn-submit:hover {
    transform: translateY(-1px);
}

.btn-submit:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.error-screen {
    text-align: center;
    background: #fee;
    padding: 2rem;
    border-radius: 8px;
    color: #c33;
}

.success-screen {
    text-align: center;
    background: #d4edda;
    padding: 3rem;
    border-radius: 8px;
    color: #155724;
}

.section-hint {
    color: #666;
    margin-bottom: 1.5rem;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
