<template>
  <div class="login-container" :style="containerStyle">
    <div class="background-overlay"></div>
    
    <div class="login-card">
      <div class="logo-section">
        <div class="logo-icon">🎯</div>
        <h1>{{ appName }}</h1>
        <p class="tagline">AI-Powered Recruitment Excellence</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label>
            <span class="icon">📧</span>
            Email Address
          </label>
          <input 
            v-model="credentials.email" 
            type="email" 
            placeholder="Enter your email"
            required
            autocomplete="email"
          />
        </div>
        
        <div class="form-group">
          <label>
            <span class="icon">🔒</span>
            Password
          </label>
          <input 
            v-model="credentials.password" 
            type="password" 
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          />
        </div>
        
        <div v-if="error" class="error-message">
          <span class="error-icon">⚠️</span>
          {{ error }}
        </div>
        
        <button type="submit" :disabled="loading" class="login-button">
          <span v-if="!loading">Sign In</span>
          <span v-else class="loading">
            <span class="spinner"></span>
            Signing in...
          </span>
        </button>
      </form>
      
      <div class="footer-hint">
        <p>Default credentials: <code>admin@candidacy.test</code> / <code>password</code></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { authAPI, adminAPI } from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: ''
})

const loading = ref(false)
const checking = ref(true)
const error = ref('')
const backgroundImage = ref('')
const appName = ref('Candidacy')

// Fetch settings
onMounted(async () => {
  try {
    // Check if setup is needed
    const response = await authAPI.setupCheck()
    if (response.data.needs_setup) {
      router.push('/setup')
    }
    
    // Fetch background image and app name from settings
    try {
      const bgResponse = await adminAPI.getSetting('login_background_image')
      if (bgResponse.data?.value) {
        backgroundImage.value = bgResponse.data.value
      }
    } catch (err) {
      console.log('Using default background')
    }
    
    try {
      const nameResponse = await adminAPI.getSetting('app_name')
      if (nameResponse.data?.value) {
        appName.value = nameResponse.data.value
      }
    } catch (err) {
      console.log('Using default app name')
    }
  } catch (err) {
    console.error('Setup check failed:', err)
  } finally {
    checking.value = false
  }
})

const containerStyle = computed(() => {
  if (backgroundImage.value) {
    return {
      backgroundImage: `url(${backgroundImage.value})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  }
  return {}
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    await authStore.login(credentials.value)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please check your credentials.'
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
  position: relative;
  overflow: hidden;
}

.background-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
  backdrop-filter: blur(0px);
}

.login-card {
  position: relative;
  z-index: 1;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 3rem 2.5rem;
  border-radius: 24px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.1),
    0 2px 8px rgba(0, 0, 0, 0.05),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  width: 100%;
  max-width: 440px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.logo-section {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo-icon {
  font-size: 3.5rem;
  margin-bottom: 1rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
}

h1 {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
}

.tagline {
  color: #666;
  font-size: 0.95rem;
  font-weight: 500;
}

.login-form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  color: #333;
  font-weight: 600;
  font-size: 0.9rem;
}

.icon {
  font-size: 1.1rem;
}

input {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.9);
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
}

input::placeholder {
  color: #999;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: linear-gradient(135deg, #fee 0%, #fdd 100%);
  color: #c33;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid #fcc;
  font-weight: 500;
  animation: shake 0.5s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.error-icon {
  font-size: 1.2rem;
}

.login-button {
  width: 100%;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  position: relative;
  overflow: hidden;
}

.login-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.login-button:hover::before {
  left: 100%;
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.login-button:active {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.footer-hint {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
}

.footer-hint p {
  color: #666;
  font-size: 0.85rem;
  margin: 0;
}

.footer-hint code {
  background: #f5f5f5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.8rem;
  color: #667eea;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .login-card {
    margin: 1rem;
    padding: 2rem 1.5rem;
  }
  
  h1 {
    font-size: 1.75rem;
  }
  
  .logo-icon {
    font-size: 3rem;
  }
}
</style>
