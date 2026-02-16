<template>
  <div class="tab-content">
    <div class="parsing-controls">
      <div class="parsing-tabs">
        <button 
          @click="currentSubTab = 'original'" 
          :class="{ active: currentSubTab === 'original' }"
        >Original Document</button>
        <button 
          @click="currentSubTab = 'text'" 
          :class="{ active: currentSubTab === 'text' }"
        >Extracted Text</button>
        <button 
          @click="currentSubTab = 'json'" 
          :class="{ active: currentSubTab === 'json' }"
        >Raw Data (JSON)</button>
      </div>
      <button @click="$emit('refresh')" class="btn-sm" :disabled="loading">
        🔄 Refresh
      </button>
    </div>

    <div v-if="loading" class="loading-state">
        Loading parsing details...
    </div>

    <div v-else-if="error" class="error-state">
        {{ error }}
    </div>

    <div v-else class="parsing-content">
        <!-- Original PDF -->
        <div v-if="currentSubTab === 'original'" class="sub-tab-content">
            <div v-if="parsingDetails?.file_path" class="pdf-container">
                <iframe :src="previewUrl || getCvViewUrl()" width="100%" height="800px"></iframe>
            </div>
            <div v-else class="no-data">
                No document file found.
            </div>
        </div>

        <!-- Extracted Text -->
        <div v-if="currentSubTab === 'text'" class="sub-tab-content">
            <div class="text-actions">
                <button @click="copyText(parsingDetails?.extracted_text)" class="btn-sm">Copy Text</button>
            </div>
            <div class="raw-text-viewer">
                <pre>{{ parsingDetails?.extracted_text || 'No extracted text available.' }}</pre>
            </div>
        </div>

        <!-- Raw JSON -->
        <div v-if="currentSubTab === 'json'" class="sub-tab-content">
             <div class="text-actions">
                <button @click="copyText(JSON.stringify(parsingDetails?.parsed_data, null, 2))" class="btn-sm">Copy JSON</button>
            </div>
            <div class="json-viewer">
                <pre><code>{{ JSON.stringify(parsingDetails?.parsed_data, null, 2) || 'No parsed data available.' }}</code></pre>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  parsingDetails: {
    type: Object,
    default: null
  },
  previewUrl: {
    type: String,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  backendUrl: {
    type: String,
    default: import.meta.env.VITE_API_GATEWAY_URL || 'http://localhost:8080' // Provide a default or pass from parent
  }
})

const emit = defineEmits(['refresh'])

const currentSubTab = ref('original')

const getCvViewUrl = () => {
    if (!props.parsingDetails?.file_path) return ''
    return `${props.backendUrl}/storage/${props.parsingDetails.file_path}`
}

const copyText = (text) => {
    if (!text) return
    navigator.clipboard.writeText(text)
    alert('Copied to clipboard!')
}
</script>

<style scoped>
.parsing-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  background: #f8f9fa;
  padding: 10px;
  border-radius: 8px;
}

.parsing-tabs {
  display: flex;
  gap: 10px;
}

.parsing-tabs button {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
}

.parsing-tabs button.active {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.sub-tab-content {
  background: white;
  border-radius: 8px;
  padding: 20px;
  min-height: 400px;
}

.raw-text-viewer, .json-viewer {
  background: #2c3e50;
  color: #ecf0f1;
  padding: 20px;
  border-radius: 4px;
  overflow-x: auto;
  max-height: 600px;
  font-family: monospace;
}

.text-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 10px;
}

.pdf-container {
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.loading-state, .error-state, .no-data {
  text-align: center;
  padding: 40px;
  background: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.error-state {
  color: #e74c3c;
  background: #fdeaea;
}

.btn-sm {
  padding: 6px 12px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.btn-sm:hover {
  background: #f5f5f5;
}

pre {
  margin: 0;
  white-space: pre-wrap;
}
</style>
