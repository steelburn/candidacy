<template>
  <div class="login-container">
    <div class="login-card card">
      <h1 class="text-2xl font-bold mb-lg text-center">Login</h1>
      
      <form @submit.prevent="handleSubmit(onSubmit)" class="login-form">
        <BaseInput
          v-model="formData.email"
          label="Email"
          type="email"
          placeholder="Enter your email"
          :error="errors.email"
          required
        />

        <BaseInput
          v-model="formData.password"
          label="Password"
          type="password"
          placeholder="Enter your password"
          :error="errors.password"
          required
        />

        <BaseButton
          type="submit"
          variant="primary"
          :loading="isSubmitting"
          class="w-full"
        >
          Login
        </BaseButton>
      </form>

      <p v-if="loginError" class="form-error mt-md text-center">{{ loginError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useForm } from '@/composables/useForm'
import { validators } from '@/utils/validators'
import { useNotification } from '@/composables/useNotification'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import api from '@/services/api'

const router = useRouter()
const { success, error: showError } = useNotification()
const loginError = ref('')

const { formData, errors, isSubmitting, handleSubmit } = useForm(
  { email: '', password: '' },
  {
    email: [validators.required, validators.email],
    password: [validators.required, validators.minLength(6)]
  }
)

async function onSubmit(data) {
  loginError.value = ''
  
  try {
    const response = await api.auth.login(data)
    
    if (response.data.access_token) {
      localStorage.setItem('token', response.data.access_token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      success('Login successful!')
      router.push('/dashboard')
    }
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Login failed. Please try again.'
    showError(loginError.value)
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  padding: var(--spacing-lg);
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: var(--spacing-2xl);
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.w-full {
  width: 100%;
}
</style>
