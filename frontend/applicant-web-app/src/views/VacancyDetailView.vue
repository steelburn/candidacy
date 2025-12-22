<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { marked } from 'marked' // We might need to install this if not already, or use v-html appropriately

const route = useRoute()
const vacancy = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get(`/vacancies/${route.params.id}`)
    vacancy.value = response.data
  } catch (error) {
    console.error('Failed to fetch vacancy', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="container">
    <div v-if="loading">Loading...</div>
    <div v-else-if="vacancy" class="detail-layout">
      <div class="content">
        <router-link to="/" class="back-link">&larr; Back to jobs</router-link>
        
        <header class="header">
          <h1>{{ vacancy.title }}</h1>
          <div class="meta">
            <span>{{ vacancy.department }}</span> • 
            <span>{{ vacancy.location }}</span> • 
            <span>{{ vacancy.type }}</span>
          </div>
        </header>

        <div class="card description-card">
            <h3>Description</h3>
            <div class="markdown-body" v-html="vacancy.description"></div> 
            <!-- Assuming description is plain text or HTML, safe to render? -->
            <!-- If markdown, we should parse it. For now, simple standard display -->
        </div>
      </div>

      <aside class="sidebar">
        <div class="card cta-card">
            <h3>Interested?</h3>
            <p>Join our team and make an impact.</p>
            <router-link :to="{ name: 'apply', params: { id: vacancy.id }}" class="btn-apply">
                Apply Now
            </router-link>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.detail-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 3rem;
  margin-top: 2rem;
}

.back-link {
  display: inline-block;
  color: #94a3b8;
  text-decoration: none;
  margin-bottom: 2rem;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.meta {
  color: #94a3b8;
  font-size: 1.1rem;
}

.btn-apply {
  display: block;
  width: 100%;
  text-align: center;
  background: white;
  color: black;
  text-decoration: none;
  padding: 1rem;
  border-radius: 0.5rem;
  font-weight: 700;
  margin-top: 1rem;
}

.cta-card {
    position: sticky;
    top: 2rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
}

.cta-card h3, .cta-card p {
    color: white;
}

@media (max-width: 768px) {
  .detail-layout {
    grid-template-columns: 1fr;
  }
}
</style>
