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

    <!-- Tab Contents -->
    <AdminSystemHealth v-if="currentTab === 'system'" />
    <AdminConfiguration v-if="currentTab === 'configuration'" />
    <AdminUserManagement v-if="currentTab === 'users'" />
    <AdminCvJobs v-if="currentTab === 'cv_jobs'" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AdminSystemHealth from '../components/admin/AdminSystemHealth.vue'
import AdminConfiguration from '../components/admin/AdminConfiguration.vue'
import AdminUserManagement from '../components/admin/AdminUserManagement.vue'
import AdminCvJobs from '../components/admin/AdminCvJobs.vue'

const currentTab = ref('system')
</script>

<style scoped>
.admin-panel {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

h1 {
  margin-bottom: 2rem;
  color: #2c3e50;
  font-size: 2rem;
}

.admin-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #eee;
  overflow-x: auto;
  padding-bottom: 2px;
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
  white-space: nowrap;
}

.admin-tabs button:hover {
  color: #667eea;
  background-color: #f8f9fa;
}

.admin-tabs button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}
</style>
