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
        
        <table v-else class="matches-table">
            <thead>
            <tr>
                <th>Candidate</th>
                <th>Vacancy</th>
                <th>Date</th>
                <th>Score</th>
                <th class="text-right">Actions</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="match in activeMatches" :key="match.id">
                <tr class="match-summary-row" :class="{ 'expanded': isExpanded(match.id) }" @click="toggleMatch(match.id)">
                <td>
                    <div class="candidate-cell">
                    <span class="name">{{ getCandidateName(match.candidate_id) }}</span>
                    </div>
                </td>
                <td>
                    <div class="vacancy-cell">
                    <span class="title">{{ getVacancyTitle(match.vacancy_id) }}</span>
                    </div>
                </td>
                <td>
                    <span class="date">{{ formatDate(match.created_at) }}</span>
                </td>
                <td>
                    <div class="score-badge" :class="getScoreClass(match.match_score)">
                    {{ match.match_score }}%
                    </div>
                </td>
                <td class="text-right">
                    <button class="btn-icon" @click.stop="openInterviewModal(match)" title="Schedule Interview">
                    📅
                    </button>
                    <button class="btn-icon" @click.stop="openOfferModal(match)" title="Create Offer">
                    📜
                    </button>
                    <button class="btn-icon delete-btn" @click.stop="dismissMatch(match)" title="Dismiss Match">
                    ❌
                    </button>
                    <button class="btn-icon" @click.stop="toggleMatch(match.id)">
                    {{ isExpanded(match.id) ? '▲' : '▼' }}
                    </button>
                </td>
                </tr>
                
                <tr v-if="isExpanded(match.id)" class="match-detail-row">
                <td colspan="5">
                    <div class="detail-container">
                        <div class="detail-actions">
                            <button class="btn-primary btn-sm" @click="openInterviewModal(match)">Schedule Interview</button>
                            <button class="btn-secondary btn-sm" @click="openOfferModal(match)">Create Offer</button>
                            <router-link :to="`/candidates/${match.candidate_id}`" class="btn-sm">View Profile</router-link>
                            <router-link :to="`/vacancies/${match.vacancy_id}`" class="btn-sm">View Vacancy</router-link>
                        </div>

                        <!-- Structured Analysis View -->
                        <div v-if="parseAnalysis(match.analysis)" class="analysis-grid">
                        <div class="analysis-section strengths">
                            <h4>✅ Strengths</h4>
                            <ul>
                            <li v-for="(point, i) in parseAnalysis(match.analysis).strengths" :key="i">
                                {{ point }}
                            </li>
                            </ul>
                        </div>

                        <div class="analysis-section gaps">
                            <h4>⚠️ Gaps</h4>
                            <ul>
                            <li v-for="(point, i) in parseAnalysis(match.analysis).gaps" :key="i">
                                {{ point }}
                            </li>
                            </ul>
                        </div>

                        <div class="analysis-section recommendation">
                            <h4>💡 Recommendation</h4>
                            <p>{{ parseAnalysis(match.analysis).recommendation }}</p>
                        </div>
                        </div>

                        <div v-else class="match-analysis fallback">
                        <p>{{ truncateAnalysis(match.analysis) }}</p>
                        </div>
                    </div>
                </td>
                </tr>
            </template>
            </tbody>
        </table>
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
  
  // Check if it follows the structured format
  const hasStructure = text.includes('SCORE:') && text.includes('STRENGTHS:') && text.includes('GAPS:')
  if (!hasStructure) return null
  
  try {
    const sections = {
      strengths: [],
      gaps: [],
      recommendation: ''
    }
    
    // Extract Strengths
    const strengthsMatch = text.match(/(?:STRENGTHS?|STRENGHTHS?|STRENTHS?)\s*:([\s\S]*?)(?=(?:GAPS?|WEAKNESS(?:ES)?)\s*:|RECOMMENDATION\s*:|$)/i)
    if (strengthsMatch && strengthsMatch[1]) {
      sections.strengths = strengthsMatch[1]
        .split('\n')
        .map(line => line.trim())
        .filter(line => (line.startsWith('-') || line.startsWith('•')) && line.length > 2)
        .map(line => line.substring(1).trim())
    }
    
    // Extract Gaps
    const gapsMatch = text.match(/(?:GAPS?|WEAKNESS(?:ES)?)\s*:([\s\S]*?)(?=RECOMMENDATION\s*:|$)/i)
    if (gapsMatch && gapsMatch[1]) {
      sections.gaps = gapsMatch[1]
        .split('\n')
        .map(line => line.trim())
        .filter(line => (line.startsWith('-') || line.startsWith('•')) && line.length > 2)
        .map(line => line.substring(1).trim())
    }
    
    // Extract Recommendation
    const recMatch = text.match(/RECOMMENDATION\s*:([\s\S]*)/i)
    if (recMatch && recMatch[1]) {
      sections.recommendation = recMatch[1].trim()
    }
    
    return sections
  } catch (e) {
    console.error('Error parsing analysis:', e)
    return null
  }
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
.matches-page {
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0;
  color: #333;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  min-width: 200px;
  background: white;
}

.loading {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  color: #666;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  color: #333;
}

.empty-state p {
  color: #666;
  margin: 0;
}



.label {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #999;
  text-transform: uppercase;
}

.match-arrow {
  font-size: 1.5rem;
  color: #667eea;
}

.match-analysis {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #666;
}

.match-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.btn-sm {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.875rem;
  text-decoration: none;
  background: #667eea;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-sm:hover {
  background: #5a6fd6;
}

.btn-secondary {
  background: white;
  color: #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 500;
  border: 2px solid #667eea;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #667eea;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.match-score-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: bold;
    min-width: 80px;
}

.score-value {
    font-size: 1.25rem;
    line-height: 1;
}

.score-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    opacity: 0.8;
}

.match-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: #666;
}

.analysis-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.analysis-section {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    padding: 1.75rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.6);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.analysis-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    transition: all 0.3s ease;
}

.analysis-section:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
}

.analysis-section h4 {
    margin: 0 0 1.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #1a202c;
    font-size: 1.125rem;
    font-weight: 700;
}

.analysis-section ul {
    margin: 0;
    padding-left: 1.5rem;
    list-style: none;
}

.analysis-section li {
    margin-bottom: 0.75rem;
    color: #4a5568;
    line-height: 1.6;
    position: relative;
    padding-left: 0.5rem;
}

.analysis-section li::before {
    content: '▸';
    position: absolute;
    left: -1rem;
    font-weight: bold;
}

.analysis-section.strengths {
    border-left: none;
    background: linear-gradient(135deg, rgba(240, 255, 244, 0.9), rgba(220, 252, 231, 0.9));
}

.analysis-section.strengths::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.analysis-section.strengths li::before {
    color: #10b981;
}

.analysis-section.gaps {
    border-left: none;
    background: linear-gradient(135deg, rgba(255, 249, 230, 0.9), rgba(254, 243, 199, 0.9));
}

.analysis-section.gaps::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.analysis-section.gaps li::before {
    color: #f59e0b;
}

.analysis-section.recommendation {
    grid-column: 1 / -1;
    border-left: none;
    background: linear-gradient(135deg, rgba(227, 242, 253, 0.9), rgba(219, 234, 254, 0.9));
}

.analysis-section.recommendation::before {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.analysis-section.recommendation p {
    margin: 0;
    color: #1e40af;
    font-weight: 500;
    line-height: 1.7;
    font-size: 1.0625rem;
}

@media (max-width: 768px) {
    .analysis-grid {
        grid-template-columns: 1fr;
    }
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.match-date {
  text-align: center;
  font-size: 0.75rem;
  color: #999;
}

/* Sections */
.matches-content {
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

.section-title {
    font-size: 1.25rem;
    color: #333;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.section-title.text-muted {
    color: #999;
}

.empty-section {
    padding: 2rem;
    text-align: center;
    color: #888;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Table Styles - Ensure consistency */
.matches-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.matches-table th {
    text-align: left;
    padding: 1rem;
    background: #f8f9fa;
    color: #666;
    font-weight: 600;
    border-bottom: 2px solid #eee;
}

.dismissed-section {
    opacity: 0.8;
}

.dismissed-table th {
    background: #fff;
}

.dismissed-table td {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    color: #777;
}

.delete-btn {
    color: #dc3545;
}

.delete-btn:hover {
    background: #fee2e2;
    border-radius: 50%;
}
</style>
