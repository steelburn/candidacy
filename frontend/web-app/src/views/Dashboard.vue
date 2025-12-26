<template>
  <div class="dashboard">
    <div class="page-header">
      <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Welcome back, {{ authStore.user?.name }}!</p>
      </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon candidates">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ metrics.total_candidates }}</span>
          <span class="stat-label">Total Candidates</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon vacancies">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
          </svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ metrics.total_vacancies }}</span>
          <span class="stat-label">Open Vacancies</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon interviews">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ upcomingInterviews.length }}</span>
          <span class="stat-label">Upcoming Interviews</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon offers">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ pendingOffers }}</span>
          <span class="stat-label">Pending Offers</span>
        </div>
      </div>
    </div>
    
    <!-- Dashboard Sections -->
    <div class="dashboard-grid">
      <!-- Hiring Pipeline -->
      <div class="card pipeline-card">
        <div class="card-header">
          <h2>Hiring Pipeline</h2>
        </div>
        <div v-if="pipeline" class="pipeline">
          <div v-for="(count, stage) in pipeline" :key="stage" class="pipeline-stage">
            <div class="stage-bar">
              <div class="stage-fill" :style="{ height: getStageHeight(count) }"></div>
              <span class="stage-count">{{ count }}</span>
            </div>
            <span class="stage-name">{{ formatStageName(stage) }}</span>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>No pipeline data available</p>
        </div>
      </div>
      
      <!-- Recent Candidates -->
      <div class="card candidates-card">
        <div class="card-header">
          <h2>Recent Candidates</h2>
          <router-link to="/candidates" class="view-all">View All</router-link>
        </div>
        <div class="candidates-list">
          <div v-for="candidate in recentCandidates" :key="candidate.id" class="candidate-item">
            <div class="candidate-avatar">
              {{ getInitials(candidate.name) }}
            </div>
            <div class="candidate-info">
              <span class="candidate-name">{{ candidate.name }}</span>
              <span :class="['candidate-status', 'status-' + candidate.status]">{{ candidate.status }}</span>
            </div>
            <router-link :to="`/candidates/${candidate.id}`" class="candidate-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </router-link>
          </div>
          <div v-if="recentCandidates.length === 0" class="empty-state">
            <p>No candidates yet</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { candidateAPI, vacancyAPI, interviewAPI, reportAPI } from '../services/api'

const authStore = useAuthStore()

const metrics = ref({ total_candidates: 0, total_vacancies: 0 })
const pipeline = ref(null)
const recentCandidates = ref([])
const upcomingInterviews = ref([])
const pendingOffers = ref(0)

const maxPipelineCount = computed(() => {
  if (!pipeline.value) return 1
  return Math.max(...Object.values(pipeline.value), 1)
})

onMounted(async () => {
  try {
    const [candidateMetrics, vacancyMetrics, pipelineData, candidates, interviews] = await Promise.all([
      reportAPI.candidateMetrics(),
      reportAPI.vacancyMetrics(),
      reportAPI.pipeline(),
      candidateAPI.list({ per_page: 5 }),
      interviewAPI.upcoming()
    ])
    
    metrics.value = {
      total_candidates: candidateMetrics.data.total_candidates,
      total_vacancies: vacancyMetrics.data.total_vacancies
    }
    pipeline.value = pipelineData.data
    recentCandidates.value = candidates.data.data || []
    upcomingInterviews.value = interviews.data || []
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  }
})

const formatStageName = (stage) => {
  return stage.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getStageHeight = (count) => {
  const percentage = (count / maxPipelineCount.value) * 100
  return `${Math.max(percentage, 10)}%`
}

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<style scoped>
.dashboard {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  margin-bottom: 4px;
}

.subtitle {
  color: var(--text-secondary, #6b7280);
  font-size: 0.95rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--bg-primary, #ffffff);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
  transition: all 0.2s ease;
}

.stat-card:hover {
  box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
  transform: translateY(-2px);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon svg {
  width: 24px;
  height: 24px;
  color: white;
}

.stat-icon.candidates {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.stat-icon.vacancies {
  background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
}

.stat-icon.interviews {
  background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
}

.stat-icon.offers {
  background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary, #111827);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-secondary, #6b7280);
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

@media (max-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: var(--bg-primary, #ffffff);
  border-radius: 12px;
  box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-gray-100, #f3f4f6);
}

.card-header h2 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.view-all {
  font-size: 0.875rem;
  color: var(--color-primary, #6366f1);
  text-decoration: none;
  font-weight: 500;
}

.view-all:hover {
  text-decoration: underline;
}

/* Pipeline */
.pipeline-card .pipeline {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  padding: 24px 20px;
  height: 200px;
  gap: 12px;
}

.pipeline-stage {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.stage-bar {
  width: 100%;
  max-width: 60px;
  height: 120px;
  background: var(--color-gray-100, #f3f4f6);
  border-radius: 8px;
  position: relative;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
}

.stage-fill {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 8px;
  transition: height 0.5s ease;
}

.stage-count {
  position: relative;
  z-index: 1;
  width: 100%;
  text-align: center;
  padding: 8px 0;
  font-weight: 700;
  font-size: 1rem;
  color: white;
  text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.stage-name {
  font-size: 0.7rem;
  color: var(--text-secondary, #6b7280);
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Candidates List */
.candidates-list {
  padding: 8px;
}

.candidate-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  transition: background 0.2s ease;
}

.candidate-item:hover {
  background: var(--color-gray-50, #f9fafb);
}

.candidate-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
}

.candidate-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.candidate-name {
  font-weight: 500;
  color: var(--text-primary, #111827);
}

.candidate-status {
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
  width: fit-content;
}

.status-draft { background: #eceff1; color: #607d8b; }
.status-new { background: #e3f2fd; color: #1976d2; }
.status-reviewing { background: #fff3e0; color: #f57c00; }
.status-shortlisted { background: #e8f5e9; color: #388e3c; }
.status-interviewed { background: #f3e5f5; color: #7b1fa2; }
.status-offered { background: #e0f2f1; color: #00796b; }
.status-hired { background: #c8e6c9; color: #2e7d32; }
.status-rejected { background: #ffebee; color: #c62828; }

.candidate-link {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: var(--text-secondary, #6b7280);
  transition: all 0.2s ease;
}

.candidate-link:hover {
  background: var(--color-gray-100, #f3f4f6);
  color: var(--color-primary, #6366f1);
}

.candidate-link svg {
  width: 18px;
  height: 18px;
}

.empty-state {
  padding: 32px;
  text-align: center;
  color: var(--text-secondary, #6b7280);
}
</style>
