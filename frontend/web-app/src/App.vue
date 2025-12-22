<template>
  <div id="app">
    <nav v-if="authStore.isAuthenticated" class="navbar">
      <div class="nav-container">
        <h1 class="logo">Candidacy</h1>
        <div class="nav-links">
          <router-link to="/dashboard">Dashboard</router-link>
          <router-link to="/candidates">Candidates</router-link>
          <router-link to="/vacancies">Vacancies</router-link>
          <router-link to="/matches">Matches</router-link>
          <router-link to="/interviews">Interviews</router-link>
          <router-link to="/offers">Offers</router-link>
          <router-link to="/reports">Reports</router-link>
          <router-link to="/admin">Admin</router-link>
        </div>
        <div class="user-menu">
          <span>{{ authStore.user?.name }}</span>
          <button @click="showChangePassword = true" class="btn-link">Change Password</button>
          <button @click="logout" class="btn-logout">Logout</button>
        </div>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>

    <!-- Change Password Modal -->
    <div v-if="showChangePassword" class="modal-overlay" @click.self="showChangePassword = false">
      <div class="modal-content">
        <h3>Change Password</h3>
        <form @submit.prevent="handleChangePassword">
          <div class="form-group">
            <label>Current Password</label>
            <input v-model="passwordForm.current_password" type="password" required />
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input v-model="passwordForm.new_password" type="password" required minlength="6" />
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input v-model="passwordForm.new_password_confirmation" type="password" required />
          </div>
          <div v-if="passwordError" class="error">{{ passwordError }}</div>
          <div v-if="passwordSuccess" class="success">{{ passwordSuccess }}</div>
          <div class="modal-actions">
            <button type="submit" class="btn-primary" :disabled="changingPassword">
              {{ changingPassword ? 'Changing...' : 'Change Password' }}
            </button>
            <button type="button" @click="showChangePassword = false" class="btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'
import { authAPI } from './services/api'

const authStore = useAuthStore()
const router = useRouter()

const showChangePassword = ref(false)
const changingPassword = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

const handleChangePassword = async () => {
  passwordError.value = ''
  passwordSuccess.value = ''
  
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    passwordError.value = 'New passwords do not match'
    return
  }
  
  changingPassword.value = true
  try {
    await authAPI.changePassword(passwordForm.value)
    passwordSuccess.value = 'Password changed successfully!'
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
    setTimeout(() => {
      showChangePassword.value = false
      passwordSuccess.value = ''
    }, 2000)
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      passwordError.value = Object.values(errors).flat().join('. ')
    } else {
      passwordError.value = 'Failed to change password'
    }
  } finally {
    changingPassword.value = false
  }
}
</script>

<style scoped>
.navbar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.nav-links {
  display: flex;
  gap: 2rem;
}

.nav-links a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  transition: opacity 0.3s;
}

.nav-links a:hover,
.nav-links a.router-link-active {
  opacity: 0.8;
  text-decoration: underline;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn-link {
  background: none;
  border: none;
  color: rgba(255,255,255,0.8);
  cursor: pointer;
  font-size: 0.875rem;
  text-decoration: underline;
}

.btn-link:hover {
  color: white;
}

.btn-logout {
  background: rgba(255,255,255,0.2);
  border: 1px solid white;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-logout:hover {
  background: white;
  color: #667eea;
}

.main-content {
  max-width: 1400px;
  margin: 2rem auto;
  padding: 0 2rem;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  max-width: 400px;
  width: 90%;
}

.modal-content h3 {
  margin: 0 0 1.5rem;
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.success {
  background: #e8f5e9;
  color: #388e3c;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>

