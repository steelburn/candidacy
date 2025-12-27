<template>
  <div class="info-card sidebar-card">
    <h3 class="card-title">
      <span class="title-icon">📋</span>
      Job Details
    </h3>
    <div class="info-list">
      <div class="info-item">
        <span class="info-icon">📍</span>
        <div class="info-content">
          <span class="info-label">Location</span>
          <span class="info-value">{{ vacancy.location }}</span>
        </div>
      </div>
      <div class="info-item">
        <span class="info-icon">🏢</span>
        <div class="info-content">
          <span class="info-label">Department</span>
          <span class="info-value">{{ vacancy.department || 'N/A' }}</span>
        </div>
      </div>
      <div class="info-item">
        <span class="info-icon">💼</span>
        <div class="info-content">
          <span class="info-label">Employment Type</span>
          <span class="info-value">{{ vacancy.employment_type?.replace('_', ' ') }}</span>
        </div>
      </div>
      <div class="info-item">
        <span class="info-icon">📊</span>
        <div class="info-content">
          <span class="info-label">Experience Level</span>
          <span class="info-value">{{ vacancy.experience_level }}</span>
        </div>
      </div>
      <div class="info-item" v-if="vacancy.min_salary">
        <span class="info-icon">💰</span>
        <div class="info-content">
          <span class="info-label">Salary Range</span>
          <span class="info-value salary-value">
            {{ vacancy.min_salary }} - {{ vacancy.max_salary }} {{ vacancy.currency }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  vacancy: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.info-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.6);
  transition: all 0.3s ease;
}

.info-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 2rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.title-icon {
  font-size: 1.75rem;
}

.sidebar-card {
  height: fit-content;
  position: sticky;
  top: 2rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem;
  background: rgba(248, 250, 252, 0.6);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.info-item:hover {
  background: rgba(241, 245, 249, 0.8);
  transform: translateX(4px);
}

.info-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.info-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  color: #1a202c;
  font-weight: 600;
  font-size: 1.125rem;
}

.salary-value {
  background: linear-gradient(135deg, #10b981, #059669);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
}
</style>
