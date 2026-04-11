<template>
  <div class="tenant-detail">
    <div class="header">
      <div class="header-left">
        <router-link to="/tenants" class="back-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
          Back to Workspaces
        </router-link>
        <h1>{{ tenant?.name || 'Loading...' }}</h1>
        <span v-if="tenant?.slug" class="tenant-slug">{{ tenant.slug }}</span>
      </div>
      <div class="header-actions">
        <button v-if="canEdit" @click="showEditModal = true" class="btn-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit
        </button>
        <button v-if="canDelete" @click="confirmDelete" class="btn-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
          </svg>
          Delete
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading workspace details...</div>

    <template v-else-if="tenant">
      <div class="tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          :class="['tab', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Overview Tab -->
      <div v-if="activeTab === 'overview'" class="tab-content">
        <div class="card">
          <h2>Workspace Details</h2>
          <div class="detail-grid">
            <div class="detail-item">
              <label>Plan</label>
              <span class="plan-badge">{{ tenant.subscription_plan || 'free' }}</span>
            </div>
            <div class="detail-item">
              <label>Status</label>
              <span :class="'status-badge status-' + (tenant.subscription_status || 'active')">
                {{ tenant.subscription_status || 'active' }}
              </span>
            </div>
            <div class="detail-item" v-if="tenant.domain">
              <label>Domain</label>
              <span>{{ tenant.domain }}</span>
            </div>
            <div class="detail-item" v-if="tenant.logo_url">
              <label>Logo</label>
              <img :src="tenant.logo_url" alt="Logo" class="tenant-logo" />
            </div>
          </div>
        </div>
      </div>

      <!-- Members Tab -->
      <div v-if="activeTab === 'members'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h2>Team Members</h2>
            <button v-if="canManageMembers" @click="showInviteModal = true" class="btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
              </svg>
              Invite Member
            </button>
          </div>

          <div v-if="membersLoading" class="loading-small">Loading members...</div>
          
          <div v-else class="members-list">
            <div v-for="member in members" :key="member.id" class="member-item">
              <div class="member-avatar">
                {{ getInitials(member.user?.name || member.email || 'U') }}
              </div>
              <div class="member-info">
                <span class="member-name">{{ member.user?.name || 'Unknown User' }}</span>
                <span class="member-email">{{ member.user?.email || member.email || '' }}</span>
              </div>
              <div class="member-role">
                <span :class="'role-badge role-' + member.role">{{ member.role }}</span>
              </div>
              <div class="member-actions" v-if="canManageMembers && !member.is_owner">
                <button @click="editMember(member)" class="action-btn" title="Edit">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button @click="removeMember(member)" class="action-btn danger" title="Remove">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </div>
            </div>

            <div v-if="members.length === 0" class="empty-message">
              No team members found
            </div>
          </div>
        </div>
      </div>

      <!-- Invitations Tab -->
      <div v-if="activeTab === 'invitations'" class="tab-content">
        <div class="card">
          <div class="card-header">
            <h2>Pending Invitations</h2>
            <button v-if="canManageMembers" @click="showInviteModal = true" class="btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
              </svg>
              Send Invitation
            </button>
          </div>

          <div v-if="invitationsLoading" class="loading-small">Loading invitations...</div>
          
          <div v-else class="invitations-list">
            <div v-for="invitation in invitations" :key="invitation.id" class="invitation-item">
              <div class="invitation-info">
                <span class="invitation-email">{{ invitation.email }}</span>
                <span class="invitation-meta">
                  <span :class="'role-badge role-' + invitation.role">{{ invitation.role }}</span>
                  <span class="expires-at">Expires {{ formatDate(invitation.expires_at) }}</span>
                </span>
              </div>
              <div class="invitation-actions" v-if="canManageMembers">
                <button @click="copyInvitationLink(invitation)" class="action-btn" title="Copy Link">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                  </svg>
                </button>
                <button @click="cancelInvitation(invitation)" class="action-btn danger" title="Cancel">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
            </div>

            <div v-if="invitations.length === 0" class="empty-message">
              No pending invitations
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>Edit Workspace</h2>
            <button @click="showEditModal = false" class="close-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="updateTenant" class="modal-form">
            <div class="form-group">
              <label>Workspace Name</label>
              <input v-model="editForm.name" type="text" required />
            </div>
            <div class="form-group">
              <label>URL Slug</label>
              <input v-model="editForm.slug" type="text" pattern="[a-z0-9-]+" />
            </div>
            <div class="form-group">
              <label>Domain</label>
              <input v-model="editForm.domain" type="text" />
            </div>
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <div class="modal-actions">
              <button type="button" @click="showEditModal = false" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="saving">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Invite Modal -->
    <Teleport to="body">
      <div v-if="showInviteModal" class="modal-overlay" @click.self="showInviteModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>Invite Team Member</h2>
            <button @click="showInviteModal = false" class="close-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="sendInvitation" class="modal-form">
            <div class="form-group">
              <label>Email Address *</label>
              <input v-model="inviteForm.email" type="email" placeholder="colleague@company.com" required />
            </div>
            <div class="form-group">
              <label>Role *</label>
              <select v-model="inviteForm.role" required>
                <option value="member">Member</option>
                <option value="interviewer">Interviewer</option>
                <option value="recruiter">Recruiter</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label>Message (optional)</label>
              <textarea v-model="inviteForm.message" rows="3" placeholder="Add a personal message..."></textarea>
            </div>
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <div class="modal-actions">
              <button type="button" @click="showInviteModal = false" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="inviting">Send Invitation</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tenantAPI } from '@/services/api'

const route = useRoute()
const router = useRouter()

const tenant = ref(null)
const members = ref([])
const invitations = ref([])
const loading = ref(true)
const membersLoading = ref(false)
const invitationsLoading = ref(false)
const activeTab = ref('overview')
const showEditModal = ref(false)
const showInviteModal = ref(false)
const saving = ref(false)
const inviting = ref(false)
const formError = ref('')

const editForm = ref({ name: '', slug: '', domain: '' })
const inviteForm = ref({ email: '', role: 'member', message: '' })

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'members', label: 'Members' },
  { id: 'invitations', label: 'Invitations' }
]

const currentUserId = ref(null)

onMounted(async () => {
  const user = JSON.parse(localStorage.getItem('user'))
  currentUserId.value = user?.id
  await loadTenant()
  await loadMembers() // Load members immediately to enable canEdit checks
})

watch(() => route.params.id, async () => {
  await loadTenant()
})

watch(activeTab, async (tab) => {
  if (tab === 'members') {
    await loadMembers()
  } else if (tab === 'invitations') {
    await loadInvitations()
  }
})

async function loadTenant() {
  loading.value = true
  try {
    const response = await tenantAPI.get(route.params.id)
    tenant.value = response.data?.data ?? response.data
    editForm.value = {
      name: tenant.value.name,
      slug: tenant.value.slug || '',
      domain: tenant.value.domain || ''
    }
  } catch (error) {
    console.error('Failed to load tenant:', error)
    router.push('/tenants')
  } finally {
    loading.value = false
  }
}

async function loadMembers() {
  membersLoading.value = true
  try {
    const response = await tenantAPI.members(route.params.id)
    members.value = response.data?.data ?? response.data ?? []
  } catch (error) {
    console.error('Failed to load members:', error)
  } finally {
    membersLoading.value = false
  }
}

async function loadInvitations() {
  invitationsLoading.value = true
  try {
    const response = await api.get(`/api/tenants/${route.params.id}/invitations`)
    invitations.value = response.data?.data ?? response.data ?? []
  } catch (error) {
    console.error('Failed to load invitations:', error)
  } finally {
    invitationsLoading.value = false
  }
}

const api = (await import('@/services/api')).default

const canEdit = computed(() => {
  const member = members.value.find(m => m.user_id === currentUserId.value || m.user?.id === currentUserId.value)
  return member?.role === 'owner' || member?.role === 'admin'
})

const canDelete = computed(() => {
  const member = members.value.find(m => m.user_id === currentUserId.value || m.user?.id === currentUserId.value)
  return member?.role === 'owner'
})

const canManageMembers = computed(() => canEdit.value)

function getInitials(name = '') {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

async function updateTenant() {
  formError.value = ''
  saving.value = true
  try {
    await tenantAPI.update(route.params.id, editForm.value)
    showEditModal.value = false
    await loadTenant()
  } catch (error) {
    formError.value = error?.response?.data?.message || 'Failed to update workspace'
  } finally {
    saving.value = false
  }
}

async function sendInvitation() {
  formError.value = ''
  inviting.value = true
  try {
    await tenantAPI.invite(route.params.id, inviteForm.value)
    showInviteModal.value = false
    inviteForm.value = { email: '', role: 'member', message: '' }
    await loadInvitations()
  } catch (error) {
    formError.value = error?.response?.data?.message || 'Failed to send invitation'
  } finally {
    inviting.value = false
  }
}

async function removeMember(member) {
  if (!confirm(`Remove ${member.user?.name || member.email} from this workspace?`)) return
  
  try {
    await api.delete(`/api/tenants/${route.params.id}/members/${member.id}`)
    await loadMembers()
  } catch (error) {
    console.error('Failed to remove member:', error)
    alert('Failed to remove member')
  }
}

async function cancelInvitation(invitation) {
  if (!confirm('Cancel this invitation?')) return
  
  try {
    await api.delete(`/api/tenants/${route.params.id}/invitations/${invitation.id}`)
    await loadInvitations()
  } catch (error) {
    console.error('Failed to cancel invitation:', error)
    alert('Failed to cancel invitation')
  }
}

function copyInvitationLink(invitation) {
  const link = `${window.location.origin}/invitation/${invitation.token}`
  navigator.clipboard.writeText(link)
  alert('Invitation link copied to clipboard!')
}

function editMember(member) {
  // For now, just show an alert - could open a modal to edit role
  const newRole = prompt(`Change role for ${member.user?.name || member.email}:`, member.role)
  if (newRole && newRole !== member.role) {
    updateMemberRole(member, newRole)
  }
}

async function updateMemberRole(member, newRole) {
  try {
    await api.put(`/api/tenants/${route.params.id}/members/${member.id}`, { role: newRole })
    await loadMembers()
  } catch (error) {
    console.error('Failed to update member role:', error)
    alert('Failed to update role')
  }
}

function confirmDelete() {
  if (confirm(`Are you sure you want to delete "${tenant.value.name}"? This action cannot be undone.`)) {
    deleteTenant()
  }
}

async function deleteTenant() {
  try {
    await tenantAPI.delete(route.params.id)
    router.push('/tenants')
  } catch (error) {
    console.error('Failed to delete tenant:', error)
    alert('Failed to delete workspace')
  }
}
</script>

<style scoped>
.tenant-detail {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: #64748b;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #3b82f6;
}

.header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
}

.tenant-slug {
  color: #6b7280;
  font-size: 0.875rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.tab {
  padding: 0.75rem 1.25rem;
  background: none;
  border: none;
  border-radius: 8px 8px 0 0;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.tab:hover {
  color: #1f2937;
  background: #f9fafb;
}

.tab.active {
  color: #667eea;
  border-bottom: 2px solid #667eea;
  margin-bottom: -2px;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.card h2 {
  margin: 0 0 1.25rem 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.card-header h2 {
  margin: 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-item span {
  font-size: 1rem;
  color: #1f2937;
}

.tenant-logo {
  max-width: 100px;
  max-height: 50px;
  object-fit: contain;
}

.plan-badge {
  text-transform: capitalize;
}

.status-badge {
  padding: 0.25rem 0.75rem;
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

.members-list,
.invitations-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.member-item,
.invitation-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 10px;
  transition: background 0.2s;
}

.member-item:hover,
.invitation-item:hover {
  background: #f3f4f6;
}

.member-avatar {
  width: 40px;
  height: 40px;
  min-width: 40px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
}

.member-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.member-name {
  font-weight: 600;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.member-email {
  font-size: 0.875rem;
  color: #6b7280;
}

.member-role {
  flex-shrink: 0;
}

.role-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.role-owner {
  background: #fef3c7;
  color: #92400e;
}

.role-admin {
  background: #e0e7ff;
  color: #3730a3;
}

.role-manager {
  background: #dbeafe;
  color: #1e40af;
}

.role-recruiter {
  background: #d1fae5;
  color: #065f46;
}

.role-interviewer {
  background: #fce7f3;
  color: #9d174d;
}

.role-member {
  background: #f3f4f6;
  color: #374151;
}

.member-actions,
.invitation-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.action-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f9fafb;
  color: #1f2937;
}

.action-btn.danger:hover {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.invitation-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.invitation-email {
  font-weight: 600;
  color: #1f2937;
}

.invitation-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.875rem;
}

.expires-at {
  color: #6b7280;
}

.empty-message {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.loading {
  text-align: center;
  padding: 4rem;
  color: #94a3b8;
}

.loading-small {
  text-align: center;
  padding: 1rem;
  color: #94a3b8;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-danger {
  background: #fef2f2;
  color: #dc2626;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  border: 1px solid #fecaca;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: #fee2e2;
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
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
}
</style>
