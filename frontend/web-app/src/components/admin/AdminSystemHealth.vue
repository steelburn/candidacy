<template>
  <div class="tab-content">
    <h2>System Health</h2>
    <div v-if="loading" class="loading">Loading system health...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else class="health-grid">
      <div v-for="service in systemHealth" :key="service.service" class="health-card">
        <div class="health-header">
          <h3>{{ service.service }}</h3>
          <span :class="'status-badge status-' + service.status">{{ service.status }}</span>
        </div>
        <div class="health-details">
          <p><strong>Response Time:</strong> {{ service.response_time }}</p>
          <p v-if="service.version"><strong>Version:</strong> {{ service.version }}</p>
          <p v-if="service.uptime"><strong>Uptime:</strong> {{ service.uptime }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminAPI } from '../../services/api'

const loading = ref(false)
const error = ref('')
const systemHealth = ref([])

const loadSystemHealth = async () => {
  loading.value = true
  error.value = ''
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

onMounted(() => {
  loadSystemHealth()
})
</script>

<style scoped>
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

.health-details p {
  margin: 0.5rem 0;
  font-size: 0.9rem;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin: 1rem 0;
}
</style>
