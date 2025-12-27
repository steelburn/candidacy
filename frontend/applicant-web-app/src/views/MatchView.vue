<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import QuestionRenderer from '../components/QuestionRenderer.vue'

const router = useRouter()
const file = ref(null)
const loading = ref(false)
const processingStatus = ref('')
const matches = ref([])
const candidateId = ref(null)
const step = ref(1) // 1: Upload, 2: Review, 3: Results
const error = ref('')

// Form Validation
const name = ref('')
const email = ref('')
const phone = ref('')
const summary = ref('')

// Question Modal Logic
const showQuestionModal = ref(false)
const currentVacancy = ref(null)
const answers = ref({})
const submittingAnswers = ref(false)

const handleFileChange = (e) => {
    file.value = e.target.files[0]
}

const parseCv = async () => {
    if (!file.value) {
        error.value = "Please select a file first."
        return
    }
    
    loading.value = true
    processingStatus.value = "Uploading and Queuing..."
    error.value = ''

    try {
        const formData = new FormData()
        formData.append('file', file.value)
        
        // Step 1: Upload and Start Job
        const res = await api.post('/candidates/parse-cv', formData, {
             headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        const jobId = res.data.job_id
        processingStatus.value = "Analyzing your resume (this may take a minute)..."

        // Step 2: Poll for Status
        const pollInterval = setInterval(async () => {
            try {
                const statusRes = await api.get(`/candidates/jobs/${jobId}`)
                const status = statusRes.data.status
                
                if (status === 'completed') {
                    clearInterval(pollInterval)
                    const data = statusRes.data.parsed_data
                    
                    // Capture the candidate ID created by the background job
                    if (statusRes.data.candidate_id) {
                        candidateId.value = statusRes.data.candidate_id
                    }

                    name.value = data.name || ''
                    email.value = data.email || ''
                    phone.value = data.phone || ''
                    summary.value = data.summary || ''
                    loading.value = false
                    step.value = 2
                } else if (status === 'failed') {
                    clearInterval(pollInterval)
                    error.value = "Analysis failed: " + (statusRes.data.error || 'Unknown error')
                    loading.value = false
                } else {
                    // Still processing
                    processingStatus.value = `Analyzing... (Status: ${status})`
                }
            } catch (pollErr) {
                console.error("Poll Error", pollErr)
                clearInterval(pollInterval)
                loading.value = false
                error.value = "Error checking status."
            }
        }, 2000) // Poll every 2 seconds

    } catch (err) {
        console.error("Upload error", err)
        error.value = "Could not upload CV."
        loading.value = false
    }
}

const findMatches = async () => {
    if (!name.value || !email.value) {
        error.value = "Name and Email are required."
        return
    }

    loading.value = true
    processingStatus.value = "Finding the best roles for you..."
    error.value = ''

    try {
        // 1. Create/Update Candidate
        const formData = new FormData()
        formData.append('name', name.value)
        formData.append('email', email.value)
        if (phone.value) formData.append('phone', phone.value)
        
        // Only append CV file if it's a new upload or if we're creating a new candidate
        if (file.value && !candidateId.value) {
            formData.append('cv_file', file.value)
        }
        
        let res
        
        // If we already have a candidate ID (from parsing job or previous lookup), update instead of create
        if (candidateId.value) {
            res = await api.put(`/candidates/${candidateId.value}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
        } else {
            res = await api.post('/candidates', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
        }
        
        // Ensure we capture the ID if it was a create operation
        if (res.data.id) {
            candidateId.value = res.data.id
        }
        
        step.value = 3
        
        // 2. Trigger Matching
        const matchRes = await api.post(`/matches/candidates/${candidateId.value}/match-vacancies?refresh=true`)
        matches.value = matchRes.data.matches

    } catch (err) {
        if (err.response?.data?.errors?.email) {
            // "Email has already been taken" -> Fetch existing candidate
            try {
                processingStatus.value = "Profile exists. Retrieving your data..."
                const searchRes = await api.get(`/candidates?search=${email.value}`)
                
                // Flexible matching:
                // 1. Try exact match
                // 2. Try case-insensitive/trimmed match
                // 3. Fallback to first result if we have results (since we searched for this specific email)
                const candidates = searchRes.data.data
                let existing = candidates.find(c => c.email === email.value)
                
                if (!existing && candidates.length > 0) {
                     const normalizedEmail = email.value.trim().toLowerCase()
                     existing = candidates.find(c => c.email.trim().toLowerCase() === normalizedEmail)
                     
                     // If still not found but we have results, take the first one (high probability it's the right one given the search)
                     if (!existing) {
                        existing = candidates[0]
                     }
                }
                
                if (existing) {
                    candidateId.value = existing.id
                    
                    // Optionally update the proifle with new data here (PUT)
                    // For now, just skip to matching to avoid overwriting with potentially partial data
                    
                    // 2. Trigger Matching with existing ID
                    step.value = 3
                    const matchRes = await api.post(`/matches/candidates/${candidateId.value}/match-vacancies?refresh=true`)
                    matches.value = matchRes.data.matches
                    loading.value = false
                    return
                }
            } catch (findErr) {
                 console.error("Failed to find existing candidate", findErr)
            }
            
            error.value = "A candidate with this email already exists."
        } else {
             console.error(err)
             error.value = "An error occurred during processing."
        }
        loading.value = false
    } finally {
        // keeping logic in catch/try blocks to handle flow correctly
        if (!candidateId.value && !error.value) loading.value = false
    }
}

const initiateApply = async (vacancyId) => {
    // Check for questions first
    try {
        const res = await api.get(`/vacancies/${vacancyId}`)
        const vacancy = res.data.data || res.data
        
        if (vacancy.questions && vacancy.questions.length > 0) {
            currentVacancy.value = vacancy
            answers.value = {}
            showQuestionModal.value = true
        } else {
            // No questions, direct apply
            await submitApply(vacancyId)
        }
    } catch (e) {
        console.error("Failed to check vacancy questions", e)
        alert("Failed to initiate application.")
    }
}

const submitQuestions = async () => {
    if (!currentVacancy.value) return
    submittingAnswers.value = true
    
    try {
        const formattedAnswers = Object.keys(answers.value).map(qid => ({
            question_id: parseInt(qid),
            answer: answers.value[qid]
        }))
        
        await submitApply(currentVacancy.value.id, formattedAnswers)
        showQuestionModal.value = false
        currentVacancy.value = null
    } catch (e) {
        alert("Failed to submit application.")
    } finally {
        submittingAnswers.value = false
    }
}

const submitApply = async (vid, formattedAnswers = []) => {
    try {
        await api.post('/matches/apply', {
            candidate_id: candidateId.value,
            vacancy_id: vid,
            answers: formattedAnswers
        })
        const match = matches.value.find(m => m.vacancy_id === vid)
        if (match) match.status = 'applied'
        
        // If modal was open, close it (handled in submitQuestions mostly, but good for direct apply success)
        if (!showQuestionModal.value) alert("Application submitted!")
        
    } catch (err) {
        console.error(err)
        throw err 
    }
}
</script>

<template>
  <div class="container margin-top animate-in">
    <!-- Step 1: Upload (CV First) -->
    <div v-if="step === 1" class="upload-area">
        <h1 class="text-gradient">Find Your Perfect Match</h1>
        <p class="subtitle">Upload your CV and let our AI find the best positions for you.</p>

        <div class="card upload-card" :class="{ 'has-file': file }">
            <div class="upload-icon">📄</div>
            
            <input type="file" id="cv-upload" @change="handleFileChange" accept=".pdf,.doc,.docx" class="file-input" />
            <label for="cv-upload" class="file-label">
                <span v-if="!file">Click to upload Resume (PDF/DOCX)</span>
                <span v-else>{{ file.name }}</span>
            </label>

            <button v-if="file" @click="parseCv" :disabled="loading" class="btn-primary full-width animate-in">
                {{ loading ? 'Analyzing...' : 'Analyze Resume' }}
            </button>
            <div v-if="loading" class="processing-text">
                <div class="spinner-sm"></div>
                {{ processingStatus }}
            </div>
        </div>
        <div v-if="error" class="error-msg center">{{ error }}</div>
    </div>

    <!-- Step 2: Review Details -->
    <div v-if="step === 2" class="review-area card animate-in">
        <h2>Confirm Your Details</h2>
        <p class="subtitle">We extracted this information from your resume. Please verify it.</p>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Full Name</label>
                <input v-model="name" type="text" placeholder="John Doe" />
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input v-model="email" type="email" placeholder="john@example.com" />
            </div>
            
             <div class="form-group">
                <label>Phone</label>
                <input v-model="phone" type="text" placeholder="+1..." />
            </div>
        </div>

        <div v-if="error" class="error-msg">{{ error }}</div>

        <div class="actions">
            <button @click="step = 1" class="btn-secondary" :disabled="loading">Back</button>
            <button @click="findMatches" class="btn-primary" :disabled="loading">
                {{ loading ? 'Matching...' : 'Find Matches' }}
            </button>
        </div>
        <div v-if="loading" class="processing-text center">
             <div class="spinner-sm"></div>
            {{ processingStatus }}
        </div>
    </div>

    <!-- Step 3: Matches -->
    <div v-if="step === 3" class="results-area animate-in">
        <div class="results-header">
             <h2>Your Matched Positions</h2>
             <button @click="step = 1" class="btn-secondary">New Upload</button>
        </div>
       
        <div v-if="matches.length === 0" class="no-matches card">
            <p>No suitable matches found based on our current threshold.</p>
        </div>
        
        <div v-else class="matches-grid">
            <div v-for="match in matches" :key="match.vacancy_id" class="card match-card">
                <div class="match-content">
                    <div class="match-header">
                        <h3>{{ match.vacancy_title }}</h3>
                        <div class="score-badge" :class="{ high: match.match_score > 80 }">
                            {{ match.match_score }}% Match
                        </div>
                    </div>
                    <div class="match-details" v-if="match.analysis">
                         <!-- TODO: Parse analysis if it's text, or display summary -->
                         <p class="analysis-snippet">{{ match.analysis.substring(0, 150) }}...</p>
                    </div>
                </div>
                <div class="match-actions">
                    <button 
                        v-if="match.status !== 'applied'" 
                        @click="initiateApply(match.vacancy_id)" 
                        class="btn-primary"
                    >
                        Apply Now
                    </button>
                    <span v-else class="applied-badge">Applied</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Modal -->
    <div v-if="showQuestionModal" class="modal-overlay">
        <div class="modal-content card animate-in">
            <h3>Additional Questions</h3>
            <p>Please answer the following questions to complete your application for <strong>{{ currentVacancy?.title }}</strong>.</p>
            
            <QuestionRenderer 
                v-if="currentVacancy"
                :questions="currentVacancy.questions" 
                v-model="answers" 
            />

            <div class="modal-actions">
                <button @click="showQuestionModal = false" class="btn-secondary" :disabled="submittingAnswers">Cancel</button>
                <button @click="submitQuestions" class="btn-primary" :disabled="submittingAnswers">
                    {{ submittingAnswers ? 'Submitting...' : 'Submit Application' }}
                </button>
            </div>
        </div>
    </div>

  </div>
</template>

<style scoped>
.margin-top { margin-top: 3rem; }
.upload-area { text-align: center; max-width: 600px; margin: 0 auto; }
.subtitle { color: var(--text-muted); margin-bottom: 2rem; }
.upload-card { padding: 3rem; border: 2px dashed var(--glass-border); display: flex; flex-direction: column; align-items: center; gap: 1.5rem; transition: all 0.3s; }
.upload-card.has-file { border-color: var(--primary-start); background: rgba(99, 102, 241, 0.05); }
.upload-icon { font-size: 3rem; filter: grayscale(1); transition: filter 0.3s; }
.upload-card.has-file .upload-icon { filter: grayscale(0); }
.file-input { display: none; }
.file-label { cursor: pointer; color: var(--text-color); font-weight: 500; font-size: 1.1rem; }
.file-label:hover { text-decoration: underline; color: white; }
.processing-text { color: var(--secondary-color); font-style: italic; margin-top: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.form-grid { display: grid; gap: 1.5rem; margin-top: 1rem; }
.form-group { display: flex; flex-direction: column; text-align: left; gap: 0.5rem; }
.form-group label { color: #cbd5e1; font-weight: 500; } 
.actions { display: flex; justify-content: flex-end; margin-top: 2rem; gap: 1rem; }
.center { text-align: center; margin-top: 1rem; }
.error-msg { color: var(--error-color); margin-top: 1rem; text-align: center;}
.full-width { width: 100%; }
.results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.matches-grid { display: grid; gap: 1.5rem; margin-top: 2rem; }
.match-card { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.match-content { flex: 1; }
.match-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
.score-badge { background: #334155; padding: 0.25rem 0.75rem; border-radius: 99px; font-weight: bold; font-size: 0.8rem; }
.score-badge.high { background: var(--success-color); color: white; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
.applied-badge { color: var(--success-color); font-weight: bold; background: rgba(16, 185, 129, 0.1); padding: 0.5rem 1rem; border-radius: 8px; }
.analysis-snippet { color: var(--text-muted); font-size: 0.9rem; margin: 0; }

.spinner-sm { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: var(--primary-start); border-radius: 50%; animation: spin 1s linear infinite; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px); }
.modal-content { max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; position: relative; }
.modal-actions { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; }

@media (max-width: 640px) {
    .match-card { flex-direction: column; align-items: flex-start; }
    .match-actions { width: 100%; display: flex; justify-content: flex-end; }
}
</style>

