<template>
  <div class="offers-page">
    <div class="page-header">
      <div>
        <h1>Offers</h1>
        <p class="subtitle">Track job offers and candidate responses</p>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <select v-model="statusFilter" @change="fetchOffers" class="filter-select">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
        <option value="withdrawn">Withdrawn</option>
        <option value="expired">Expired</option>
      </select>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Loading offers...</span>
    </div>
    
    <!-- Table -->
    <div v-else class="table-card">
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
            <td>
              <div class="candidate-cell">
                <div class="avatar">{{ getInitials(offer.candidate_id) }}</div>
                <span class="name">Candidate #{{ offer.candidate_id }}</span>
              </div>
            </td>
            <td>Vacancy #{{ offer.vacancy_id }}</td>
            <td class="salary-cell">
              <span class="salary-amount">{{ formatSalary(offer.salary_offered) }}</span>
              <span class="salary-currency">{{ offer.currency }}</span>
            </td>
            <td class="date-cell">{{ formatDate(offer.offer_date) }}</td>
            <td class="date-cell">{{ offer.expiry_date ? formatDate(offer.expiry_date) : 'N/A' }}</td>
            <td>
              <span :class="['status-badge', 'status-' + offer.status]">
                {{ offer.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="offers.length === 0" class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <p>No offers found</p>
      </div>
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

const formatSalary = (salary) => {
  if (!salary) return 'N/A'
  return new Intl.NumberFormat().format(salary)
}

const getInitials = (id) => {
  return `C${id}`.slice(0, 2).toUpperCase()
}

onMounted(() => {
  fetchOffers()
})
</script>

<style scoped>
.offers-page {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  margin-bottom: 4px;
}

.subtitle {
  color: var(--text-secondary, #6b7280);
  font-size: 0.95rem;
}

/* Filters */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filter-select {
  padding: 10px 14px;
  background: var(--bg-primary, #ffffff);
  border: 1px solid var(--color-gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--text-primary, #111827);
  min-width: 180px;
  cursor: pointer;
}

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px;
  gap: 16px;
  color: var(--text-secondary, #6b7280);
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-gray-200, #e5e7eb);
  border-top-color: var(--color-primary, #6366f1);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table */
.table-card {
  background: var(--bg-primary, #ffffff);
  border-radius: 12px;
  box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  text-align: left;
  font-weight: 600;
  color: var(--text-secondary, #6b7280);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 14px 16px;
  background: var(--color-gray-50, #f9fafb);
  border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
}

td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--color-gray-100, #f3f4f6);
  vertical-align: middle;
}

tr:hover td {
  background: var(--color-gray-50, #f9fafb);
}

/* Candidate Cell */
.candidate-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
}

.name {
  font-weight: 500;
  color: var(--text-primary, #111827);
}

/* Salary */
.salary-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.salary-amount {
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.salary-currency {
  font-size: 0.75rem;
  color: var(--text-secondary, #6b7280);
}

.date-cell {
  color: var(--text-secondary, #6b7280);
  font-size: 0.9rem;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status-pending { background: #fff3e0; color: #f57c00; }
.status-accepted { background: #e8f5e9; color: #388e3c; }
.status-rejected { background: #ffebee; color: #c62828; }
.status-withdrawn { background: #f5f5f5; color: #666; }
.status-expired { background: #ffebee; color: #c62828; }

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 24px;
  text-align: center;
  color: var(--text-secondary, #6b7280);
}

.empty-state svg {
  width: 64px;
  height: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 1rem;
}
</style>
