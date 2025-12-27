<template>
  <div class="tab-content">
    <div class="header">
      <h2>CV Parsing Jobs</h2>
      <div class="actions">
        <button @click="loadCvJobs" class="btn-secondary">
          Refresh
        </button>
      </div>
    </div>

    <div class="filters-bar">
      <select v-model="cvJobStatusFilter" @change="loadCvJobs">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="parsing_document">Parsing Document</option>
        <option value="processing">Processing</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
      </select>
    </div>

    <div v-if="loadingCvJobs" class="loading">Loading jobs...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Status</th>
          <th>Candidate</th>
          <th>Created</th>
          <th>Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="job in cvJobs" :key="job.id">
          <td>#{{ job.id }}</td>
          <td>
            <span :class="'status-badge status-' + job.status">
              {{ job.status }}
            </span>
          </td>
          <td>
            <span v-if="job.candidate">
              {{ job.candidate.name || 'Unknown' }}
              <span v-if="job.candidate.email" class="text-sm text-gray-500">
                ({{ job.candidate.email }})
              </span>
            </span>
            <span v-else>-</span>
          </td>
          <td>{{ formatDateTime(job.created_at) }}</td>
          <td>{{ formatDateTime(job.updated_at) }}</td>
          <td>
            <button 
              v-if="['failed', 'pending', 'completed'].includes(job.status)" 
              @click="openReprocessModal(job.id)" 
              class="btn-sm"
            >
              {{ job.status === 'completed' ? 'Reprocess' : 'Retry' }}
            </button>
            <button 
              @click="deleteCvJob(job.id)" 
              class="btn-sm btn-danger ml-2"
              style="margin-left: 0.5rem;"
            >
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    
    <!-- Pagination -->
    <div v-if="cvJobsPagination.total > cvJobsPagination.per_page" class="pagination">
      <button 
        :disabled="cvJobsPagination.current_page === 1"
        @click="changeCvJobsPage(cvJobsPagination.current_page - 1)"
      >
        Previous
      </button>
      <span>Page {{ cvJobsPagination.current_page }} of {{ cvJobsPagination.last_page }}</span>
      <button 
        :disabled="cvJobsPagination.current_page === cvJobsPagination.last_page"
        @click="changeCvJobsPage(cvJobsPagination.current_page + 1)"
      >
        Next
      </button>
    </div>

    <!-- Reprocess Modal -->
    <div v-if="showReprocessModal" class="modal-overlay" @click="showReprocessModal = false">
      <div class="modal-content" @click.stop>
        <h3>Reprocess Job #{{ reprocessingJobId }}</h3>
        <div class="reprocess-options">
          <label class="radio-option">
            <input type="radio" v-model="reprocessMode" value="parse_only">
            <div>
              <strong>Re-parse Only (AI)</strong>
              <p class="text-sm text-gray-500">Uses existing extracted text. Faster, good for fixing AI errors.</p>
            </div>
          </label>
          <label class="radio-option">
            <input type="radio" v-model="reprocessMode" value="full">
            <div>
              <strong>Full Reprocess</strong>
              <p class="text-sm text-gray-500">Re-reads the document file and re-parses. Slower.</p>
            </div>
          </label>
        </div>
        <div class="modal-actions">
          <button @click="confirmReprocess" class="btn-primary">Confirm</button>
          <button @click="showReprocessModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { candidateAPI } from '../../services/api'

const loadingCvJobs = ref(false)
const error = ref('')
const cvJobs = ref([])
const cvJobStatusFilter = ref('')
const cvJobsPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20
})
let cvJobsRefreshInterval = null

// Reprocess Logic
const showReprocessModal = ref(false)
const reprocessingJobId = ref(null)
const reprocessMode = ref('parse_only')

const loadCvJobs = async (page = 1) => {
  // Handle event object or invalid page
  if (typeof page !== 'number') {
    page = 1
  }
  
  loadingCvJobs.value = true
  error.value = ''
  try {
    const params = {
      page,
      per_page: cvJobsPagination.value.per_page,
      status: cvJobStatusFilter.value
    }
    const response = await candidateAPI.listCvJobs(params)
    const data = response.data
    
    // Handle both straight array response and paginated response
    if (data.data) {
      cvJobs.value = data.data
      cvJobsPagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        per_page: data.per_page
      }
    } else {
      cvJobs.value = data
    }
  } catch (err) {
    console.error('Failed to load CV jobs:', err)
    error.value = 'Failed to load CV jobs'
  } finally {
    loadingCvJobs.value = false
  }
}

const changeCvJobsPage = (page) => {
  loadCvJobs(page)
}

const openReprocessModal = (id) => {
  reprocessingJobId.value = id
  showReprocessModal.value = true
  reprocessMode.value = 'parse_only'
}

const confirmReprocess = async () => {
    if (!reprocessingJobId.value) return
    
    try {
        await candidateAPI.retryCvJob(reprocessingJobId.value, reprocessMode.value)
        alert('Job queued for reprocessing')
        showReprocessModal.value = false
        loadCvJobs(cvJobsPagination.value.current_page)
    } catch (err) {
        console.error('Failed to reprocess job:', err)
        alert('Failed to reprocess job')
    }
}

const deleteCvJob = async (id) => {
  if (!confirm('Are you sure you want to delete this job? This action cannot be undone.')) return
  
  try {
    await candidateAPI.deleteCvJob(id)
    loadCvJobs(cvJobsPagination.value.current_page)
  } catch (err) {
    console.error('Failed to delete job:', err)
    alert('Failed to delete job')
  }
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString()
}

onMounted(() => {
  // Initial load
  loadCvJobs()
  
  // Auto-refresh every 30 seconds
  cvJobsRefreshInterval = setInterval(() => {
    // Only refresh if not loading
    if (!loadingCvJobs.value) {
      // Silent refresh
      const params = {
        page: cvJobsPagination.value.current_page,
        per_page: cvJobsPagination.value.per_page,
        status: cvJobStatusFilter.value
      }
      candidateAPI.listCvJobs(params).then(response => {
          if (response.data.data) {
            cvJobs.value = response.data.data
            cvJobsPagination.value = { ...cvJobsPagination.value, total: response.data.total }
          }
      }).catch(err => console.error(err))
    }
  }, 30000)
})

onUnmounted(() => {
  if (cvJobsRefreshInterval) clearInterval(cvJobsRefreshInterval)
})
</script>

<style scoped>
/* Scoped styles */
.tab-content { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.actions { display: flex; gap: 0.5rem; }
.filters-bar { margin-bottom: 1rem; display: flex; gap: 1rem; }
.filters-bar select { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
.data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.data-table th, .data-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
.data-table th { font-weight: 600; color: #666; background: #f8f9fa; }
.status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
.status-badge.status-completed { background: #e8f5e9; color: #2e7d32; }
.status-badge.status-failed { background: #ffebee; color: #c62828; }
.status-badge.status-processing, .status-badge.status-parsing_document { background: #e3f2fd; color: #1976d2; }
.status-badge.status-pending { background: #f5f5f5; color: #616161; }
.pagination { margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 1rem; }
.pagination button { padding: 0.5rem 1rem; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-sm { padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.875rem; background: #667eea; color: white; border: none; cursor: pointer; }
.btn-danger { background: #dc3545; color: white; }
.btn-secondary { background: #6c757d; color: white; padding: 0.5rem 1rem; border-radius: 4px; border: none; cursor: pointer; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; }
.reprocess-options { display: flex; flex-direction: column; gap: 1rem; margin: 1.5rem 0; }
.radio-option { display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; border: 1px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.radio-option:hover { background: #f8f9fa; border-color: #667eea; }
.radio-option input { margin-top: 0.25rem; }
.modal-actions { display: flex; gap: 1rem; margin-top: 2rem; }
.btn-primary { background: #667eea; color: white; padding: 0.75rem 2rem; border-radius: 6px; border: none; cursor: pointer; }
.loading { text-align: center; padding: 3rem; color: #666; }
.error { background: #fee; color: #c33; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }
.text-sm { font-size: 0.875rem; }
.text-gray-500 { color: #6b7280; }
</style>
