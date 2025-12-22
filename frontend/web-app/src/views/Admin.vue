<template>
  <div class="admin-panel">
    <h1>Administration</h1>
    
    <div class="admin-tabs">
      <button 
        @click="currentTab = 'system'" 
        :class="{ active: currentTab === 'system' }"
      >
        System Health
      </button>
      <button 
        @click="currentTab = 'settings'" 
        :class="{ active: currentTab === 'settings' }"
      >
        Settings
      </button>
      <button 
        @click="currentTab = 'users'" 
        :class="{ active: currentTab === 'users' }"
      >
        User Management
      </button>
    </div>

    <!-- System Health Tab -->
    <div v-if="currentTab === 'system'" class="tab-content">
      <h2>System Health</h2>
      <div v-if="loading" class="loading">Loading system health...</div>
      <div v-else class="health-grid">
        <div v-for="service in systemHealth" :key="service.service" class="health-card">
          <div class="health-header">
            <h3>{{ service.service }}</h3>
            <span :class="'status-badge status-' + service.status">{{ service.status }}</span>
          </div>
          <div class="health-details">
            <p><strong>Response Time:</strong> {{ service.response_time }}ms</p>
            <p v-if="service.version"><strong>Version:</strong> {{ service.version }}</p>
            <p v-if="service.uptime"><strong>Uptime:</strong> {{ service.uptime }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings Tab -->
    <div v-if="currentTab === 'settings'" class="tab-content">
      <h2>System Settings</h2>
      <div v-if="loading" class="loading">Loading settings...</div>
      <form v-else @submit.prevent="saveSettings" class="settings-form">
        <div class="form-group">
          <label>Application Name</label>
          <input v-model="settings.app_name" type="text" />
        </div>
        
        <div class="form-group">
          <label>Company Name</label>
          <input v-model="settings.company_name" type="text" />
        </div>
        
        <div class="form-group">
          <label>Contact Email</label>
          <input v-model="settings.contact_email" type="email" />
        </div>

        <div class="form-group">
          <label>Applicant Portal Base URL</label>
          <input 
            v-model="settings.candidate_portal_url" 
            type="text" 
            placeholder="http://localhost:5173/portal" 
          />
          <small>The base URL for generated applicant links (e.g. https://your-domain.com/portal)</small>
        </div>
        
        <div class="form-group">
          <label>
            <input v-model="settings.enable_notifications" type="checkbox" />
            Enable Email Notifications
          </label>
        </div>
        
        <div class="form-group">
          <label>
            <input v-model="settings.enable_ai" type="checkbox" />
            Enable AI Features
          </label>
        </div>
        
        <div class="form-group">
          <label>Max File Upload Size (MB)</label>
          <input v-model.number="settings.max_upload_size" type="number" min="1" max="100" />
        </div>

        <div class="form-group">
          <label>Login Background Image</label>
          <input 
            v-model="settings.login_background_image" 
            type="text" 
            placeholder="https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920"
          />
          <small>URL to background image for login page. Leave empty for gradient fallback.</small>
        </div>

        <hr style="margin: 2rem 0; border: none; border-top: 2px solid #eee;" />
        <h3 style="margin-bottom: 1rem;">AI Provider Settings</h3>

        <div class="form-group">
          <label>AI Provider</label>
          <select v-model="settings.ai_provider" class="select-input">
            <option value="ollama">Ollama (Local, Free)</option>
            <option value="openrouter">OpenRouter (Cloud API)</option>
          </select>
          <small style="color: #666; margin-top: 0.5rem; display: block;">
            Ollama runs locally (free), OpenRouter uses cloud API (requires key)
          </small>
        </div>

        <div v-if="settings.ai_provider === 'ollama'" class="provider-settings">
          <div class="form-group">
            <label>Ollama URL</label>
            <input 
              v-model="settings.ollama_url" 
              type="text" 
              placeholder="http://ollama:11434"
            />
            <small>Default: http://ollama:11434 (Internal Docker DNS) or http://host.docker.internal:11434 (Localhost)</small>
          </div>
          
          <div class="form-group">
            <label>Ollama Model (CV Parsing)</label>
            <input 
              v-model="settings.ollama_model" 
              type="text" 
              placeholder="mistral"
            />
            <small>Larger model for detailed CV parsing. Default: mistral</small>
          </div>
          
          <div class="form-group">
            <label>Ollama Model (Matching)</label>
            <input 
              v-model="settings.ollama_matching_model" 
              type="text" 
              placeholder="llama3.2:3b"
            />
            <small>Faster model for candidate matching. Recommended: llama3.2:3b, phi3:mini, gemma2:2b</small>
          </div>
          
          <div class="form-group">
            <label>Ollama Model (Interview Questions)</label>
            <input 
              v-model="settings.ollama_questionnaire_model" 
              type="text" 
              placeholder="gemma2:2b"
            />
            <small>Model for generating interview questions. Recommended: gemma2:2b, llama3.2:3b, phi3:mini</small>
          </div>
        </div>

        <div v-if="settings.ai_provider === 'openrouter'" class="form-group">
          <label>OpenRouter API Key</label>
          <input 
            v-model="settings.openrouter_api_key" 
            type="password" 
            placeholder="sk-or-..." 
          />
          <small style="color: #666; margin-top: 0.5rem; display: block;">
            Get your API key from <a href="https://openrouter.ai/" target="_blank">openrouter.ai</a>
          </small>
        </div>

        <div v-if="error" class="error">{{ error }}</div>
        <div v-if="success" class="success">{{ success }}</div>
        
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </form>

      <hr style="margin: 2rem 0; border: none; border-top: 2px solid #eee;" />
      <h3 style="margin-bottom: 1rem;">Maintenance</h3>
      
      <div class="maintenance-section">
        <div class="maintenance-item">
          <div class="maintenance-info">
            <strong>Clear All Matches</strong>
            <p>Delete all calculated matches from database and clear cache. Use this to recalculate all matches fresh.</p>
          </div>
          <button 
            @click="clearMatches" 
            class="btn-danger" 
            :disabled="clearingMatches"
          >
            {{ clearingMatches ? 'Clearing...' : 'Clear Matches' }}
          </button>
        </div>
      </div>
    </div>

    <!-- User Management Tab -->
    <div v-if="currentTab === 'users'" class="tab-content">
      <div class="header">
        <h2>User Management</h2>
        <button @click="showCreateUser = true" class="btn-primary">Add User</button>
      </div>
      
      <div v-if="loading" class="loading">Loading users...</div>
      <table v-else class="users-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Department</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td><strong>{{ user.name }}</strong></td>
            <td>{{ user.email }}</td>
            <td>
              <span v-for="role in (user.roles || [])" :key="role.id" class="role-badge">
                {{ role.display_name || role.name }}
              </span>
              <span v-if="!user.roles || user.roles.length === 0" class="no-role">No role</span>
            </td>
            <td>{{ user.department || 'N/A' }}</td>
            <td>
              <span :class="'status-badge status-' + (user.is_active ? 'active' : 'inactive')">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <button @click="editUser(user)" class="btn-sm">Edit</button>
              <button @click="deleteUser(user.id)" class="btn-sm btn-danger">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit User Modal -->
    <div v-if="showCreateUser" class="modal-overlay" @click="showCreateUser = false">
      <div class="modal-content" @click.stop>
        <h3>{{ editingUser ? 'Edit User' : 'Create New User' }}</h3>
        <form @submit.prevent="submitUser" class="user-form">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="userForm.name" type="text" required />
          </div>
          
          <div class="form-group">
            <label>Email *</label>
            <input v-model="userForm.email" type="email" required />
          </div>
          
          <div class="form-group" v-if="!editingUser">
            <label>Password *</label>
            <input v-model="userForm.password" type="password" required />
          </div>
          
          <div class="form-group">
            <label>Department</label>
            <input v-model="userForm.department" type="text" />
          </div>
          
          <div class="form-group">
            <label>Position</label>
            <input v-model="userForm.position" type="text" />
          </div>
          
          <div class="form-group">
            <label>
              <input v-model="userForm.is_active" type="checkbox" />
              Active
            </label>
          </div>
          
          <div class="form-group">
            <label>Role</label>
            <select v-model="userForm.role_id" class="select-input">
              <option value="">Select a role...</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.display_name }}
              </option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn-primary">{{ editingUser ? 'Update' : 'Create' }}</button>
            <button type="button" @click="showCreateUser = false" class="btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { adminAPI, userAPI, authAPI, roleAPI, matchingAPI } from '../services/api'

const currentTab = ref('system')
const loading = ref(false)
const saving = ref(false)
const clearingMatches = ref(false)
const error = ref('')
const success = ref('')

// System Health
const systemHealth = ref([])

// Settings
const settings = ref({
  app_name: 'Candidacy',
  company_name: '',
  contact_email: '',
  enable_notifications: true,
  enable_ai: true,
  max_upload_size: 10,
  login_background_image: '',
  ai_provider: 'ollama',
  ollama_url: 'http://ollama:11434',
  ollama_model: 'mistral',
  ollama_matching_model: 'llama3.2:3b',
  ollama_questionnaire_model: 'gemma2:2b',
  openrouter_api_key: ''
})

// Users
const users = ref([])
const roles = ref([])
const showCreateUser = ref(false)
const editingUser = ref(null)
const userForm = ref({
  name: '',
  email: '',
  password: '',
  department: '',
  position: '',
  is_active: true,
  role_id: ''
})

const loadSystemHealth = async () => {
  loading.value = true
  try {
    const response = await adminAPI.getSystemHealth()
    systemHealth.value = response.data.services || []
  } catch (err) {
    console.error('Failed to load system health:', err)
    error.value = 'Failed to load system health'
  } finally {
    loading.value = false
  }
}

const loadSettings = async () => {
  loading.value = true
  try {
    const response = await adminAPI.getSettings()
    console.log('Settings API response:', response.data)
    
    // The API returns {settings: {...}}, so access response.data.settings
    const settingsData = response.data.settings || response.data
    Object.assign(settings.value, settingsData)
    
    console.log('Settings after merge:', settings.value)
  } catch (err) {
    console.error('Failed to load settings:', err)
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  error.value = ''
  success.value = ''
  
  try {
    await adminAPI.updateSettings(settings.value)
    success.value = 'Settings saved successfully!'
    setTimeout(() => success.value = '', 3000)
  } catch (err) {
    error.value = 'Failed to save settings'
  } finally {
    saving.value = false
  }
}

const clearMatches = async () => {
  if (!confirm('Are you sure you want to delete ALL matches? This cannot be undone.')) {
    return
  }
  
  clearingMatches.value = true
  error.value = ''
  
  try {
    const response = await matchingAPI.clear()
    const count = response.data?.deleted_count || 0
    success.value = `Successfully cleared ${count} matches!`
    setTimeout(() => success.value = '', 5000)
  } catch (err) {
    error.value = 'Failed to clear matches: ' + (err.response?.data?.message || err.message)
  } finally {
    clearingMatches.value = false
  }
}

const loadUsers = async () => {
  loading.value = true
  try {
    const [usersRes, rolesRes] = await Promise.all([
      userAPI.list(),
      roleAPI.list()
    ])
    users.value = usersRes.data.data || usersRes.data || []
    roles.value = rolesRes.data || []
  } catch (err) {
    console.error('Failed to load users:', err)
    users.value = []
  } finally {
    loading.value = false
  }
}

const editUser = (user) => {
  editingUser.value = user
  userForm.value = { 
    ...user, 
    password: '',
    role_id: user.roles && user.roles.length > 0 ? user.roles[0].id : ''
  }
  showCreateUser.value = true
}

const submitUser = async () => {
  try {
    let userId
    if (editingUser.value) {
      await userAPI.update(editingUser.value.id, userForm.value)
      userId = editingUser.value.id
    } else {
      const response = await userAPI.create(userForm.value)
      userId = response.data.id
    }
    
    // Handle role assignment
    if (userForm.value.role_id && userId) {
      try {
        await userAPI.assignRole(userId, userForm.value.role_id)
      } catch (roleErr) {
        console.error('Failed to assign role:', roleErr)
      }
    }
    
    showCreateUser.value = false
    editingUser.value = null
    userForm.value = {
      name: '',
      email: '',
      password: '',
      department: '',
      position: '',
      is_active: true,
      role_id: ''
    }
    loadUsers()
  } catch (err) {
    console.error('Failed to save user:', err)
    alert('Failed to save user')
  }
}

const deleteUser = async (id) => {
  if (!confirm('Are you sure you want to delete this user?')) return
  
  try {
    await userAPI.delete(id)
    loadUsers()
  } catch (err) {
    console.error('Failed to delete user:', err)
    alert('Failed to delete user')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

// Watch for tab changes and load appropriate data
watch(currentTab, (newTab) => {
  if (newTab === 'system') {
    loadSystemHealth()
  } else if (newTab === 'settings') {
    loadSettings()
  } else if (newTab === 'users') {
    loadUsers()
  }
})

onMounted(() => {
  // Load initial tab data
  if (currentTab.value === 'system') {
    loadSystemHealth()
  } else if (currentTab.value === 'settings') {
    loadSettings()
  } else if (currentTab.value === 'users') {
    loadUsers()
  }
})
</script>

<style scoped>
.admin-panel {
  padding: 2rem;
}

.admin-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #eee;
}

.admin-tabs button {
  padding: 1rem 2rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-weight: 500;
  color: #666;
  transition: all 0.3s;
}

.admin-tabs button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.tab-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.health-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.health-card {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.health-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.health-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.status-online,
.status-badge.status-active {
  background: #e8f5e9;
  color: #388e3c;
}

.status-badge.status-offline,
.status-badge.status-inactive {
  background: #ffebee;
  color: #c62828;
}

.health-details p {
  margin: 0.5rem 0;
  font-size: 0.9rem;
}

.settings-form {
  max-width: 600px;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="number"] {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.form-group input[type="checkbox"] {
  margin-right: 0.5rem;
}

.select-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  background: white;
  cursor: pointer;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

.users-table td {
  padding: 1rem;
  border-top: 1px solid #eee;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-danger {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-danger:hover {
  background: linear-gradient(135deg, #c82333 0%, #a71d2a 100%);
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.maintenance-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.maintenance-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #eee;
}

.maintenance-info p {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #666;
}

.btn-sm {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.875rem;
  margin-right: 0.5rem;
  background: #667eea;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-danger {
  background: #dc3545;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin: 1rem 0;
}

.success {
  background: #e8f5e9;
  color: #388e3c;
  padding: 0.75rem;
  border-radius: 6px;
  margin: 1rem 0;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.role-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-right: 0.25rem;
}

.no-role {
  color: #999;
  font-style: italic;
  font-size: 0.875rem;
}
</style>
