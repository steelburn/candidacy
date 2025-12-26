<template>
  <header class="app-header">
    <div class="header-left">
      <button @click="$emit('toggleSidebar')" class="toggle-btn" :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
        <svg v-if="isCollapsed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <line x1="9" y1="3" x2="9" y2="21"/>
        </svg>
      </button>
      <div class="breadcrumb">
        <span class="page-title">{{ pageTitle }}</span>
      </div>
    </div>
    <div class="header-right">
      <!-- Quick search placeholder -->
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" placeholder="Search..." class="search-input" />
      </div>
      <!-- Theme toggle placeholder -->
      <button class="header-action" title="Toggle theme" @click="toggleTheme">
        <svg v-if="isDarkMode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="5"/>
          <line x1="12" y1="1" x2="12" y2="3"/>
          <line x1="12" y1="21" x2="12" y2="23"/>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
          <line x1="1" y1="12" x2="3" y2="12"/>
          <line x1="21" y1="12" x2="23" y2="12"/>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggleSidebar'])

const route = useRoute()
const isDarkMode = ref(document.body.classList.contains('dark-mode'))

const pageTitle = computed(() => {
  const routeName = route.name || 'Dashboard'
  // Convert camelCase/PascalCase to Title Case with spaces
  return routeName.replace(/([A-Z])/g, ' $1').trim()
})

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  document.body.classList.toggle('dark-mode', isDarkMode.value)
}
</script>

<style scoped>
.app-header {
  height: 64px;
  background: var(--bg-primary, #ffffff);
  border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.toggle-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: var(--text-secondary, #6b7280);
  cursor: pointer;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  background: var(--color-gray-100, #f3f4f6);
  color: var(--text-primary, #111827);
}

.toggle-btn svg {
  width: 20px;
  height: 20px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--color-gray-100, #f3f4f6);
  border-radius: 8px;
  min-width: 200px;
}

.search-box svg {
  width: 18px;
  height: 18px;
  color: var(--text-secondary, #6b7280);
}

.search-input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.875rem;
  color: var(--text-primary, #111827);
  width: 100%;
}

.search-input::placeholder {
  color: var(--text-secondary, #6b7280);
}

.header-action {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: var(--text-secondary, #6b7280);
  cursor: pointer;
  transition: all 0.2s ease;
}

.header-action:hover {
  background: var(--color-gray-100, #f3f4f6);
  color: var(--text-primary, #111827);
}

.header-action svg {
  width: 20px;
  height: 20px;
}

@media (max-width: 768px) {
  .search-box {
    display: none;
  }
}
</style>
