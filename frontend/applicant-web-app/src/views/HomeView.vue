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
    <div class="hero">
      <h1>Find Your Dream Job</h1>
      <p>Explore opportunities matching your skills and experience.</p>
    </div>

    <div v-if="loading" class="loading">
      Loading opportunities...
    </div>

    <div v-else class="vacancy-grid">
      <router-link 
        v-for="vacancy in vacancies" 
        :key="vacancy.id" 
        :to="{ name: 'vacancy-detail', params: { id: vacancy.id }}"
        class="card vacancy-card"
      >
        <h2>{{ vacancy.title }}</h2>
        <div class="meta">
            <span class="bagde">{{ vacancy.department }}</span>
            <span class="badge">{{ vacancy.location }}</span>
            <span class="badge type">{{ vacancy.type }}</span>
        </div>
        <p class="description">{{ vacancy.description.substring(0, 150) }}...</p>
        <div class="footer">
            <span class="btn-text">View Details &rarr;</span>
        </div>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.hero {
  text-align: center;
  margin-bottom: 4rem;
  padding: 2rem 0;
}

.hero h1 {
  font-size: 3rem;
  background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 1rem;
}

.vacancy-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.vacancy-card {
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
}

.meta {
  display: flex;
  gap: 0.5rem;
  margin: 1rem 0;
  flex-wrap: wrap;
}

.badge {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.875rem;
}

.description {
  color: #94a3b8;
  flex-grow: 1;
  line-height: 1.6;
}

.footer {
  margin-top: 1.5rem;
  color: var(--primary-color);
  font-weight: 600;
}
</style>
