<template>
  <div class="card-container">
    <div class="card-header">
      <h3>Tambah Lokasi</h3>
      <p class="subtitle">Buat lokasi baru untuk pengelompokan titik kerusakan/aset.</p>
    </div>
    
    <form @submit.prevent="handleSubmit" class="form-body">
      <!-- Input Nama Lokasi -->
      <div class="form-group">
        <label for="name">Nama Lokasi</label>
        <div class="input-wrapper">
          <input 
            id="name"
            v-model="form.name" 
            type="text" 
            placeholder="Contoh: Kelas XII RPL 1, Lab Komputer 2..."
            :class="{ 'is-invalid': errors.name }"
          />
        </div>
        <transition name="fade">
          <span v-if="errors.name" class="error-text">
            {{ errors.name[0] }}
          </span>
        </transition>
      </div>

      <!-- Input Deskripsi Lokasi -->
      <div class="form-group">
        <label for="description">Deskripsi <span class="text-optional">(Opsional)</span></label>
        <div class="input-wrapper">
          <textarea 
            id="description"
            v-model="form.description" 
            rows="3"
            placeholder="Contoh: Ruang kelas jurusan RPL di lantai 2..."
            :class="{ 'is-invalid': errors.description }"
          ></textarea>
        </div>
        <transition name="fade">
          <span v-if="errors.description" class="error-text">
            {{ errors.description[0] }}
          </span>
        </transition>
      </div>

      <!-- Submit & Action Buttons -->
      <div class="button-group">
        <RouterLink to="/lokasi" class="btn-cancel">
          Batal
        </RouterLink>
        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span>{{ loading ? 'Menyimpan...' : 'Simpan Lokasi' }}</span>
        </button>
      </div>

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
import { useRouter, RouterLink } from 'vue-router';
import api from '../../../utils/api'; // Menggunakan axios instance kamu

const router = useRouter();

const form = reactive({ 
  name: '',
  description: '' 
});

const errors = ref({});
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};
  successMessage.value = '';
  errorMessage.value = '';

  const token = localStorage.getItem('token'); 

  if (!token) {
    errorMessage.value = 'Belum login atau token tidak ditemukan!';
    loading.value = false;
    return;
  }

  try {
    const response = await api.post('/locations', form);

    // Reset Form
    form.name = '';
    form.description = '';
    
    successMessage.value = response.data.message || 'Lokasi berhasil ditambahkan.';
    
    // Redirect otomatis ke list lokasi setelah 1.5 detik (opsional)
    setTimeout(() => {
      router.push('/lokasi');
    }, 1500);

  } catch (error) {
    if (error.response) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors || {};
      } else if (error.response.status === 401 || error.response.status === 403) {
        errorMessage.value = 'Akses ditolak! Akun bukan Admin atau session sudah habis.';
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
  max-width: 1000px;
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

.text-optional {
  font-size: 0.75rem;
  font-weight: 400;
  color: #94a3b8;
}

.input-wrapper input,
.input-wrapper textarea {
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
  resize: vertical;
}

.input-wrapper input::placeholder,
.input-wrapper textarea::placeholder {
  color: #94a3b8;
}

.input-wrapper input:focus,
.input-wrapper textarea:focus {
  background-color: #ffffff;
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.input-wrapper input.is-invalid,
.input-wrapper textarea.is-invalid {
  border-color: #f43f5e;
  background-color: #fff5f5;
}

.input-wrapper input.is-invalid:focus,
.input-wrapper textarea.is-invalid:focus {
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

.button-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn-cancel {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px 20px;
  background-color: #f1f5f9;
  color: #475569;
  font-family: inherit;
  font-size: 0.925rem;
  font-weight: 600;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background-color: #e2e8f0;
  color: #1e293b;
}

.btn-submit {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  max-width: 250px;
  gap: 8px;
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