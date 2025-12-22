<template>
  <div class="offers">
    <h1>Offers</h1>
    
    <div class="filters">
      <select v-model="statusFilter" @change="fetchOffers" class="filter-select">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
        <option value="withdrawn">Withdrawn</option>
        <option value="expired">Expired</option>
      </select>
    </div>
    
    <div v-if="loading" class="loading">Loading offers...</div>
    
    <div v-else class="offers-table">
      <table>
        <thead>
          <tr>
            <th>Candidate</th>
            <th>Vacancy</th>
            <th>Salary</th>
            <th>Offer Date</th>
            <th>Expiry</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="offer in offers" :key="offer.id">
            <td><strong>Candidate #{{ offer.candidate_id }}</strong></td>
            <td>Vacancy #{{ offer.vacancy_id }}</td>
            <td>{{ offer.salary_offered }} {{ offer.currency }}</td>
            <td>{{ formatDate(offer.offer_date) }}</td>
            <td>{{ offer.expiry_date ? formatDate(offer.expiry_date) : 'N/A' }}</td>
            <td><span :class="'status status-' + offer.status">{{ offer.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="!loading && offers.length === 0" class="empty-state">
      <p>No offers found</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { offerAPI } from '../../services/api'

const offers = ref([])
const loading = ref(false)
const statusFilter = ref('')

const fetchOffers = async () => {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    
    const response = await offerAPI.list(params)
    offers.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch offers:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  fetchOffers()
})
</script>

<style scoped>
.filters {
  margin-bottom: 2rem;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  min-width: 200px;
}

.offers-table {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

td {
  padding: 1rem;
  border-top: 1px solid #eee;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
}

.status-pending { background: #fff3e0; color: #f57c00; }
.status-accepted { background: #e8f5e9; color: #388e3c; }
.status-rejected { background: #ffebee; color: #c62828; }
.status-withdrawn { background: #f5f5f5; color: #666; }
.status-expired { background: #ffebee; color: #c62828; }

.loading, .empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
}
</style>
