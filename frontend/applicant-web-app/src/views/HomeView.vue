<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const vacancies = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/vacancies?status=open')
    // Handle both wrapped response { data: [...] } and direct array
    vacancies.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch vacancies', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="container">
    <div class="hero animate-in">
      <h1 class="text-gradient">Find Your Next Adventure</h1>
      <p>Discover roles that match your unique skills and ambitions.</p>
      
      <div class="hero-stats">
        <div class="stat-item">
            <span class="stat-value">Active</span>
            <span class="stat-label">Vacancies</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-value">Direct</span>
            <span class="stat-label">Access</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Finding opportunities...</p>
    </div>

    <div v-else class="vacancy-grid animate-in" style="animation-delay: 0.2s">
      <router-link 
        v-for="vacancy in vacancies" 
        :key="vacancy.id" 
        :to="{ name: 'vacancy-detail', params: { id: vacancy.id }}"
        class="card vacancy-card"
      >
        <div class="card-glow"></div>
        <div class="card-content">
            <h2>{{ vacancy.title }}</h2>
            <div class="meta">
                <span class="badge">{{ vacancy.department }}</span>
                <span class="badge">{{ vacancy.location }}</span>
                <span class="badge type">{{ vacancy.type }}</span>
            </div>
            <p class="description">{{ vacancy.description.substring(0, 150) }}...</p>
            <div class="footer">
                <span class="btn-text">View Details <span class="arrow">&rarr;</span></span>
            </div>
        </div>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.hero {
  text-align: center;
  margin-bottom: 5rem;
  padding: 4rem 0 2rem;
  position: relative;
}

.hero h1 {
  font-size: 4.5rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
  line-height: 1.1;
  letter-spacing: -0.05em;
}

.hero p {
  font-size: 1.25rem;
  color: var(--text-muted);
  max-width: 600px;
  margin: 0 auto 3rem;
  line-height: 1.6;
}

.hero-stats {
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  padding: 1rem 2rem;
  border-radius: 99px;
  border: 1px solid var(--glass-border);
}

.stat-item {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
}

.stat-value {
  font-weight: 700;
  color: white;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-muted);
}

.stat-divider {
  width: 1px;
  height: 24px;
  background: var(--glass-border);
}

.vacancy-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 2rem;
}

.vacancy-card {
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  height: 100%;
}

.card-glow {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.1), transparent 70%);
  opacity: 0;
  transition: opacity 0.3s;
  pointer-events: none;
}

.vacancy-card:hover .card-glow {
  opacity: 1;
}

.card-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.vacancy-card h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.meta {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.badge {
  background: rgba(255, 255, 255, 0.05);
  padding: 0.35rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8rem;
  border: 1px solid var(--glass-border);
  color: var(--text-muted);
}

.badge.type {
    background: rgba(168, 85, 247, 0.1);
    border-color: rgba(168, 85, 247, 0.2);
    color: #d8b4fe;
}

.description {
  color: var(--text-muted);
  flex-grow: 1;
  line-height: 1.7;
  margin-bottom: 2rem;
}

.footer {
  margin-top: auto;
}

.btn-text {
    font-weight: 600;
    color: var(--primary-start);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: gap 0.2s;
}

.vacancy-card:hover .btn-text {
    gap: 0.75rem;
}

.loading-state {
    text-align: center;
    padding: 4rem;
    color: var(--text-muted);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255,255,255,0.1);
    border-radius: 50%;
    border-top-color: var(--primary-start);
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .hero h1 { font-size: 3rem; }
}
</style>

