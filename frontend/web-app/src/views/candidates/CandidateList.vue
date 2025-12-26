<template>
  <div class="candidate-list">
    <div class="page-header">
      <div>
        <h1>Candidates</h1>
        <p class="subtitle">Manage your candidate pipeline</p>
      </div>
      <div class="header-actions">
        <button @click="showBulkUpload = true" class="btn btn-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
          <span>Bulk Upload</span>
        </button>
        <router-link to="/candidates/create" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          <span>Add Candidate</span>
        </router-link>
      </div>
    </div>
    
    <BulkUploadModal 
      :show="showBulkUpload" 
      @close="showBulkUpload = false"
      @refresh="fetchCandidates"
    />
    
    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input 
          v-model="search" 
          @input="debouncedSearch"
          type="text" 
          placeholder="Search candidates..."
          class="search-input"
        />
      </div>
      <select v-model="statusFilter" @change="fetchCandidates" class="filter-select">
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="new">New</option>
        <option value="reviewing">Reviewing</option>
        <option value="shortlisted">Shortlisted</option>
        <option value="interviewed">Interviewed</option>
        <option value="offered">Offered</option>
        <option value="hired">Hired</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Loading candidates...</span>
    </div>
    
    <!-- Table -->
    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Candidate</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Added</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="candidate in candidates" :key="candidate.id">
            <td>
              <div class="candidate-cell">
                <div class="avatar">{{ getInitials(candidate.name) }}</div>
                <span class="name">{{ candidate.name }}</span>
              </div>
            </td>
            <td>
              <div class="contact-cell">
                <span class="email">{{ candidate.email }}</span>
                <span class="phone">{{ candidate.phone || 'N/A' }}</span>
              </div>
            </td>
            <td>
              <span :class="['status-badge', 'status-' + candidate.status]">
                {{ candidate.status }}
              </span>
            </td>
            <td class="date-cell">{{ formatDate(candidate.created_at) }}</td>
            <td>
              <div class="actions-cell">
                <router-link :to="`/candidates/${candidate.id}`" class="action-btn" title="View">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </router-link>
                <router-link :to="`/candidates/${candidate.id}/edit`" class="action-btn" title="Edit">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </router-link>
                <button @click="deleteCandidate(candidate.id)" class="action-btn danger" title="Delete">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="candidates.length === 0" class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <line x1="17" y1="11" x2="23" y2="11"/>
        </svg>
        <p>No candidates found</p>
        <router-link to="/candidates/create" class="btn btn-primary">Add Your First Candidate</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { candidateAPI } from '../../services/api'
import BulkUploadModal from '../../components/BulkUploadModal.vue'

const candidates = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const showBulkUpload = ref(false)

let searchTimeout

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchCandidates()
  }, 500)
}

const fetchCandidates = async () => {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    
    const response = await candidateAPI.list(params)
    candidates.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch candidates:', error)
  } finally {
    loading.value = false
  }
}

const deleteCandidate = async (id) => {
  if (!confirm('Are you sure you want to delete this candidate?')) return
  
  try {
    await candidateAPI.delete(id)
    fetchCandidates()
  } catch (error) {
    console.error('Failed to delete candidate:', error)
    alert('Failed to delete candidate')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

onMounted(() => {
  fetchCandidates()
})
</script>

<style scoped>
.candidate-list {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.page-header h1 {
  margin-bottom: 4px;
}

.subtitle {
  color: var(--text-secondary, #6b7280);
  font-size: 0.95rem;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.9rem;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn svg {
  width: 18px;
  height: 18px;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.btn-primary:hover {
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
  transform: translateY(-1px);
}

.btn-secondary {
  background: var(--bg-primary, #ffffff);
  color: var(--color-primary, #6366f1);
  border: 1px solid var(--color-gray-200, #e5e7eb);
}

.btn-secondary:hover {
  background: var(--color-gray-50, #f9fafb);
  border-color: var(--color-primary, #6366f1);
}

/* Filters */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.search-box {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: var(--bg-primary, #ffffff);
  border: 1px solid var(--color-gray-200, #e5e7eb);
  border-radius: 8px;
}

.search-box svg {
  width: 18px;
  height: 18px;
  color: var(--text-secondary, #6b7280);
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.9rem;
  color: var(--text-primary, #111827);
}

.search-input::placeholder {
  color: var(--text-secondary, #6b7280);
}

.filter-select {
  padding: 10px 14px;
  background: var(--bg-primary, #ffffff);
  border: 1px solid var(--color-gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 0.9rem;
  color: var(--text-primary, #111827);
  min-width: 160px;
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

/* Contact Cell */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.email {
  color: var(--text-primary, #111827);
  font-size: 0.9rem;
}

.phone {
  color: var(--text-secondary, #6b7280);
  font-size: 0.8rem;
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

.status-draft { background: #eceff1; color: #607d8b; }
.status-new { background: #e3f2fd; color: #1976d2; }
.status-reviewing { background: #fff3e0; color: #f57c00; }
.status-shortlisted { background: #e8f5e9; color: #388e3c; }
.status-interviewed { background: #f3e5f5; color: #7b1fa2; }
.status-offered { background: #e0f2f1; color: #00796b; }
.status-hired { background: #c8e6c9; color: #2e7d32; }
.status-rejected { background: #ffebee; color: #c62828; }

.date-cell {
  color: var(--text-secondary, #6b7280);
  font-size: 0.9rem;
}

/* Actions */
.actions-cell {
  display: flex;
  gap: 8px;
}

.action-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: transparent;
  border: none;
  color: var(--text-secondary, #6b7280);
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn:hover {
  background: var(--color-gray-100, #f3f4f6);
  color: var(--color-primary, #6366f1);
}

.action-btn.danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

.action-btn svg {
  width: 16px;
  height: 16px;
  display: block;
  flex-shrink: 0;
}

/* Ensure button doesn't hide overflow */
button.action-btn {
  padding: 0;
  overflow: visible;
}

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
  margin-bottom: 16px;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: flex-end;
  }

  .filters-bar {
    flex-direction: column;
  }

  .filter-select {
    min-width: auto;
  }
}
</style>
