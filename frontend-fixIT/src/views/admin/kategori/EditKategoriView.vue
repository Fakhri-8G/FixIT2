<template>
  <div class="card-container">
    <!-- Header Section -->
    <div class="card-header">
      <div>
        <h3>Edit Kategori</h3>
        <p class="subtitle">Ubah nama kategori sesuai dokumentasi FixIT Backend.</p>
      </div>
      <RouterLink :to="{ name: 'kategori' }" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
      </RouterLink>
    </div>

    <!-- Alert Notifications -->
    <transition name="fade">
      <div v-if="errorMessage" class="alert alert-error" role="alert">
        {{ errorMessage }}
      </div>
    </transition>
    <transition name="fade">
      <div v-if="successMessage" class="alert alert-success" role="status">
        {{ successMessage }}
      </div>
    </transition>

    <!-- Loading State Pas Ambil Data -->
    <div v-if="fetching" class="loading-state">
      <div class="spinner-main"></div>
      <p>Mengambil data kategori dari server...</p>
    </div>

    <!-- Form Section -->
    <form v-else @submit.prevent="handleSubmit" class="form-container">
      <div class="form-group">
        <label for="categoryName" class="form-label">
          Nama Kategori <span class="required-asterisk">*</span>
        </label>
        <input
          id="categoryName"
          v-model.trim="form.name"
          type="text"
          class="form-input"
          :class="{ 'input-error': validationError }"
          placeholder="Contoh: Elektronik, Mebel, Dll."
          :disabled="submitting"
          autocomplete="off"
        />
        <span v-if="validationError" class="error-text">{{ validationError }}</span>
      </div>

      <!-- Action Buttons -->
      <div class="form-actions">
        <button type="submit" class="btn-submit" :disabled="submitting">
          <span v-if="submitting" class="spinner-small"></span>
          <span>{{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

// ID Kategori dari params route (/admin/kategori/edit/:id)
const categoryId = route.params.id;

const form = reactive({
  name: ''
});

const fetching = ref(true);
const submitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const validationError = ref('');

// --- Setup Base URL & Header sesuai Dokumen FixIT --------------------------
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://10.10.11.20:8000';
const ENDPOINT = `${API_BASE_URL}/api/categories/${categoryId}`;

const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    headers: {
      Authorization: token ? `Bearer ${token}` : '',
      Accept: 'application/json'
    }
  };
};

// Extractor pesan error biar sesuai struktur { status: false, message: "...", errors: {...} }
const handleApiError = (error, defaultMsg) => {
  if (error?.response?.data) {
    const res = error.response.data;
    // Cek error validasi laravel
    if (res.errors) {
      const firstKey = Object.keys(res.errors)[0];
      return res.errors[firstKey][0];
    }
    if (res.message) return res.message;
  }
  return defaultMsg;
};

// --- Fetch Detail Kategori (GET /api/categories/{id}) ----------------------
// --- Fetch Detail Kategori (Akalin pake GET /api/categories) ----------------------
const fetchCategoryDetail = async () => {
  fetching.value = true;
  errorMessage.value = '';

  try {
    // 1. Tembak list SEMUA kategori
    const response = await axios.get(`${API_BASE_URL}/api/categories`, getAuthHeaders());
    const categoriesList = response.data?.data || [];

    // 2. Cari kategori yang ID-nya cocok sama URL params
    const selectedCategory = categoriesList.find(
      (item) => item.id === parseInt(categoryId)
    );

    if (selectedCategory) {
      form.name = selectedCategory.name;
    } else {
      errorMessage.value = 'Data kategori dengan ID tersebut tidak ditemukan.';
    }
  } catch (error) {
    errorMessage.value = handleApiError(error, 'Gagal mengambil data kategori.');
  } finally {
    fetching.value = false;
  }
};

// --- Submit Update (PUT /api/categories/{id}) ------------------------------
const handleSubmit = async () => {
  validationError.value = '';
  errorMessage.value = '';
  successMessage.value = '';

  // Validasi lokal sebelum hit API
  if (!form.name) {
    validationError.value = 'Nama kategori wajib diisi!';
    return;
  }

  submitting.value = true;

  try {
    // Payloads sesuai Dokumen: { "name": "Elektronik" }
    const response = await axios.put(
      ENDPOINT,
      { name: form.name },
      getAuthHeaders()
    );

    successMessage.value = response.data?.message || 'Kategori berhasil diperbarui.';

    // Redirect balik ke kelola kategori setelah 1 detik
    setTimeout(() => {
      router.push({ name: 'kategori' });
    }, 1000);

  } catch (error) {
    errorMessage.value = handleApiError(error, 'Gagal memperbarui kategori.');
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  if (!categoryId) {
    errorMessage.value = 'ID Kategori tidak valid.';
    fetching.value = false;
    return;
  }
  fetchCategoryDetail();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.card-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 1000px;
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  margin: 40px auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.card-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.card-header .subtitle {
  margin: 4px 0 0 0;
  font-size: 0.85rem;
  color: #64748b;
}

.btn-back {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background-color: #f1f5f9;
  color: #475569;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background-color: #e2e8f0;
  color: #1e293b;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

.required-asterisk {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  font-family: inherit;
  font-size: 0.9rem;
  color: #0f172a;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-input.input-error {
  border-color: #ef4444;
}

.error-text {
  font-size: 0.8rem;
  color: #ef4444;
  font-weight: 500;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  margin-top: 10px;
}

.btn-cancel {
  padding: 10px 18px;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background-color: #f1f5f9;
  color: #1e293b;
}

.btn-submit {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #ffffff;
  font-family: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
  transition: all 0.2s ease;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 16px;
}

.alert-success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 0;
  gap: 12px;
  color: #64748b;
  font-size: 0.875rem;
}

.spinner-main {
  width: 26px;
  height: 26px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-small {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>