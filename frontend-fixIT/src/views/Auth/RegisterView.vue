<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <!-- Header / Logo -->
      <div class="auth-header">
        <div class="brand-logo">🔧</div>
        <h2 class="auth-title">Buat Akun Baru</h2>
        <p class="auth-subtitle">Daftar untuk mulai melaporkan kerusakan fasilitas</p>
      </div>

      <!-- Alert Pesan Error -->
      <div v-if="errorMessage" class="alert alert-error">
        <span>⚠️</span>
        <p>{{ errorMessage }}</p>
      </div>

      <!-- Form Register -->
      <form @submit.prevent="handleRegister" class="auth-form">
        <!-- Input Nama Lengkap -->
        <div class="form-group">
          <label for="name">Nama Lengkap</label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Masukkan nama lengkap"
              required
            />
          </div>
        </div>

        <!-- Input Email -->
        <div class="form-group">
          <label for="email">Alamat Email</label>
          <div class="input-wrapper">
            <span class="input-icon">✉️</span>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="nama@email.com"
              required
            />
          </div>
        </div>

        <!-- Input Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Minimal 8 karakter"
              required
            />
            <button
              type="button"
              class="toggle-password"
              @click="showPassword = !showPassword"
            >
              {{ showPassword ? '👁️' : '🙈' }}
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          <span>{{ isLoading ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}</span>
        </button>
      </form>

      <!-- Footer / Link ke Login -->
      <div class="auth-footer">
        <p>
          Sudah punya akun?
          <router-link to="/login" class="auth-link">Masuk di sini</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()

// State Form Data
const form = reactive({
  name: '',
  email: '',
  password: ''
})

const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref(null)

// Handle Register Submit
const handleRegister = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await api.post('/register', {
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password
    })

    // Opsi 1: Jika backend langsung kirim token (Auto Login)
    if (response.data?.token) {
      localStorage.setItem('token', response.data.token)
      router.push('/dashboard')
    } else {
      // Opsi 2: Arahkan ke halaman login kalau butuh login manual
      router.push('/login?registered=true')
    }
  } catch (err) {
    // Handling error dari response Laravel Validation
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const firstKey = Object.keys(errors)[0]
      errorMessage.value = errors[firstKey][0]
    } else {
      errorMessage.value =
        err.response?.data?.message || 'Gagal mendaftar. Silakan coba lagi nanti.'
    }
    console.error('Register Error:', err)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Main Wrapper Centers content */
.auth-wrapper {
  min-height: calc(100vh - 80px); /* Menyesuaikan space navbar */
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background-color: transparent;
}

/* Auth Card Container */
.auth-card {
  background: #1e293b; /* Matches dark surface theme */
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 32px 24px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
}

/* Header */
.auth-header {
  text-align: center;
  margin-bottom: 24px;
}

.brand-logo {
  font-size: 36px;
  margin-bottom: 8px;
  display: inline-block;
  background: #0f172a;
  padding: 12px;
  border-radius: 14px;
  border: 1px solid #334155;
}

.auth-title {
  font-size: 22px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 4px;
}

.auth-subtitle {
  font-size: 13px;
  color: #94a3b8;
}

/* Alert Box */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 20px;
}

.alert-error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

/* Form Controls */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #cbd5e1;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  font-size: 14px;
  pointer-events: none;
  color: #64748b;
}

.input-wrapper input {
  width: 100%;
  padding: 11px 12px 11px 38px;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 10px;
  color: #ffffff;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
}

.input-wrapper input::placeholder {
  color: #64748b;
}

.input-wrapper input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.toggle-password {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  padding: 0;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.toggle-password:hover {
  opacity: 1;
}

/* Submit Button & Spinner */
.btn-submit {
  margin-top: 6px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  border: none;
  padding: 12px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Footer Link */
.auth-footer {
  margin-top: 24px;
  text-align: center;
  border-top: 1px solid #334155;
  padding-top: 16px;
}

.auth-footer p {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

.auth-link {
  color: #60a5fa;
  font-weight: 600;
  text-decoration: none;
  margin-left: 4px;
  transition: color 0.2s;
}

.auth-link:hover {
  color: #93c5fd;
  text-decoration: underline;
}
</style>