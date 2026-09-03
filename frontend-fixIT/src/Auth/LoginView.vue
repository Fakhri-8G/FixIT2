<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      
      <!-- Brand & Header -->
        <header class="auth-header">
          <div class="brand-badge">🔧 FixIT PORTAL</div>
          <h1 class="auth-title">Masuk ke FixIT</h1>
          <p class="auth-subtitle">Masuk dengan akun petugas atau warga sekolah untuk verifikasi pengaduan sarpras.</p>
        </header>

      <!-- Alert Pesan Error -->
      <transition name="fade">
        <div v-if="authError" class="alert-banner">
          <span class="alert-icon">⚠️</span>
          <div class="alert-content">
            <strong>Gagal Otentikasi</strong>
            <p>{{ authError }}</p>
          </div>
        </div>
      </transition>

      <!-- Form Login -->
      <form @submit.prevent="executeLogin" class="auth-form" novalidate>
        
        <!-- Input Identitas (NIP / NISN / Email) -->
        <div class="field-group">
          <label for="identity">Email </label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              id="identity"
              v-model.trim="credentials.identity"
              type="text"
              placeholder="Contoh: 19820310... / admin@gmail.com"
              required
              :disabled="isSubmitting"
            />
          </div>
        </div>

        <!-- Input Password + Toggle Eye -->
        <div class="field-group">
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

        <!-- Checkbox Remember / Keep Active -->
        <div class="options-row">
          <label class="checkbox-container">
            <input type="checkbox" v-model="rememberMe" />
            <span class="checkmark"></span>
            Ingat sesi saya di perangkat ini
          </label>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          class="btn-submit" 
          :disabled="isSubmitting || !isFormValid"
        >
          <span v-if="isSubmitting" class="loader-inline"></span>
          <span>{{ isSubmitting ? 'Verifikasi Hak Akses...' : 'Masuk Portal Sistem' }}</span>
        </button>

      </form>

      <!-- Footer Info -->
        <footer class="auth-footer">
          <RouterLink to="/" class="nav-back">← Kembali ke Feed FixIT Public</RouterLink>
          <p class="help-text">Kendala akun? Hubungi Tim IT / Admin FixIT Sekolah.</p>
        </footer>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../utils/api' // Pake instance axios terpusat, JANGAN panggil axios mentah lagi

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

// Computed validation biar tombol gak bisa diklik kalau form masih kosong
const isFormValid = computed(() => {
  return credentials.identity.length > 3 && credentials.password.length >= 4
})

// Auto-redirect jika token sudah tersimpan
onMounted(() => {
  const existingToken = localStorage.getItem('token')
  if (existingToken) {
    redirectBasedOnRole()
  }
})

// ─── Handler Logic Login ────────────────────────────────────
const executeLogin = async () => {
  if (!isFormValid.value) return

  isSubmitting.value = true
  authError.value = ''

  try {
    // API Call terpusat — backend yang bagus bakal return { token, user } sekaligus!
    const response = await api.post('/login', {
      email: credentials.identity, // disesuaikan dengan key backend
      password: credentials.password
    })

    const { token, user } = response.data

    if (!token) {
      throw new Error('Respon API tidak valid: Token tidak ditemukan.')
    }

    // Storage Management
    localStorage.setItem('token', token)
    if (user) {
      localStorage.setItem('user', JSON.stringify(user))
    }

    // Set header default axios di utils/api.js agar request selanjutnya ter-authorize
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // Arahkan user
    redirectBasedOnRole(user)

  } catch (err) {
    handleErrorResponse(err)
  } finally {
    isSubmitting.value = false
  }
}

// ─── Utility Methods ────────────────────────────────────────
const handleErrorResponse = (err) => {
  if (!err.response) {
    authError.value = 'Tidak dapat terhubung ke server FixIT. Periksa koneksi internet/server.'
    return
  }

  const status = err.response.status
  const serverMsg = err.response.data?.message

  switch (status) {
    case 401:
      authError.value = serverMsg || 'Kredensial salah. Periksa kembali Email dan Kata Sandi!'
      break
    case 404:
      authError.value = 'Akun tidak terdaftar dalam database inventaris sekolah.'
      break
    case 422:
      authError.value = 'Format inputan tidak sesuai standar sistem.'
      break
    case 429:
      authError.value = 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'
      break
    default:
      authError.value = serverMsg || 'Terjadi kesalahan sistem. Hubungi administrator.'
  }
}

const redirectBasedOnRole = (userObj = null) => {
  const user = userObj || JSON.parse(localStorage.getItem('user') || '{}')
  
  // Custom routing sesuai role pengaduan FixIT
  if (user.role === 'admin' || user.role === 'teknisi') {
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
/* Industrial / Utility Palette */
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1a202c; /* Slate Dark */
  background-image: radial-gradient(#2d3748 1px, transparent 1px);
  background-size: 20px 20px;
  padding: 20px;
  font-family: system-ui, -apple-system, sans-serif;
}

.auth-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 36px 32px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border-top: 5px solid #2b6cb0; /* Highlight Accent */
}

/* Header */
.auth-header { margin-bottom: 24px; text-align: left; }
.brand-badge {
  display: inline-block;
  background: #ebf8ff;
  color: #2b6cb0;
  font-size: 11px;
  font-weight: 800;
  padding: 4px 8px;
  border-radius: 4px;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.auth-title { font-size: 22px; font-weight: 800; color: #1a202c; line-height: 1.2; }
.auth-subtitle { font-size: 13px; color: #718096; margin-top: 6px; line-height: 1.4; }

/* Alert */
.alert-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 20px;
  color: #9b2c2c;
}
.alert-icon { font-size: 16px; }
.alert-content strong { display: block; font-size: 13px; }
.alert-content p { font-size: 12px; margin-top: 2px; }

/* Form Elements */
.auth-form { display: flex; flex-direction: column; gap: 18px; }

.field-group { display: flex; flex-direction: column; gap: 6px; }

.label-row { display: flex; justify-content: space-between; align-items: center; }
label { font-size: 12px; font-weight: 700; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
.forgot-link { font-size: 12px; color: #3182ce; text-decoration: none; font-weight: 600; }
.forgot-link:hover { text-decoration: underline; }

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
}

.input-wrapper input {
  width: 100%;
  padding: 10px 38px 10px 36px;
  border: 1.5px solid #cbd5e0;
  border-radius: 6px;
  font-size: 14px;
  color: #2d3748;
  outline: none;
  transition: all 0.2s ease;
}

.input-wrapper input:focus {
  border-color: #3182ce;
  box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
}

.toggle-password {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  padding: 4px;
}

/* Checkbox */
.options-row { display: flex; align-items: center; }
.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #718096;
  cursor: pointer;
  user-select: none;
}

/* Buttons & State */
.btn-submit {
  background: #2b6cb0;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  transition: background 0.2s;
}

.btn-submit:hover:not(:disabled) { background: #2c5282; }
.btn-submit:disabled { background: #a0aec0; cursor: not-allowed; opacity: 0.7; }

/* Inline Spinner Loader */
.loader-inline {
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-bottom-color: transparent;
  border-radius: 50%;
  display: inline-block;
  animation: rotation 1s linear infinite;
}

@keyframes rotation {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Footer */
.auth-footer {
  margin-top: 28px;
  border-top: 1px solid #edf2f7;
  padding-top: 16px;
  text-align: center;
}

.nav-back {
  display: inline-block;
  font-size: 13px;
  color: #4a5568;
  text-decoration: none;
  font-weight: 600;
  margin-bottom: 8px;
}
.nav-back:hover { color: #2b6cb0; }
.help-text { font-size: 11px; color: #a0aec0; }

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>