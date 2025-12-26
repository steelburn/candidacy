<template>
  <div class="vacancy-list">
    <div class="header">
      <h1>Vacancies</h1>
      <router-link to="/vacancies/create" class="btn-primary">Add Vacancy</router-link>
    </div>
    
    <div class="filters">
      <input 
        v-model="search" 
        @input="debouncedSearch"
        type="text" 
        placeholder="Search vacancies..."
        class="search-input"
      />
      <select v-model="statusFilter" @change="fetchVacancies" class="filter-select">
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="open">Open</option>
        <option value="closed">Closed</option>
        <option value="on_hold">On Hold</option>
      </select>
    </div>
    
    <div v-if="loading" class="loading">Loading vacancies...</div>
    
    <div v-else class="vacancies-grid">
      <div v-for="vacancy in vacancies" :key="vacancy.id" class="vacancy-card">
        <div class="vacancy-header">
          <h3>{{ vacancy.title }}</h3>
          <span :class="'status status-' + vacancy.status">{{ vacancy.status }}</span>
        </div>
        
        <div class="meta-info">
          <p class="vacancy-location">
            <span class="icon">📍</span> 
            {{ vacancy.location }}
          </p>
          <p class="vacancy-type">
            <span class="icon">💼</span> 
            {{ vacancy.employment_type?.replace('_', ' ') }} • {{ vacancy.experience_level }}
          </p>
          <p class="vacancy-salary" v-if="vacancy.min_salary">
            <span class="icon">💰</span> 
            {{ vacancy.min_salary }} - {{ vacancy.max_salary }} {{ vacancy.currency }}
          </p>
        </div>
        
        <div class="vacancy-actions">
          <router-link :to="`/vacancies/${vacancy.id}`" class="btn-sm btn-view">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            View
          </router-link>
          <button @click="deleteVacancy(vacancy.id)" class="btn-sm btn-danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="!loading && vacancies.length === 0" class="empty-state">
      <p>No vacancies found</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { vacancyAPI } from '../../services/api'

const vacancies = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')

let searchTimeout

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchVacancies()
  }, 500)
}

const fetchVacancies = async () => {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    
    const response = await vacancyAPI.list(params)
    vacancies.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch vacancies:', error)
  } finally {
    loading.value = false
  }
}

const deleteVacancy = async (id) => {
  if (!confirm('Are you sure you want to delete this vacancy?')) return
  
  try {
    await vacancyAPI.delete(id)
    fetchVacancies()
  } catch (error) {
    console.error('Failed to delete vacancy:', error)
    alert('Failed to delete vacancy')
  }
}

onMounted(() => {
  fetchVacancies()
})
</script>

<style scoped>
.vacancy-list {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  padding: 0 0.5rem;
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin: 0;
  letter-spacing: -0.02em;
}

.filters {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
  background: white;
  padding: 1rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.search-input {
  flex: 1;
  padding: 0.875rem 1.25rem;
  border: 2px solid #edf2f7;
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background-color: #f8fafc;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  background-color: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-select {
  padding: 0.875rem 2.5rem 0.875rem 1.25rem;
  border: 2px solid #edf2f7;
  border-radius: 12px;
  min-width: 200px;
  font-size: 0.95rem;
  background-color: #f8fafc;
  cursor: pointer;
  transition: all 0.3s ease;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.vacancies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 2rem;
  padding: 0.5rem;
}

.vacancy-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.vacancy-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border-color: #667eea;
}

.vacancy-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.vacancy-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.4;
  letter-spacing: -0.01em;
}

.meta-info {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 2rem;
  flex: 1;
}

.vacancy-location, .vacancy-type, .vacancy-salary {
  margin: 0;
  color: #64748b;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.icon {
  width: 24px;
  text-align: center;
  font-size: 1.1em;
}

.status {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-draft { 
  background: #f1f5f9; 
  color: #64748b;
  border: 1px solid #e2e8f0;
}
.status-open { 
  background: #dcfce7; 
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.status-closed { 
  background: #fee2e2; 
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.status-on_hold { 
  background: #ffedd5; 
  color: #c2410c;
  border: 1px solid #fed7aa;
}

.vacancy-actions {
  display: flex;
  gap: 1rem;
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px solid #f1f5f9;
}

.btn-sm {
  flex: 1;
  padding: 0.75rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-sm svg {
  display: block;
  flex-shrink: 0;
}

.btn-view {
  background: #eff6ff;
  color: #3b82f6;
}

.btn-view:hover {
  background: #dbeafe;
  color: #2563eb;
}

.btn-danger {
  background: #fef2f2;
  color: #ef4444;
}

.btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.875rem 2rem;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 600;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
  transition: all 0.3s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.5);
}

.loading, .empty-state {
  text-align: center;
  padding: 4rem;
  color: #94a3b8;
  font-size: 1.1rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
</style>
