<template>
  <div class="reports">
    <h1>Reports & Analytics</h1>
    
    <div class="reports-grid">
      <div class="report-card">
        <h3>Candidate Metrics</h3>
        <div v-if="candidateMetrics" class="metrics">
          <div class="metric">
            <span class="value">{{ candidateMetrics.total_candidates }}</span>
            <span class="label">Total Candidates</span>
          </div>
          <div class="metric">
            <span class="value">{{ candidateMetrics.this_month }}</span>
            <span class="label">This Month</span>
          </div>
        </div>
        <div v-if="candidateMetrics?.by_status" class="status-breakdown">
          <h4>By Status</h4>
          <div v-for="(count, status) in candidateMetrics.by_status" :key="status" class="status-row">
            <span>{{ formatStatus(status) }}</span>
            <strong>{{ count }}</strong>
          </div>
        </div>
      </div>
      
      <div class="report-card">
        <h3>Vacancy Metrics</h3>
        <div v-if="vacancyMetrics" class="metrics">
          <div class="metric">
            <span class="value">{{ vacancyMetrics.total_vacancies }}</span>
            <span class="label">Total Vacancies</span>
          </div>
          <div class="metric">
            <span class="value">{{ vacancyMetrics.avg_time_to_fill || 'N/A' }}</span>
            <span class="label">Avg Time to Fill</span>
          </div>
        </div>
        <div v-if="vacancyMetrics?.by_status" class="status-breakdown">
          <h4>By Status</h4>
          <div v-for="(count, status) in vacancyMetrics.by_status" :key="status" class="status-row">
            <span>{{ formatStatus(status) }}</span>
            <strong>{{ count }}</strong>
          </div>
        </div>
      </div>
      
      <div class="report-card full-width">
        <h3>Hiring Pipeline</h3>
        <div v-if="pipeline" class="pipeline">
          <div v-for="(count, stage) in pipeline" :key="stage" class="pipeline-stage">
            <div class="stage-count">{{ count }}</div>
            <div class="stage-name">{{ formatStatus(stage) }}</div>
          </div>
        </div>
      </div>
      
      <div class="report-card full-width">
        <h3>Performance Metrics</h3>
        <div v-if="performance" class="performance-grid">
          <div class="perf-metric">
            <span class="perf-label">Avg Time to Hire</span>
            <span class="perf-value">{{ performance.avg_time_to_hire }}</span>
          </div>
          <div class="perf-metric">
            <span class="perf-label">Offer Acceptance Rate</span>
            <span class="perf-value">{{ performance.offer_acceptance_rate }}</span>
          </div>
          <div class="perf-metric">
            <span class="perf-label">Interview to Offer Ratio</span>
            <span class="perf-value">{{ performance.interview_to_offer_ratio }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportAPI } from '../services/api'

const candidateMetrics = ref(null)
const vacancyMetrics = ref(null)
const pipeline = ref(null)
const performance = ref(null)

const fetchReports = async () => {
  try {
    const [candidates, vacancies, pipelineData, performanceData] = await Promise.all([
      reportAPI.candidateMetrics(),
      reportAPI.vacancyMetrics(),
      reportAPI.pipeline(),
      reportAPI.performance()
    ])
    
    candidateMetrics.value = candidates.data
    vacancyMetrics.value = vacancies.data
    pipeline.value = pipelineData.data
    performance.value = performanceData.data
  } catch (error) {
    console.error('Failed to fetch reports:', error)
  }
}

const formatStatus = (status) => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

onMounted(() => {
  fetchReports()
})
</script>

<style scoped>
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.full-width {
  grid-column: 1 / -1;
}

.report-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.report-card h3 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #667eea;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.metric {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.value {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.label {
  display: block;
  font-size: 0.875rem;
  color: #666;
}

.status-breakdown h4 {
  margin-bottom: 1rem;
  color: #333;
}

.status-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #eee;
}

.status-row:last-child {
  border-bottom: none;
}

.pipeline {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
}

.pipeline-stage {
  flex: 1;
  text-align: center;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  min-width: 150px;
}

.stage-count {
  font-size: 2.5rem;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.stage-name {
  font-size: 0.875rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.perf-metric {
  text-align: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px;
}

.perf-label {
  display: block;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
  opacity: 0.9;
}

.perf-value {
  display: block;
  font-size: 2rem;
  font-weight: 700;
}
</style>
