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
        @click="currentTab = 'configuration'" 
        :class="{ active: currentTab === 'configuration' }"
      >
        Configuration
      </button>
      <button 
        @click="currentTab = 'users'" 
        :class="{ active: currentTab === 'users' }"
      >
        User Management
      </button>
      <button 
        @click="currentTab = 'cv_jobs'" 
        :class="{ active: currentTab === 'cv_jobs' }"
      >
        CV Jobs
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

    <!-- Configuration Tab -->
    <div v-if="currentTab === 'configuration'" class="tab-content">
      <div class="config-header">
        <h2>Configuration Management</h2>
        <div class="config-actions">
          <button @click="exportConfiguration" class="btn-secondary">
            📥 Export
          </button>
          <button @click="showImportModal = true" class="btn-secondary">
            📤 Import
          </button>
          <button @click="loadConfiguration" class="btn-secondary">
            🔄 Refresh
          </button>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="config-filters">
        <input 
          v-model="configSearch" 
          type="text" 
          placeholder="Search settings..." 
          class="search-input"
        />
        <select v-model="configCategoryFilter" class="filter-select">
          <option value="">All Categories</option>
          <option value="system">System</option>
          <option value="ai">AI</option>
          <option value="document_parser">Document Parser</option>
          <option value="recruitment">Recruitment</option>
          <option value="storage">Storage</option>
          <option value="features">Features</option>
          <option value="services">Services</option>
          <option value="ui">UI Customization</option>
        </select>
      </div>

      <div v-if="loadingConfig" class="loading">Loading configuration...</div>
      
      <!-- Configuration Categories -->
      <div v-else class="config-categories">
        <div 
          v-for="category in filteredCategories" 
          :key="category.name" 
          class="config-category"
        >
          <div 
            class="category-header" 
            @click="toggleCategory(category.name)"
          >
            <div class="category-title">
              <span class="category-icon">{{ category.icon }}</span>
              <h3>{{ category.label }}</h3>
              <span class="setting-count">({{ category.settings.length }} settings)</span>
            </div>
            <span class="toggle-icon">{{ expandedCategories[category.name] ? '▼' : '▶' }}</span>
          </div>

          <div v-if="expandedCategories[category.name]" class="category-content">
            <div 
              v-for="setting in category.settings" 
              :key="setting.key" 
              class="setting-item"
            >
              <div class="setting-info">
                <div class="setting-key-row">
                  <code class="setting-key">{{ setting.key }}</code>
                  <span v-if="setting.is_sensitive" class="sensitive-badge">🔒 Sensitive</span>
                  <span v-if="setting.service_scope" class="scope-badge">
                    {{ setting.service_scope }}
                  </span>
                </div>
                <p class="setting-description">{{ setting.description }}</p>
              </div>

              <div class="setting-value">
                <div v-if="editingSetting === setting.key" class="setting-edit">
                  <!-- Boolean Type - Toggle Switch -->
                  <div v-if="setting.type === 'boolean'" class="edit-control">
                    <label class="toggle-switch">
                      <input 
                        v-model="editValue" 
                        type="checkbox"
                        :true-value="true"
                        :false-value="false"
                      />
                      <span class="toggle-slider"></span>
                      <span class="toggle-label">{{ editValue ? 'Enabled' : 'Disabled' }}</span>
                    </label>
                  </div>

                  <!-- Color Picker for primary_color -->
                  <div v-else-if="setting.key.includes('_color') || setting.key.includes('.color')" class="edit-control color-picker-control">
                    <input 
                      v-model="editValue" 
                      type="color" 
                      class="color-picker"
                    />
                    <input 
                      v-model="editValue" 
                      type="text" 
                      class="edit-input color-text"
                      placeholder="#RRGGBB"
                    />
                  </div>

                  <!-- Dropdown for AI Provider -->
                  <select 
                    v-else-if="setting.key === 'ai.provider'" 
                    v-model="editValue" 
                    class="edit-select"
                  >
                    <option value="ollama">Ollama (Local)</option>
                    <option value="openrouter">OpenRouter (Cloud)</option>
                  </select>

                  <!-- Dropdown for Date Format -->
                  <select 
                    v-else-if="setting.key === 'ui.date_format'" 
                    v-model="editValue" 
                    class="edit-select"
                  >
                    <option value="YYYY-MM-DD">YYYY-MM-DD (2025-12-26)</option>
                    <option value="DD/MM/YYYY">DD/MM/YYYY (26/12/2025)</option>
                    <option value="MM/DD/YYYY">MM/DD/YYYY (12/26/2025)</option>
                    <option value="DD MMM YYYY">DD MMM YYYY (26 Dec 2025)</option>
                    <option value="MMM DD, YYYY">MMM DD, YYYY (Dec 26, 2025)</option>
                  </select>

                  <!-- Dropdown for Time Format -->
                  <select 
                    v-else-if="setting.key === 'ui.time_format'" 
                    v-model="editValue" 
                    class="edit-select"
                  >
                    <option value="HH:mm">24-hour (14:30)</option>
                    <option value="HH:mm:ss">24-hour with seconds (14:30:00)</option>
                    <option value="hh:mm A">12-hour (02:30 PM)</option>
                    <option value="hh:mm:ss A">12-hour with seconds (02:30:00 PM)</option>
                  </select>

                  <!-- Dropdown for Employment Type (if exists) -->
                  <select 
                    v-else-if="setting.key.includes('employment_type')" 
                    v-model="editValue" 
                    class="edit-select"
                  >
                    <option value="full_time">Full Time</option>
                    <option value="part_time">Part Time</option>
                    <option value="contract">Contract</option>
                    <option value="intern">Intern</option>
                  </select>

                  <!-- Integer Type with Range Slider for percentages/thresholds -->
                  <div v-else-if="setting.type === 'integer' && (setting.key.includes('threshold') || setting.key.includes('score'))" class="edit-control range-control">
                    <input 
                      v-model.number="editValue" 
                      type="range" 
                      min="0" 
                      max="100" 
                      class="range-slider"
                    />
                    <input 
                      v-model.number="editValue" 
                      type="number" 
                      min="0"
                      max="100"
                      class="edit-input range-value"
                    />
                    <span class="range-unit">%</span>
                  </div>

                  <!-- Integer Type - Regular Number -->
                  <input 
                    v-else-if="setting.type === 'integer'" 
                    v-model.number="editValue" 
                    type="number" 
                    class="edit-input"
                  />

                  <!-- URL Type -->
                  <input 
                    v-else-if="setting.key.includes('_url') || setting.key.includes('.url')"
                    v-model="editValue" 
                    type="url" 
                    class="edit-input edit-url"
                    placeholder="https://..."
                  />

                  <!-- Sensitive String -->
                  <input 
                    v-else-if="setting.is_sensitive"
                    v-model="editValue" 
                    :type="showSensitive[setting.key] ? 'text' : 'password'"
                    class="edit-input"
                  />

                  <!-- Regular String Type -->
                  <input 
                    v-else
                    v-model="editValue" 
                    type="text" 
                    class="edit-input"
                  />

                  <div class="edit-actions">
                    <button @click="saveSetting(setting)" class="btn-sm btn-save">
                      ✓ Save
                    </button>
                    <button @click="cancelEdit" class="btn-sm btn-cancel">
                      ✗ Cancel
                    </button>
                  </div>
                </div>

                <div v-else class="setting-display">
                  <span class="setting-current-value">
                    <!-- Boolean Display -->
                    <span v-if="setting.type === 'boolean'" :class="setting.value ? 'value-true' : 'value-false'">
                      {{ setting.value ? '✓ Enabled' : '✗ Disabled' }}
                    </span>

                    <!-- Sensitive Value Display -->
                    <span v-else-if="setting.is_sensitive">
                      <span v-if="showSensitive[setting.key]">{{ setting.value || '(empty)' }}</span>
                      <span v-else>••••••••</span>
                      <button 
                        @click="toggleSensitive(setting.key)" 
                        class="btn-icon"
                        :title="showSensitive[setting.key] ? 'Hide' : 'Show'"
                      >
                        {{ showSensitive[setting.key] ? '👁️' : '👁️‍🗨️' }}
                      </button>
                    </span>

                    <!-- Regular Value Display -->
                    <span v-else>{{ setting.value || '(empty)' }}</span>
                  </span>

                  <div class="setting-actions">
                    <button @click="startEdit(setting)" class="btn-icon" title="Edit">
                      ✏️
                    </button>
                    <button @click="viewHistory(setting.key)" class="btn-icon" title="View History">
                      📜
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="configError" class="error">{{ configError }}</div>
      <div v-if="configSuccess" class="success">{{ configSuccess }}</div>
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

    <!-- CV Jobs Tab -->
    <div v-if="currentTab === 'cv_jobs'" class="tab-content">
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
                v-if="['failed', 'pending'].includes(job.status)" 
                @click="retryCvJob(job.id)" 
                class="btn-sm"
              >
                Retry
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

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
      <div class="modal-content modal-large" @click.stop>
        <h3>Change History: {{ historyData?.setting?.key }}</h3>
        <div v-if="historyData?.history && historyData.history.length > 0" class="history-list">
          <div v-for="(change, index) in historyData.history" :key="index" class="history-item">
            <div class="history-header">
              <span class="history-date">{{ formatDate(change.changed_at) }}</span>
              <span class="history-user">by User #{{ change.changed_by }}</span>
            </div>
            <div class="history-changes">
              <div class="history-value">
                <strong>Old:</strong> <code>{{ change.old_value || '(empty)' }}</code>
              </div>
              <div class="history-value">
                <strong>New:</strong> <code>{{ change.new_value }}</code>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="no-history">No change history available</div>
        <div class="modal-actions">
          <button @click="showHistoryModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click="showImportModal = false">
      <div class="modal-content modal-large" @click.stop>
        <h3>Import Configuration</h3>
        <p>Paste your configuration JSON below:</p>
        <textarea 
          v-model="importData" 
          class="import-textarea"
          placeholder='{"settings": [...]}'
          rows="15"
        ></textarea>
        <div class="modal-actions">
          <button @click="importConfiguration" class="btn-primary">Import</button>
          <button @click="showImportModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { adminAPI, userAPI, authAPI, roleAPI, candidateAPI } from '../services/api'

const currentTab = ref('system')
const loading = ref(false)
const error = ref('')
const success = ref('')

// System Health
const systemHealth = ref([])

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

// Configuration Management
const loadingConfig = ref(false)
const configError = ref('')
const configSuccess = ref('')
const configSettings = ref([])
const configSearch = ref('')
const configCategoryFilter = ref('')
const expandedCategories = ref({})
const editingSetting = ref(null)
const editValue = ref(null)
const showSensitive = ref({})
const showImportModal = ref(false)
const showHistoryModal = ref(false)
const historyData = ref(null)
const importData = ref('')

// CV Jobs
const cvJobs = ref([])
const loadingCvJobs = ref(false)
const cvJobStatusFilter = ref('')
const cvJobsPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20
})
let cvJobsRefreshInterval = null

// Category configuration
const categoryConfig = {
  system: { label: 'System', icon: '⚙️' },
  ai: { label: 'AI Configuration', icon: '🤖' },
  document_parser: { label: 'Document Parser', icon: '📄' },
  recruitment: { label: 'Recruitment', icon: '👥' },
  storage: { label: 'Storage', icon: '💾' },
  features: { label: 'Features', icon: '✨' },
  services: { label: 'Services', icon: '🔗' },
  ui: { label: 'UI Customization', icon: '🎨' }
}


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

// CV Jobs Methods
const loadCvJobs = async (page = 1) => {
  // Handle event object or invalid page
  if (typeof page !== 'number') {
    page = 1
  }
  
  loadingCvJobs.value = true
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
  } finally {
    loadingCvJobs.value = false
  }
}

const changeCvJobsPage = (page) => {
  loadCvJobs(page)
}

const retryCvJob = async (id) => {
  try {
    await candidateAPI.retryCvJob(id)
    alert('Job queued for retry')
    loadCvJobs(cvJobsPagination.value.current_page)
  } catch (err) {
    console.error('Failed to retry job:', err)
    alert('Failed to retry job')
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

// Configuration Management Methods
const loadConfiguration = async () => {
  loadingConfig.value = true
  configError.value = ''
  try {
    const response = await adminAPI.getDetailedSettings()
    configSettings.value = response.data.settings || []
    // Initialize expanded categories
    Object.keys(categoryConfig).forEach(cat => {
      expandedCategories.value[cat] = true
    })
  } catch (err) {
    console.error('Failed to load configuration:', err)
    configError.value = 'Failed to load configuration settings'
  } finally {
    loadingConfig.value = false
  }
}

const filteredCategories = computed(() => {
  const categories = {}
  
  // Group settings by category
  configSettings.value.forEach(setting => {
    if (!categories[setting.category]) {
      categories[setting.category] = {
        name: setting.category,
        label: categoryConfig[setting.category]?.label || setting.category,
        icon: categoryConfig[setting.category]?.icon || '📋',
        settings: []
      }
    }
    categories[setting.category].settings.push(setting)
  })
  
  // Apply filters
  let filtered = Object.values(categories)
  
  if (configCategoryFilter.value) {
    filtered = filtered.filter(cat => cat.name === configCategoryFilter.value)
  }
  
  if (configSearch.value) {
    const search = configSearch.value.toLowerCase()
    filtered = filtered.map(cat => ({
      ...cat,
      settings: cat.settings.filter(s => 
        s.key.toLowerCase().includes(search) ||
        s.description?.toLowerCase().includes(search)
      )
    })).filter(cat => cat.settings.length > 0)
  }
  
  return filtered
})

const toggleCategory = (categoryName) => {
  expandedCategories.value[categoryName] = !expandedCategories.value[categoryName]
}

const startEdit = (setting) => {
  editingSetting.value = setting.key
  // Convert boolean string to actual boolean
  if (setting.type === 'boolean') {
    editValue.value = setting.value === true || setting.value === 'true'
  } else {
    editValue.value = setting.value
  }
}

const cancelEdit = () => {
  editingSetting.value = null
  editValue.value = null
}

const saveSetting = async (setting) => {
  configError.value = ''
  configSuccess.value = ''
  
  try {
    await adminAPI.updateSetting(setting.key, editValue.value)
    configSuccess.value = `Successfully updated ${setting.key}`
    
    // Update local value
    const settingIndex = configSettings.value.findIndex(s => s.key === setting.key)
    if (settingIndex !== -1) {
      configSettings.value[settingIndex].value = editValue.value
    }
    
    editingSetting.value = null
    editValue.value = null
    
    setTimeout(() => configSuccess.value = '', 3000)
  } catch (err) {
    console.error('Failed to save setting:', err)
    configError.value = `Failed to save ${setting.key}: ${err.response?.data?.message || err.message}`
  }
}

const toggleSensitive = (key) => {
  showSensitive.value[key] = !showSensitive.value[key]
}

const viewHistory = async (key) => {
  try {
    const response = await adminAPI.getSettingHistory(key)
    historyData.value = response.data
    showHistoryModal.value = true
  } catch (err) {
    console.error('Failed to load history:', err)
    configError.value = 'Failed to load change history'
  }
}

const exportConfiguration = async () => {
  try {
    const response = await adminAPI.exportSettings()
    const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `configuration-${new Date().toISOString().split('T')[0]}.json`
    a.click()
    window.URL.revokeObjectURL(url)
    configSuccess.value = 'Configuration exported successfully'
    setTimeout(() => configSuccess.value = '', 3000)
  } catch (err) {
    console.error('Failed to export configuration:', err)
    configError.value = 'Failed to export configuration'
  }
}

const importConfiguration = async () => {
  try {
    const data = JSON.parse(importData.value)
    await adminAPI.importSettings(data)
    configSuccess.value = 'Configuration imported successfully'
    showImportModal.value = false
    importData.value = ''
    loadConfiguration()
    setTimeout(() => configSuccess.value = '', 3000)
  } catch (err) {
    console.error('Failed to import configuration:', err)
    configError.value = `Failed to import: ${err.response?.data?.message || err.message}`
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

// Watch for tab changes and load appropriate data
watch(currentTab, (newTab) => {
  if (newTab === 'system') {
    loadSystemHealth()
  } else if (newTab === 'configuration') {
    loadConfiguration()
  } else if (newTab === 'users') {
    loadUsers()
  } else if (newTab === 'cv_jobs') {
    loadCvJobs()
    // Auto-refresh every 30 seconds
    if (cvJobsRefreshInterval) clearInterval(cvJobsRefreshInterval)
    cvJobsRefreshInterval = setInterval(() => {
      // Only refresh if tab is still active and not loading
      if (currentTab.value === 'cv_jobs' && !loadingCvJobs.value) {
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
        })
      }
    }, 30000)
  } else {
    if (cvJobsRefreshInterval) clearInterval(cvJobsRefreshInterval)
  }
})

onMounted(() => {
  // Load initial tab data
  if (currentTab.value === 'system') {
    loadSystemHealth()
  } else if (currentTab.value === 'configuration') {
    loadConfiguration()
  } else if (currentTab.value === 'users') {
    loadUsers()
  }
})


onUnmounted(() => {
  if (cvJobsRefreshInterval) clearInterval(cvJobsRefreshInterval)
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
.status-badge.status-inactive,
.status-badge.status-failed {
  background: #ffebee;
  color: #c62828;
}

.status-badge.status-pending {
  background: #f5f5f5;
  color: #616161;
}

.status-badge.status-processing,
.status-badge.status-parsing_document {
  background: #e3f2fd;
  color: #1976d2;
}

.status-badge.status-completed {
  background: #e8f5e9;
  color: #2e7d32;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.data-table th,
.data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  font-weight: 600;
  color: #666;
  background: #f8f9fa;
}

.filters-bar {
  margin-bottom: 1rem;
  display: flex;
  gap: 1rem;
}

.pagination {
  margin-top: 1.5rem;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
}

.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

/* Info Banner */
.info-banner {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-left: 4px solid #2196f3;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.info-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.info-content {
  flex: 1;
}

.info-content strong {
  display: block;
  margin-bottom: 0.5rem;
  color: #1976d2;
  font-size: 1.1rem;
}

.info-content p {
  margin: 0 0 0.75rem 0;
  color: #555;
  line-height: 1.5;
}

.btn-link {
  background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
  color: white;
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.btn-link:hover {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  transform: translateX(2px);
}

/* Configuration Tab Styles */
.config-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.config-actions {
  display: flex;
  gap: 0.5rem;
}

.config-filters {
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
  background: white;
  cursor: pointer;
  min-width: 200px;
}

.config-categories {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.config-category {
  background: white;
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  cursor: pointer;
  transition: all 0.3s;
}

.category-header:hover {
  background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
}

.category-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.category-icon {
  font-size: 1.5rem;
}

.category-title h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.setting-count {
  font-size: 0.875rem;
  opacity: 0.9;
}

.toggle-icon {
  font-size: 1.2rem;
}

.category-content {
  padding: 1rem;
  background: #f8f9fa;
}

.setting-item {
  background: white;
  padding: 1.25rem;
  margin-bottom: 0.75rem;
  border-radius: 6px;
  border: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.setting-info {
  flex: 1;
}

.setting-key-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.setting-key {
  background: #f0f0f0;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  color: #667eea;
  font-weight: 600;
}

.sensitive-badge {
  background: #fff3cd;
  color: #856404;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.scope-badge {
  background: #e8f5e9;
  color: #388e3c;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.setting-description {
  margin: 0;
  font-size: 0.875rem;
  color: #666;
  line-height: 1.5;
}

.setting-value {
  min-width: 300px;
}

.setting-display {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.setting-current-value {
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.value-true {
  color: #388e3c;
}

.value-false {
  color: #666;
}

.setting-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.2s;
}

.btn-icon:hover {
  background: #f0f0f0;
}

.setting-edit {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.edit-control {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.edit-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #667eea;
  border-radius: 4px;
  font-size: 0.875rem;
}

.edit-select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #667eea;
  border-radius: 4px;
  font-size: 0.875rem;
  background: white;
  cursor: pointer;
}

.edit-url {
  font-family: monospace;
}

/* Toggle Switch */
.toggle-switch {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.toggle-switch input {
  display: none;
}

.toggle-slider {
  position: relative;
  width: 48px;
  height: 24px;
  background: #ccc;
  border-radius: 24px;
  transition: background 0.3s;
}

.toggle-slider::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
}

.toggle-switch input:checked + .toggle-slider::after {
  transform: translateX(24px);
}

.toggle-label {
  font-weight: 500;
  color: #333;
}

/* Color Picker */
.color-picker-control {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.color-picker {
  width: 48px;
  height: 36px;
  padding: 0;
  border: 2px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  background: none;
}

.color-picker::-webkit-color-swatch-wrapper {
  padding: 2px;
}

.color-picker::-webkit-color-swatch {
  border-radius: 4px;
  border: none;
}

.color-text {
  width: 100px !important;
  font-family: monospace;
  text-transform: uppercase;
}

/* Range Slider */
.range-control {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.range-slider {
  flex: 1;
  height: 6px;
  -webkit-appearance: none;
  background: linear-gradient(to right, #667eea, #764ba2);
  border-radius: 3px;
  outline: none;
}

.range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  background: white;
  border: 2px solid #667eea;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.range-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: white;
  border: 2px solid #667eea;
  border-radius: 50%;
  cursor: pointer;
}

.range-value {
  width: 60px !important;
  text-align: center;
}

.range-unit {
  font-weight: 500;
  color: #666;
}


.edit-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-save {
  background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
  color: white;
}

.btn-save:hover {
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
}

.btn-cancel {
  background: #6c757d;
  color: white;
}

.btn-cancel:hover {
  background: #5a6268;
}

.modal-large {
  max-width: 700px;
}

.history-list {
  max-height: 400px;
  overflow-y: auto;
  margin: 1rem 0;
}

.history-item {
  background: #f8f9fa;
  padding: 1rem;
  margin-bottom: 0.75rem;
  border-radius: 6px;
  border-left: 3px solid #667eea;
}

.history-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
}

.history-date {
  font-weight: 600;
  color: #667eea;
}

.history-user {
  color: #666;
}

.history-changes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.history-value {
  font-size: 0.875rem;
}

.history-value code {
  background: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

.no-history {
  text-align: center;
  padding: 2rem;
  color: #999;
}

.import-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  resize: vertical;
}
</style>
