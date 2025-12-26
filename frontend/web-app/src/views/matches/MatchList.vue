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

    <div class="filters">
      <select v-model="candidateFilter" @change="fetchMatches" class="filter-select">
        <option value="">All Candidates</option>
        <option v-for="c in candidates" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="vacancyFilter" @change="fetchMatches" class="filter-select">
        <option value="">All Vacancies</option>
        <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
      </select>
      <select v-model="minScore" @change="fetchMatches" class="filter-select">
        <option value="">Any Score</option>
        <option value="80">80%+</option>
        <option value="60">60%+</option>
        <option value="40">40%+</option>
      </select>
      <select v-model="sortOption" @change="fetchMatches" class="filter-select">
        <option value="match_score:desc">Score (High to Low)</option>
        <option value="match_score:asc">Score (Low to High)</option>
        <option value="created_at:desc">Newest First</option>
        <option value="created_at:asc">Oldest First</option>
      </select>
    </div>

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
          <div 
            v-for="match in activeMatches" 
            :key="match.id" 
            class="match-card"
            :class="{ 'expanded': isExpanded(match.id) }"
          >
            <!-- Card Header -->
            <div class="card-header" @click="toggleMatch(match.id)">
              <div class="header-left">
                <div class="candidate-info">
                  <span class="candidate-name">{{ getCandidateName(match.candidate_id) }}</span>
                  <span class="match-arrow">→</span>
                  <span class="vacancy-title">{{ getVacancyTitle(match.vacancy_id) }}</span>
                </div>
                <div class="match-date">{{ formatDate(match.created_at) }}</div>
              </div>
              <div class="header-right">
                <div class="score-badge" :class="getScoreClass(match.match_score)">
                  <span class="score-value">{{ match.match_score }}</span>
                  <span class="score-label">Match</span>
                </div>
              </div>
            </div>

            <!-- Quick Actions Bar -->
            <div class="quick-actions">
              <button class="action-btn interview" @click.stop="openInterviewModal(match)" title="Schedule Interview">
                <span class="icon">📅</span>
                <span class="text">Interview</span>
              </button>
              <button class="action-btn offer" @click.stop="openOfferModal(match)" title="Create Offer">
                <span class="icon">📜</span>
                <span class="text">Offer</span>
              </button>
              <router-link :to="`/candidates/${match.candidate_id}`" class="action-btn view">
                <span class="icon">👤</span>
                <span class="text">Profile</span>
              </router-link>
              <router-link :to="`/vacancies/${match.vacancy_id}`" class="action-btn view">
                <span class="icon">💼</span>
                <span class="text">Vacancy</span>
              </router-link>
              <button class="action-btn dismiss" @click.stop="dismissMatch(match)" title="Dismiss">
                <span class="icon">❌</span>
              </button>
              <button class="action-btn expand" @click.stop="toggleMatch(match.id)">
                <span class="icon">{{ isExpanded(match.id) ? '▲' : '▼' }}</span>
              </button>
            </div>

            <!-- Expanded Analysis -->
            <div v-if="isExpanded(match.id)" class="card-details">
              <!-- Structured Analysis View -->
              <div v-if="parseAnalysis(match.analysis)" class="analysis-grid">
                <div class="analysis-section strengths">
                  <h4><span class="section-icon">✅</span> Strengths</h4>
                  <ul v-if="parseAnalysis(match.analysis).strengths.length > 0">
                    <li v-for="(point, i) in parseAnalysis(match.analysis).strengths" :key="i">
                      {{ point }}
                    </li>
                  </ul>
                  <p v-else class="no-data">No specific strengths highlighted.</p>
                </div>

                <div class="analysis-section gaps">
                  <h4><span class="section-icon">⚠️</span> Gaps</h4>
                  <ul v-if="parseAnalysis(match.analysis).gaps.length > 0">
                    <li v-for="(point, i) in parseAnalysis(match.analysis).gaps" :key="i">
                      {{ point }}
                    </li>
                  </ul>
                  <p v-else class="no-data">No specific gaps identified.</p>
                </div>

                <div class="analysis-section recommendation">
                  <h4><span class="section-icon">💡</span> Recommendation</h4>
                  <p>{{ parseAnalysis(match.analysis).recommendation }}</p>
                </div>
              </div>

              <div v-else class="match-analysis fallback">
                <p>{{ truncateAnalysis(match.analysis) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dismissed Matches -->
      <div v-if="dismissedMatches.length > 0" class="section-container dismissed-section">
        <h2 class="section-title text-muted">Dismissed Matches ({{ dismissedMatches.length }})</h2>
         <table class="matches-table dismissed-table">
            <thead>
            <tr>
                <th>Candidate</th>
                <th>Vacancy</th>
                <th>Score</th>
                <th>Dismissed Date</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
                <tr v-for="match in dismissedMatches" :key="match.id">
                    <td>{{ getCandidateName(match.candidate_id) }}</td>
                    <td>{{ getVacancyTitle(match.vacancy_id) }}</td>
                    <td>{{ match.match_score }}%</td>
                    <td>{{ formatDate(match.updated_at) }}</td>
                    <td>
                        <button class="btn-icon restore-btn" @click="restoreMatch(match)" title="Restore Match">
                            ↩️
                        </button>
                    </td>
                </tr>
            </tbody>
         </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { matchingAPI, candidateAPI, vacancyAPI } from '../../services/api'
import InterviewModal from '../interviews/InterviewModal.vue'
import OfferModal from '../offers/OfferModal.vue'

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
    // Optional: Fetch updated status if available
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
    
    // Parse sort option
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

// Run matching for a specific candidate or all candidates with refresh
const runMatching = async () => {
  if (running.value) return
  
  running.value = true
  try {
    const candidatesToMatch = candidateFilter.value 
      ? [{ id: candidateFilter.value }] 
      : candidates.value.slice(0, 10) // Limit to 10 for performance
    
    for (const candidate of candidatesToMatch) {
      await matchingAPI.matchCandidate(candidate.id, { refresh: true })
    }
    
    // Reload matches from database
    await fetchMatches()
  } catch (error) {
    console.error('Failed to run matching:', error)
  } finally {
    running.value = false
  }
}

const getScoreClass = (score) => {
  const numScore = Number(score)
  if (numScore >= 80) return 'score-high'
  if (numScore >= 60) return 'score-medium'
  return 'score-low'
}

const truncateAnalysis = (text) => {
  if (!text) return ''
  return text.length > 150 ? text.substring(0, 150) + '...' : text
}

const parseAnalysis = (text) => {
  if (!text) return null
  
  const sections = {
    strengths: [],
    gaps: [],
    recommendation: ''
  }
  
  let foundAny = false

  // Helper to extract list items from text block
  // Handles: - item, • item, * item, 1. item, 1) item, and plain lines
  const extractListItems = (block) => {
    if (!block) return []
    return block
      .split('\n')
      .map(line => line.trim())
      .filter(line => line.length > 2)
      .map(line => {
        // Remove common list prefixes
        return line
          .replace(/^[-•*]\s*/, '')           // - or • or *
          .replace(/^\d+[.)]\s*/, '')          // 1. or 1)
          .replace(/^[a-z][.)]\s*/i, '')       // a. or a)
          .trim()
      })
      .filter(line => line.length > 2 && !line.match(/^(GAPS?|STRENGTHS?|RECOMMENDATION|SCORE)/i))
  }

  // Extract Strengths (handle typos)
  const strengthsMatch = text.match(/(?:STRENGTHS?|STRENGHTHS?|STRENTHS?)\s*:([\s\S]*?)(?=(?:GAPS?|WEAKNESS(?:ES)?)\s*:|RECOMMENDATION\s*:|$)/i)
  if (strengthsMatch && strengthsMatch[1]) {
    foundAny = true
    sections.strengths = extractListItems(strengthsMatch[1])
  }

  // Extract Gaps
  const gapsMatch = text.match(/(?:GAPS?|WEAKNESS(?:ES)?)\s*:([\s\S]*?)(?=RECOMMENDATION\s*:|$)/i)
  if (gapsMatch && gapsMatch[1]) {
    foundAny = true
    sections.gaps = extractListItems(gapsMatch[1])
  }

  // Extract Recommendation
  const recMatch = text.match(/RECOMMENDATION\s*:([\s\S]*)/i)
  if (recMatch && recMatch[1]) {
    foundAny = true
    sections.recommendation = recMatch[1].trim()
  }

  if (foundAny) {
    return sections
  }
  
  return null
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const getCandidateName = (id) => {
  if (!id) return 'Unknown Candidate'
  const candidate = candidates.value.find(c => c.id == id) // loose equality for string/number mismatch
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

/* Filters */
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  min-width: 200px;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

/* Match Card */
.match-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.match-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  border-color: #cbd5e1;
}

.match-card.expanded {
  box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

/* Card Header */
.card-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  background: linear-gradient(to right, #ffffff, #f8fafc);
  transition: background 0.2s;
}

.card-header:hover {
  background: linear-gradient(to right, #f8fafc, #f1f5f9);
}

.header-left {
  flex: 1;
}

.candidate-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.candidate-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
}

.match-arrow {
  color: #667eea;
  font-weight: 600;
}

.vacancy-title {
  font-size: 1rem;
  color: #475569;
  font-weight: 500;
}

.match-date {
  font-size: 0.85rem;
  color: #94a3b8;
  margin-top: 0.5rem;
}

/* Score Badge */
.score-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 16px;
  color: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  position: relative;
  overflow: hidden;
}

.score-badge::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
}

.score-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
  z-index: 1;
}

.score-label {
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.9;
  margin-top: 0.25rem;
  z-index: 1;
}

.score-high {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
}

.score-medium {
  background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
  box-shadow: 0 6px 15px rgba(245, 158, 11, 0.3);
}

.score-low {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
  box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
}

/* Quick Actions */
.quick-actions {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #475569;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.action-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.action-btn .icon {
  font-size: 1rem;
}

.action-btn.interview:hover {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #1d4ed8;
}

.action-btn.offer:hover {
  background: #f0fdf4;
  border-color: #22c55e;
  color: #16a34a;
}

.action-btn.view:hover {
  background: #faf5ff;
  border-color: #a855f7;
  color: #7c3aed;
}

.action-btn.dismiss {
  margin-left: auto;
}

.action-btn.dismiss:hover {
  background: #fef2f2;
  border-color: #ef4444;
  color: #dc2626;
}

.action-btn.expand {
  background: transparent;
  border: none;
  padding: 0.5rem;
}

/* Card Details */
.card-details {
  padding: 1.5rem;
  background: #fafbfc;
  border-top: 1px solid #f1f5f9;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Analysis Grid */
.analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.analysis-section {
  padding: 1.5rem;
  border-radius: 16px;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s;
}

.analysis-section:hover {
  transform: translateY(-2px);
}

.analysis-section h4 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-icon {
  font-size: 1.1rem;
}

.analysis-section.strengths {
  background: linear-gradient(145deg, #f0fdf4 0%, #ffffff 100%);
  border: 1px solid #dcfce7;
}

.analysis-section.strengths h4 {
  color: #166534;
}

.analysis-section.gaps {
  background: linear-gradient(145deg, #fffbeb 0%, #ffffff 100%);
  border: 1px solid #fef3c7;
}

.analysis-section.gaps h4 {
  color: #92400e;
}

.analysis-section.recommendation {
  grid-column: 1 / -1;
  background: linear-gradient(145deg, #eff6ff 0%, #ffffff 100%);
  border: 1px solid #dbeafe;
}

.analysis-section.recommendation h4 {
  color: #1e40af;
}

.analysis-section.recommendation p {
  color: #1e3a8a;
  font-size: 1rem;
  line-height: 1.6;
  margin: 0;
}

.analysis-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.analysis-section li {
  padding-left: 1.5rem;
  position: relative;
  margin-bottom: 0.75rem;
  color: #475569;
  line-height: 1.5;
}

.analysis-section.strengths li::before {
  content: '✓';
  color: #22c55e;
  position: absolute;
  left: 0;
  font-weight: 700;
}

.analysis-section.gaps li::before {
  content: '!';
  color: #f59e0b;
  position: absolute;
  left: 0;
  font-weight: 700;
}

.no-data {
  color: #94a3b8;
  font-style: italic;
  margin: 0;
}

.match-analysis.fallback {
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  color: #64748b;
  line-height: 1.6;
}

/* Dismissed Section */
.dismissed-section {
  opacity: 0.85;
}

.dismissed-section .section-container {
  background: #f8fafc;
}

/* Dismissed Table */
.matches-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.matches-table th {
  text-align: left;
  padding: 1rem;
  background: #f1f5f9;
  color: #64748b;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.matches-table th:first-child {
  border-radius: 8px 0 0 8px;
}

.matches-table th:last-child {
  border-radius: 0 8px 8px 0;
}

.matches-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #64748b;
}

.restore-btn {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: background 0.2s;
}

.restore-btn:hover {
  background: #f0fdf4;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  border: 2px solid #667eea;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: #667eea;
  color: white;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .matches-page {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .header-actions {
    justify-content: center;
  }

  .filters {
    flex-direction: column;
  }

  .filter-select {
    min-width: 100%;
  }

  .card-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .header-right {
    align-self: flex-end;
  }

  .quick-actions {
    justify-content: center;
  }

  .action-btn .text {
    display: none;
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }
}
</style>
