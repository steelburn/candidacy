<template>
  <div class="tab-content">
    <div class="match-controls">
        <button @click="$emit('refresh')" class="btn-secondary" :disabled="loading">
            {{ loading ? 'Analyzing...' : 'Refresh Matches' }}
        </button>
    </div>
    
    <div v-if="matches.length" class="matches-container">
        <!-- Active Matches -->
        <div class="active-matches">
            <h3 class="section-title">Verified Matches ({{ activeMatches.length }})</h3>
            <div v-if="activeMatches.length === 0" class="no-active-matches">
                <p>No active matches found.</p>
            </div>
            
            <div class="matches-list">
                <div v-for="match in activeMatches" :key="match.id" class="match-card">
                <div class="match-header">
                    <h3>{{ match.vacancy_title }}</h3>
                    <div class="header-right">
                            <div class="match-score-badge" :class="getScoreClass(match.match_score)">
                            <span class="score-value">{{ match.match_score }}%</span>
                            <span class="score-label">Match</span>
                        </div>
                        <button @click="dismiss(match)" class="btn-icon" title="Dismiss Match">
                            ❌
                        </button>
                    </div>
                </div>

                <div class="match-meta">
                    <span class="match-date"><i class="icon">📅</i> {{ formatDate(match.created_at) }}</span>
                    <span class="match-status" :class="'status-' + match.status">{{ match.status }}</span>
                </div>

                <!-- Vacancy Details -->
                <div v-if="match.vacancy" class="vacancy-details">
                    <h4><span class="icon">💼</span> Position Details</h4>
                    <div class="vacancy-grid">
                        <div class="vacancy-item" v-if="match.vacancy.department">
                            <span class="label">Department:</span>
                            <span class="value">{{ match.vacancy.department }}</span>
                        </div>
                        <div class="vacancy-item" v-if="match.vacancy.location">
                            <span class="label">Location:</span>
                            <span class="value">{{ match.vacancy.location }}</span>
                        </div>
                        <div class="vacancy-item" v-if="match.vacancy.employment_type">
                            <span class="label">Type:</span>
                            <span class="value">{{ match.vacancy.employment_type }}</span>
                        </div>
                        <div class="vacancy-item" v-if="match.vacancy.salary_range">
                            <span class="label">Salary:</span>
                            <span class="value">{{ match.vacancy.salary_range }}</span>
                        </div>
                    </div>
                    <div v-if="match.vacancy.required_skills && match.vacancy.required_skills.length > 0" class="vacancy-skills">
                        <span class="label">Required Skills:</span>
                        <div class="skills-tags">
                            <span v-for="(skill, idx) in getSkillsArray(match.vacancy.required_skills)" :key="idx" class="skill-tag">
                                {{ skill }}
                            </span>
                        </div>
                    </div>
                    <div v-if="match.vacancy.description" class="vacancy-description">
                        <button @click="toggleVacancyDesc(match.vacancy_id)" class="desc-toggle">
                            <span v-if="expandedVacancies[match.vacancy_id]">▼</span>
                            <span v-else>▶</span>
                            View Full Job Description
                        </button>
                        <div v-if="expandedVacancies[match.vacancy_id]" class="desc-content">
                            <MarkdownRenderer :content="match.vacancy.description" />
                        </div>
                    </div>
                </div>

                <!-- Structured Analysis View -->
                <div v-if="parseAnalysis(match.analysis)" class="analysis-grid">
                    
                    <div class="analysis-section strengths">
                    <h4><span class="icon">✅</span> Strengths</h4>
                    <ul v-if="parseAnalysis(match.analysis).strengths.length > 0">
                        <li v-for="(point, i) in parseAnalysis(match.analysis).strengths" :key="i">
                        {{ point }}
                        </li>
                    </ul>
                    <p v-else class="text-muted" style="font-style: italic;">No specific strengths highlighted.</p>
                    </div>

                    <div class="analysis-section gaps">
                    <h4><span class="icon">⚠️</span> Gaps</h4>
                    <ul v-if="parseAnalysis(match.analysis).gaps.length > 0">
                        <li v-for="(point, i) in parseAnalysis(match.analysis).gaps" :key="i">
                        {{ point }}
                        </li>
                    </ul>
                    <p v-else class="text-muted" style="font-style: italic;">No specific gaps identified.</p>
                    </div>
                </div>

                <!-- Fallback Logic for Recommendation -->
                <div class="analysis-section recommendation">
                    <h4><span class="icon">💡</span> Recommendation</h4>
                    <p v-if="parseAnalysis(match.analysis)">{{ parseAnalysis(match.analysis).recommendation }}</p>
                    <div v-else class="legacy-analysis">
                         <MarkdownRenderer :content="match.analysis || 'No analysis available.'" />
                    </div>
                </div>

                <!-- Interview Questions Section -->
                <div class="interview-questions-section">
                    <div class="section-header">
                        <h4><span class="icon">❓</span> Interview Questions</h4>
                        <button 
                            @click="generateQuestions(match)" 
                            class="btn-sm" 
                            :disabled="generatingQuestions[match.vacancy_id]"
                        >
                            {{ generatingQuestions[match.vacancy_id] ? 'Generating...' : (match.interview_questions ? 'Regenerate Questions' : 'Generate Questions') }}
                        </button>
                    </div>

                    <div v-if="match.interview_questions && match.interview_questions.length > 0" class="questions-list">
                         <div class="questions-actions">
                            <button @click="copyQuestions(match)" class="btn-text">
                                {{ copyingQuestions[match.vacancy_id] ? 'Copied!' : 'Copy All' }}
                            </button>
                        </div>
                        <div v-for="(q, idx) in match.interview_questions" :key="idx" class="question-item">
                            <div class="question-content">
                                <span class="q-number">{{ idx + 1 }}.</span>
                                <p class="q-text">{{ q.question }}</p>
                            </div>
                            <div class="question-meta">
                                <span class="q-type" :class="q.type">{{ q.type }}</span>
                                <button @click="toggleHint(match.vacancy_id, idx)" class="btn-hint">
                                    {{ expandedHints[`${match.vacancy_id}-${idx}`] ? 'Hide Hint' : 'Show Hint' }}
                                </button>
                            </div>
                            <div v-if="expandedHints[`${match.vacancy_id}-${idx}`]" class="hint-box">
                                <strong>What to look for:</strong> {{ q.hint || q.expected_answer }}
                            </div>
                            
                            <!-- Discussion Notes -->
                            <div class="discussion-box">
                                <textarea 
                                    v-model="q.discussion" 
                                    placeholder="Add notes from interview discussion..."
                                    rows="2"
                                    @change="$emit('save-discussion', match.vacancy_id, idx, q.discussion)"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="!generatingQuestions[match.vacancy_id]" class="no-questions">
                        <p>No interview questions generated yet.</p>
                        <button @click="generateQuestions(match)" class="btn-link">Generate suggested questions based on analysis</button>
                    </div>
                </div>

                <div class="match-actions">
                    <router-link :to="`/interviews/create?candidate=${candidateId}&vacancy=${match.vacancy_id}`" class="btn-primary">Schedule Interview</router-link>
                    <router-link :to="`/offers/create?candidate=${candidateId}&vacancy=${match.vacancy_id}`" class="btn-primary">Create Offer</router-link>
                </div>
                </div>
            </div>
        </div>

        <!-- Dismissed Matches -->
        <div v-if="dismissedMatches.length > 0" class="dismissed-matches">
            <h3 class="section-title text-muted">Dismissed Matches ({{ dismissedMatches.length }})</h3>
            <div class="matches-list">
                <div v-for="match in dismissedMatches" :key="match.id" class="match-card dismissed">
                    <div class="match-header">
                        <h3>{{ match.vacancy_title }}</h3>
                        <div class="header-right">
                            <span class="match-score-badge">{{ match.match_score }}%</span>
                            <button @click="$emit('restore', match)" class="btn-text">Undo Dismiss</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div v-else-if="!loading" class="no-data">
        <p>No matches found specific to this candidate. Click verify to run analysis.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import MarkdownRenderer from '../MarkdownRenderer.vue'
import { parseAnalysis, getScoreClass, formatDate, getSkillsArray } from '../../composables/useMatchAnalysis'

const props = defineProps({
  matches: {
    type: Array,
    required: true
  },
  candidateId: {
    type: [String, Number],
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh', 'dismiss', 'restore', 'generate-questions', 'save-discussion'])

// Local state for UI toggles
const expandedVacancies = ref({})
const expandedHints = ref({})
const generatingQuestions = ref({})
const copyingQuestions = ref({})

const activeMatches = computed(() => props.matches.filter(m => m.status !== 'dismissed'))
const dismissedMatches = computed(() => props.matches.filter(m => m.status === 'dismissed'))

const toggleVacancyDesc = (vacancyId) => {
    expandedVacancies.value[vacancyId] = !expandedVacancies.value[vacancyId]
}

const toggleHint = (vacancyId, questionIdx) => {
    const key = `${vacancyId}-${questionIdx}`
    expandedHints.value[key] = !expandedHints.value[key]
}

const generateQuestions = (match) => {
    generatingQuestions.value[match.vacancy_id] = true
    emit('generate-questions', match, () => {
        generatingQuestions.value[match.vacancy_id] = false
    })
}

const dismiss = (match) => {
    if (confirm('Are you sure you want to dismiss this match? It will be hidden from the main list.')) {
        emit('dismiss', match)
    }
}

const copyQuestions = async (match) => {
    if (!match.interview_questions) return
    
    const text = match.interview_questions.map((q, i) => 
        `${i+1}. ${q.question}\n(Type: ${q.type})`
    ).join('\n\n')
    
    try {
        await navigator.clipboard.writeText(text)
        copyingQuestions.value[match.vacancy_id] = true
        setTimeout(() => {
            copyingQuestions.value[match.vacancy_id] = false
        }, 2000)
    } catch (e) {
        alert('Failed to copy questions')
    }
}
</script>

<style scoped>
.matches-container {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.match-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.match-card.dismissed {
  opacity: 0.7;
  background: #f9f9f9;
}

.match-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.match-score-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #f0f0f0;
  padding: 5px 10px;
  border-radius: 6px;
  font-weight: bold;
}

.score-high { background: #d4edda; color: #155724; }
.score-medium { background: #fff3cd; color: #856404; }
.score-low { background: #f8d7da; color: #721c24; }

.match-meta {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  font-size: 0.9em;
  color: #666;
}

.analysis-grid {
  display: grid;
  gap: 20px;
  margin: 20px 0;
  grid-template-columns: 1fr;
}

@media (min-width: 768px) {
  .analysis-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.analysis-section {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
}

.analysis-section.strengths { border-left: 4px solid #2ecc71; }
.analysis-section.gaps { border-left: 4px solid #e74c3c; }
.analysis-section.recommendation { border-left: 4px solid #3498db; margin-top: 10px; }

.analysis-section ul {
  padding-left: 20px;
  margin: 0;
}

.vacancy-details {
  background: #fff;
  border: 1px solid #eee;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 20px;
}

.vacancy-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  margin-bottom: 10px;
}

.skills-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 5px;
}

.skill-tag {
    background: #eef2f7;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85em;
    color: #4a5568;
}

.interview-questions-section {
    margin-top: 20px;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.question-item {
    background: #fff;
    border: 1px solid #eef;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 10px;
}

.question-meta {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 0.85em;
}

.q-type {
    background: #e3f2fd;
    padding: 2px 6px;
    border-radius: 4px;
    color: #1976d2;
}

.hint-box {
    margin-top: 10px;
    background: #fff3cd;
    padding: 10px;
    border-radius: 4px;
    font-size: 0.9em;
}

.discussion-box textarea {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    margin-top: 10px;
    font-size: 0.9em;
}

.match-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2em;
}

.section-title {
  color: #2c3e50;
  border-bottom: 2px solid #eee;
  padding-bottom: 10px;
  margin-bottom: 20px;
}

.no-data {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 8px;
    color: #666;
}

/* Button styles that mimic parent for local use */
.btn-sm, .btn-secondary, .btn-primary, .btn-text {
    /* Basic button styling to match generic expectations if parent styles cascade */
    cursor: pointer;
}
.btn-sm { padding: 4px 8px; font-size: 0.9em; }
.btn-text { background: none; border: none; color: #3498db; text-decoration: underline; }
</style>
