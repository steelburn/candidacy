<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Login</h1>
      
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email</label>
          <input 
            v-model="credentials.email" 
            type="email" 
            required 
            placeholder="admin@test.com"
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input 
            v-model="credentials.password" 
            type="password" 
            required 
            placeholder="password"
          />
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="btn-primary">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <p class="hint">Default: admin@test.com / password</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const credentials = ref({
  email: '',
  password: ''
})
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''
  
  try {
    console.log('Attempting login with:', credentials.value.email)
    const response = await api.auth.login(credentials.value)
    console.log('Login response:', response.data)
    
    if (response.data.access_token) {
      localStorage.setItem('token', response.data.access_token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      console.log('Login successful, redirecting...')
      router.push('/dashboard')
    }
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || err.response?.data?.error || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.login-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 400px;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: #333;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.btn-primary {
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.hint {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.85rem;
  color: #666;
}
</style>
