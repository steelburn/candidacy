<template>
  <div class="header-card">
    <div class="header-content">
      <div class="title-section">
        <h1 class="gradient-text">{{ vacancy.title }}</h1>
        <div class="meta-info">
          <span :class="'status-badge status-' + vacancy.status">
            <span class="status-icon">●</span>
            {{ vacancy.status }}
          </span>
          <span class="job-type-badge">
            💼 {{ vacancy.employment_type?.replace('_', ' ') || 'Full-time' }}
          </span>
        </div>
      </div>
      <div class="actions">
        <router-link :to="`/vacancies/${vacancy.id}/edit`" class="btn-primary">
          <span class="btn-icon">✏️</span>
          Edit Vacancy
        </router-link>
        <button 
            @click="$emit('find-candidates')" 
            class="btn-secondary" 
            :disabled="loadingMatches"
        >
          <span class="btn-icon">🔍</span>
          {{ loadingMatches ? 'Finding...' : 'Find Candidates' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  vacancy: {
    type: Object,
    required: true
  },
  loadingMatches: {
    type: Boolean,
    default: false
  }
})

defineEmits(['find-candidates'])
</script>

<style scoped>
.header-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 3rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.5);
  position: relative;
  overflow: hidden;
  animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-30px); }
  to { opacity: 1; transform: translateY(0); }
}

.header-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
  background-size: 200% 100%;
  animation: gradientSlide 3s ease infinite;
}

@keyframes gradientSlide {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.gradient-text {
  font-size: 3rem;
  font-weight: 900;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 1.5rem 0;
  letter-spacing: -0.03em;
  line-height: 1.2;
}

.meta-info {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.status-icon {
  font-size: 0.625rem;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.status-draft { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }
.status-open { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
.status-closed { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #b91c1c; }
.status-on_hold { background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #c2410c; }

.job-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: linear-gradient(135deg, #ebf8ff, #dbeafe);
  color: #1e40af;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: capitalize;
  box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
}

.actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  border-radius: 14px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-icon {
  font-size: 1.125rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #4a5568;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  color: #2d3748;
  border-color: #cbd5e0;
  transform: translateY(-2px);
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
