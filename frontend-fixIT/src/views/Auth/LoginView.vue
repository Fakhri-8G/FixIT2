<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      
      <!-- Header / Logo -->
      <div class="auth-header">
        <div class="brand-logo">🔧</div>
        <h1 class="auth-title">Masuk ke FixIT</h1>
        <p class="auth-subtitle">Masuk dengan akun petugas atau warga sekolah untuk verifikasi pengaduan sarpras.</p>
      </div>

      <!-- Alert Pesan Error -->
      <transition name="fade">
        <div v-if="authError" class="alert alert-error">
          <span class="alert-icon">⚠️</span>
          <div class="alert-content">
            <strong>Gagal Otentikasi</strong>
            <p>{{ authError }}</p>
          </div>
        </div>
      </transition>

      <!-- Form Login -->
      <form @submit.prevent="executeLogin" class="auth-form" novalidate>
        
        <!-- Input Identitas -->
        <div class="form-group">
          <label for="identity">Email / Identitas</label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              id="identity"
              v-model.trim="credentials.identity"
              type="text"
              placeholder="Contoh: admin@gmail.com / 19820310..."
              required
              :disabled="isSubmitting"
            />
          </div>
        </div>

        <!-- Input Password + Toggle Eye -->
        <div class="form-group">
          <div class="label-row">
            <label for="password">Kata Sandi</label>
            <a href="#" class="forgot-link" @click.prevent="alertLupaPassword">Lupa sandi?</a>
          </div>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              id="password"
              v-model="credentials.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Masukkan kata sandi..."
              required
              :disabled="isSubmitting"
            />
            <button 
              type="button" 
              class="toggle-password" 
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              {{ showPassword ? '👁️' : '🙈' }}
            </button>
          </div>
        </div>

        <!-- Checkbox Remember -->
        <div class="options-row">
          <label class="checkbox-container">
            <input type="checkbox" v-model="rememberMe" />
            Ingat sesi saya di perangkat ini
          </label>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          class="btn-submit" 
          :disabled="isSubmitting || !isFormValid"
        >
          <span v-if="isSubmitting" class="spinner"></span>
          <span>{{ isSubmitting ? 'Verifikasi Hak Akses...' : 'Masuk Portal Sistem' }}</span>
        </button>

      </form>

      <!-- Footer / Link ke Feed & Register -->
      <div class="auth-footer">
        <RouterLink to="/" class="nav-back">← Kembali ke Feed FixIT Public</RouterLink>
        <p class="register-hint">
          Belum punya akun? 
          <RouterLink to="/register" class="auth-link">Daftar sekarang</RouterLink>
        </p>
        <p class="help-text">Kendala akun? Hubungi Tim IT / Admin FixIT Sekolah.</p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()

// ─── State Management ──────────────────────────────────────
const credentials = reactive({
  identity: '',
  password: ''
})

const isSubmitting = ref(false)
const showPassword = ref(false)
const rememberMe   = ref(false)
const authError    = ref('')

const isFormValid = computed(() => {
  return credentials.identity.trim().length >= 3 && credentials.password.length >= 4
})

onMounted(() => {
  const existingToken = localStorage.getItem('token')
  if (existingToken) {
    api.defaults.headers.common['Authorization'] = `Bearer ${existingToken}`
    redirectBasedOnRole()
  }
})

// ─── Handler Logic Login ────────────────────────────────────
const executeLogin = async () => {
  if (!isFormValid.value) return

  isSubmitting.value = true
  authError.value = ''

  try {
    const response = await api.post('/login', {
      email: credentials.identity.trim(),
      password: credentials.password
    })

    const resData = response.data?.data || response.data
    const token = resData?.token || response.data?.token
    const user = resData?.user || response.data?.user

    if (!token) {
      throw new Error('Respon API tidak valid: Token otentikasi tidak ditemukan.')
    }

    localStorage.setItem('token', token)
    if (user) {
      localStorage.setItem('user', JSON.stringify(user))
    }

    api.defaults.headers.common['Authorization'] = `Bearer ${token}`
    redirectBasedOnRole(user)

  } catch (err) {
    console.error('DEBUG LOGIN ERROR:', err)
    handleErrorResponse(err)
  } finally {
    isSubmitting.value = false
  }
}

// ─── Utility Methods ────────────────────────────────────────
const handleErrorResponse = (err) => {
  if (!err.response) {
    authError.value = err.message || 'Tidak dapat terhubung ke server. Periksa koneksi internet atau CORS backend.'
    return
  }

  const status = err.response.status
  const serverMsg = err.response.data?.message || err.response.data?.error

  switch (status) {
    case 401:
      authError.value = serverMsg || 'Kredensial salah. Periksa kembali Email dan Kata Sandi!'
      break
    case 404:
      authError.value = 'Endpoint login tidak ditemukan atau akun tidak terdaftar.'
      break
    case 422:
      const validationErrors = err.response.data?.errors
      if (validationErrors) {
        const firstKey = Object.keys(validationErrors)[0]
        authError.value = validationErrors[firstKey][0]
      } else {
        authError.value = serverMsg || 'Format inputan tidak sesuai standar sistem.'
      }
      break
    case 429:
      authError.value = 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'
      break
    default:
      authError.value = serverMsg || `Terjadi kesalahan sistem (Status Code: ${status}).`
  }
}

const redirectBasedOnRole = (userObj = null) => {
  let user = userObj
  if (!user) {
    try {
      user = JSON.parse(localStorage.getItem('user') || '{}')
    } catch {
      user = {}
    }
  }

  const role = user?.role ? String(user.role).toLowerCase() : ''

  if (role === 'admin' || role === 'petugas') {
    router.push('/admin/dashboard-kerusakan')
  } else {
    router.push('/dashboard')
  }
}

const alertLupaPassword = () => {
  alert('Silakan ajukan reset password ke Ruang FixIT / Tim TI Sekolah dengan membawa Kartu Identitas.')
}
</script>

<style scoped>
/* Wrapper (Senada Register) */
.auth-wrapper {
  min-height: calc(100vh - 80px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background-color: transparent;
}

/* Auth Card */
.auth-card {
  background: #1e293b;
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
  line-height: 1.4;
}

/* Alert Box Error */
.alert {
  display: flex;
  align-items: flex-start;
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

.alert-content strong {
  display: block;
  font-size: 13px;
  color: #f87171;
}

.alert-content p {
  font-size: 12px;
  margin-top: 2px;
  color: #fca5a5;
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

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #cbd5e1;
}

.forgot-link {
  font-size: 12px;
  color: #60a5fa;
  text-decoration: none;
  font-weight: 600;
}

.forgot-link:hover {
  text-decoration: underline;
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

/* Options / Checkbox */
.options-row {
  display: flex;
  align-items: center;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #94a3b8;
  cursor: pointer;
  user-select: none;
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
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-back {
  display: inline-block;
  font-size: 13px;
  color: #94a3b8;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.nav-back:hover {
  color: #ffffff;
}

.register-hint {
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

.help-text {
  font-size: 11px;
  color: #64748b;
  margin-top: 4px;
}

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>