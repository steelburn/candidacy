<template>
  <div class="setup-container">
    <div class="setup-box">
      <div class="setup-header">
        <div class="logo-icon">🚀</div>
        <h1>Welcome to Candidacy</h1>
        <p class="subtitle">AI-Powered Recruitment System</p>
      </div>
      
      <div class="setup-content">
        <h2>Initial Setup</h2>
        <p class="description">Create your administrator account to get started.</p>
        
        <form @submit.prevent="handleSetup">
          <div class="form-group">
            <label>Full Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="John Doe"
              required
            />
          </div>
          
          <div class="form-group">
            <label>Email Address</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="admin@yourcompany.com"
              required
            />
          </div>
          
          <div class="form-group">
            <label>Password</label>
            <input 
              v-model="form.password" 
              type="password" 
              placeholder="Minimum 6 characters"
              required
              minlength="6"
            />
          </div>
          
          <div class="form-group">
            <label>Confirm Password</label>
            <input 
              v-model="form.password_confirmation" 
              type="password" 
              placeholder="Confirm your password"
              required
            />
          </div>
          
          <div v-if="error" class="error">{{ error }}</div>
          
          <button type="submit" :disabled="loading" class="btn-primary">
            {{ loading ? 'Creating...' : 'Create Admin Account' }}
          </button>
        </form>
      </div>
      
      <div class="setup-footer">
        <p>This wizard appears only when no users exist in the system.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { authAPI } from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const loading = ref(false)
const error = ref('')

const handleSetup = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }
  
  loading.value = true
  error.value = ''
  
  try {
    const response = await authAPI.createAdmin(form.value)
    
    // Store the token and user data
    localStorage.setItem('token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    authStore.token = response.data.access_token
    authStore.user = response.data.user
    
    router.push('/dashboard')
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join('. ')
    } else {
      error.value = err.response?.data?.error || 'Setup failed. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.setup-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.setup-box {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  width: 100%;
  max-width: 500px;
  overflow: hidden;
}

.setup-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  text-align: center;
}

.logo-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

.setup-header h1 {
  margin: 0;
  font-size: 1.75rem;
}

.subtitle {
  margin: 0.5rem 0 0;
  opacity: 0.9;
}

.setup-content {
  padding: 2rem;
}

.setup-content h2 {
  margin: 0 0 0.5rem;
  color: #333;
  font-size: 1.25rem;
}

.description {
  color: #666;
  margin: 0 0 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #333;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  transition: border 0.3s;
}

input:focus {
  outline: none;
  border-color: #667eea;
}

.btn-primary {
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.setup-footer {
  padding: 1rem 2rem;
  background: #f8f9fa;
  text-align: center;
}

.setup-footer p {
  margin: 0;
  color: #999;
  font-size: 0.75rem;
}
</style>
