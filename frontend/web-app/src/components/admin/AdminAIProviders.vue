<template>
  <div class="tab-content">
    <div class="ai-header">
      <h2>AI Providers & Failover</h2>
      <button @click="loadProviders" class="btn-secondary">🔄 Refresh</button>
    </div>

    <div v-if="loading" class="loading">Loading providers...</div>
    <div v-if="error" class="error">{{ error }}</div>
    <div v-if="success" class="success">{{ success }}</div>

    <!-- Provider Instances Section -->
    <div class="section">
      <div class="section-header">
        <h3>Provider Instances</h3>
        <button @click="showAddModal = true" class="btn-add-instance">+ Add Instance</button>
      </div>
      <p class="section-desc">Configure multiple instances of the same provider type (e.g., multiple Ollama servers).</p>
      
      <div class="providers-grid">
        <div v-for="provider in providers" :key="provider.name" 
             class="provider-card" :class="{ available: provider.available }">
          <div class="provider-header">
            <span class="provider-icon">{{ providerIcons[provider.type] || providerIcons[provider.name] || '🤖' }}</span>
            <span class="provider-name">{{ provider.displayName }}</span>
            <span v-if="provider.hasApiKey" class="key-badge" title="API Key Configured">🔑</span>
          </div>
          <div class="provider-status">
            <span :class="provider.available ? 'status-ok' : 'status-error'">
              {{ provider.available ? '✓ Available' : '✗ Unavailable' }}
            </span>
          </div>
          <div class="provider-model">
            Model: {{ provider.defaultModel }}
          </div>
          <button @click="openEditModal(provider)" class="btn-sm btn-edit-card">Edit</button>
        </div>
      </div>
    </div>

    <!-- Custom Instances -->
    <div v-if="instances.length > 0" class="section">
      <h3>Custom Instances</h3>
      <div class="instances-list">
        <div v-for="instance in instances" :key="instance.id" class="instance-item">
          <div class="instance-info">
            <span class="instance-name">{{ instance.name }}</span>
            <span class="instance-type">{{ instance.type }}</span>
            <span v-if="instance.hasApiKey" class="key-badge-sm" title="API Key Configured">🔑</span>
            <span class="instance-url">{{ instance.baseUrl }}</span>
          </div>
          <button @click="openEditModal(instance)" class="btn-icon btn-edit" title="Edit">✏️</button>
          <button @click="deleteInstance(instance.id)" class="btn-icon btn-delete" title="Delete">🗑️</button>
        </div>
      </div>
    </div>

    <!-- Failover Chain Configuration -->
    <div class="section">
      <h3>Failover Chains by Service</h3>
      <p class="section-desc">Configure which providers to use for each AI service. Lower priority = tried first.</p>
      
      <div class="chains-container">
        <div v-for="(chain, serviceType) in serviceChains" :key="serviceType" class="chain-card">
          <div class="chain-header">
            <span class="chain-icon">{{ serviceIcons[serviceType] || '⚡' }}</span>
            <span class="chain-name">{{ formatServiceType(serviceType) }}</span>
          </div>
          
          <div class="chain-items">
            <div v-for="(item, index) in chain" :key="index" class="chain-item">
              <span class="priority-badge">{{ index + 1 }}</span>
              <select v-model="chain[index].provider" class="provider-select">
                <option v-for="p in providers" :key="p.name" :value="p.name">
                  {{ p.displayName }}
                </option>
              </select>
              <input v-model="chain[index].model" type="text" placeholder="Model (optional)" class="model-input" />
              <button @click="removeFromChain(serviceType, index)" class="btn-icon" title="Remove">🗑️</button>
            </div>
          </div>
          
          <div class="chain-actions">
            <button @click="addToChain(serviceType)" class="btn-sm btn-add">+ Add Fallback</button>
          </div>
        </div>
      </div>

      <div class="save-section">
        <button @click="saveChains" class="btn-primary" :disabled="saving">
          {{ saving ? 'Saving...' : '💾 Save Failover Configuration' }}
        </button>
      </div>
    </div>

    <!-- Add Instance Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h3>{{ isEditing ? 'Edit Provider Instance' : 'Add Provider Instance' }}</h3>
        <div class="form-group">
          <label>Instance Name</label>
          <input v-model="newInstance.name" type="text" placeholder="e.g., ollama-gpu-server" :disabled="isEditing && (newInstance.isDefault || newInstance.id)" />
          <small v-if="isEditing && newInstance.isDefault" class="text-muted">Cannot change name of default provider</small>
        </div>
        <div class="form-group">
          <label>Display Name</label>
          <input v-model="newInstance.display_name" type="text" placeholder="e.g., GPU Ollama Server" />
        </div>
        <div class="form-group">
          <label>Provider Type</label>
          <select v-model="newInstance.type" :disabled="isEditing && newInstance.isDefault">
            <option value="ollama">Ollama</option>
            <option value="openrouter">OpenRouter</option>
            <option value="openai">OpenAI</option>
            <option value="gemini">Google Gemini</option>
            <option value="azure">Azure OpenAI</option>
            <option value="litellm">LiteLLM</option>
            <option value="llamacpp">llama.cpp</option>
          </select>
        </div>
        <div class="form-group" v-if="['openai', 'openrouter', 'gemini', 'azure', 'litellm'].includes(newInstance.type)">
          <label>API Key <span v-if="isEditing && newInstance.hasApiKey" class="badge-success">✓ Configured</span></label>
          <input v-model="newInstance.api_key" type="password" :placeholder="isEditing && newInstance.hasApiKey ? 'Leave empty to keep current key' : 'sk-...'" />
        </div>
        <div class="form-group">
          <label>Base URL</label>
          <input v-model="newInstance.base_url" type="url" placeholder="http://192.168.88.120:11535" />
        </div>
        <div class="form-group">
          <label>Default Model</label>
          <div class="input-with-btn">
            <input v-model="newInstance.model" type="text" placeholder="mistral" list="available-models" />
            <button @click="fetchModels" class="btn-secondary btn-icon-text" :disabled="fetchingModels" title="Fetch available models">
              {{ fetchingModels ? '⏳' : '🔄 Fetch' }}
            </button>
          </div>
          <datalist id="available-models">
            <option v-for="m in availableModels" :key="m" :value="m" />
          </datalist>
        </div>
        <div class="modal-actions">
          <button @click="closeModal" class="btn-secondary">Cancel</button>
          <button @click="handleSave" class="btn-primary" :disabled="!newInstance.name">
            {{ isEditing ? 'Update Instance' : 'Add Instance' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const showAddModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const providers = ref([])
const instances = ref([])
const availableModels = ref([])
const fetchingModels = ref(false)

const newInstance = ref({
  name: '',
  display_name: '',
  type: 'ollama',
  base_url: '',
  model: '',
  api_key: '',
  hasApiKey: false
})

const providerIcons = {
  ollama: '🦙',
  openrouter: '🌐',
  openai: '🧠',
  gemini: '💎',
  azure: '☁️',
  litellm: '🔄',
  llamacpp: '🖥️'
}

const serviceIcons = {
  cv_parsing: '📄',
  matching: '🎯',
  jd_generation: '📝',
  questions: '❓',
  discussion: '💬',
}

const serviceChains = ref({
  cv_parsing: [{ provider: 'ollama', model: null }],
  matching: [{ provider: 'ollama', model: null }],
  jd_generation: [{ provider: 'ollama', model: null }],
  questions: [{ provider: 'ollama', model: null }],
  discussion: [{ provider: 'ollama', model: null }]
})

const formatServiceType = (type) => {
  return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const addToChain = (serviceType) => {
  serviceChains.value[serviceType].push({ provider: 'ollama', model: null })
}

const removeFromChain = (serviceType, index) => {
  if (serviceChains.value[serviceType].length > 1) {
    serviceChains.value[serviceType].splice(index, 1)
  }
}

const loadProviders = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await fetch('http://localhost:9080/api/providers')
    if (response.ok) {
      const data = await response.json()
      if (data.providers) providers.value = data.providers
      if (data.instances) instances.value = data.instances
      if (data.chains) serviceChains.value = data.chains
    }
  } catch (err) {
    console.log('Using default provider configuration', err)
  } finally {
    loading.value = false
  }
}


const openEditModal = (item) => {
  isEditing.value = true
  editingId.value = item.id || item.name // ID for custom, Name for default
  newInstance.value = {
    name: item.name,
    display_name: item.displayName || item.display_name, // Handle inconsistent naming
    type: item.type,
    base_url: item.baseUrl || item.base_url || '',
    model: item.defaultModel || item.model || '',
    api_key: '', // Don't show existing API keys for security, but allow overwrite
    hasApiKey: item.hasApiKey,
    isDefault: item.isDefault || false,
    id: item.id
  }
  showAddModal.value = true
}

const closeModal = () => {
  showAddModal.value = false
  isEditing.value = false
  editingId.value = null
  availableModels.value = []
  newInstance.value = { name: '', display_name: '', type: 'ollama', base_url: '', model: '', api_key: '' }
}

const handleSave = () => {
  if (isEditing.value) {
    updateInstance()
  } else {
    addInstance()
  }
}

const updateInstance = async () => {
  error.value = ''
  try {
    const response = await fetch(`http://localhost:9080/api/providers/${editingId.value}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...newInstance.value,
        config: { model: newInstance.value.model }
      })
    })
    
    if (response.ok) {
      success.value = 'Instance updated successfully!'
      closeModal()
      loadProviders()
      setTimeout(() => success.value = '', 3000)
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to update instance'
    }
  } catch (err) {
    error.value = 'Failed to update instance: ' + err.message
  }
}

const addInstance = async () => {
  error.value = ''
  try {
    const response = await fetch('http://localhost:9080/api/providers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...newInstance.value,
        config: { model: newInstance.value.model }
      })
    })
    if (response.ok) {
      success.value = 'Instance added successfully!'
      closeModal()
      loadProviders()
      setTimeout(() => success.value = '', 3000)
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to add instance'
    }
  } catch (err) {
    error.value = 'Failed to add instance: ' + err.message
  }
}

const deleteInstance = async (id) => {
  if (!confirm('Delete this provider instance?')) return
  try {
    const response = await fetch(`http://localhost:9080/api/providers/${id}`, {
      method: 'DELETE'
    })
    if (response.ok) {
      success.value = 'Instance deleted'
      loadProviders()
      setTimeout(() => success.value = '', 3000)
    }
  } catch (err) {
    error.value = 'Failed to delete instance'
  }
}

const fetchModels = async () => {
  fetchingModels.value = true
  availableModels.value = []
  try {
    const response = await fetch('http://localhost:9080/api/providers/models', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: newInstance.value.type,
        id: editingId.value,
        api_key: newInstance.value.api_key,
        base_url: newInstance.value.base_url
      })
    })

    if (response.ok) {
      const data = await response.json()
      availableModels.value = data.models || []
      if (availableModels.value.length === 0) {
        alert('No models found for this configuration.')
      }
    } else {
      const data = await response.json()
      alert('Failed to fetch models: ' + (data.error || 'Unknown error'))
    }
  } catch (err) {
    alert('Failed to fetch models: ' + err.message)
  } finally {
    fetchingModels.value = false
  }
}

const saveChains = async () => {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const response = await fetch('http://localhost:9080/api/providers/chains', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ chains: serviceChains.value })
    })
    if (response.ok) {
      success.value = 'Failover configuration saved!'
      setTimeout(() => success.value = '', 3000)
    }
  } catch (err) {
    error.value = 'Failed to save: ' + err.message
  } finally {
    saving.value = false
  }
}

onMounted(loadProviders)
</script>

<style scoped>
.tab-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.ai-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.section { margin-bottom: 2rem; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.section h3 { margin-bottom: 0.5rem; color: #333; }
.section-desc { color: #666; font-size: 0.9rem; margin-bottom: 1rem; }

/* Provider Grid */
.providers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}

.provider-card {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem;
  text-align: center;
  transition: all 0.3s;
}

.provider-card.available {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  border-color: #81c784;
}

.provider-header { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.provider-icon { font-size: 2rem; }
.provider-name { font-weight: 600; color: #333; }
.provider-status { margin: 0.5rem 0; }
.status-ok { color: #388e3c; font-weight: 500; }
.status-error { color: #999; }
.provider-model { font-size: 0.8rem; color: #666; }

/* Instances List */
.instances-list { display: flex; flex-direction: column; gap: 0.5rem; }
.instance-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}
.instance-info { display: flex; gap: 1rem; align-items: center; }
.instance-name { font-weight: 600; }
.instance-type { background: #667eea; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
.instance-url { color: #666; font-size: 0.875rem; }

/* Chain Cards */
.chains-container { display: grid; gap: 1rem; }
.chain-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; padding: 1.5rem; }
.chain-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.chain-icon { font-size: 1.5rem; }
.chain-name { font-weight: 600; font-size: 1.1rem; }
.chain-items { display: flex; flex-direction: column; gap: 0.75rem; }
.chain-item { display: flex; align-items: center; gap: 0.75rem; background: white; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd; }
.priority-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; }
.provider-select, .model-input { flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; }
.chain-actions { margin-top: 0.75rem; }
.save-section { margin-top: 2rem; text-align: center; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
.form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; }
.modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; justify-content: flex-end; }
.input-with-btn { display: flex; gap: 0.5rem; }
.input-with-btn input { flex: 1; }
.btn-icon-text { padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: center; min-width: 80px; }

/* Buttons */
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-secondary { background: #6c757d; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; border: none; cursor: pointer; }
.btn-add-instance { background: #28a745; color: white; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.875rem; }
.btn-add { background: #e9ecef; color: #333; border: 1px dashed #adb5bd; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.2rem; opacity: 0.6; }
.btn-icon:hover { opacity: 1; }
.btn-delete:hover { color: #c00; }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.875rem; border-radius: 4px; }
.btn-edit-card { background: #6c757d; color: white; display: block; margin: 0.5rem auto 0; border: none; cursor: pointer; }
.btn-edit:hover { opacity: 1; transform: scale(1.1); }
.text-muted { font-size: 0.8rem; color: #888; }

/* Status */
.loading { text-align: center; padding: 2rem; color: #666; }
.error { background: #fee; color: #c33; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }
.success { background: #e8f5e9; color: #388e3c; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }

.key-badge {
  font-size: 1rem;
  margin-left: 0.5rem;
  cursor: help;
}
.key-badge-sm {
  font-size: 0.9rem;
  margin-left: 0.5rem;
  cursor: help;
}
.badge-success {
  background: #e8f5e9;
  color: #388e3c;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
  border: 1px solid #c8e6c9;
}
</style>
