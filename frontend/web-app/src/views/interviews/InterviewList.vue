<template>
  <div class="interview-list">
    <div class="header">
      <h1>Interviews</h1>
      <p>Manage and schedule candidate interviews</p>
    </div>
    <div class="actions">
      <router-link to="/interviews/calendar" class="btn-secondary">Calendar View</router-link>
      <button class="btn-primary" @click="showCreateModal = true">+ Schedule Interview</button>
    </div>
    
    <div class="filters">
      <div class="tabs">
        <button 
          @click="currentTab = 'upcoming'" 
          :class="{ active: currentTab === 'upcoming' }"
        >
          Upcoming
        </button>
        <button 
          @click="currentTab = 'all'" 
          :class="{ active: currentTab === 'all' }"
        >
          All Interviews
        </button>
      </div>
    </div>
    
    <div v-if="loading" class="loading">Loading interviews...</div>
    
    <div v-else class="interviews-grid">
      <div 
        v-for="interview in interviews" 
        :key="interview.id" 
        class="interview-card"
        @click="openInterview(interview)"
        style="cursor: pointer"
      >
        <div class="interview-header">
          <div>
            <h3>{{ getCandidateName(interview) }}</h3>
            <p class="vacancy">{{ getVacancyTitle(interview) }}</p>
          </div>
          <span :class="'status status-' + interview.status">{{ interview.status }}</span>
        </div>
        
        <div class="interview-details">
          <div class="detail-row">
            <span class="icon">📅</span>
            <span>{{ formatDateTime(interview.scheduled_at) }}</span>
          </div>
          <div class="detail-row">
            <span class="icon">⏱️</span>
            <span>{{ interview.duration_minutes }} minutes</span>
          </div>
          <div class="detail-row">
            <span class="icon">🎯</span>
            <span class="capitalize">{{ interview.stage }}</span>
          </div>
          <div class="detail-row">
            <span class="icon">📍</span>
            <span>
              <span class="capitalize">{{ interview.type?.replace('_', ' ') }}</span>
              <span v-if="interview.location">: {{ interview.location }}</span>
            </span>
          </div>
          <div class="detail-row" v-if="interview.interviewer_ids && interview.interviewer_ids.length">
            <span class="icon">👥</span>
            <span>{{ getInterviewerNames(interview) }}</span>
          </div>
        </div>
        
        <div v-if="interview.feedback && interview.feedback.length" class="feedback-summary">
          <strong>Feedback:</strong> {{ interview.feedback.length }} review(s)
        </div>
    </div>
  </div>
    
    <div v-if="!loading && interviews.length === 0" class="empty-state">
      <p>No interviews found</p>
    </div>

    <InterviewModal 
      :show="showCreateModal"
      :interview="selectedInterview"
      :candidate-name="selectedInterview ? getCandidateName(selectedInterview) : ''"
      :vacancy-title="selectedInterview ? getVacancyTitle(selectedInterview) : ''"
      @close="closeModal"
      @created="handleSaved"
      @updated="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { interviewAPI, candidateAPI, vacancyAPI, userAPI } from '../../services/api'
import InterviewModal from './InterviewModal.vue'
import { useFormat } from '../../composables/useFormat.js'

const { formatDateTime } = useFormat()
const interviews = ref([])
const loading = ref(false)
const currentTab = ref('upcoming')
const showCreateModal = ref(false)
const selectedInterview = ref(null)

const candidates = ref({})
const vacancies = ref({})
const users = ref({})

const openInterview = (interview) => {
    selectedInterview.value = interview
    showCreateModal.value = true
}

const closeModal = () => {
    showCreateModal.value = false
    selectedInterview.value = null
}

const handleSaved = () => {
  fetchInterviews()
}

const fetchMetadata = async () => {
    // 1. Candidates
    const uniqueCandidateIds = [...new Set(interviews.value.map(i => i.candidate_id))]
    if (uniqueCandidateIds.length > 0) {
        // Simple strategy: check if missing
        const missing = uniqueCandidateIds.filter(id => !candidates.value[id])
        if (missing.length > 0) {
             try {
                // Fetch bulk mostly for simplicity
                const cRes = await candidateAPI.list({ per_page: 1000 })
                cRes.data.data.forEach(c => {
                    candidates.value[c.id] = c.name || `${c.first_name} ${c.last_name}`
                })
             } catch (e) {
                 console.error("Failed candidates fetch", e)
             }
        }
    }

    // 2. Vacancies
    const uniqueVacancyIds = [...new Set(interviews.value.map(i => i.vacancy_id))]
    if (uniqueVacancyIds.length > 0) {
        const missing = uniqueVacancyIds.filter(id => !vacancies.value[id])
        if (missing.length > 0) {
             try {
                const vRes = await vacancyAPI.list({ per_page: 1000 })
                const vData = vRes.data.data || vRes.data || []
                vData.forEach(v => {
                    vacancies.value[v.id] = v.title
                })
             } catch (e) {
                 console.error("Failed vacancies fetch", e)
             }
        }
    }

    // 3. Users (Interviewers)
    const allInterviewerIds = interviews.value.flatMap(i => i.interviewer_ids || [])
    const uniqueUserIds = [...new Set(allInterviewerIds)]
    if (uniqueUserIds.length > 0) {
        const missing = uniqueUserIds.filter(id => !users.value[id])
        if (missing.length > 0) {
             try {
                 const uRes = await userAPI.list()
                 const uData = uRes.data.data || uRes.data || []
                 uData.forEach(u => {
                     users.value[u.id] = u.name
                 })
             } catch (e) {
                 console.error("Failed users fetch", e)
             }
        }
    }
}

const fetchInterviews = async () => {
  loading.value = true
  try {
    let response
    if (currentTab.value === 'upcoming') {
      response = await interviewAPI.upcoming()
      interviews.value = response.data
    } else {
      response = await interviewAPI.list()
      interviews.value = response.data.data || response.data
    }
    
    // Fetch related metadata
    await fetchMetadata()
    
  } catch (error) {
    console.error('Failed to fetch interviews:', error)
  } finally {
    loading.value = false
  }
}

const handleCreated = () => {
  fetchInterviews()
}

// formatDateTime is now imported from composable

const getCandidateName = (interview) => {
    return candidates.value[interview.candidate_id] || `Candidate #${interview.candidate_id}`
}

const getVacancyTitle = (interview) => {
    return vacancies.value[interview.vacancy_id] || `Vacancy #${interview.vacancy_id}`
}

const getInterviewerNames = (interview) => {
     if (!interview.interviewer_ids || interview.interviewer_ids.length === 0) return 'TBD'
     return interview.interviewer_ids.map(id => users.value[id] || `User #${id}`).join(', ')
}

watch(currentTab, () => {
  fetchInterviews()
})

onMounted(() => {
  fetchInterviews()
})
</script>

<style scoped>
.interview-list {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  padding: 0 0.5rem;
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  margin: 0;
  letter-spacing: -0.02em;
}

.filters {
  margin-bottom: 2.5rem;
}

.tabs {
  display: inline-flex;
  background: white;
  padding: 0.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.tabs button {
  padding: 0.75rem 2rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  color: #64748b;
  transition: all 0.3s ease;
}

.tabs button.active {
  background: #f1f5f9;
  color: #1a202c;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.interviews-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 2rem;
  padding: 0.5rem;
}

.interview-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
}

.interview-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border-color: #667eea;
}

.interview-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.interview-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.1rem;
  font-weight: 700;
}

.vacancy {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0.25rem 0 0 0;
  font-weight: 500;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  color: #4a5568;
  font-size: 0.95rem;
}

.icon {
  width: 24px;
  text-align: center;
}

.capitalize {
  text-transform: capitalize;
}

.status {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-scheduled { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
.status-completed { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
.status-cancelled { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.status-rescheduled { background: #fff3e0; color: #c2410c; border: 1px solid #fed7aa; }

.feedback-summary {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
  color: #667eea;
  font-weight: 500;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.875rem 2rem;
  border-radius: 12px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
  transition: all 0.3s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.5);
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  background: white;
  color: #4a5568;
  padding: 0.875rem 2rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #667eea;
  color: #667eea;
  transform: translateY(-2px);
}

.loading, .empty-state {
  text-align: center;
  padding: 4rem;
  color: #94a3b8;
  font-size: 1.1rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
</style>
