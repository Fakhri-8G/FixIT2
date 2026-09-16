<template>
  <div class="profile-container">
    <!-- Alert Success/Error Global -->
    <div 
      v-if="statusMessage.text" 
      :class="['alert', statusMessage.type === 'success' ? 'alert-success' : 'alert-error']"
    >
      <span>{{ statusMessage.text }}</span>
      <button @click="statusMessage.text = ''" class="close-btn">&times;</button>
    </div>

    <!-- Section 1: Form Profil (Nama & Email) -->
    <div class="card">
      <h2 class="card-title">Edit Informasi Profil</h2>
      
      <form @submit.prevent="handleUpdateProfile" class="form-group">
        <div class="field">
          <label class="label">Nama Lengkap</label>
          <input 
            v-model="profileForm.name" 
            type="text" 
            class="input"
            :class="{'input-error': profileErrors.name}"
            required 
          />
          <p v-if="profileErrors.name" class="error-text">{{ profileErrors.name[0] }}</p>
        </div>

        <div class="field">
          <label class="label">Alamat Email</label>
          <input 
            v-model="profileForm.email" 
            type="email" 
            class="input"
            :class="{'input-error': profileErrors.email}"
            required 
          />
          <p v-if="profileErrors.email" class="error-text">{{ profileErrors.email[0] }}</p>
        </div>

        <button 
          type="submit" 
          :disabled="loadingProfile"
          class="btn btn-primary"
        >
          {{ loadingProfile ? 'Menyimpan...' : 'Simpan Profil' }}
        </button>
      </form>
    </div>

    <!-- Section 2: Form Ganti Password -->
    <div class="card">
      <h2 class="card-title">Ganti Password</h2>
      
      <form @submit.prevent="handleUpdatePassword" class="form-group">
        <div class="field">
          <label class="label">Password Saat Ini</label>
          <input 
            v-model="passwordForm.current_password" 
            type="password" 
            class="input"
            :class="{'input-error': passwordErrors.current_password}"
            required 
          />
          <p v-if="passwordErrors.current_password" class="error-text">{{ passwordErrors.current_password[0] }}</p>
        </div>

        <div class="field">
          <label class="label">Password Baru</label>
          <input 
            v-model="passwordForm.password" 
            type="password" 
            class="input"
            :class="{'input-error': passwordErrors.password}"
            required 
          />
          <p v-if="passwordErrors.password" class="error-text">{{ passwordErrors.password[0] }}</p>
        </div>

        <div class="field">
          <label class="label">Konfirmasi Password Baru</label>
          <input 
            v-model="passwordForm.password_confirmation" 
            type="password" 
            class="input"
            required 
          />
        </div>

        <button 
          type="submit" 
          :disabled="loadingPassword"
          class="btn btn-warning"
        >
          {{ loadingPassword ? 'Memproses...' : 'Perbarui Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../utils/api';
// State Data & Form
const profileForm = reactive({ name: '', email: '' })
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })

// State Handling Error Validasi 422
const profileErrors = ref({})
const passwordErrors = ref({})

// State UI Loading & Status Alert
const loadingProfile = ref(false)
const loadingPassword = ref(false)
const statusMessage = reactive({ text: '', type: 'success' })

const setAlert = (text, type = 'success') => {
  statusMessage.text = text
  statusMessage.type = type
}

// 1. Fetch Data Profil
const fetchProfile = async () => {
  try {
    const res = await api.get('/profile')
    profileForm.name = res.data.data.name
    profileForm.email = res.data.data.email
  } catch (err) {
    setAlert(err.response?.data?.message || 'Gagal memuat profil.', 'error')
  }
}

// 2. Handler Update Profil
const handleUpdateProfile = async () => {
  loadingProfile.value = true
  profileErrors.value = {}
  statusMessage.text = ''

  try {
    const res = await api.put('/profile', profileForm)
    setAlert(res.data.message || 'Profil berhasil diperbarui.', 'success')
  } catch (err) {
    if (err.response && err.response.status === 422) {
      profileErrors.value = err.response.data.errors || {}
    } else {
      setAlert('Gagal mengubah data profil.', 'error')
    }
  } finally {
    loadingProfile.value = false
  }
}

// 3. Handler Update Password
const handleUpdatePassword = async () => {
  loadingPassword.value = true
  passwordErrors.value = {}
  statusMessage.text = ''

  try {
    const res = await api.put('/profile/password', passwordForm)
    setAlert(res.data.message || 'Password berhasil diperbarui.', 'success')
    
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (err) {
    if (err.response && err.response.status === 422) {
      passwordErrors.value = err.response.data.errors || {}
    } else {
      setAlert('Gagal memperbarui password.', 'error')
    }
  } finally {
    loadingPassword.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>

<style scoped>
.profile-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 32px;
  font-family: system-ui, -apple-system, sans-serif;
}

/* Alert Styling */
.alert {
  padding: 16px;
  border-radius: 8px;
  border: 1px solid;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.alert-success {
  background-color: #f0fdf4;
  color: #15803d;
  border-color: #bbf7d0;
}

.alert-error {
  background-color: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  color: inherit;
}

/* Card Styling */
.card {
  background-color: #ffffff;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.card-title {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 16px;
  margin-top: 0;
}

/* Form Styling */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.input-error {
  border-color: #ef4444 !important;
}

.error-text {
  font-size: 12px;
  color: #ef4444;
  margin: 0;
}

/* Button Styling */
.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s, opacity 0.2s;
  align-self: flex-start;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #4f46e5;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background-color: #4338ca;
}

.btn-warning {
  background-color: #d97706;
  color: #ffffff;
}

.btn-warning:hover:not(:disabled) {
  background-color: #b45309;
}
</style>