<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
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
        <button @click="$emit('close')" class="btn-secondary">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  historyData: { type: Object, default: null }
})

defineEmits(['close'])

const formatDate = (date) => new Date(date).toLocaleString()
</script>

<style scoped>
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
.modal-content h3 {
  margin: 0 0 1.5rem 0;
  color: #333;
}
.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}
.btn-secondary {
  background: white;
  border: 1px solid #e0e0e0;
  color: #444;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
}
.btn-secondary:hover {
  background: #f8f9fa;
}
.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.history-item {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  border-left: 3px solid #667eea;
}
.history-header-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
}
.history-date {
  font-weight: 600;
  color: #333;
}
.history-user {
  color: #666;
}
.history-changes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.history-value code {
  background: #e9ecef;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  font-size: 0.85rem;
}
.no-history {
  text-align: center;
  padding: 3rem;
  color: #888;
  font-style: italic;
}
</style>
