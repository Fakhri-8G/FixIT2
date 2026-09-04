<template>
  <div class="card-container">
    <div class="card-header">
      <h3>Tambah Kategori</h3>
      <p class="subtitle">Buat kategori baru untuk mengelompokkan data.</p>
    </div>
    
    <form @submit.prevent="handleSubmit" class="form-body">
      <div class="form-group">
        <label for="name">Nama Kategori</label>
        <div class="input-wrapper">
          <input 
            id="name"
            v-model="form.name" 
            type="text" 
            placeholder="Contoh: Elektronik, Pakaian..."
            :class="{ 'is-invalid': errors.name }"
          />
        </div>
        <transition name="fade">
          <span v-if="errors.name" class="error-text">
            {{ errors.name[0] }}
          </span>
        </transition>
      </div>

      <button type="submit" class="btn-submit" :disabled="loading">
        <span v-if="loading" class="spinner"></span>
        <span>{{ loading ? 'Menyimpan...' : 'Simpan Kategori' }}</span>
      </button>

      <transition name="fade">
        <div v-if="successMessage" class="alert alert-success">
          {{ successMessage }}
        </div>
      </transition>
      <transition name="fade">
        <div v-if="errorMessage" class="alert alert-error">
          {{ errorMessage }}
        </div>
      </transition>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useRouter, RouterLink } from 'vue-router'
import api                   from '../../../utils/api'

const form = reactive({ name: '' });
const errors = ref({});
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};
  successMessage.value = '';
  errorMessage.value = '';

  // Ambil token dari localStorage (sesuaikan nama key token pas user login)
  const token = localStorage.getItem('token'); 

  if (!token) {
    errorMessage.value = 'belum login / token gak ada!';
    loading.value = false;
    return;
  }

  try {
    // wajib kirim Headers Authorization Bearer
    const response = await api.post('/categories', form);


    form.name = '';
    successMessage.value = response.data.message || 'Kategori berhasil ditambahkan.';
  } catch (error) {
    if (error.response) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors || {};
      } else if (error.response.status === 401 || error.response.status === 403) {
        errorMessage.value = 'Akses ditolak! Akun bukan Admin atau session udah abis.';
      } else {
        errorMessage.value = error.response.data.message || 'Terjadi kesalahan pada server.';
      }
    } else {
      errorMessage.value = 'Gagal konek ke server. Cek koneksi / CORS!';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.card-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 440px;
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 20px 25px -5px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
  margin: 20px auto;
  transition: all 0.3s ease;
}

.card-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.025em;
}

.card-header .subtitle {
  margin: 6px 0 20px 0;
  font-size: 0.875rem;
  color: #64748b;
}

.form-body {
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
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 16px;
  font-family: inherit;
  font-size: 0.925rem;
  color: #1e293b;
  background-color: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  outline: none;
  box-sizing: border-box;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.input-wrapper input::placeholder {
  color: #94a3b8;
}

.input-wrapper input:focus {
  background-color: #ffffff;
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.input-wrapper input.is-invalid {
  border-color: #f43f5e;
  background-color: #fff5f5;
}

.input-wrapper input.is-invalid:focus {
  box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.1);
}

.error-text {
  color: #e11d48;
  font-size: 0.8rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-submit {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 12px 20px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #ffffff;
  font-family: inherit;
  font-size: 0.925rem;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
  transition: all 0.2s ease;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  box-shadow: none;
}

.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
}

.alert-success {
  background-color: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.alert-error {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Spinner Animation */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Vue Transitions */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>