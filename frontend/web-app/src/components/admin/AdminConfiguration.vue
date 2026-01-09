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
        <div v-for="category in availableCategories" :key="category.name" class="category-wrapper">
            <button 
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
            
            <!-- Submenu for Anchors -->
            <div v-if="activeCategory === category.name && Object.keys(groupedSettings).length > 1" class="category-submenu">
                 <button 
                    v-for="(settings, groupName) in groupedSettings" 
                    :key="groupName"
                    class="submenu-item"
                    @click.stop="scrollToGroup(groupName)"
                >
                    {{ groupName }}
                </button>
            </div>
        </div>
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
              <SettingCard 
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

          <div v-for="(settings, groupName) in groupedSettings" :key="groupName" class="settings-group-wrapper">
            <h4 
                v-if="Object.keys(groupedSettings).length > 1" 
                class="group-header"
                :id="'group-' + groupName.replace(/\s+/g, '-').toLowerCase()"
            >{{ groupName }}</h4>
            <div class="settings-grid">
              <div 
                v-for="setting in settings" 
                :key="setting.key" 
                class="setting-card"
              >
                <!-- Reusable Setting Card Content -->
                <SettingCard 
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
    </div>

    <!-- Success Message Toast -->
    <Transition name="fade">
      <div v-if="configSuccess" class="toast-success">{{ configSuccess }}</div>
    </Transition>

    <!-- History Modal -->
    <SettingHistoryModal 
      :show="showHistoryModal"
      :historyData="historyData"
      @close="showHistoryModal = false"
    />

    <!-- Import Modal -->
    <ConfigImportModal 
      :show="showImportModal"
      @close="showImportModal = false"
      @import="handleImportData"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { adminAPI } from '../../services/api'
import { useThemeStore } from '../../stores/useThemeStore'
import SettingCard from './SettingCard.vue'
import SettingHistoryModal from './SettingHistoryModal.vue'
import ConfigImportModal from './ConfigImportModal.vue'

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
const themeStore = useThemeStore()

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

// Computed: Settings for Current Category (Grouped)
const groupedSettings = computed(() => {
  const settings = configSettings.value.filter(s => s.category === activeCategory.value)
  const groups = { 'General': [] }

  settings.forEach(setting => {
    const parts = setting.key.split('.')
    // Assuming format: category.group.key or just category.key
    // If parts.length > 2, grouping is the second part.
    // E.g. ai.generation.timeout -> group 'Generation'
    // E.g. ai.provider -> group 'General'
    
    if (parts.length > 2) {
      // Capitalize first letter
      const groupName = parts[1].charAt(0).toUpperCase() + parts[1].slice(1).replace(/_/g, ' ')
      
      if (!groups[groupName]) {
        groups[groupName] = []
      }
      groups[groupName].push(setting)
    } else {
      groups['General'].push(setting)
    }
  })

  // Filter out empty General group if not needed, or just return as is
  if (groups['General'].length === 0) {
    delete groups['General']
  }
  
  return groups
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
  } else if (setting.type === 'json') {
    try {
      editValue.value = JSON.parse(setting.value)
    } catch (e) {
      editValue.value = setting.value // Fallback to raw string
    }
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
  
  let valToSave = editValue.value
  if (setting.type === 'json' && typeof editValue.value === 'object') {
    valToSave = JSON.stringify(editValue.value)
  }

  try {
    await adminAPI.updateSetting(setting.key, valToSave)
    configSuccess.value = `Updated ${setting.key}`
    
    // Update local state
    const idx = configSettings.value.findIndex(s => s.key === setting.key)
    if (idx !== -1) configSettings.value[idx].value = valToSave
    
    // Refresh theme if UI setting
    if (setting.category === 'ui') {
        await themeStore.initializeTheme()
    }
    
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

const scrollToGroup = (groupName) => {
    const id = 'group-' + groupName.replace(/\s+/g, '-').toLowerCase()
    const element = document.getElementById(id)
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' })
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

// Handler for ConfigImportModal
const handleImportData = async (jsonString) => {
  try {
    const data = JSON.parse(jsonString)
    await adminAPI.importSettings(data)
    configSuccess.value = 'Configuration imported successfully'
    showImportModal.value = false
    loadConfiguration()
    setTimeout(() => configSuccess.value = '', 3000)
  } catch (err) {
    configError.value = 'Failed to import configuration'
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
  background: #667eea;
  color: white;
}
.category-submenu {
    padding: 0.25rem 0 0.5rem 2.8rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.submenu-item {
    background: none;
    border: none;
    text-align: left;
    font-size: 0.85rem;
    color: #4a5568;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
}
.submenu-item:hover {
    color: #667eea;
    background: #edf2f7;
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
  background: transparent;
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

/* Checkbox Group */
.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.5rem;
}
.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  background: #f8f9fa;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}
.checkbox-item:hover {
  background: #f0f2f5;
  border-color: #d0d0d0;
}
.checkbox-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #444;
}

/* Pipeline List */
.pipeline-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}
.pipeline-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}
.pipeline-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}
.pipeline-controls {
  display: flex;
  gap: 0.25rem;
}
.btn-icon-sm {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.2rem;
  font-size: 1rem;
  opacity: 0.6;
  transition: opacity 0.2s;
}
.btn-icon-sm:hover:not(:disabled) {
  opacity: 1;
  background: #eee;
  border-radius: 4px;
}
.btn-icon-sm:disabled {
  opacity: 0.2;
  cursor: not-allowed;
}

/* Configuration Groups */
.settings-group-wrapper {
  margin-bottom: 2rem;
}
.group-header {
  font-size: 1.1rem;
  color: #4a5568;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #edf2f7;
  font-weight: 600;
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
