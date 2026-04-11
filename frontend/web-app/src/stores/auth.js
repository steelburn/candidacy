import { defineStore } from 'pinia'
import { authAPI, tenantAPI } from '../services/api'

/**
 * Decode the tenant_id claim from a JWT without a library.
 * Returns null if the token is missing or malformed.
 */
function parseTenantIdFromToken(token) {
    try {
        const payload = JSON.parse(atob(token.split('.')[1]))
        return payload.tenant_id ?? null
    } catch {
        return null
    }
}

const storedToken = localStorage.getItem('token')

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: storedToken || null,
        // Tenant state
        tenants: [],
        currentTenantId: storedToken ? parseTenantIdFromToken(storedToken) : null,
        tenantsLoading: false,
        tenantsFetched: false, // Track if we've attempted to fetch tenants
        tenantSwitching: false
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        currentUser: (state) => state.user,
        userRoles: (state) => state.user?.roles || [],
        currentTenant: (state) => state.tenants.find(t => t.id === state.currentTenantId) || null,
        currentTenantName: (state) => {
            const tenant = state.tenants.find(t => t.id === state.currentTenantId)
            return tenant?.name ?? (state.currentTenantId ? `Tenant #${state.currentTenantId}` : 'No tenant')
        },
        hasMultipleTenants: (state) => state.tenants.length > 1
    },

    actions: {
        async login(credentials) {
            try {
                const response = await authAPI.login(credentials)
                this.token = response.data.access_token
                this.user = response.data.user
                this.currentTenantId = parseTenantIdFromToken(this.token)

                localStorage.setItem('token', this.token)
                localStorage.setItem('user', JSON.stringify(this.user))

                // Load tenants in the background after login
                this.fetchTenants().catch(() => { })

                return response.data
            } catch (error) {
                throw error
            }
        },

        async logout() {
            try {
                await authAPI.logout()
            } catch (error) {
                console.error('Logout error:', error)
            } finally {
                this.user = null
                this.token = null
                this.tenants = []
                this.tenantsFetched = false
                this.currentTenantId = null
                localStorage.removeItem('token')
                localStorage.removeItem('user')
            }
        },

        async fetchUser() {
            try {
                const response = await authAPI.me()
                this.user = response.data
                localStorage.setItem('user', JSON.stringify(this.user))
            } catch (error) {
                this.logout()
                throw error
            }
        },

        async fetchTenants() {
            if (this.tenantsLoading) return
            this.tenantsLoading = true
            try {
                const response = await tenantAPI.list()
                // Support both { data: [...] } and plain array responses
                this.tenants = response.data?.data ?? response.data ?? []
            } catch (error) {
                console.warn('Could not load tenants:', error?.response?.status)
                this.tenants = []
            } finally {
                this.tenantsLoading = false
                this.tenantsFetched = true
            }
        },

        async switchTenant(tenantId) {
            if (this.tenantSwitching) return
            this.tenantSwitching = true
            try {
                const response = await authAPI.switchTenant(tenantId)
                const newToken = response.data.access_token
                const newUser = response.data.user ?? this.user

                this.token = newToken
                this.user = newUser
                this.currentTenantId = parseTenantIdFromToken(newToken)

                localStorage.setItem('token', newToken)
                localStorage.setItem('user', JSON.stringify(newUser))

                // Re-fetch tenants under new context
                await this.fetchTenants()

                return response.data
            } finally {
                this.tenantSwitching = false
            }
        }
    }
})
