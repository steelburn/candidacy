<template>
  <div class="matches-page">
    <InterviewModal 
      :show="showInterviewModal"
      :candidate-id="selectedMatch?.candidate_id"
      :vacancy-id="selectedMatch?.vacancy_id"
      :candidate-name="getCandidateName(selectedMatch?.candidate_id)"
      :vacancy-title="getVacancyTitle(selectedMatch?.vacancy_id)"
      @close="closeInterviewModal"
      @created="onInterviewCreated"
    />

    <OfferModal 
      :show="showOfferModal"
      :candidate-id="selectedMatch?.candidate_id"
      :vacancy-id="selectedMatch?.vacancy_id"
      :candidate-name="getCandidateName(selectedMatch?.candidate_id)"
      :vacancy-title="getVacancyTitle(selectedMatch?.vacancy_id)"
      @close="closeOfferModal"
      @created="onOfferCreated"
    />

    <div class="header">
      <h1>Candidate Matches</h1>
      <div class="header-actions">
        <button @click="refreshMatches" class="btn-secondary" :disabled="loading || running">
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
        <button @click="runMatching" class="btn-primary" :disabled="loading || running">
          {{ running ? 'Running AI Matching...' : 'Run Matching (Recalculate)' }}
        </button>
      </div>
    </div>

    <MatchFilters 
      :candidates="candidates"
      :vacancies="vacancies"
      v-model:candidateFilter="candidateFilter"
      v-model:vacancyFilter="vacancyFilter"
      v-model:minScore="minScore"
      v-model:sortOption="sortOption"
      @refresh="fetchMatches"
    />

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading matches...</p>
    </div>

    <div v-else-if="matches.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <h3>No matches found</h3>
      <p>Try adjusting your filters or run matching for candidates</p>
    </div>

    <div v-else class="matches-content">
      <!-- Active Matches -->
      <div class="section-container">
        <h2 class="section-title">Active Matches ({{ activeMatches.length }})</h2>
        <div v-if="activeMatches.length === 0" class="empty-section">
            <p>No active matches. Try adjusting filters.</p>
        </div>
        
        <div v-else class="matches-grid">
          <MatchListCard 
            v-for="match in activeMatches"
            :key="match.id"
            :match="match"
            :candidate-name="getCandidateName(match.candidate_id)"
            :vacancy-title="getVacancyTitle(match.vacancy_id)"
            :is-expanded="isExpanded(match.id)"
            @toggle="toggleMatch(match.id)"
            @dismiss="dismissMatch"
            @interview="openInterviewModal"
            @offer="openOfferModal"
          />
        </div>
      </div>

      <!-- Dismissed Matches -->
      <div v-if="dismissedMatches.length > 0" class="section-container dismissed-section">
        <h2 class="section-title text-muted">Dismissed Matches ({{ dismissedMatches.length }})</h2>
         <DismissedMatchTable 
            :matches="dismissedMatches"
            :candidates="candidates"
            :vacancies="vacancies"
            @restore="restoreMatch"
         />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { matchingAPI, candidateAPI, vacancyAPI } from '../../services/api'
import InterviewModal from '../interviews/InterviewModal.vue'
import OfferModal from '../offers/OfferModal.vue'

// Import New Components
import MatchFilters from '../../components/matches/MatchFilters.vue'
import MatchListCard from '../../components/matches/MatchListCard.vue'
import DismissedMatchTable from '../../components/matches/DismissedMatchTable.vue'

const matches = ref([])
const candidates = ref([])
const vacancies = ref([])
const loading = ref(false)
const running = ref(false)
const candidateFilter = ref('')
const vacancyFilter = ref('')
const minScore = ref('')
const sortOption = ref('match_score:desc')
const expandedMatches = ref(new Set())

const activeMatches = computed(() => matches.value.filter(m => m.status !== 'dismissed'))
const dismissedMatches = computed(() => matches.value.filter(m => m.status === 'dismissed'))

const showInterviewModal = ref(false)
const selectedMatch = ref(null)

const openInterviewModal = (match) => {
    selectedMatch.value = match
    showInterviewModal.value = true
}

const closeInterviewModal = () => {
    showInterviewModal.value = false
    selectedMatch.value = null
}

const onInterviewCreated = () => {
    alert('Interview Scheduled Successfully!')
}

const showOfferModal = ref(false)

const openOfferModal = (match) => {
    selectedMatch.value = match
    showOfferModal.value = true
}

const closeOfferModal = () => {
    showOfferModal.value = false
    selectedMatch.value = null
}

const onOfferCreated = () => {
    alert('Offer Created Successfully!')
}

const toggleMatch = (id) => {
    if (expandedMatches.value.has(id)) {
        expandedMatches.value.delete(id)
    } else {
        expandedMatches.value.add(id)
    }
}

const isExpanded = (id) => {
    return expandedMatches.value.has(id)
}

const dismissMatch = async (match) => {
    if (!confirm('Are you sure you want to dismiss this match? It will be hidden from the main list.')) return
    
    try {
        await matchingAPI.dismiss(match.candidate_id, match.vacancy_id)
        match.status = 'dismissed'
    } catch (e) {
        alert("Failed to dismiss match")
        console.error(e)
    }
}

const restoreMatch = async (match) => {
    try {
        await matchingAPI.restore(match.candidate_id, match.vacancy_id)
        match.status = 'pending'
    } catch (e) {
        alert("Failed to restore match")
        console.error(e)
    }
}

const fetchMatches = async () => {
  loading.value = true
  try {
    const params = {}
    if (candidateFilter.value) params.candidate_id = candidateFilter.value
    if (vacancyFilter.value) params.vacancy_id = vacancyFilter.value
    if (minScore.value) params.min_score = minScore.value
    
    const [sortBy, sortOrder] = sortOption.value.split(':')
    params.sort_by = sortBy
    params.sort_order = sortOrder
    
    const response = await matchingAPI.list(params)
    matches.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to fetch matches:', error)
    matches.value = []
  } finally {
    loading.value = false
  }
}

const fetchFilters = async () => {
  try {
    const [candidatesRes, vacanciesRes] = await Promise.all([
      candidateAPI.list({ per_page: 100 }),
      vacancyAPI.list({ per_page: 100 })
    ])
    const rawCandidates = candidatesRes.data.data || candidatesRes.data || []
    candidates.value = rawCandidates.map(c => ({
      ...c,
      name: c.name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || `Candidate #${c.id}`
    }))
    vacancies.value = vacanciesRes.data.data || vacanciesRes.data || []
  } catch (error) {
    console.error('Failed to fetch filters:', error)
  }
}

const refreshMatches = () => {
  fetchMatches()
}

const runMatching = async () => {
  if (running.value) return
  
  running.value = true
  try {
    const candidatesToMatch = candidateFilter.value 
      ? [{ id: candidateFilter.value }] 
      : candidates.value.slice(0, 10) 
    
    for (const candidate of candidatesToMatch) {
      await matchingAPI.matchCandidate(candidate.id, { refresh: true })
    }
    
    await fetchMatches()
  } catch (error) {
    console.error('Failed to run matching:', error)
  } finally {
    running.value = false
  }
}

const getCandidateName = (id) => {
  if (!id) return 'Unknown Candidate'
  const candidate = candidates.value.find(c => c.id == id) 
  return candidate ? candidate.name : `Candidate #${id}`
}

const getVacancyTitle = (id) => {
  if (!id) return 'Unknown Vacancy'
  const vacancy = vacancies.value.find(v => v.id == id)
  return vacancy ? vacancy.title : `Vacancy #${id}`
}

onMounted(() => {
  fetchFilters()
  fetchMatches()
})
</script>

<style scoped>
/* Base Layout */
.matches-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Loading & Empty States */
.loading {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  color: #1e293b;
  font-size: 1.5rem;
}

.empty-state p {
  color: #64748b;
  margin: 0;
}

/* Section Styles */
.matches-content {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.section-container {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1.5rem 0;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title.text-muted {
  color: #94a3b8;
}

.empty-section {
  padding: 2rem;
  text-align: center;
  color: #64748b;
  background: #f8fafc;
  border-radius: 12px;
}

/* Matches Grid */
.matches-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Button styles that mimic parent for local use if used */
.btn-primary, .btn-secondary {
    padding: 0.75rem 1.75rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-secondary {
    background: white;
    border: 1px solid #e2e8f0;
    color: #334155;
}
</style>
