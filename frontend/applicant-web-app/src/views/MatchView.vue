<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

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
                    const data = statusRes.data.result
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
        formData.append('cv_file', file.value)
        formData.append('name', name.value)
        formData.append('email', email.value)
        if (phone.value) formData.append('phone', phone.value)
        // We could also send parsed skills/exp to help backend, but backend extracts again on create usually?
        // Actually, candidate-service create method might re-parse or we just trust the file.
        // Let's just send the basics + file. The backend 'create' will re-parse for storage if needed 
        // OR we should allow sending extracted data. 
        // For now, let's stick to standard create.
        
        let existingId = null
        try {
            const candidateRes = await api.post('/candidates', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            candidateId.value = candidateRes.data.id
        } catch (postErr) {
             // Handle duplicate email logic?
             // Ideally we find by email if existing?
             // Since we don't have a public 'find-by-email' for security...
             // If 422 (email taken), we might want to prompt login?
             // But for this flow, let's assume if email exists, we update or just parse matches for that ID?
             // Need a way to handle existing user.
             if (postErr.response?.data?.errors?.email) {
                 // Try to get matches for this email? No, insecure.
                 // Suggest Login.
                 error.value = "A candidate with this email already exists. Please login to the Portal."
                 loading.value = false
                 return
             }
             throw postErr
        }
        
        // 2. Trigger Matching
        step.value = 3
        
        const matchRes = await api.post(`/matches/candidates/${candidateId.value}/match-vacancies?refresh=true`)
        matches.value = matchRes.data.matches

    } catch (err) {
        console.error(err)
        error.value = "An error occurred during processing."
    } finally {
        loading.value = false
    }
}

const apply = async (vacancyId) => {
    try {
        await api.post('/matches/apply', {
            candidate_id: candidateId.value,
            vacancy_id: vacancyId
        })
        const match = matches.value.find(m => m.vacancy_id === vacancyId)
        if (match) match.status = 'applied'
        alert("Application submitted!")
    } catch (err) {
        alert("Failed to apply.")
    }
}
</script>

<template>
  <div class="container margin-top">
    <!-- Step 1: Upload (CV First) -->
    <div v-if="step === 1" class="upload-area">
        <h1>Find Your Perfect Match</h1>
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
            <div v-if="loading" class="processing-text">{{ processingStatus }}</div>
        </div>
        <div v-if="error" class="error-msg center">{{ error }}</div>
    </div>

    <!-- Step 2: Review Details -->
    <div v-if="step === 2" class="review-area card">
        <h2>Confirm Your Details</h2>
        <p>We extracted this information from your resume. Please verify it.</p>
        
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
        <div v-if="loading" class="processing-text center">{{ processingStatus }}</div>
    </div>

    <!-- Step 3: Matches -->
    <div v-if="step === 3" class="results-area">
        <h2>Your Matched Positions</h2>
        
        <div v-if="matches.length === 0" class="no-matches card">
            <p>No suitable matches found based on our current threshold.</p>
            <button @click="step = 1" class="btn-secondary">Try another CV</button>
        </div>
        
        <div v-else class="matches-grid">
            <div v-for="match in matches" :key="match.vacancy_id" class="card match-card">
                <div class="match-header">
                    <h3>{{ match.vacancy_title }}</h3>
                    <div class="score-badge" :class="{ high: match.match_score > 80 }">
                        {{ match.match_score }}% Match
                    </div>
                </div>
                <div class="match-details" v-if="match.analysis">
                     <!-- Truncated analysis if needed -->
                </div>
                <div class="actions">
                    <button 
                        v-if="match.status !== 'applied'" 
                        @click="apply(match.vacancy_id)" 
                        class="btn-primary"
                    >
                        Apply Now
                    </button>
                    <span v-else class="applied-badge">Applied</span>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.margin-top { margin-top: 3rem; }
.upload-area { text-align: center; max-width: 600px; margin: 0 auto; }
.subtitle { color: #94a3b8; margin-bottom: 2rem; }
.upload-card { padding: 3rem; border: 2px dashed var(--border-color); display: flex; flex-direction: column; align-items: center; gap: 1.5rem; transition: all 0.3s; }
.upload-card.has-file { border-color: var(--primary-color); background: rgba(99, 102, 241, 0.05); }
.upload-icon { font-size: 3rem; }
.file-input { display: none; }
.file-label { cursor: pointer; color: var(--text-color); font-weight: 500; font-size: 1.1rem; }
.file-label:hover { text-decoration: underline; }
.processing-text { color: var(--secondary-color); font-style: italic; margin-top: 1rem; }
.form-grid { display: grid; gap: 1rem; margin-top: 1rem; }
.form-group { display: flex; flex-direction: column; text-align: left; gap: 0.5rem; }
.form-group input { padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 0.5rem; color: white; }
.actions { display: flex; justify-content: space-between; margin-top: 2rem; gap: 1rem; }
.btn-secondary { background: #334155; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; border:none; cursor: pointer; }
.center { text-align: center; margin-top: 1rem; }
.error-msg { color: var(--error-color); }
.full-width { width: 100%; }
.matches-grid { display: grid; gap: 1.5rem; margin-top: 2rem; }
.match-card { display: flex; justify-content: space-between; align-items: center; }
.score-badge { background: #334155; padding: 0.5rem; border-radius: 0.5rem; font-weight: bold; }
.score-badge.high { background: var(--success-color); color: black; }
.applied-badge { color: var(--success-color); font-weight: bold; }
</style>
