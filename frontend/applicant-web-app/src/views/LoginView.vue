<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const email = ref('')
const pin = ref('')
const loading = ref(false)
const error = ref('')
const message = ref('')

const requestPin = async () => {
    if (!email.value) {
        error.value = "Enter email first"
        return
    }
    loading.value = true
    try {
        const res = await api.post('/portal/generate-pin', { email: email.value })
        message.value = res.data.message
        if (res.data.dev_pin) alert("DEV PIN: " + res.data.dev_pin) // For easier testing
    } catch (err) {
        error.value = "Failed to send PIN. Email might not exist."
    } finally {
        loading.value = false
    }
}

const login = async () => {
    loading.value = true
    error.value = ''
    try {
        const res = await api.post('/portal/login', { email: email.value, pin: pin.value })
        const token = res.data.token
        localStorage.setItem('candidate_token', token)
        router.push({ name: 'portal-dashboard' })
    } catch (err) {
        error.value = "Invalid login credentials."
    } finally {
        loading.value = false
    }
}
</script>

<template>
  <div class="container center-box">
    <div class="card login-card">
        <h2>Candidate Portal</h2>
        <div class="form-group">
            <label>Email</label>
            <div class="input-group">
                <input v-model="email" type="email" placeholder="email@example.com" />
                <button @click="requestPin" type="button" class="btn-secondary">Get PIN</button>
            </div>
        </div>
        <div class="form-group">
            <label>PIN Code</label>
            <input v-model="pin" type="text" placeholder="123456" maxlength="6" />
        </div>
        
        <div v-if="message" class="success">{{ message }}</div>
        <div v-if="error" class="error">{{ error }}</div>

        <button @click="login" :disabled="loading" class="btn-primary full-width">
            Login
        </button>
    </div>
  </div>
</template>

<style scoped>
.center-box { display: flex; justify-content: center; align-items: center; min-height: 80vh; }
.login-card { width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 1.5rem; }
.input-group { display: flex; gap: 0.5rem; }
.input-group input { flex-grow: 1; }
.btn-secondary { background: #334155; color: white; padding: 0.5rem 1rem; }
.full-width { width: 100%; }
input { padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 0.5rem; color: white; width: 100%; box-sizing: border-box; }
.success { color: var(--success-color); }
.error { color: var(--error-color); }
</style>
