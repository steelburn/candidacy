<template>
  <div class="vacancy-detail-wrapper">
    <div class="animated-background"></div>
    
    <div class="vacancy-detail">
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Loading vacancy details...</p>
      </div>
      
      <div v-else-if="vacancy" class="content-wrapper">
        <div class="header-card">
          <div class="header-content">
            <div class="title-section">
              <h1 class="gradient-text">{{ vacancy.title }}</h1>
              <div class="meta-info">
                <span :class="'status-badge status-' + vacancy.status">
                  <span class="status-icon">●</span>
                  {{ vacancy.status }}
                </span>
                <span class="job-type-badge">
                  💼 {{ vacancy.employment_type?.replace('_', ' ') || 'Full-time' }}
                </span>
              </div>
            </div>
            <div class="actions">
              <router-link :to="`/vacancies/${vacancy.id}/edit`" class="btn-primary">
                <span class="btn-icon">✏️</span>
                Edit Vacancy
              </router-link>
              <button @click="viewMatches" class="btn-secondary" :disabled="loadingMatches">
                <span class="btn-icon">🔍</span>
                {{ loadingMatches ? 'Finding...' : 'Find Candidates' }}
              </button>
            </div>
          </div>
        </div>
        
        <div class="detail-grid">
          <div class="info-card sidebar-card">
            <h3 class="card-title">
              <span class="title-icon">📋</span>
              Job Details
            </h3>
            <div class="info-list">
              <div class="info-item">
                <span class="info-icon">📍</span>
                <div class="info-content">
                  <span class="info-label">Location</span>
                  <span class="info-value">{{ vacancy.location }}</span>
                </div>
              </div>
              <div class="info-item">
                <span class="info-icon">🏢</span>
                <div class="info-content">
                  <span class="info-label">Department</span>
                  <span class="info-value">{{ vacancy.department || 'N/A' }}</span>
                </div>
              </div>
              <div class="info-item">
                <span class="info-icon">💼</span>
                <div class="info-content">
                  <span class="info-label">Employment Type</span>
                  <span class="info-value">{{ vacancy.employment_type?.replace('_', ' ') }}</span>
                </div>
              </div>
              <div class="info-item">
                <span class="info-icon">📊</span>
                <div class="info-content">
                  <span class="info-label">Experience Level</span>
                  <span class="info-value">{{ vacancy.experience_level }}</span>
                </div>
              </div>
              <div class="info-item" v-if="vacancy.min_salary">
                <span class="info-icon">💰</span>
                <div class="info-content">
                  <span class="info-label">Salary Range</span>
                  <span class="info-value salary-value">
                    {{ vacancy.min_salary }} - {{ vacancy.max_salary }} {{ vacancy.currency }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="info-card description-card">
            <h3 class="card-title">
              <span class="title-icon">📄</span>
              Job Description
            </h3>
            <div class="description-content">
              <MarkdownRenderer :content="vacancy.description || 'No description available'" />
            </div>
          </div>
          
          <div v-if="loadingMatches" class="info-card full-width">
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
                          :class="getMatchScoreClass(match.match_score)"
                          :style="{ width: match.match_score + '%' }"
                        ></div>
                      </div>
                      <span class="match-score-text" :class="getMatchScoreClass(match.match_score)">
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
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { vacancyAPI, matchingAPI } from '../../services/api'
import MarkdownRenderer from '../../components/MarkdownRenderer.vue'

const route = useRoute()

const vacancy = ref(null)
const matches = ref([])
const loading = ref(false)
const loadingMatches = ref(false)
const error = ref(null)

const fetchVacancy = async () => {
  loading.value = true
  try {
    const response = await vacancyAPI.get(route.params.id)
    vacancy.value = response.data
  } catch (error) {
    console.error('Failed to fetch vacancy:', error)
  } finally {
    loading.value = false
  }
}

const viewMatches = async () => {
  loadingMatches.value = true
  error.value = null
  matches.value = []
  
  try {
    const res = await matchingAPI.forVacancy(route.params.id)
    
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
                    const finalRes = await matchingAPI.forVacancy(route.params.id)
                    matches.value = finalRes.data.data || []
                    loadingMatches.value = false
                } else if (statusRes.data.status === 'failed') {
                    clearInterval(pollInterval)
                    error.value = "Matching failed: " + (statusRes.data.error || 'Unknown error')
                    loadingMatches.value = false
                }
            } catch (e) {
                clearInterval(pollInterval)
                console.error("Poll error", e)
            }
        }, 2000)
        
    } else {
        matches.value = res.data.data || []
        loadingMatches.value = false
    }
  } catch (err) {
    console.error('Failed to fetch matches:', err)
    error.value = "Could not load matches."
    loadingMatches.value = false
  }
}

const getMatchScoreClass = (score) => {
  if (score >= 80) return 'score-high'
  if (score >= 60) return 'score-medium'
  return 'score-low'
}

onMounted(() => {
  fetchVacancy()
  viewMatches()
})
</script>

<style scoped>
/* Animated Background */
.vacancy-detail-wrapper {
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.animated-background {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: -1;
  background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
  background-size: 400% 400%;
  animation: gradientShift 15s ease infinite;
  opacity: 0.05;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Main Container */
.vacancy-detail {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.content-wrapper {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading States */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1.5rem;
}

.loading-container p {
  color: #64748b;
  font-size: 1.125rem;
  font-weight: 500;
}

.loading-spinner {
  width: 60px;
  height: 60px;
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

.loading-matches {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
}

.loading-matches p {
  color: #64748b;
  font-weight: 500;
}

/* Header Card */
.header-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 3rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.5);
  position: relative;
  overflow: hidden;
  animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.header-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
  background-size: 200% 100%;
  animation: gradientSlide 3s ease infinite;
}

@keyframes gradientSlide {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.gradient-text {
  font-size: 3rem;
  font-weight: 900;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 1.5rem 0;
  letter-spacing: -0.03em;
  line-height: 1.2;
}

.meta-info {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.status-icon {
  font-size: 0.625rem;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.status-draft { 
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0); 
  color: #475569; 
}

.status-open { 
  background: linear-gradient(135deg, #dcfce7, #bbf7d0); 
  color: #15803d; 
}

.status-closed { 
  background: linear-gradient(135deg, #fee2e2, #fecaca); 
  color: #b91c1c; 
}

.status-on_hold { 
  background: linear-gradient(135deg, #ffedd5, #fed7aa); 
  color: #c2410c; 
}

.job-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: linear-gradient(135deg, #ebf8ff, #dbeafe);
  color: #1e40af;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: capitalize;
  box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
}

/* Actions */
.actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  border-radius: 14px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-icon {
  font-size: 1.125rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  color: #2d3748;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Detail Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 2rem;
  animation: fadeInUp 0.6s ease-out 0.2s both;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.full-width {
  grid-column: 1 / -1;
}

/* Info Cards */
.info-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.6);
  transition: all 0.3s ease;
}

.info-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
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

/* Sidebar Info Card */
.sidebar-card {
  height: fit-content;
  position: sticky;
  top: 2rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem;
  background: rgba(248, 250, 252, 0.6);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.info-item:hover {
  background: rgba(241, 245, 249, 0.8);
  transform: translateX(4px);
}

.info-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.info-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  color: #1a202c;
  font-weight: 600;
  font-size: 1.125rem;
}

.salary-value {
  background: linear-gradient(135deg, #10b981, #059669);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
}

/* Description Card */
.description-card {
  grid-column: 2;
  grid-row: 1 / 3;
}

.description-content {
  color: #4a5568;
  line-height: 1.8;
  font-size: 1.0625rem;
}

.description-content :deep(h1),
.description-content :deep(h2),
.description-content :deep(h3) {
  color: #1a202c;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}

.description-content :deep(ul),
.description-content :deep(ol) {
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.description-content :deep(li) {
  margin-bottom: 0.5rem;
}

/* Matches Card */
.matches-card {
  animation: fadeInUp 0.6s ease-out 0.4s both;
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

.match-score-fill.score-high {
  background: linear-gradient(90deg, #10b981, #059669);
}

.match-score-fill.score-medium {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.match-score-fill.score-low {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.match-score-text {
  font-weight: 700;
  font-size: 0.875rem;
  min-width: 45px;
  text-align: right;
}

.match-score-text.score-high {
  color: #059669;
}

.match-score-text.score-medium {
  color: #d97706;
}

.match-score-text.score-low {
  color: #dc2626;
}

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

/* Responsive Design */
@media (max-width: 1024px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .description-card {
    grid-column: 1;
    grid-row: auto;
  }
  
  .sidebar-card {
    position: static;
  }
  
  .header-content {
    flex-direction: column;
  }
  
  .gradient-text {
    font-size: 2.25rem;
  }
}

@media (max-width: 640px) {
  .vacancy-detail {
    padding: 1rem;
  }
  
  .header-card {
    padding: 2rem;
  }
  
  .gradient-text {
    font-size: 1.875rem;
  }
  
  .actions {
    flex-direction: column;
    width: 100%;
  }
  
  .btn-primary, .btn-secondary {
    width: 100%;
    justify-content: center;
  }
  
  .matches-grid {
    grid-template-columns: 1fr;
  }
  
  .info-card {
    padding: 1.5rem;
  }
}
</style>
