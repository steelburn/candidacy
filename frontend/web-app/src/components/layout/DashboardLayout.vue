<template>
  <div class="dashboard-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <!-- Mobile overlay -->
    <div 
      v-if="mobileMenuOpen" 
      class="mobile-overlay" 
      @click="mobileMenuOpen = false"
    ></div>

    <!-- Sidebar -->
    <AppSidebar 
      :isCollapsed="sidebarCollapsed"
      :class="{ 'open': mobileMenuOpen }"
      @logout="$emit('logout')"
      @changePassword="$emit('changePassword')"
    />

    <!-- Main Content Area -->
    <div class="main-wrapper">
      <AppHeader 
        :isCollapsed="sidebarCollapsed"
        @toggleSidebar="toggleSidebar"
      />
      <main class="main-content">
        <slot></slot>
      </main>
    </div>

    <!-- Mobile menu button -->
    <button 
      class="mobile-menu-btn"
      @click="mobileMenuOpen = !mobileMenuOpen"
      v-if="isMobile"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'

defineEmits(['logout', 'changePassword'])

const route = useRoute()

// Sidebar state - try to restore from localStorage
const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')
const mobileMenuOpen = ref(false)
const isMobile = ref(false)

const toggleSidebar = () => {
  if (isMobile.value) {
    mobileMenuOpen.value = !mobileMenuOpen.value
  } else {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebar-collapsed', sidebarCollapsed.value)
  }
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
  if (isMobile.value) {
    mobileMenuOpen.value = false
  }
}

// Close mobile menu on route change
watch(() => route.path, () => {
  mobileMenuOpen.value = false
})

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-secondary, #f9fafb);
}

.main-wrapper {
  flex: 1;
  margin-left: var(--sidebar-width-expanded, 260px);
  transition: margin-left 0.3s ease;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.dashboard-layout.sidebar-collapsed .main-wrapper {
  margin-left: var(--sidebar-width-collapsed, 72px);
}

.main-content {
  flex: 1;
  padding: 24px;
  max-width: var(--max-content-width, 1400px);
  width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
}

/* Mobile Overlay */
.mobile-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
  display: none;
}

.mobile-menu-btn {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border: none;
  border-radius: 16px;
  display: none;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  z-index: 100;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.mobile-menu-btn svg {
  width: 24px;
  height: 24px;
}

@media (max-width: 768px) {
  .main-wrapper {
    margin-left: 0;
  }

  .dashboard-layout.sidebar-collapsed .main-wrapper {
    margin-left: 0;
  }

  .mobile-overlay {
    display: block;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .main-content {
    padding: 16px;
  }
}
</style>
