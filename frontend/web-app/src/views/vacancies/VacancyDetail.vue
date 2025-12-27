<template>
  <div class="vacancy-detail-wrapper">
    <div class="animated-background"></div>
    
    <div class="vacancy-detail">
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Loading vacancy details...</p>
      </div>
      
      <div v-else-if="vacancy" class="content-wrapper">
        <VacancyHeader 
            :vacancy="vacancy" 
            :loading-matches="loadingMatches" 
            @find-candidates="triggerMatch"
        />
        
        <div class="detail-grid">
          <VacancyInfoSidebar :vacancy="vacancy" />
          
          <div class="info-card description-card">
            <h3 class="card-title">
              <span class="title-icon">📄</span>
              Job Description
            </h3>
            <div class="description-content">
              <MarkdownRenderer :content="vacancy.description || 'No description available'" />
            </div>
          </div>
          
          <VacancyMatches 
            ref="matchesRef"
            :vacancy-id="vacancy.id"
            @update:loading="loadingMatches = $event"
          />
        </div>
      </div>
      
      <div v-else class="error-container">
          <p>Vacancy not found.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { vacancyAPI } from '../../services/api'
import MarkdownRenderer from '../../components/MarkdownRenderer.vue'

// Import New Components
import VacancyHeader from '../../components/vacancies/VacancyHeader.vue'
import VacancyInfoSidebar from '../../components/vacancies/VacancyInfoSidebar.vue'
import VacancyMatches from '../../components/vacancies/VacancyMatches.vue'

const route = useRoute()
const vacancyId = route.params.id

const vacancy = ref(null)
const loading = ref(true)
const loadingMatches = ref(false)
const matchesRef = ref(null)

const fetchVacancy = async () => {
  loading.value = true
  try {
    const response = await vacancyAPI.get(vacancyId)
    vacancy.value = response.data
  } catch (error) {
    console.error('Failed to fetch vacancy:', error)
  } finally {
    loading.value = false
  }
}

const triggerMatch = () => {
    if (matchesRef.value) {
        matchesRef.value.fetchMatches()
    }
}

onMounted(() => {
  fetchVacancy()
  // Matches component will auto-fetch on mount if configured, 
  // or wait for trigger? Original was FETCH on mount.
  // My VacancyMatches implementation has onMounted(fetchMatches).
  // So it will fetch automatically. 
  // The triggerMatch is for manual re-fetch via button.
})
</script>

<style scoped>
/* Animated Background */
.vacancy-detail-wrapper {
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.animated-background {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: -1;
  background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
  background-size: 400% 400%;
  animation: gradientShift 15s ease infinite;
  opacity: 0.05;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Main Container */
.vacancy-detail {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.content-wrapper {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading States */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1.5rem;
}

.loading-container p {
  color: #64748b;
  font-size: 1.125rem;
  font-weight: 500;
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(102, 126, 234, 0.1);
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-container {
    text-align: center;
    padding: 4rem;
    color: #64748b;
}

/* Detail Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 2rem;
  animation: fadeInUp 0.6s ease-out 0.2s both;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Description Card */
.description-card {
  grid-column: 2;
  grid-row: 1 / 3;
}

.info-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.6);
  transition: all 0.3s ease;
}

.info-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 2rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.title-icon {
  font-size: 1.75rem;
}

.description-content {
  color: #4a5568;
  line-height: 1.8;
  font-size: 1.0625rem;
}

.description-content :deep(h1),
.description-content :deep(h2),
.description-content :deep(h3) {
  color: #1a202c;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}

.description-content :deep(ul),
.description-content :deep(ol) {
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.description-content :deep(li) {
  margin-bottom: 0.5rem;
}
</style>
