<template>
  <div class="tenant-list">
    <div class="header">
      <div>
        <h1>Workspaces</h1>
        <p class="subtitle">Manage your workspaces and switch between them</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        New Workspace
      </button>
    </div>

    <div v-if="loading" class="loading">Loading workspaces...</div>

    <div v-else-if="tenants.length === 0" class="empty-state">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="64" height="64">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </div>
      <h3>No Workspaces Yet</h3>
      <p>Create your first workspace to start managing candidates and vacancies</p>
      <button @click="showCreateModal = true" class="btn-primary">Create Workspace</button>
    </div>

    <div v-else class="tenants-grid">
      <div 
        v-for="tenant in tenants" 
        :key="tenant.id" 
        class="tenant-card"
        :class="{ 'is-active': tenant.id === currentTenantId, 'is-switching': switching }"
        @click="switchToTenant(tenant)"
      >
        <div class="tenant-card-header">
          <div class="tenant-avatar">
            {{ getInitials(tenant.name) }}
          </div>
          <div class="tenant-status" v-if="tenant.id === currentTenantId">
            <span class="active-badge">Active</span>
          </div>
        </div>
        
        <div class="tenant-info">
          <h3>{{ tenant.name }}</h3>
          <span class="tenant-slug">{{ tenant.slug || tenant.id }}</span>
        </div>

        <div class="tenant-meta">
          <div class="meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>{{ tenant.subscription_plan || 'Free' }} plan</span>
          </div>
          <div class="meta-item" v-if="tenant.subscription_status">
            <span :class="'status-badge status-' + tenant.subscription_status">
              {{ tenant.subscription_status }}
            </span>
          </div>
        </div>

        <div class="tenant-card-actions">
          <router-link :to="`/tenants/${tenant.uuid}`" class="btn-sm btn-view" @click.stop>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            Manage
          </router-link>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>Create New Workspace</h2>
            <button @click="showCreateModal = false" class="close-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="createTenant" class="modal-form">
            <div class="form-group">
              <label for="name">Workspace Name *</label>
              <input
                id="name"
                v-model="newTenant.name"
                type="text"
                placeholder="e.g., Acme Corp Recruiting"
                required
                maxlength="255"
              />
            </div>

            <div class="form-group">
              <label for="slug">URL Slug (optional)</label>
              <input
                id="slug"
                v-model="newTenant.slug"
                type="text"
                placeholder="acme-recruiting"
                pattern="[a-z0-9-]+"
              />
              <span class="help-text">Lowercase letters, numbers, and hyphens only</span>
            </div>

            <div class="form-group">
              <label for="plan">Subscription Plan</label>
              <select id="plan" v-model="newTenant.subscription_plan">
                <option value="free">Free</option>
                <option value="starter">Starter</option>
                <option value="professional">Professional</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>

            <div v-if="formError" class="form-error">{{ formError }}</div>

            <div class="modal-actions">
              <button type="button" @click="showCreateModal = false" class="btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="creating">
                {{ creating ? 'Creating...' : 'Create Workspace' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { tenantAPI } from '@/services/api'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const tenants = ref([])
const loading = ref(true)
const showCreateModal = ref(false)
const creating = ref(false)
const formError = ref('')
const switching = ref(false)

const currentTenantId = computed(() => authStore.currentTenantId)

const newTenant = ref({
  name: '',
  slug: '',
  subscription_plan: 'free'
})

onMounted(async () => {
  await loadTenants()
})

async function loadTenants() {
  loading.value = true
  try {
    const response = await tenantAPI.list()
    tenants.value = response.data?.data ?? response.data ?? []
  } catch (error) {
    console.error('Failed to load tenants:', error)
  } finally {
    loading.value = false
  }
}

function getInitials(name = '') {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

async function switchToTenant(tenant) {
  if (tenant.id === currentTenantId.value || switching.value) return
  
  switching.value = true
  try {
    await authStore.switchTenant(tenant.id)
    router.push('/dashboard')
  } catch (error) {
    console.error('Failed to switch tenant:', error)
  } finally {
    switching.value = false
  }
}

async function createTenant() {
  formError.value = ''
  creating.value = true
  
  try {
    await tenantAPI.create(newTenant.value)
    showCreateModal.value = false
    newTenant.value = { name: '', slug: '', subscription_plan: 'free' }
    await loadTenants()
    await authStore.fetchTenants()
  } catch (error) {
    formError.value = error?.response?.data?.message || 'Failed to create workspace'
  } finally {
    creating.value = false
  }
}
</script>

<style scoped>
.tenant-list {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2.5rem;
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.tenants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

.tenant-card {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.3s ease;
}

.tenant-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.tenant-card.is-active {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
}

.tenant-card.is-switching {
  opacity: 0.7;
  pointer-events: none;
  cursor: wait;
}

.tenant-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.tenant-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
  font-weight: 700;
}

.active-badge {
  background: #dcfce7;
  color: #15803d;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.tenant-info {
  margin-bottom: 1rem;
}

.tenant-info h3 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
}

.tenant-slug {
  color: #6b7280;
  font-size: 0.875rem;
}

.tenant-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}

.status-expired {
  background: #fee2e2;
  color: #b91c1c;
}

.tenant-card-actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.btn-sm {
  flex: 1;
  padding: 0.625rem;
  border-radius: 8px;
  font-size: 0.875rem;
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

.btn-view {
  background: #eff6ff;
  color: #3b82f6;
}

.btn-view:hover {
  background: #dbeafe;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
  transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.5);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f5f9;
  color: #64748b;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.empty-state p {
  color: #64748b;
  margin: 0 0 1.5rem 0;
}

.loading {
  text-align: center;
  padding: 4rem;
  color: #94a3b8;
  font-size: 1.1rem;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.modal-form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.help-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.form-error {
  background: #fef2f2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 0.5rem;
}
</style>
