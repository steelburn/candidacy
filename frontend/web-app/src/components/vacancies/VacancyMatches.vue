<template>
  <div v-if="loading" class="info-card full-width">
    <div class="loading-matches">
      <div class="loading-spinner small"></div>
      <p>Finding matching candidates...</p>
    </div>
  </div>
  
  <div v-else-if="matches.length" class="info-card full-width matches-card">
    <h3 class="card-title">
      <span class="title-icon">⭐</span>
      Top Matching Candidates
      <span class="match-count">{{ matches.length }} found</span>
    </h3>
    <div class="matches-grid">
      <div v-for="match in matches.slice(0, 10)" :key="match.candidate_id" class="match-card">
        <div class="match-header">
          <div class="match-avatar">
            <span class="avatar-text">{{ match.candidate_id }}</span>
          </div>
          <div class="match-info">
            <strong class="candidate-name">Candidate #{{ match.candidate_id }}</strong>
            <div class="match-score-container">
              <div class="match-score-bar">
                <div 
                  class="match-score-fill" 
                  :class="getScoreClass(match.match_score)"
                  :style="{ width: match.match_score + '%' }"
                ></div>
              </div>
              <span class="match-score-text" :class="getScoreClass(match.match_score)">
                {{ match.match_score }}%
              </span>
            </div>
          </div>
        </div>
        <router-link :to="`/candidates/${match.candidate_id}`" class="match-link">
          View Profile →
        </router-link>
      </div>
    </div>
  </div>
  
  <div v-else-if="error" class="info-card full-width">
      <p class="error-msg">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { matchingAPI } from '../../services/api'
import { getScoreClass } from '../../composables/useMatchAnalysis'

const props = defineProps({
  vacancyId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['update:loading'])

const matches = ref([])
const loading = ref(false)
const error = ref(null)

const fetchMatches = async () => {
  if (loading.value) return
  
  loading.value = true
  emit('update:loading', true)
  error.value = null
  matches.value = []
  
  try {
    const res = await matchingAPI.forVacancy(props.vacancyId)
    
    // Check if async processing started
    if (res.data.status === 'processing' && res.data.job_id) {
        const jobId = res.data.job_id
        
        // Poll for completion
        const pollInterval = setInterval(async () => {
            try {
                const statusRes = await matchingAPI.getJobStatus(jobId)
                if (statusRes.data.status === 'completed') {
                    clearInterval(pollInterval)
                    // Refresh matches list
                    const finalRes = await matchingAPI.forVacancy(props.vacancyId)
                    matches.value = finalRes.data.data || []
                    loading.value = false
                    emit('update:loading', false)
                } else if (statusRes.data.status === 'failed') {
                    clearInterval(pollInterval)
                    error.value = "Matching failed: " + (statusRes.data.error || 'Unknown error')
                    loading.value = false
                    emit('update:loading', false)
                }
            } catch (e) {
                clearInterval(pollInterval)
                console.error("Poll error", e)
                loading.value = false
                emit('update:loading', false)
            }
        }, 2000)
        
    } else {
        matches.value = res.data.data || []
        loading.value = false
        emit('update:loading', false)
    }
  } catch (err) {
    console.error('Failed to fetch matches:', err)
    error.value = "Could not load matches."
    loading.value = false
    emit('update:loading', false)
  }
}

// Optionally fetch on mount if needed, or wait for trigger? 
// Original fetched on mount.
onMounted(() => {
    fetchMatches()
})

defineExpose({ fetchMatches })
</script>

<style scoped>
/* Info Cards */
.info-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.6);
  transition: all 0.3s ease;
  width: 100%;
}

.full-width {
  grid-column: 1 / -1;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 2rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.title-icon {
  font-size: 1.75rem;
}

.match-count {
  margin-left: auto;
  font-size: 0.875rem;
  font-weight: 600;
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  padding: 0.375rem 0.875rem;
  border-radius: 50px;
}


/* Loading */
.loading-matches {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
}

.loading-spinner {
  border: 4px solid rgba(102, 126, 234, 0.1);
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-spinner.small {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Matches Grid */
.matches-card {
  animation: fadeInUp 0.6s ease-out 0.4s both;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.matches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.match-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
  backdrop-filter: blur(10px);
  padding: 1.75rem;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.match-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
  border-color: rgba(102, 126, 234, 0.3);
}

.match-header {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.match-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.avatar-text {
  color: white;
  font-weight: 700;
  font-size: 0.875rem;
}

.match-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.candidate-name {
  color: #1a202c;
  font-size: 1.125rem;
  font-weight: 700;
}

.match-score-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.match-score-bar {
  flex: 1;
  height: 8px;
  background: #e2e8f0;
  border-radius: 50px;
  overflow: hidden;
}

.match-score-fill {
  height: 100%;
  border-radius: 50px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  animation: fillBar 1s ease-out;
}

@keyframes fillBar {
  from { width: 0 !important; }
}

.match-score-fill.score-high { background: linear-gradient(90deg, #10b981, #059669); }
.match-score-fill.score-medium { background: linear-gradient(90deg, #f59e0b, #d97706); }
.match-score-fill.score-low { background: linear-gradient(90deg, #ef4444, #dc2626); }

.match-score-text {
  font-weight: 700;
  font-size: 0.875rem;
  min-width: 45px;
  text-align: right;
}

.match-score-text.score-high { color: #059669; }
.match-score-text.score-medium { color: #d97706; }
.match-score-text.score-low { color: #dc2626; }

.match-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.match-link:hover {
  color: #5a67d8;
  gap: 0.75rem;
}

.error-msg {
    color: #ef4444;
    text-align: center;
    padding: 1rem;
    background: #fee2e2;
    border-radius: 8px;
}
</style>
