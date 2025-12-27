<template>
  <div class="tab-content">
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
    <div v-if="configError" class="error">{{ configError }}</div>
    <div v-if="configSuccess" class="success">{{ configSuccess }}</div>
    
    <!-- Configuration Categories -->
    <div v-if="!loadingConfig" class="config-categories">
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
import { ref, computed, onMounted } from 'vue'
import { adminAPI } from '../../services/api'

// Configuration Management state
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

onMounted(() => {
  loadConfiguration()
})
</script>

<style scoped>
/* Replicating or sharing styles */
.tab-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.config-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.config-actions { display: flex; gap: 0.5rem; }
.btn-secondary { background: #6c757d; color: white; padding: 0.75rem 2rem; border-radius: 6px; border: none; cursor: pointer; }
.config-filters { display: flex; gap: 1rem; margin-bottom: 2rem; }
.search-input, .filter-select { flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; }
.filter-select { background: white; min-width: 200px; }

.config-categories { display: flex; flex-direction: column; gap: 1rem; }
.config-category { background: white; border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
.category-header {
  display: flex; justify-content: space-between; align-items: center; padding: 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white; cursor: pointer; transition: all 0.3s;
}
.category-header:hover { background: linear-gradient(135deg, #5568d3 0%, #65408b 100%); }
.category-title { display: flex; align-items: center; gap: 0.75rem; }
.category-icon { font-size: 1.5rem; }
.category-title h3 { margin: 0; font-size: 1.1rem; font-weight: 600; }
.setting-count { font-size: 0.875rem; opacity: 0.9; }
.toggle-icon { font-size: 1.2rem; }
.category-content { padding: 1rem; background: #f8f9fa; }

.setting-item {
  background: white; padding: 1.25rem; margin-bottom: 0.75rem; border-radius: 6px; border: 1px solid #eee;
  display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;
}
.setting-info { flex: 1; }
.setting-key-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
.setting-key {
  background: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 4px;
  font-family: 'Courier New', monospace; font-size: 0.875rem; color: #667eea; font-weight: 600;
}
.sensitive-badge, .scope-badge {
  padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500;
}
.sensitive-badge { background: #fff3cd; color: #856404; }
.scope-badge { background: #e8f5e9; color: #388e3c; }
.setting-description { margin: 0; font-size: 0.875rem; color: #666; line-height: 1.5; }

.setting-value { min-width: 300px; }
.edit-control { margin-bottom: 0.5rem; }
.edit-input, .edit-select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; border-radius: 4px; border: none; cursor: pointer; margin-left: 0.5rem; }
.btn-save { background: #388e3c; color: white; }
.btn-cancel { background: #c62828; color: white; }
.btn-icon { background: none; border: none; font-size: 1.2rem; cursor: pointer; }

.toggle-switch { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.toggle-switch input { display: none; }
.toggle-slider {
  width: 40px; height: 20px; background: #ccc; border-radius: 20px; position: relative; transition: .3s;
}
.toggle-switch input:checked + .toggle-slider { background: #667eea; }
.toggle-slider:before {
  content: ''; position: absolute; width: 16px; height: 16px; left: 2px; bottom: 2px;
  background: white; border-radius: 50%; transition: .3s;
}
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }

.setting-display { display: flex; align-items: center; justify-content: space-between; }
.value-true { color: #388e3c; font-weight: bold; }
.value-false { color: #c62828; font-weight: bold; }

.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal-content { background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; }
.modal-large { max-width: 800px; }
.import-textarea { width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 6px; font-family: monospace; }
.modal-actions { display: flex; gap: 1rem; margin-top: 2rem; }
.btn-primary { background: #667eea; color: white; padding: 0.75rem 2rem; border-radius: 6px; border: none; cursor: pointer; }

.history-list { max-height: 400px; overflow-y: auto; }
.history-item { padding: 1rem; border-bottom: 1px solid #eee; }
.history-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: #666; }
.history-changes { background: #f8f9fa; padding: 0.5rem; border-radius: 4px; }
.history-value code { background: white; padding: 0.2rem 0.4rem; border-radius: 4px; border: 1px solid #eee; }
.color-picker-control { display: flex; gap: 0.5rem; }
.color-picker { width: 40px; height: 40px; padding: 0; border: none; border-radius: 4px; cursor: pointer; }
.range-control { display: flex; align-items: center; gap: 0.5rem; }
.range-slider { flex: 1; }
.range-value { width: 60px; }
.loading { text-align: center; padding: 3rem; color: #666; }
.error { background: #fee; color: #c33; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }
.success { background: #e8f5e9; color: #388e3c; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }
</style>
