import { defineStore } from 'pinia'
import { authAPI } from '../services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        currentUser: (state) => state.user,
        userRoles: (state) => state.user?.roles || []
    },

    actions: {
        async login(credentials) {
            try {
                const response = await authAPI.login(credentials)
                this.token = response.data.access_token
                this.user = response.data.user

                localStorage.setItem('token', this.token)
                localStorage.setItem('user', JSON.stringify(this.user))

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
        }
    }
})
