<template>
  <div class="candidate-detail">
    <div v-if="loading" class="loading">Loading...</div>
    
    <div v-else-if="candidate">
      <div class="header">
        <div>
          <h1>{{ candidate.name }}</h1>
          <span :class="'status status-' + candidate.status">{{ candidate.status }}</span>
        </div>
        <div class="actions">
          <button @click="openLinkModal" class="btn-secondary">Generate Link</button>
          <router-link :to="`/candidates/${candidate.id}/edit`" class="btn-primary">Edit</router-link>
        </div>
      </div>

      <!-- Generate Link Modal -->
      <div v-if="showLinkModal" class="modal-overlay" @click.self="closeLinkModal">
        <div class="modal-content">
          <h3>Generate Applicant Portal Link</h3>
          <div class="form-group">
            <label>Link to Vacancy (Optional)</label>
            <select v-model="selectedVacancyId">
                <option :value="null">General Profile Update</option>
                <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
            </select>
            <p class="hint">Linking to a vacancy allows the applicant to answer specific screening questions.</p>
          </div>
          
          <div v-if="generatedLink" class="generated-result">
            <label>Portal Link:</label>
            <div class="copy-box">
                <input type="text" readonly :value="generatedLink" ref="linkInput" />
                <button @click="copyLink" class="btn-secondary">Copy</button>
            </div>
            <p class="success-msg" v-if="copied">Copied to clipboard!</p>
          </div>

          <div class="modal-actions">
            <button v-if="!generatedLink" @click="generateLink" :disabled="generating" class="btn-primary">
                {{ generating ? 'Generating...' : 'Generate Link' }}
            </button>
            <button v-else @click="closeLinkModal" class="btn-primary">Done</button>
            <button @click="closeLinkModal" class="btn-text">Close</button>
          </div>
        </div>
      </div>

      <div class="tabs">
        <button 
          @click="currentTab = 'overview'" 
          :class="{ active: currentTab === 'overview' }"
        >Overview</button>
        <button 
          @click="currentTab = 'cv'" 
          :class="{ active: currentTab === 'cv' }"
        >Original CV</button>
        <button 
          @click="currentTab = 'generated'" 
          :class="{ active: currentTab === 'generated' }"
        >Generated CV</button>
        <button 
          @click="currentTab = 'matches'" 
          :class="{ active: currentTab === 'matches' }"
        >Matches</button>
      </div>
      
      <!-- TAB: Overview -->
      <div v-if="currentTab === 'overview'" class="detail-grid">
        <div class="info-card">
          <h3>Contact Information</h3>
          <p><strong>Email:</strong> {{ candidate.email }}</p>
          <p><strong>Phone:</strong> {{ displayPhone }}</p>
          <p v-if="candidate.years_of_experience"><strong>Years of Experience:</strong> {{ candidate.years_of_experience }} years</p>
          <p><strong>LinkedIn:</strong> <a v-if="candidate.linkedin_url" :href="candidate.linkedin_url" target="_blank">View Profile</a><span v-else>N/A</span></p>
          <p v-if="candidate.github_url"><strong>GitHub:</strong> <a :href="candidate.github_url" target="_blank">View Profile</a></p>
          <p v-if="candidate.portfolio_url"><strong>Portfolio:</strong> <a :href="candidate.portfolio_url" target="_blank">View Portfolio</a></p>
        </div>
        
        <div class="info-card">
          <h3>Summary</h3>
          <p>{{ candidate.summary || 'No summary available' }}</p>
        </div>
        
        <div class="info-card">
          <h3>Skills</h3>
          <div v-if="parsedSkills.length" class="skills">
            <span v-for="skill in parsedSkills" :key="skill" class="skill-tag">{{ skill }}</span>
          </div>
          <p v-else>No skills listed</p>
        </div>
        
        <div class="info-card">
          <h3>Work Experience</h3>
          <div v-if="parsedExperience.length">
            <div v-for="(exp, index) in parsedExperience" :key="index" class="experience-item">
              <h4>{{ exp.title }}</h4>
              <p class="company">{{ exp.company }} • {{ exp.duration }}</p>
              <p class="description">{{ exp.description }}</p>
            </div>
          </div>
          <p v-else>No work experience listed</p>
        </div>
        
        <div class="info-card">
          <h3>Education</h3>
          <div v-if="parsedEducation.length">
            <div v-for="(edu, index) in parsedEducation" :key="index" class="education-item">
              <h4>{{ edu.degree }}</h4>
              <p class="institution">{{ edu.institution }} • {{ edu.year }}</p>
            </div>
          </div>
          <p v-else>No education listed</p>
        </div>
      </div>

      <!-- TAB: Original CV -->
      <div v-if="currentTab === 'cv'" class="tab-content">
        <div v-if="previewUrl" class="pdf-container">
            <iframe :src="previewUrl" width="100%" height="800px"></iframe>
        </div>
        <div v-else class="no-data">
            <p>No CV document available.</p>
        </div>
      </div>

      <!-- TAB: Generated CV -->
      <div v-if="currentTab === 'generated'" class="tab-content">
        <div class="info-card">
            <h3>Standardized Profile for Matching</h3>
            <p class="hint">This is the text representation used by the AI Model for matching analysis.</p>
            <MarkdownRenderer :content="candidate.generated_cv_content || 'No generated content available.'" />
        </div>
      </div>

      <!-- TAB: Matches -->
      <div v-if="currentTab === 'matches'" class="tab-content">
        <div class="match-controls">
            <button @click="viewMatches" class="btn-secondary" :disabled="loadingMatches">
                {{ loadingMatches ? 'Analyzing...' : 'Refresh Matches' }}
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
                            <button @click="dismissMatch(match)" class="btn-icon" title="Dismiss Match">
                                ❌
                            </button>
                        </div>
                    </div>

                    <div class="match-meta">
                        <span class="match-date"><i class="icon">📅</i> {{ new Date(match.created_at).toLocaleDateString() }}</span>
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
                        <ul>
                            <li v-for="(point, i) in parseAnalysis(match.analysis).strengths" :key="i">
                            {{ point }}
                            </li>
                        </ul>
                        </div>

                        <div class="analysis-section gaps">
                        <h4><span class="icon">⚠️</span> Gaps</h4>
                        <ul>
                            <li v-for="(point, i) in parseAnalysis(match.analysis).gaps" :key="i">
                            {{ point }}
                            </li>
                        </ul>
                        </div>

                        <div class="analysis-section recommendation">
                        <h4><span class="icon">💡</span> Recommendation</h4>
                        <p>{{ parseAnalysis(match.analysis).recommendation }}</p>
                        </div>
                    </div>

                    <!-- Fallback for legacy/unparsable analysis -->
                    <div v-else class="match-analysis fallback">
                        <h4>Analysis</h4>
                        <MarkdownRenderer :content="match.analysis || 'No analysis available'" />
                    </div>

                    <!-- Interview Questions Section -->
                    <div class="questions-section">
                        <div class="questions-header">
                            <h4><span class="icon">❓</span> Interview Questionnaire</h4>
                            <div class="header-actions">
                                <button 
                                    v-if="hasQuestions(match)"
                                    @click="copyQuestions(match)" 
                                    class="btn-sm btn-copy" 
                                    :disabled="copyingQuestions[match.vacancy_id]"
                                >
                                    <span v-if="copyingQuestions[match.vacancy_id]">✓ Copied!</span>
                                    <span v-else>📋 Copy All</span>
                                </button>
                                <button 
                                    @click="generateQuestions(match)" 
                                    class="btn-sm btn-generate" 
                                    :disabled="generatingQuestions[match.vacancy_id]"
                                >
                                    <span v-if="generatingQuestions[match.vacancy_id]">⚡ Generating...</span>
                                    <span v-else-if="hasQuestions(match)">🔄 Regenerate</span>
                                    <span v-else>✨ Generate Questions</span>
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="hasQuestions(match)" class="questions-list">
                            <div v-for="(q, idx) in match.interview_questions" :key="idx" class="q-item">
                                <div class="q-header">
                                    <div class="q-number">Q{{ idx+1 }}</div>
                                    <div class="q-meta">
                                        <span v-if="q.type" class="q-type" :class="`type-${q.type}`">
                                            {{ q.type }}
                                        </span>
                                        <span v-if="q.difficulty" class="q-difficulty" :class="`diff-${q.difficulty}`">
                                            {{ q.difficulty }}
                                        </span>
                                    </div>
                                </div>
                                <div class="q-content">
                                    <p class="q-text">{{ q.question }}</p>
                                    <div v-if="q.context" class="q-context">
                                        <span class="context-icon">ℹ️</span> 
                                        <em>{{ q.context }}</em>
                                    </div>
                                    <div v-if="q.hint" class="q-hint" :class="{ 'hint-expanded': expandedHints[`${match.vacancy_id}-${idx}`] }">
                                        <button 
                                            @click="toggleHint(match.vacancy_id, idx)" 
                                            class="hint-toggle"
                                        >
                                            <span v-if="expandedHints[`${match.vacancy_id}-${idx}`]">▼</span>
                                            <span v-else>▶</span>
                                            Interviewer Hint
                                        </button>
                                        <div v-if="expandedHints[`${match.vacancy_id}-${idx}`]" class="hint-content">
                                            {{ q.hint }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="questions-placeholder">
                             <p>Generate targeted interview questions based on the candidate's match analysis.</p>
                        </div>
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
                                <div class="match-score-badge" :class="getScoreClass(match.match_score)">
                                    <span class="score-value">{{ match.match_score }}%</span>
                                </div>
                                <button @click="restoreMatch(match)" class="btn-icon" title="Restore Match">
                                    ↩️
                                </button>
                             </div>
                        </div>
                        <div class="match-meta">
                            <span>Dismissed on {{ new Date(match.updated_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div v-else class="no-data">
            <p>No matches analysis found. Click "Refresh Matches" to run analysis.</p>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { candidateAPI, matchingAPI, vacancyAPI } from '../../services/api'
import MarkdownRenderer from '../../components/MarkdownRenderer.vue'

const route = useRoute()

const candidate = ref(null)
const matches = ref([])
const loading = ref(false)
const loadingMatches = ref(false)
const currentTab = ref('overview')
const previewUrl = ref('')

const fetchCandidate = async () => {
  loading.value = true
  try {
    const response = await candidateAPI.get(route.params.id)
    candidate.value = response.data
    
    // Check for CV file to set up preview
    if (candidate.value.cv_files && candidate.value.cv_files.length > 0) {
        // Get latest CV
        const latestCv = candidate.value.cv_files[candidate.value.cv_files.length - 1]
        if (latestCv && latestCv.id) {
            fetchCvUrl(latestCv.id)
        }
    }
  } catch (error) {
    console.error('Failed to fetch candidate:', error)
  } finally {
    loading.value = false
  }
}

const fetchCvUrl = async (cvId) => {
    try {
        // We need a way to view the file. 
        // Typically we'd have a route to stream the file. 
        // For now, assuming we might need an endpoint or just use the API to get blob
        const response = await candidateAPI.getCv(route.params.id) // This gets metadata
        // If we want to view the file in iframe, we need a blob URL or a direct link
        // Let's assume there's a route /api/candidates/{id}/cv/download or similar
        // For this demo, let's try to fetch it as blob
        // Note: The backend needs to support this.
        // If not, we might need a workaround.
        // Let's rely on a direct backend route if possible: http://localhost:8082/api/candidates/{id}/cv/view
        previewUrl.value = `http://localhost:8082/api/candidates/${route.params.id}/cv/view`
    } catch (e) {
        console.error('Error fetching CV url', e)
    }
}

const viewMatches = async () => {
  loadingMatches.value = true
  try {
    const res = await matchingAPI.forCandidate(route.params.id)
    
    if (res.data.status === 'processing' && res.data.job_id) {
        const jobId = res.data.job_id
        // Show what we have initially (e.g. old matches)
        matches.value = res.data.matches || []
        
        const pollInterval = setInterval(async () => {
            try {
                const statusRes = await matchingAPI.getJobStatus(jobId)
                if (statusRes.data.status === 'completed') {
                    clearInterval(pollInterval)
                    const finalRes = await matchingAPI.forCandidate(route.params.id)
                    matches.value = finalRes.data.data || [] // Controller returns { data: matches } on standard get
                    loadingMatches.value = false
                } else if (statusRes.data.status === 'failed') {
                    clearInterval(pollInterval)
                    console.error("Async matching failed", statusRes.data.error)
                    alert("Matching process failed in background.")
                    loadingMatches.value = false
                }
            } catch (e) {
                console.error("Poll error", e)
                clearInterval(pollInterval)
            }
        }, 2000)
    } else {
        matches.value = res.data.data || []
        loadingMatches.value = false
    }
  } catch (error) {
    console.error('Failed to fetch matches:', error)
    loadingMatches.value = false
  }
}

// Link Generation Logic
const showLinkModal = ref(false)
const vacancies = ref([])
const selectedVacancyId = ref(null)
const generatedLink = ref('')
const generating = ref(false)
const copied = ref(false)
const linkInput = ref(null)

const openLinkModal = async () => {
    showLinkModal.value = true
    generatedLink.value = ''
    copied.value = false
    selectedVacancyId.value = null
    
    // Fetch vacancies for dropdown
    try {
        const res = await vacancyAPI.list({ status: 'open' })
        vacancies.value = res.data.data
    } catch (e) {
        console.error("Failed to fetch vacancies", e)
    }
}

const closeLinkModal = () => {
    showLinkModal.value = false
}

const generateLink = async () => {
    generating.value = true
    try {
        const res = await candidateAPI.generateToken(route.params.id, selectedVacancyId.value)
        generatedLink.value = res.data.url
    } catch (e) {
        alert("Failed to generate link")
    } finally {
        generating.value = false
    }
}

const copyLink = () => {
    if (linkInput.value) {
        linkInput.value.select()
        document.execCommand('copy')
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    }
}

// Helpers
const getScoreClass = (score) => {
    if (score >= 80) return 'score-high'
    if (score >= 60) return 'score-medium'
    return 'score-low'
}

const formatAnalysis = (text) => {
    if (!text) return '<p>No analysis provided.</p>'
    // Simple conversion of newlines to breaks
    return text.replace(/\n/g, '<br>')
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
    const strengthsMatch = text.match(/STRENGTHS:([\s\S]*?)GAPS:/)
    if (strengthsMatch && strengthsMatch[1]) {
      sections.strengths = strengthsMatch[1]
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.startsWith('-') || line.startsWith('•'))
        .map(line => line.substring(1).trim())
    }
    
    // Extract Gaps
    const gapsMatch = text.match(/GAPS:([\s\S]*?)RECOMMENDATION:/)
    if (gapsMatch && gapsMatch[1]) {
      sections.gaps = gapsMatch[1]
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.startsWith('-') || line.startsWith('•'))
        .map(line => line.substring(1).trim())
    }
    
    // Extract Recommendation
    const recMatch = text.match(/RECOMMENDATION:([\s\S]*)/)
    if (recMatch && recMatch[1]) {
      sections.recommendation = recMatch[1].trim()
    }
    
    return sections
  } catch (e) {
    console.error('Error parsing analysis:', e)
    return null
  }
}

// Parse JSON string fields to arrays
const parsedSkills = computed(() => {
  if (!candidate.value?.skills) return []
  if (Array.isArray(candidate.value.skills)) return candidate.value.skills
  try {
    return JSON.parse(candidate.value.skills)
  } catch {
    return []
  }
})

const parsedExperience = computed(() => {
  if (!candidate.value?.experience) return []
  if (Array.isArray(candidate.value.experience)) return candidate.value.experience
  try {
    return JSON.parse(candidate.value.experience)
  } catch {
    return []
  }
})

const parsedEducation = computed(() => {
  if (!candidate.value?.education) return []
  if (Array.isArray(candidate.value.education)) return candidate.value.education
  try {
    return JSON.parse(candidate.value.education)
  } catch {
    return []
  }
})

// Get phone from candidate or from latest CV parsed data
const displayPhone = computed(() => {
  if (candidate.value?.phone) return candidate.value.phone
  
  // Try to get from latest CV file
  const cvFiles = candidate.value?.cv_files
  if (cvFiles && cvFiles.length > 0) {
    const latestCv = cvFiles[cvFiles.length - 1]
    const phone = latestCv?.parsed_data?.parsed_data?.phone
    if (phone) return phone
  }
  
  return 'N/A'
})

const generatingQuestions = ref({}) // track loading state per match
const copyingQuestions = ref({}) // track copy state per match
const expandedHints = ref({}) // track expanded hints
const expandedVacancies = ref({}) // track expanded vacancy descriptions

const activeMatches = computed(() => matches.value.filter(m => m.status !== 'dismissed'))
const dismissedMatches = computed(() => matches.value.filter(m => m.status === 'dismissed'))

const generateQuestions = async (match) => {
    generatingQuestions.value[match.vacancy_id] = true
    try {
        const res = await matchingAPI.generateQuestions(route.params.id, match.vacancy_id)
        match.interview_questions = res.data
    } catch (e) {
        alert("Failed to generate questions")
    } finally {
        generatingQuestions.value[match.vacancy_id] = false
    }
}

const copyQuestions = async (match) => {
    if (!match.interview_questions || match.interview_questions.length === 0) return
    
    // Format questions for clipboard with better browser-friendly formatting
    let text = `INTERVIEW QUESTIONNAIRE\n`
    text += `Position: ${match.vacancy_title}\n`
    text += `Candidate: ${candidate.value.name}\n`
    text += `Match Score: ${match.match_score}%\n`
    text += `Generated: ${new Date().toLocaleDateString()}\n\n`
    text += '━'.repeat(70) + '\n\n'
    
    match.interview_questions.forEach((q, idx) => {
        text += `${idx + 1}. ${q.question}\n`
        
        if (q.type || q.difficulty) {
            const badges = []
            if (q.type) badges.push(`Type: ${q.type.charAt(0).toUpperCase() + q.type.slice(1)}`)
            if (q.difficulty) badges.push(`Difficulty: ${q.difficulty.charAt(0).toUpperCase() + q.difficulty.slice(1)}`)
            text += `   ${badges.join(' | ')}\n`
        }
        
        if (q.context) {
            text += `\n   📌 Context:\n`
            text += `   ${q.context}\n`
        }
        
        if (q.hint) {
            text += `\n   💡 Interviewer Hint:\n`
            text += `   ${q.hint}\n`
        }
        
        text += '\n' + '─'.repeat(70) + '\n\n'
    })
    
    text += `\nEnd of Questionnaire\n`
    text += `Total Questions: ${match.interview_questions.length}`
    
    try {
        await navigator.clipboard.writeText(text)
        copyingQuestions.value[match.vacancy_id] = true
        setTimeout(() => {
            copyingQuestions.value[match.vacancy_id] = false
        }, 2000)
    } catch (e) {
        alert('Failed to copy to clipboard')
    }
}

const toggleHint = (vacancyId, questionIdx) => {
    const key = `${vacancyId}-${questionIdx}`
    expandedHints.value[key] = !expandedHints.value[key]
}

const toggleVacancyDesc = (vacancyId) => {
    expandedVacancies.value[vacancyId] = !expandedVacancies.value[vacancyId]
}

const getSkillsArray = (skills) => {
    if (Array.isArray(skills)) return skills
    if (typeof skills === 'string') {
        try {
            return JSON.parse(skills)
        } catch {
            return skills.split(',').map(s => s.trim())
        }
    }
    return []
}

const dismissMatch = async (match) => {
    if (!confirm('Are you sure you want to dismiss this match? It will be hidden from the main list.')) return
    
    try {
        await matchingAPI.dismiss(route.params.id, match.vacancy_id)
        // Update local state by finding and updating the match object
        // Since we bind to matches.value, updating the object property will trigger computed re-eval
        match.status = 'dismissed'
    } catch (e) {
        alert("Failed to dismiss match")
        console.error(e)
    }
}

const restoreMatch = async (match) => {
    try {
        await matchingAPI.restore(route.params.id, match.vacancy_id)
        match.status = 'pending'
    } catch (e) {
        alert("Failed to restore match")
        console.error(e)
    }
}

// Helper to check if questions exist
const hasQuestions = (match) => {
    return match.interview_questions && match.interview_questions.length > 0
}

onMounted(() => {
  fetchCandidate()
  viewMatches()
})
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  margin-left: 1rem;
}

.actions {
  display: flex;
  gap: 1rem;
}

.detail-grid {
  display: grid;
  gap: 1.5rem;
}

.info-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.info-card h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #667eea;
}

.skills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skill-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
}

.experience-item, .education-item {
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
}

.experience-item:last-child, .education-item:last-child {
  border-bottom: none;
}

.experience-item h4, .education-item h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.company, .institution {
  color: #667eea;
  font-weight: 500;
  margin: 0.25rem 0;
}

.description {
  color: #666;
  margin: 0.5rem 0 0 0;
  line-height: 1.6;
}

.match-item {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.match-item:last-child {
  border-bottom: none;
}

.match-score {
  color: #667eea;
  font-weight: 600;
  margin-left: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  display: inline-block;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
}

.tabs button {
  background: none;
  border: none;
  font-size: 1rem;
  font-weight: 500;
  color: #666;
  padding: 0.5rem 1rem;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tabs button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.tabs button:hover:not(.active) {
  color: #333;
}

.tab-content {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.pdf-container {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    height: 820px;
}

.no-data {
    text-align: center;
    padding: 3rem;
    color: #999;
    font-size: 1.1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.generated-cv-content {
    white-space: pre-wrap;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    font-family: inherit;
    font-size: 0.95rem;
    line-height: 1.6;
    color: #333;
    border: 1px solid #eee;
}

.matches-list {
    display: grid;
    gap: 1.5rem;
}

.match-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.match-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 0.75rem;
}

.match-header h4 {
    margin: 0;
    color: #333;
}

.match-score {
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.9rem;
}

.score-high {
    background: #d4edda;
    color: #155724;
}

.score-medium {
    background: #fff3cd;
    color: #856404;
}

.score-low {
    background: #f8d7da;
    color: #721c24;
}

.match-analysis {
    color: #555;
    line-height: 1.6;
}

.vacancy-details {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #667eea;
}

.vacancy-details h4 {
    margin: 0 0 1rem 0;
    color: #555;
    font-size: 1rem;
    font-weight: 600;
}

.vacancy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.vacancy-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.vacancy-item .label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vacancy-item .value {
    font-size: 0.9rem;
    color: #333;
}

.vacancy-skills {
    margin-top: 1rem;
}

.vacancy-skills .label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #555;
    display: block;
    margin-bottom: 0.5rem;
}

.skills-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.skill-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.35rem 0.75rem;
    border-radius: 16px;
    font-size: 0.85rem;
    font-weight: 500;
}

.vacancy-description {
    margin-top: 1rem;
}

.desc-toggle {
    background: #fff;
    border: 1px solid #ddd;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #555;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    justify-content: center;
}

.desc-toggle:hover {
    background: #f5f5f5;
    border-color: #bbb;
}

.desc-content {
    margin-top: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
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

.analysis-section h4 .icon {
    font-size: 1.5rem;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
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

.match-actions {
    display: flex;
    justify-content: flex-end;
}

.btn-text {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-content h3 { margin-top: 0; }

.modal-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.copy-box {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.copy-box input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fa;
}

/* Match UI Improvements */
.matches-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.section-title {
    margin-bottom: 1rem;
    color: #444;
    font-size: 1.1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.text-muted {
    color: #999;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.btn-icon:hover {
    opacity: 1;
}

.match-card.dismissed {
    opacity: 0.7;
    background: #f8f9fa;
}

.questions-section {
    margin-top: 2rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 16px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.questions-section:hover {
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
}

.questions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95), rgba(241, 245, 249, 0.95));
    backdrop-filter: blur(10px);
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.questions-header h4 {
    margin: 0;
    color: #1a202c;
    font-weight: 700;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.questions-placeholder {
    padding: 3rem 2rem;
    text-align: center;
    color: #64748b;
    font-style: italic;
    font-size: 1rem;
}

.questions-list {
    display: flex;
    flex-direction: column;
}

.q-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.75rem;
    border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    transition: all 0.3s ease;
}

.q-item:hover {
    background: rgba(248, 250, 252, 0.6);
}

.q-item:last-child {
    border-bottom: none;
}

.q-number {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
    height: 40px;
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.q-content {
    flex: 1;
}

.q-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.875rem;
}

.q-meta {
    display: flex;
    gap: 0.625rem;
    flex-wrap: wrap;
}

.q-type, .q-difficulty {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.375rem 0.875rem;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.type-technical {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.type-behavioral {
    background: linear-gradient(135deg, #a855f7, #7c3aed);
    color: white;
}

.type-situational {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.diff-easy {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.diff-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.diff-hard {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.q-text {
    margin: 0.25rem 0 0.75rem 0;
    color: #1a202c;
    font-weight: 600;
    font-size: 1.0625rem;
    line-height: 1.6;
}

.q-context {
    margin: 0.75rem 0;
    font-size: 0.9375rem;
    color: #4a5568;
    background: linear-gradient(135deg, rgba(255, 249, 230, 0.8), rgba(254, 243, 199, 0.8));
    backdrop-filter: blur(10px);
    padding: 0.875rem 1rem;
    border-radius: 10px;
    border-left: 4px solid #f59e0b;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
}

.q-hint {
    margin-top: 1rem;
}

.hint-toggle {
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9), rgba(241, 245, 249, 0.9));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    padding: 0.625rem 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.hint-toggle:hover {
    background: linear-gradient(135deg, rgba(241, 245, 249, 0.95), rgba(226, 232, 240, 0.95));
    border-color: rgba(203, 213, 225, 0.9);
    transform: translateX(4px);
}

.hint-toggle span:first-child {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.hint-content {
    margin-top: 0.75rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(240, 247, 255, 0.9), rgba(224, 242, 254, 0.9));
    backdrop-filter: blur(10px);
    border-left: 4px solid #3b82f6;
    border-radius: 10px;
    font-size: 0.9375rem;
    color: #1e40af;
    line-height: 1.7;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
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

.context-icon {
    font-style: normal;
    margin-right: 0.375rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-copy {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.btn-copy:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-copy:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-generate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.btn-generate:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-generate:disabled {
    opacity: 0.7;
    cursor: wait;
    transform: none;
}

.success-msg {
    color: #28a745;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.hint {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.5rem;
}
</style>
