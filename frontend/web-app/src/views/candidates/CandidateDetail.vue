<template>
  <div class="candidate-detail">
    <div v-if="loading" class="loading">Loading...</div>
    
    <div v-else-if="candidate">
      <!-- Header -->
      <div class="header">
        <div>
          <h1>{{ candidate.name }}</h1>
          <span :class="'status status-' + candidate.status">{{ candidate.status }}</span>
        </div>
        <div class="actions">
          <button @click="showLinkModal = true" class="btn-secondary">Generate Link</button>
          <router-link :to="`/candidates/${candidate.id}/edit`" class="btn-primary">Edit</router-link>
        </div>
      </div>

      <!-- Link Generation Modal -->
      <CandidateLinkModal 
        :show="showLinkModal" 
        :vacancies="vacancies"
        :generated-link="generatedLink"
        :loading="generatingLink"
        @close="closeLinkModal" 
        @generate="handleGenerateLink"
      />

      <!-- Tabs Navigation -->
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
          @click="currentTab = 'parsing'" 
          :class="{ active: currentTab === 'parsing' }"
        >Parsing Details</button>
        <button 
          @click="currentTab = 'matches'" 
          :class="{ active: currentTab === 'matches' }"
        >Matches</button>
      </div>
      
      <!-- Tab: Overview -->
      <CandidateOverview 
        v-if="currentTab === 'overview'" 
        :candidate="candidate"
        :parsed-skills="parsedSkills"
        :parsed-experience="parsedExperience"
        :parsed-education="parsedEducation"
      />

      <!-- Tab: Original CV -->
      <div v-if="currentTab === 'cv'" class="tab-content">
        <div v-if="previewUrl" class="pdf-container">
            <iframe :src="previewUrl" width="100%" height="800px"></iframe>
        </div>
        <div v-else class="no-data">
            <p>No CV document available.</p>
        </div>
      </div>

      <!-- Tab: Generated CV -->
      <div v-if="currentTab === 'generated'" class="tab-content">
        <div class="info-card">
            <h3>Standardized Profile for Matching</h3>
            <p class="hint">This is the text representation used by the AI Model for matching analysis.</p>
            <MarkdownRenderer :content="candidate.generated_cv_content || 'No generated content available.'" />
        </div>
      </div>

      <!-- Tab: Parsing Details -->
      <CandidateParsingDetails 
        v-if="currentTab === 'parsing'"
        :parsing-details="parsingDetails"
        :preview-url="previewUrl"
        :loading="loadingParsing"
        :error="parsingError"
        :backend-url="backendUrl"
        @refresh="loadParsingDetails"
      />

      <!-- Tab: Matches -->
      <CandidateMatches 
        v-if="currentTab === 'matches'"
        :matches="matches"
        :candidate-id="candidate.id"
        :loading="loadingMatches"
        @refresh="viewMatches"
        @dismiss="handleDismissMatch"
        @restore="handleRestoreMatch"
        @generate-questions="handleGenerateQuestions"
        @save-discussion="handleSaveDiscussion"
      />

    </div>
    
    <div v-else class="error">
        Candidate not found.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { candidateAPI, matchingAPI, vacancyAPI, aiAPI } from '../../services/api'
import { parseSkills, parseJsonArray } from '../../composables/useMatchAnalysis'
import MarkdownRenderer from '../../components/MarkdownRenderer.vue'

// Import New Components
import CandidateOverview from '../../components/candidates/CandidateOverview.vue'
import CandidateParsingDetails from '../../components/candidates/CandidateParsingDetails.vue'
import CandidateMatches from '../../components/candidates/CandidateMatches.vue'
import CandidateLinkModal from '../../components/candidates/CandidateLinkModal.vue'

const route = useRoute()
const candidateId = route.params.id

// State
const candidate = ref(null)
const loading = ref(true)
const currentTab = ref('overview')
const vacancies = ref([])
const backendUrl = import.meta.env.VITE_API_GATEWAY_URL || 'http://localhost:8080'

// Parsing Details State
const parsingDetails = ref(null)
const loadingParsing = ref(false)
const parsingError = ref(null)

// Matches State
const matches = ref([])
const loadingMatches = ref(false)

// Link Modal State
const showLinkModal = ref(false)
const generatingLink = ref(false)
const generatedLink = ref(null)

// Computed helpers using composable
const parsedSkills = computed(() => parseSkills(candidate.value?.skills))
const parsedExperience = computed(() => parseJsonArray(candidate.value?.experience))
const parsedEducation = computed(() => parseJsonArray(candidate.value?.education))

const previewUrl = computed(() => {
    if (!candidate.value?.cv_files?.length) return null
    const latestCv = candidate.value.cv_files[candidate.value.cv_files.length - 1]
    return `${backendUrl}/api/candidates/${candidateId}/cv/download?token=${localStorage.getItem('token')}` 
})

// === Methods ===

const loadCandidate = async () => {
    loading.value = true
    try {
        const res = await candidateAPI.get(candidateId)
        candidate.value = res.data
        // Pre-load duplicates matches? No, lazy load on tab switch usually better, 
        // but original code might have loaded them. Let's stick to simple logic.
    } catch (e) {
        console.error("Failed to load candidate", e)
    } finally {
        loading.value = false
    }
}

const loadVacancies = async () => {
    try {
        const res = await vacancyAPI.list()
        vacancies.value = res.data.data
    } catch (e) {
        console.error(e)
    }
}

// -- Parsing Details --
const loadParsingDetails = async () => {
    loadingParsing.value = true
    parsingError.value = null
    try {
        const res = await candidateAPI.getParsingDetails(candidateId)
        parsingDetails.value = res.data
    } catch (e) {
        parsingError.value = "Failed to load parsing details."
    } finally {
        loadingParsing.value = false
    }
}

// -- Matches --
const viewMatches = async () => {
    loadingMatches.value = true
    try {
        // Trigger matching
        await matchingAPI.matchCandidate(candidateId) 
        // Then fetch results
        const res = await matchingAPI.getMatches({ candidate_id: candidateId })
        matches.value = res.data.data
    } catch (e) {
        console.error("Failed to load matches", e)
    } finally {
        loadingMatches.value = false
    }
}

const handleDismissMatch = async (match) => {
    try {
        await matchingAPI.dismiss(candidateId, match.vacancy_id)
        match.status = 'dismissed'
    } catch (e) {
        alert("Failed to dismiss match")
    }
}

const handleRestoreMatch = async (match) => {
    try {
        await matchingAPI.restore(candidateId, match.vacancy_id)
        match.status = 'pending'
    } catch (e) {
        alert("Failed to restore match")
    }
}

const handleGenerateQuestions = async (match, doneCallback) => {
    try {
        const res = await matchingAPI.generateQuestions(candidateId, match.vacancy_id)
        match.interview_questions = res.data
    } catch (e) {
        alert("Failed to generate questions")
    } finally {
        if (doneCallback) doneCallback()
    }
}

const handleSaveDiscussion = async (vacancyId, questionIdx, discussion) => {
    try {
        await matchingAPI.saveDiscussion(candidateId, vacancyId, questionIdx, discussion)
    } catch (e) {
        console.error("Failed to save discussion", e)
    }
}

// -- Link Generation --

const handleGenerateLink = async (vacancyId) => {
    generatingLink.value = true
    try {
        const res = await candidateAPI.generateToken(candidateId, vacancyId)
        generatedLink.value = res.data.url
    } catch (e) {
        alert("Failed to generate link")
    } finally {
        generatingLink.value = false
    }
}

const closeLinkModal = () => {
    showLinkModal.value = false
    generatedLink.value = null
}

onMounted(() => {
    loadCandidate()
    loadVacancies()
    loadParsingDetails()
})

watch(currentTab, (newTab) => {
    if (newTab === 'matches' && matches.value.length === 0) {
        viewMatches()
    }
})
</script>

<style scoped>
/* Keep necessary global styles or those not fitting in components */
.candidate-detail {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  margin-left: 1rem;
}
.status-new { background: #e3f2fd; color: #1976d2; }
.status-reviewing { background: #fff3cd; color: #856404; }
.status-shortlisted { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.actions {
  display: flex;
  gap: 1rem;
}

/* Tabs */
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
}

.tabs button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.tab-content {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Common Card Styles for Generated/Original CV tabs */
.info-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pdf-container {
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  height: 800px;
}

/* Button styles that might be used by router-link or button actions */
.btn-primary, .btn-secondary {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.loading, .error, .no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.error { color: #e74c3c; }

.hint {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 15px;
}
</style>
