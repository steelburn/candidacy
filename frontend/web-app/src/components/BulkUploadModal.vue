<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Bulk Upload Resumes</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <div class="modal-body">
        <!-- Drop Zone -->
        <div 
          v-if="!uploading && results.length === 0"
          class="drop-zone"
          :class="{ 'drag-over': isDragging }"
          @dragover.prevent="isDragging = true"
          @dragleave="isDragging = false"
          @drop.prevent="handleDrop"
          @click="$refs.fileInput.click()"
        >
          <input 
            ref="fileInput"
            type="file"
            multiple
            accept=".pdf,.doc,.docx"
            @change="handleFileSelect"
            style="display: none"
          />
          <div class="drop-icon">📄</div>
          <p class="drop-text">Drag & drop resume files here</p>
          <p class="drop-hint">or click to select files</p>
          <p class="file-types">Supported: PDF, DOC, DOCX (max 10MB each, up to 20 files)</p>
        </div>

        <!-- Selected Files -->
        <div v-if="files.length > 0 && !uploading && results.length === 0" class="file-list">
          <h3>Selected Files ({{ files.length }})</h3>
          <div class="file-list-container">
            <ul>
              <li v-for="(file, index) in files" :key="index">
                <span class="file-name">{{ file.name }}</span>
                <span class="file-size">{{ formatSize(file.size) }}</span>
                <button class="remove-btn" @click="removeFile(index)">&times;</button>
              </li>
            </ul>
          </div>
          <button class="btn-primary" @click="startUpload" :disabled="files.length === 0">
            Upload {{ files.length }} file(s)
          </button>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="upload-progress">
          <div class="progress-spinner"></div>
          <p>Processing resumes with AI...</p>
          <p class="progress-hint">This may take a few minutes depending on file count</p>
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
          </div>
          <p class="progress-percent">{{ uploadProgress }}%</p>
        </div>

        <!-- Results -->
        <div v-if="results.length > 0" class="results">
          <div class="summary" :class="summaryClass">
            <span class="summary-icon">{{ summary.failed === 0 ? '✅' : '⚠️' }}</span>
            <span>{{ summary.success }} of {{ summary.total }} uploaded successfully</span>
          </div>
          
          <ul class="results-list">
            <li v-for="(result, index) in results" :key="index" :class="'result-' + result.status">
              <span class="result-icon">{{ result.status === 'success' ? '✓' : result.status === 'skipped' ? '⊘' : '✗' }}</span>
              <span class="result-file">{{ result.file }}</span>
              <span v-if="result.status === 'success'" class="result-name">→ {{ result.candidate_name }}</span>
              <span v-else class="result-error">{{ result.error }}</span>
            </li>
          </ul>
          
          <button class="btn-primary" @click="$emit('close'); $emit('refresh')">
            Done
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { candidateAPI } from '../services/api'

defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'refresh'])

const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const results = ref([])
const summary = ref({ total: 0, success: 0, failed: 0 })

const summaryClass = computed(() => ({
  'summary-success': summary.value.failed === 0,
  'summary-warning': summary.value.failed > 0 && summary.value.success > 0,
  'summary-error': summary.value.success === 0
}))

const handleDrop = (e) => {
  isDragging.value = false
  const droppedFiles = Array.from(e.dataTransfer.files).filter(f => 
    ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(f.type)
  )
  files.value = [...files.value, ...droppedFiles].slice(0, 20)
}

const handleFileSelect = (e) => {
  const selectedFiles = Array.from(e.target.files)
  files.value = [...files.value, ...selectedFiles].slice(0, 20)
}

const removeFile = (index) => {
  files.value.splice(index, 1)
}

const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const startUpload = async () => {
  uploading.value = true
  uploadProgress.value = 0
  
  try {
    const response = await candidateAPI.bulkUpload(files.value, (progressEvent) => {
      uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
    })
    
    results.value = response.data.results
    summary.value = response.data.summary
  } catch (error) {
    console.error('Bulk upload failed:', error)
    results.value = [{ file: 'Upload', status: 'failed', error: error.message || 'Upload failed' }]
    summary.value = { total: files.value.length, success: 0, failed: files.value.length }
  } finally {
    uploading.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #999;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: calc(80vh - 80px);
}

.drop-zone {
  border: 3px dashed #ddd;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.drop-zone:hover,
.drop-zone.drag-over {
  border-color: #667eea;
  background: #f8f9ff;
}

.drop-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.drop-text {
  font-size: 1.25rem;
  color: #333;
  margin: 0;
}

.drop-hint {
  color: #666;
  margin: 0.5rem 0;
}

.file-types {
  font-size: 0.875rem;
  color: #999;
  margin-top: 1rem;
}

.file-list {
  margin-top: 1rem;
}

.file-list h3 {
  margin: 0 0 1rem;
  color: #333;
}

.file-list-container {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 1.5rem;
}

.file-list ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.file-list li {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.file-name {
  flex: 1;
  font-weight: 500;
}

.file-size {
  color: #666;
  margin-right: 1rem;
  font-size: 0.875rem;
}

.remove-btn {
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

.upload-progress {
  text-align: center;
  padding: 2rem;
}

.progress-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.progress-hint {
  color: #666;
  font-size: 0.875rem;
}

.progress-bar {
  background: #eee;
  border-radius: 10px;
  height: 8px;
  margin: 1rem 0;
  overflow: hidden;
}

.progress-fill {
  background: linear-gradient(90deg, #667eea, #764ba2);
  height: 100%;
  transition: width 0.3s ease;
}

.progress-percent {
  font-weight: 600;
  color: #667eea;
}

.results {
  margin-top: 1rem;
}

.summary {
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  font-weight: 500;
}

.summary-success { background: #d4edda; color: #155724; }
.summary-warning { background: #fff3cd; color: #856404; }
.summary-error { background: #f8d7da; color: #721c24; }

.summary-icon {
  font-size: 1.5rem;
}

.results-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.results-list li {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  gap: 0.5rem;
}

.result-success { background: #d4edda; }
.result-skipped { background: #fff3cd; }
.result-failed { background: #f8d7da; }

.result-icon {
  font-weight: bold;
  width: 20px;
}

.result-file {
  font-weight: 500;
}

.result-name {
  color: #155724;
  flex: 1;
  text-align: right;
}

.result-error {
  color: #721c24;
  flex: 1;
  text-align: right;
  font-size: 0.875rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  width: 100%;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
