<template>
  <div class="tenant-switcher" ref="switcherRef">
    <!-- Loading skeleton -->
    <div v-if="authStore.tenantsLoading" class="tenant-badge tenant-badge--loading">
      <span class="tenant-skeleton" />
    </div>

    <!-- Single tenant — badge only, no dropdown -->
    <div
      v-else-if="!authStore.hasMultipleTenants"
      class="tenant-badge"
      title="Your current workspace"
    >
      <span class="tenant-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </span>
      <span class="tenant-name">{{ authStore.currentTenantName }}</span>
    </div>

    <!-- Multi-tenant dropdown trigger -->
    <button
      v-else
      class="tenant-badge tenant-badge--interactive"
      :class="{ 'is-switching': authStore.tenantSwitching }"
      @click="toggleDropdown"
      :disabled="authStore.tenantSwitching"
      :title="`Switch workspace (current: ${authStore.currentTenantName})`"
    >
      <span class="tenant-icon">
        <svg v-if="authStore.tenantSwitching" class="spinner-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </span>
      <span class="tenant-name">{{ authStore.currentTenantName }}</span>
      <span class="tenant-chevron" :class="{ rotated: isOpen }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </span>
    </button>

    <!-- Dropdown popover -->
    <Transition name="dropdown">
      <div v-if="isOpen && authStore.hasMultipleTenants" class="tenant-dropdown">
        <div class="dropdown-header">
          <span>Switch Workspace</span>
        </div>
        <ul class="tenant-list" role="listbox">
          <li
            v-for="tenant in authStore.tenants"
            :key="tenant.id"
            class="tenant-option"
            :class="{ 'is-active': tenant.id === authStore.currentTenantId }"
            role="option"
            :aria-selected="tenant.id === authStore.currentTenantId"
            @click="handleSwitch(tenant)"
          >
            <span class="tenant-option-avatar">{{ tenantInitials(tenant.name) }}</span>
            <span class="tenant-option-info">
              <span class="tenant-option-name">{{ tenant.name }}</span>
              <span v-if="tenant.slug" class="tenant-option-slug">{{ tenant.slug }}</span>
            </span>
            <span v-if="tenant.id === authStore.currentTenantId" class="tenant-option-check">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </span>
          </li>
        </ul>

        <!-- Error message -->
        <div v-if="switchError" class="dropdown-error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ switchError }}
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const isOpen = ref(false)
const switchError = ref('')
const switcherRef = ref(null)

function tenantInitials(name = '') {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function toggleDropdown() {
  isOpen.value = !isOpen.value
  switchError.value = ''
}

function closeDropdown() {
  isOpen.value = false
}

async function handleSwitch(tenant) {
  if (tenant.id === authStore.currentTenantId || authStore.tenantSwitching) return
  switchError.value = ''
  try {
    await authStore.switchTenant(tenant.id)
    closeDropdown()
    // Reload current route so data re-fetches under new tenant scope
    const current = router.currentRoute.value.fullPath
    await router.replace('/dashboard')
    if (current !== '/dashboard') {
      await router.replace(current)
    }
  } catch (err) {
    switchError.value = err?.response?.data?.message || 'Failed to switch workspace. Please try again.'
  }
}

// Close on outside click
function onOutsideClick(event) {
  if (switcherRef.value && !switcherRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => document.addEventListener('mousedown', onOutsideClick))
onUnmounted(() => document.removeEventListener('mousedown', onOutsideClick))
</script>

<style scoped>
.tenant-switcher {
  position: relative;
}

/* ─── Badge ─────────────────────────────────────── */
.tenant-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: var(--color-gray-100, #f3f4f6);
  border-radius: 8px;
  border: none;
  cursor: default;
  font-family: inherit;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--text-primary, #111827);
  white-space: nowrap;
  max-width: 200px;
  transition: background 0.15s ease;
}

.tenant-badge--interactive {
  cursor: pointer;
}

.tenant-badge--interactive:hover {
  background: var(--color-gray-200, #e5e7eb);
}

.tenant-badge--interactive:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.tenant-badge--loading {
  min-width: 120px;
}

.tenant-skeleton {
  display: block;
  width: 100px;
  height: 14px;
  background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.tenant-icon {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  color: var(--text-secondary, #6b7280);
  flex-shrink: 0;
}

.tenant-icon svg {
  width: 16px;
  height: 16px;
}

.tenant-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tenant-chevron {
  width: 14px;
  height: 14px;
  display: flex;
  align-items: center;
  color: var(--text-secondary, #6b7280);
  transition: transform 0.2s ease;
  flex-shrink: 0;
}

.tenant-chevron svg {
  width: 14px;
  height: 14px;
}

.tenant-chevron.rotated {
  transform: rotate(180deg);
}

.spinner-icon {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ─── Dropdown ───────────────────────────────────── */
.tenant-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 240px;
  max-width: 300px;
  background: var(--bg-primary, #ffffff);
  border: 1px solid var(--color-gray-200, #e5e7eb);
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
  z-index: 200;
  overflow: hidden;
}

.dropdown-header {
  padding: 10px 14px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-gray-100, #f3f4f6);
}

.tenant-list {
  list-style: none;
  margin: 0;
  padding: 6px;
  max-height: 280px;
  overflow-y: auto;
}

.tenant-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease;
}

.tenant-option:hover {
  background: var(--color-gray-50, #f9fafb);
}

.tenant-option.is-active {
  background: rgba(99, 102, 241, 0.07);
}

.tenant-option-avatar {
  width: 32px;
  height: 32px;
  min-width: 32px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
}

.tenant-option-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  flex: 1;
}

.tenant-option-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary, #111827);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tenant-option-slug {
  font-size: 0.75rem;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tenant-option-check {
  width: 16px;
  height: 16px;
  color: #6366f1;
  flex-shrink: 0;
}

.tenant-option-check svg {
  width: 16px;
  height: 16px;
}

.dropdown-error {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  font-size: 0.8125rem;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.06);
  border-top: 1px solid rgba(239, 68, 68, 0.12);
}

.dropdown-error svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

/* ─── Transition ─────────────────────────────────── */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>
