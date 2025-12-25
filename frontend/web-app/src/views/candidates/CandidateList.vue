<template>
  <div class="candidate-list">
    <div class="header">
      <h1>Candidates</h1>
      <div class="header-actions">
        <button @click="showBulkUpload = true" class="btn-secondary">Bulk Upload</button>
        <router-link to="/candidates/create" class="btn-primary">Add Candidate</router-link>
      </div>
    </div>
    
    <BulkUploadModal 
      :show="showBulkUpload" 
      @close="showBulkUpload = false"
      @refresh="fetchCandidates"
    />
    
    <div class="filters">
      <input 
        v-model="search" 
        @input="debouncedSearch"
        type="text" 
        placeholder="Search candidates..."
        class="search-input"
      />
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
    
    <div v-if="loading" class="loading">Loading candidates...</div>
    
    <div v-else class="candidates-table">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="candidate in candidates" :key="candidate.id">
            <td><strong>{{ candidate.name }}</strong></td>
            <td>{{ candidate.email }}</td>
            <td>{{ candidate.phone || 'N/A' }}</td>
            <td><span :class="'status status-' + candidate.status">{{ candidate.status }}</span></td>
            <td>{{ formatDate(candidate.created_at) }}</td>
            <td>
              <router-link :to="`/candidates/${candidate.id}`" class="btn-sm">View</router-link>
              <router-link :to="`/candidates/${candidate.id}/edit`" class="btn-sm" style="background: #4a5568;">Edit</router-link>
              <button @click="deleteCandidate(candidate.id)" class="btn-sm btn-danger">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="!loading && candidates.length === 0" class="empty-state">
      <p>No candidates found</p>
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

onMounted(() => {
  fetchCandidates()
})
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  min-width: 200px;
}

.candidates-table {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow-x: auto; /* Allow horizontal scroll */
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed; /* Fix column widths */
  min-width: 900px; /* Ensure minimum width to prevent squashing */
}

th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #333;
}

/* Column Widths */
th:nth-child(1) { width: 25%; } /* Name */
th:nth-child(2) { width: 20%; } /* Email */
th:nth-child(3) { width: 15%; } /* Phone */
th:nth-child(4) { width: 12%; } /* Status */
th:nth-child(5) { width: 12%; } /* Created */
th:nth-child(6) { width: 16%; } /* Actions */

td {
  padding: 1rem;
  border-top: 1px solid #eee;
  vertical-align: middle;
  
  /* Truncate long text */
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ensure actions column doesn't cut off buttons via overflow:hidden if possible, 
   but td has it. We need to override for actions if buttons wrap or need space.
   Actually, buttons inside actions need to be visible. */
td:last-child {
  overflow: visible; 
  white-space: normal; /* Allow buttons to wrap if really needed, or stay on one line */
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap; 
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-draft { background: #eceff1; color: #607d8b; }
.status-new { background: #e3f2fd; color: #1976d2; }
.status-reviewing { background: #fff3e0; color: #f57c00; }
.status-shortlisted { background: #e8f5e9; color: #388e3c; }
.status-interviewed { background: #f3e5f5; color: #7b1fa2; }
.status-offered { background: #e0f2f1; color: #00796b; }
.status-hired { background: #c8e6c9; color: #2e7d32; }
.status-rejected { background: #ffebee; color: #c62828; }

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  display: inline-block;
  border: none;
  cursor: pointer;
}

.btn-secondary {
  background: white;
  color: #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  display: inline-block;
  border: 2px solid #667eea;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #667eea;
  color: white;
}

.btn-sm {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.875rem;
  text-decoration: none;
  margin-right: 0.5rem;
  background: #667eea;
  color: white;
  border: none;
  cursor: pointer;
  display: inline-block; /* Ensure inline-block */
  visibility: visible !important; /* Force visibility */
  opacity: 1 !important; /* Force opacity */
}

.btn-danger {
  background: #dc3545;
}

td .btn-sm {
    margin-bottom: 0.25rem;
}

.loading, .empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
}
</style>
