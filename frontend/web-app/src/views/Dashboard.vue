<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
    <p class="welcome">Welcome back, {{ authStore.user?.name }}!</p>
    
    <div class="stats-grid">
      <div class="stat-card">
        <h3>{{ metrics.total_candidates }}</h3>
        <p>Total Candidates</p>
      </div>
      <div class="stat-card">
        <h3>{{ metrics.total_vacancies }}</h3>
        <p>Open Vacancies</p>
      </div>
      <div class="stat-card">
        <h3>{{ upcomingInterviews.length }}</h3>
        <p>Upcoming Interviews</p>
      </div>
      <div class="stat-card">
        <h3>{{ pendingOffers }}</h3>
        <p>Pending Offers</p>
      </div>
    </div>
    
    <div class="dashboard-sections">
      <div class="section">
        <h2>Hiring Pipeline</h2>
        <div v-if="pipeline" class="pipeline">
          <div v-for="(count, stage) in pipeline" :key="stage" class="pipeline-stage">
            <div class="stage-count">{{ count }}</div>
            <div class="stage-name">{{ formatStageName(stage) }}</div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <h2>Recent Candidates</h2>
        <div class="list">
          <div v-for="candidate in recentCandidates" :key="candidate.id" class="list-item">
            <div>
              <strong>{{ candidate.name }}</strong>
              <span class="status">{{ candidate.status }}</span>
            </div>
            <router-link :to="`/candidates/${candidate.id}`" class="link">View</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { candidateAPI, vacancyAPI, interviewAPI, reportAPI } from '../services/api'

const authStore = useAuthStore()

const metrics = ref({ total_candidates: 0, total_vacancies: 0 })
const pipeline = ref(null)
const recentCandidates = ref([])
const upcomingInterviews = ref([])
const pendingOffers = ref(0)

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
</script>

<style scoped>
.dashboard {
  animation: fadeIn 0.5s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.welcome {
  color: #666;
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  text-align: center;
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-card h3 {
  font-size: 2.5rem;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.stat-card p {
  color: #666;
  font-size: 0.875rem;
}

.dashboard-sections {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
}

.section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section h2 {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  color: #333;
}

.pipeline {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
}

.pipeline-stage {
  flex: 1;
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stage-count {
  font-size: 2rem;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.stage-name {
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.list-item:last-child {
  border-bottom: none;
}

.status {
  display: inline-block;
  margin-left: 1rem;
  padding: 0.25rem 0.75rem;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 12px;
  font-size: 0.75rem;
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.link:hover {
  text-decoration: underline;
}
</style>
