<template>
  <div class="config-container">
    <div class="config-header-bar">
      <h2>Configuration Management</h2>
      
      <!-- Search Bar -->
      <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input 
          v-model="configSearch" 
          type="text" 
          placeholder="Search all settings..." 
          class="search-input"
        />
        <button v-if="configSearch" @click="configSearch = ''" class="clear-search">✕</button>
      </div>

      <div class="config-actions">
        <button @click="exportConfiguration" class="btn-secondary" title="Export Configuration">
          📥 Export
        </button>
        <button @click="showImportModal = true" class="btn-secondary" title="Import Configuration">
          📤 Import
        </button>
        <button @click="loadConfiguration" class="btn-secondary" title="Refresh">
          🔄 Refresh
        </button>
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loadingConfig" class="state-message loading">
      <div class="spinner"></div>
      Loading configuration...
    </div>
    <div v-else-if="configError" class="state-message error">{{ configError }}</div>
    
    <!-- Main Content Area -->
    <div v-else class="config-layout">
      <!-- Sidebar Categories -->
      <div class="config-sidebar" role="tablist">
        <button 
          v-for="category in availableCategories" 
          :key="category.name"
          class="category-tab"
          :class="{ active: activeCategory === category.name && !configSearch }"
          @click="selectCategory(category.name)"
          role="tab"
          :aria-selected="activeCategory === category.name && !configSearch"
        >
          <span class="category-icon" aria-hidden="true">{{ category.icon }}</span>
          <span class="category-label">{{ category.label }}</span>
          <span class="category-count">{{ category.count }}</span>
        </button>
      </div>

      <!-- Settings Content -->
      <div class="config-content" role="tabpanel">
        
        <!-- Search Results View -->
        <div v-if="configSearch" class="search-results-view">
          <div class="view-header">
            <h3>Search Results for "{{ configSearch }}"</h3>
            <span class="result-count">{{ searchResults.length }} settings found</span>
          </div>

          <div v-if="searchResults.length === 0" class="no-results">
            No settings found matching your search.
          </div>

          <div class="settings-grid">
            <div 
              v-for="setting in searchResults" 
              :key="setting.key" 
              class="setting-card"
            >
              <!-- Reusable Setting Card Content -->
              <SettingCardContent 
                :setting="setting"
                :editingSetting="editingSetting"
                :editValue="editValue"
                :showSensitive="showSensitive"
                @startEdit="startEdit"
                @save="saveSetting"
                @cancel="cancelEdit"
                @updateEditValue="(val) => editValue = val"
                @toggleSensitive="toggleSensitive"
                @viewHistory="viewHistory"
              />
            </div>
          </div>
        </div>

        <!-- Category View -->
        <div v-else class="category-view">
          <div class="view-header">
            <div class="header-left">
              <span class="header-icon">{{ currentCategoryData?.icon }}</span>
              <h3>{{ currentCategoryData?.label }}</h3>
            </div>
            <p class="header-desc">
              Manage settings for {{ currentCategoryData?.label?.toLowerCase() }} module.
            </p>
          </div>

          <div class="settings-grid">
            <div 
              v-for="setting in currentCategorySettings" 
              :key="setting.key" 
              class="setting-card"
            >
              <!-- Reusable Setting Card Content -->
              <SettingCardContent 
                :setting="setting"
                :editingSetting="editingSetting"
                :editValue="editValue"
                :showSensitive="showSensitive"
                @startEdit="startEdit"
                @save="saveSetting"
                @cancel="cancelEdit"
                @updateEditValue="(val) => editValue = val"
                @toggleSensitive="toggleSensitive"
                @viewHistory="viewHistory"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Message Toast -->
    <Transition name="fade">
      <div v-if="configSuccess" class="toast-success">{{ configSuccess }}</div>
    </Transition>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
      <div class="modal-content modal-large" @click.stop>
        <h3>Change History: {{ historyData?.setting?.key }}</h3>
        <div v-if="historyData?.history && historyData.history.length > 0" class="history-list">
          <div v-for="(change, index) in historyData.history" :key="index" class="history-item">
            <div class="history-header-row">
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
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import { adminAPI } from '../../services/api'

// --- Internal Component for Setting Card Content to reduce template duplication ---
const SettingCardContent = defineComponent({
  props: ['setting', 'editingSetting', 'editValue', 'showSensitive'],
  emits: ['startEdit', 'save', 'cancel', 'updateEditValue', 'toggleSensitive', 'viewHistory'],
  setup(props, { emit }) {
    return () => {
      const { setting, editingSetting, editValue, showSensitive } = props
      const isEditing = editingSetting === setting.key
      
      // Helper to emit update
      const onInput = (val) => emit('updateEditValue', val)

      // Edit Mode
      if (isEditing) {
        return h('div', { class: 'setting-edit-mode' }, [
          h('div', { class: 'setting-meta-edit' }, [
             h('code', { class: 'setting-key' }, setting.key),
             h('p', { class: 'setting-desc' }, setting.description)
          ]),
          h('div', { class: 'edit-controls' }, [
             renderInputControl(setting, editValue, onInput),
             h('div', { class: 'edit-actions' }, [
               h('button', { class: 'btn-sm btn-save', onClick: () => emit('save', setting) }, '✓ Save'),
               h('button', { class: 'btn-sm btn-cancel', onClick: () => emit('cancel') }, 'Cancel')
             ])
          ])
        ])
      }

      // View Mode
      return h('div', { class: 'setting-view-mode' }, [
        // Row 1: Key and Actions
        h('div', { class: 'setting-header' }, [
            h('code', { class: 'setting-key' }, setting.key),
            h('div', { class: 'action-row' }, [
              h('button', { class: 'btn-icon', title: 'Edit', onClick: () => emit('startEdit', setting) }, '✏️'),
              h('button', { class: 'btn-icon', title: 'History', onClick: () => emit('viewHistory', setting.key) }, '📜')
            ])
        ]),
        
        // Row 2: Badges (Meta)
        (setting.is_sensitive || (setting.service_scope && setting.service_scope !== 'all')) 
          ? h('div', { class: 'setting-meta-row' }, [
              setting.is_sensitive ? h('span', { class: 'badge badge-sensitive' }, 'Sensitive') : null,
              setting.service_scope && setting.service_scope !== 'all' ? h('span', { class: 'badge badge-scope' }, setting.service_scope) : null
            ]) 
          : null,

        // Row 3: Description
        h('p', { class: 'setting-desc' }, setting.description),

        // Row 4: Value
        h('div', { class: 'setting-value-display' }, [
           renderValueDisplay(setting, showSensitive, (k) => emit('toggleSensitive', k))
        ])
      ])
    }
  }
})

// Helper functions for the functional component (simplified logic)
function renderInputControl(setting, value, onInput) {
  // Boolean
  if (setting.type === 'boolean') {
    return h('label', { class: 'toggle-switch' }, [
      h('input', { type: 'checkbox', checked: value, onChange: (e) => onInput(e.target.checked) }),
      h('span', { class: 'toggle-slider' }),
      h('span', { class: 'toggle-label' }, value ? 'Enabled' : 'Disabled')
    ])
  }
  // Color
  if (setting.key.includes('color')) {
    return h('div', { class: 'color-input-group' }, [
      h('input', { type: 'color', value: value, onInput: (e) => onInput(e.target.value) }),
      h('input', { type: 'text', value: value, onInput: (e) => onInput(e.target.value), class: 'text-input' })
    ])
  }
  // Selects
  if (setting.key === 'ai.provider') {
     return renderSelect(value, onInput, [
       { val: 'ollama', label: 'Ollama (Local)' },
       { val: 'openrouter', label: 'OpenRouter (Cloud)' }
     ])
  }
  if (setting.key === 'ui.date_format') {
      return renderSelect(value, onInput, [
       { val: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
       { val: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
       { val: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
       { val: 'DD MMM YYYY', label: 'DD MMM YYYY' },
       { val: 'MMM DD, YYYY', label: 'MMM DD, YYYY' }
     ])
  }
   if (setting.key === 'ui.time_format') {
      return renderSelect(value, onInput, [
       { val: 'HH:mm', label: '24-hour' },
       { val: 'hh:mm A', label: '12-hour' }
     ])
  }
  // Range
  if (setting.type === 'integer' && (setting.key.includes('threshold') || setting.key.includes('score'))) {
    return h('div', { class: 'range-group' }, [
      h('input', { type: 'range', min: 0, max: 100, value: value, onInput: (e) => onInput(parseInt(e.target.value)) }),
      h('span', { class: 'range-val' }, value + '%')
    ])
  }
  
  // Default Input
  return h('input', { 
    type: setting.type === 'integer' ? 'number' : 'text',
    value: value,
    onInput: (e) => onInput(setting.type === 'integer' ? parseInt(e.target.value) : e.target.value),
    class: 'text-input full-width'
  })
}

function renderSelect(value, onInput, options) {
  return h('select', { value, onChange: (e) => onInput(e.target.value), class: 'select-input' }, 
    options.map(o => h('option', { value: o.val }, o.label))
  )
}

function renderValueDisplay(setting, showSensitive, toggle) {
  if (setting.type === 'boolean') {
    return h('span', { class: setting.value ? 'val-true' : 'val-false' }, setting.value ? '✓ Enabled' : '✗ Disabled')
  }
  if (setting.is_sensitive) {
    const visible = showSensitive[setting.key]
    return h('div', { class: 'sensitive-row' }, [
      h('span', { class: 'val-text' }, visible ? (setting.value || '(empty)') : '••••••••'),
      h('button', { class: 'btn-icon-sm', onClick: () => toggle(setting.key) }, visible ? '👁️' : '👁️‍🗨️')
    ])
  }
  if (setting.key.includes('color')) {
      return h('div', { class: 'color-preview-row' }, [
          h('div', { class: 'color-dot', style: { backgroundColor: setting.value } }),
          h('span', { class: 'val-text' }, setting.value)
      ])
  }
  return h('span', { class: 'val-text' }, setting.value || '(empty)')
}


// --- Main Layout Script ---
const loadingConfig = ref(false)
const configError = ref('')
const configSuccess = ref('')
const configSettings = ref([])
const configSearch = ref('')
const activeCategory = ref('system')
const editingSetting = ref(null)
const editValue = ref(null)
const showSensitive = ref({})
const showImportModal = ref(false)
const showHistoryModal = ref(false)
const historyData = ref(null)
const importData = ref('')

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
  } catch (err) {
    configError.value = 'Failed to load configuration settings'
  } finally {
    loadingConfig.value = false
  }
}

// Computed: List of Categories with counts
const availableCategories = computed(() => {
  const counts = {}
  configSettings.value.forEach(s => {
    counts[s.category] = (counts[s.category] || 0) + 1
  })
  
  return Object.keys(categoryConfig).map(key => ({
    name: key,
    label: categoryConfig[key].label,
    icon: categoryConfig[key].icon,
    count: counts[key] || 0
  })).filter(c => c.count > 0) // Only show categories with settings
})

// Computed: Current Category Data
const currentCategoryData = computed(() => {
  return availableCategories.value.find(c => c.name === activeCategory.value) || {}
})

// Computed: Settings for Current Category
const currentCategorySettings = computed(() => {
  return configSettings.value.filter(s => s.category === activeCategory.value)
})

// Computed: Global Search Results
const searchResults = computed(() => {
  if (!configSearch.value) return []
  const query = configSearch.value.toLowerCase()
  return configSettings.value.filter(s => 
    s.key.toLowerCase().includes(query) ||
    (s.description && s.description.toLowerCase().includes(query))
  )
})

const selectCategory = (cat) => {
  activeCategory.value = cat
  configSearch.value = '' // Clear search on category switch
}

const startEdit = (setting) => {
  editingSetting.value = setting.key
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
    configSuccess.value = `Updated ${setting.key}`
    
    // Update local state
    const idx = configSettings.value.findIndex(s => s.key === setting.key)
    if (idx !== -1) configSettings.value[idx].value = editValue.value
    
    editingSetting.value = null
    setTimeout(() => configSuccess.value = '', 3000)
  } catch (err) {
    configError.value = `Failed to save ${setting.key}`
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
    configError.value = `Failed to import`
  }
}

const formatDate = (date) => new Date(date).toLocaleDateString()

onMounted(() => {
  loadConfiguration()
})
</script>

<style>
/* Main Container */
.config-container {
  background: transparent;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 120px); /* Adjust based on header/padding */
  gap: 1.5rem;
}

/* Header Bar */
.config-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  flex-shrink: 0;
}

.config-header-bar h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #2c3e50;
  white-space: nowrap;
  margin-right: 1.5rem;
}

/* Search */
.search-wrapper {
  flex: 1;
  max-width: 400px;
  position: relative;
  display: flex;
  align-items: center;
}
.search-input {
  width: 100%;
  padding: 0.6rem 1rem 0.6rem 2.2rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: #f8f9fa;
  transition: all 0.2s;
}
.search-input:focus {
  background: white;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}
.search-icon {
  position: absolute;
  left: 0.75rem;
  opacity: 0.5;
  font-size: 0.9rem;
}
.clear-search {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  font-size: 0.9rem;
}

/* Actions */
.config-actions {
  display: flex;
  gap: 0.75rem;
  margin-left: 1.5rem;
}
.btn-secondary {
  background: white;
  border: 1px solid #e0e0e0;
  color: #444;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-secondary:hover {
  background: #f8f9fa;
  border-color: #ccc;
  transform: translateY(-1px);
}

/* Layout Grid */
.config-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.5rem;
  flex: 1;
  min-height: 0; /* Important for scroll */
  overflow: hidden;
}

/* Sidebar */
.config-sidebar {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  padding: 1rem 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.category-tab {
  display: flex;
  align-items: center;
  padding: 0.85rem 1.25rem;
  background: none;
  border: none;
  border-left: 3px solid transparent;
  cursor: pointer;
  text-align: left;
  color: #555;
  transition: all 0.2s;
  width: 100%;
}
.category-tab:hover {
  background: #f8f9fa;
  color: #2c3e50;
}
.category-tab.active {
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.08) 0%, transparent 100%);
  border-left-color: #667eea;
  color: #667eea;
  font-weight: 600;
}
.category-icon {
  margin-right: 0.8rem;
  font-size: 1.1rem;
}
.category-label {
  flex: 1;
}
.category-count {
  background: #eee;
  padding: 0.15rem 0.5rem;
  border-radius: 10px;
  font-size: 0.75rem;
  color: #777;
}
.category-tab.active .category-count {
  background: #667eea;
  color: white;
}

/* Content Area */
.config-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.category-view, .search-results-view {
  padding: 2rem;
}
.view-header {
  margin-bottom: 2rem;
  border-bottom: 1px solid #eee;
  padding-bottom: 1rem;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}
.header-icon {
  font-size: 2rem;
}
.view-header h3 {
  margin: 0;
  font-size: 1.5rem;
  color: #2c3e50;
}
.header-desc {
  margin: 0;
  color: #777;
}
.no-results {
  text-align: center;
  padding: 3rem;
  color: #888;
  font-style: italic;
}

/* Settings Grid */
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}
.setting-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  transition: all 0.2s;
  height: 100%;
  display: flex;
  flex-direction: column;
  position: relative; /* For absolute actions */
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.setting-card:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Elevated shadow */
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

/* Card Content: View Mode */
.setting-header {
  margin-bottom: 0.75rem;
  padding-right: 3rem; /* Space for absolute actions */
}

.setting-meta-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.setting-key {
  font-family: 'Courier New', monospace;
  font-size: 0.95rem;
  font-weight: 700;
  color: #2d3748; 
  background: #edf2f7;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  display: inline-block;
}

.badge {
  font-size: 0.7rem;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
}
.badge-sensitive { background: #fff3cd; color: #b7791f; border: 1px solid #f6e05e; }
.badge-scope { background: #c6f6d5; color: #2f855a; border: 1px solid #9ae6b4; }

.action-row {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  gap: 0.5rem;
  background: white;
  padding-left: 0.5rem;
}
.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  font-size: 1rem;
}
.setting-desc {
  font-size: 0.85rem;
  color: #718096; /* Lighter gray */
  margin: 0 0 1rem 0;
  flex: 1; 
  line-height: 1.5;
  font-style: italic; /* Differentiate styling */
}
.setting-value-display {
  margin-top: auto;
  padding: 0.75rem;
  background: linear-gradient(to right, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.05)); /* Accent background */
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-left: 4px solid #667eea; /* Primary accent color */
  border-radius: 4px; /* Slightly sharper to match border-left */
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.val-true { color: #388e3c; }
.val-false { color: #c62828; }
.sensitive-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.val-text {
  font-family: 'Courier New', monospace;
  font-size: 0.8rem;
  font-weight: 700;
  color: #2d3748;
  letter-spacing: -0.5px;
}
  
/* Card Content: Edit Mode */
.setting-edit-mode {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.setting-meta-edit {
  margin-bottom: 1rem;
}
.edit-controls {
  margin-top: auto;
}
.text-input, .select-input {
  width: 100%;
  padding: 0.6rem;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}
.text-input:focus, .select-input:focus {
  border-color: #667eea;
  outline: none;
}
.toggle-switch {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}
.toggle-slider {
  width: 44px;
  height: 24px;
  background: #cbd5e0;
  border-radius: 24px;
  position: relative;
  transition: .3s;
}
.toggle-switch input:checked + .toggle-slider {
  background: #667eea;
}
.toggle-slider:before {
  content: '';
  position: absolute;
  left: 2px;
  bottom: 2px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  transition: .3s;
}
.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(20px);
}
.edit-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}
.btn-sm {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
}
.btn-save { background: #388e3c; color: white; }
.btn-cancel { background: #f2f2f2; color: #555; }

/* Utilities */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
.modal-large { max-width: 800px; }
.btn-primary { background: #667eea; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; }
.toast-success {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: #388e3c;
  color: white;
  padding: 1rem 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 2000;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.color-input-group { display: flex; gap: 0.5rem; align-items: center; }
.color-dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); }
.color-preview-row { display: flex; align-items: center; gap: 0.5rem; }
.range-group { display: flex; align-items: center; gap: 1rem; }
.range-val { font-weight: bold; width: 40px; }
</style>
