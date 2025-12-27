<template>
  <div 
    class="match-card"
    :class="{ 'expanded': isExpanded }"
  >
    <!-- Card Header -->
    <div class="card-header" @click="$emit('toggle')">
      <div class="header-left">
        <div class="candidate-info">
          <span class="candidate-name">{{ candidateName }}</span>
          <span class="match-arrow">→</span>
          <span class="vacancy-title">{{ vacancyTitle }}</span>
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
      <button class="action-btn interview" @click.stop="$emit('interview', match)" title="Schedule Interview">
        <span class="icon">📅</span>
        <span class="text">Interview</span>
      </button>
      <button class="action-btn offer" @click.stop="$emit('offer', match)" title="Create Offer">
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
      <button class="action-btn dismiss" @click.stop="$emit('dismiss', match)" title="Dismiss">
        <span class="icon">❌</span>
      </button>
      <button class="action-btn expand" @click.stop="$emit('toggle')">
        <span class="icon">{{ isExpanded ? '▲' : '▼' }}</span>
      </button>
    </div>

    <!-- Expanded Analysis -->
    <div v-if="isExpanded" class="card-details">
      <!-- Structured Analysis View -->
      <div v-if="hasStructuredAnalysis" class="analysis-grid">
        <div class="analysis-section strengths">
          <h4><span class="section-icon">✅</span> Strengths</h4>
          <ul v-if="parsed.strengths.length > 0">
            <li v-for="(point, i) in parsed.strengths" :key="i">
              {{ point }}
            </li>
          </ul>
          <p v-else class="no-data">No specific strengths highlighted.</p>
        </div>

        <div class="analysis-section gaps">
          <h4><span class="section-icon">⚠️</span> Gaps</h4>
          <ul v-if="parsed.gaps.length > 0">
            <li v-for="(point, i) in parsed.gaps" :key="i">
              {{ point }}
            </li>
          </ul>
          <p v-else class="no-data">No specific gaps identified.</p>
        </div>

        <div class="analysis-section recommendation">
          <h4><span class="section-icon">💡</span> Recommendation</h4>
          <p>{{ parsed.recommendation }}</p>
        </div>
      </div>

      <div v-else class="match-analysis fallback">
        <p>{{ truncateAnalysis(match.analysis) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { parseAnalysis, getScoreClass, truncateAnalysis, formatDate } from '../../composables/useMatchAnalysis'

const props = defineProps({
  match: {
    type: Object,
    required: true
  },
  candidateName: {
    type: String,
    default: 'Unknown Candidate'
  },
  vacancyTitle: {
    type: String,
    default: 'Unknown Vacancy'
  },
  isExpanded: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggle', 'dismiss', 'interview', 'offer'])

const parsed = computed(() => parseAnalysis(props.match.analysis))
const hasStructuredAnalysis = computed(() => !!parsed.value)
</script>

<style scoped>
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
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
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
  color: #334155;
  line-height: 1.5;
}

.analysis-section.strengths li::before {
  content: '✓';
  color: #22c55e;
  position: absolute;
  left: 0;
  font-weight: bold;
}

.analysis-section.gaps li::before {
  content: '!';
  color: #f59e0b;
  position: absolute;
  left: 0;
  font-weight: bold;
}

.no-data {
  color: #94a3b8;
  font-style: italic;
  margin: 0;
}

.match-analysis.fallback {
  color: #475569;
  line-height: 1.6;
}
</style>
