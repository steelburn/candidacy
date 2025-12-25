<template>
  <div class="login-page">
    <!-- Animated Background -->
    <div class="background-layer">
      <div class="gradient-orb orb-1"></div>
      <div class="gradient-orb orb-2"></div>
      <div class="gradient-orb orb-3"></div>
    </div>

    <div class="login-container">
      <!-- Branding -->
      <div class="brand">
        <div class="brand-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <h1 class="brand-name">Candidacy</h1>
        <p class="brand-tagline">Recruitment Management Platform</p>
      </div>

      <!-- Login Card -->
      <div class="login-card">
        <div class="card-header">
          <h2>Welcome Back</h2>
          <p>Sign in to continue to your dashboard</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form">
          <!-- Email Field -->
          <div class="form-group" :class="{ focused: emailFocused || credentials.email }">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </span>
              <input 
                id="email"
                v-model="credentials.email" 
                type="email" 
                required 
                autocomplete="email"
                @focus="emailFocused = true"
                @blur="emailFocused = false"
              />
            </div>
          </div>

          <!-- Password Field -->
          <div class="form-group" :class="{ focused: passwordFocused || credentials.password }">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input 
                id="password"
                v-model="credentials.password" 
                :type="showPassword ? 'text' : 'password'" 
                required
                autocomplete="current-password"
                @focus="passwordFocused = true"
                @blur="passwordFocused = false"
              />
              <button 
                type="button" 
                class="toggle-password"
                @click="showPassword = !showPassword"
                tabindex="-1"
              >
                <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Error Message -->
          <transition name="shake">
            <div v-if="error" class="error-message">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <span>{{ error }}</span>
            </div>
          </transition>

          <!-- Submit Button -->
          <button type="submit" :disabled="loading" class="btn-login">
            <span v-if="loading" class="loading-spinner"></span>
            <span v-else>Sign In</span>
            <svg v-if="!loading" class="arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </button>
        </form>

        <!-- Demo Credentials -->
        <div class="demo-hint">
          <span class="hint-label">Demo Account</span>
          <button type="button" class="fill-demo" @click="fillDemoCredentials">
            admin@test.com / password
          </button>
        </div>
      </div>

      <!-- Footer -->
      <p class="footer-text">© 2025 Candidacy. All rights reserved.</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: ''
})
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const emailFocused = ref(false)
const passwordFocused = ref(false)

const fillDemoCredentials = () => {
  credentials.value.email = 'admin@test.com'
  credentials.value.password = 'password'
}

async function handleLogin() {
  loading.value = true
  error.value = ''
  
  try {
    await authStore.login(credentials.value)
    router.push('/dashboard')
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || err.response?.data?.error || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  padding: 2rem;
  position: relative;
  overflow: hidden;
}

/* Animated Background Orbs */
.background-layer {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.gradient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.5;
  animation: float 20s ease-in-out infinite;
}

.orb-1 {
  width: 600px;
  height: 600px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  top: -200px;
  left: -100px;
  animation-delay: 0s;
}

.orb-2 {
  width: 400px;
  height: 400px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  bottom: -100px;
  right: -50px;
  animation-delay: -7s;
}

.orb-3 {
  width: 300px;
  height: 300px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  top: 50%;
  right: 20%;
  animation-delay: -14s;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(30px, -30px) scale(1.05); }
  50% { transform: translate(-20px, 20px) scale(0.95); }
  75% { transform: translate(20px, 30px) scale(1.02); }
}

.login-container {
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
  width: 100%;
  max-width: 420px;
}

/* Branding */
.brand {
  text-align: center;
  color: white;
}

.brand-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.brand-icon svg {
  width: 32px;
  height: 32px;
  color: white;
}

.brand-name {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #fff 0%, #e0e0ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-tagline {
  font-size: 0.9rem;
  opacity: 0.7;
  margin: 0.5rem 0 0;
}

/* Login Card */
.login-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 24px;
  padding: 2.5rem;
  width: 100%;
  box-shadow: 
    0 25px 50px rgba(0, 0, 0, 0.25),
    inset 0 1px 1px rgba(255, 255, 255, 0.1);
}

.card-header {
  text-align: center;
  margin-bottom: 2rem;
}

.card-header h2 {
  color: white;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.card-header p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  margin: 0.5rem 0 0;
}

/* Form */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  position: relative;
}

.form-group label {
  display: block;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  transition: color 0.3s;
}

.form-group.focused label {
  color: #667eea;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  width: 20px;
  height: 20px;
  color: rgba(255, 255, 255, 0.4);
  transition: color 0.3s;
  pointer-events: none;
}

.form-group.focused .input-icon {
  color: #667eea;
}

.input-icon svg {
  width: 100%;
  height: 100%;
}

.form-group input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  font-size: 1rem;
  color: white;
  transition: all 0.3s;
  box-sizing: border-box;
}

.form-group input::placeholder {
  color: rgba(255, 255, 255, 0.3);
}

.form-group input:focus {
  outline: none;
  background: rgba(255, 255, 255, 0.12);
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
}

.toggle-password {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  padding: 0;
  width: 20px;
  height: 20px;
  color: rgba(255, 255, 255, 0.4);
  cursor: pointer;
  transition: color 0.3s;
}

.toggle-password:hover {
  color: rgba(255, 255, 255, 0.8);
}

.toggle-password svg {
  width: 100%;
  height: 100%;
}

/* Error Message */
.error-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
  padding: 1rem;
  border-radius: 12px;
  font-size: 0.9rem;
}

.error-message svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.shake-enter-active {
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-8px); }
  50% { transform: translateX(8px); }
  75% { transform: translateX(-4px); }
}

/* Login Button */
.btn-login {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.arrow-icon {
  width: 20px;
  height: 20px;
  transition: transform 0.3s;
}

.btn-login:hover .arrow-icon {
  transform: translateX(4px);
}

/* Loading Spinner */
.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Demo Hint */
.demo-hint {
  margin-top: 1.5rem;
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.hint-label {
  display: block;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.4);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.5rem;
}

.fill-demo {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.7);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s;
}

.fill-demo:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

/* Footer */
.footer-text {
  color: rgba(255, 255, 255, 0.3);
  font-size: 0.8rem;
  margin: 0;
}

/* Responsive */
@media (max-width: 480px) {
  .login-page {
    padding: 1rem;
  }
  
  .login-card {
    padding: 1.5rem;
  }
  
  .brand-name {
    font-size: 1.5rem;
  }
}
</style>
