<template>
  <div class="card-container">
    <!-- Header Section -->
    <div class="card-header">
      <div>
        <h3>Daftar Kategori</h3>
        <p class="subtitle">Kelola dan atur semua kategori data di sini.</p>
      </div>
      <div class="header-actions">
        <span class="badge-count">{{ categoryCountLabel }}</span>
        <RouterLink to="/TambahKategori" class="nav-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Tambah Baru
        </RouterLink>
      </div>
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

    <!-- Table Section -->
    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th width="80">No</th>
            <th>Nama Kategori</th>
            <th>Tanggal Dibuat</th>
            <th width="140" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading State -->
          <tr v-if="loading">
            <td colspan="4" class="text-center py-8">
              <div class="spinner-container">
                <div class="spinner-main"></div>
                <span>Memuat data kategori...</span>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="categories.length === 0">
            <td colspan="4" class="text-center py-8">
              <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                <p>Belum ada kategori yang ditambahkan.</p>
              </div>
            </td>
          </tr>

          <!-- Data List -->
          <tr v-else v-for="(category, index) in categories" :key="category.id" class="table-row">
            <td class="font-medium text-muted">{{ index + 1 }}</td>
            <td class="font-semibold text-dark">{{ category.name }}</td>
            <td class="text-muted">{{ formatDate(category.created_at) }}</td>
            <td>
              <div class="action-buttons">
                <!-- Edit Button -->
                <RouterLink :to="'/edit-kategori/' + category.id" class="btn-icon btn-edit" title="Edit Kategori">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </RouterLink>
                <!-- Delete Button -->
                <button
                  class="btn-icon btn-delete"
                  type="button"
                  title="Hapus Kategori"
                  :disabled="deletingId === category.id"
                  @click="handleDelete(category.id)"
                >
                  <span v-if="deletingId === category.id" class="spinner-small"></span>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['open-create-modal', 'edit-category']);

// --- State ---------------------------------------------------------------
const categories = ref([]);
const loading = ref(true);
const deletingId = ref(null);
const successMessage = ref('');
const errorMessage = ref('');

// --- Constants -------------------------------------------------------------
const API_ENDPOINT = 'http://localhost:8000/api/categories';
const DATE_LOCALE = 'id-ID';
const DATE_FORMAT_OPTIONS = { year: 'numeric', month: 'short', day: 'numeric' };
const MESSAGE_AUTO_HIDE_MS = 4000;

// --- Computed --------------------------------------------------------------
const categoryCountLabel = computed(() => `${categories.value.length} Kategori`);

// --- Helpers -----------------------------------------------------------------
const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json'
    }
  };
};

const resetMessages = () => {
  successMessage.value = '';
  errorMessage.value = '';
};

const extractErrorMessage = (error, fallback) =>
  error?.response?.data?.message || fallback;

let hideMessageTimer = null;
const autoHideMessages = () => {
  clearTimeout(hideMessageTimer);
  hideMessageTimer = setTimeout(resetMessages, MESSAGE_AUTO_HIDE_MS);
};

// --- API Actions -----------------------------------------------------------
const fetchCategories = async () => {
  loading.value = true;
  try {
    const response = await axios.get(API_ENDPOINT, getAuthHeaders());
    // Tangani pembungkusan dari Trait ApiResponse
    if (response.data && Array.isArray(response.data.data)) {
      categories.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      categories.value = response.data;
    } else {
      categories.value = [];
    }
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Gagal mengambil data kategori.');
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id) => {
  if (!confirm('Apakah yakin ingin menghapus kategori ini?')) return;

  deletingId.value = id;
  resetMessages();

  try {
    const response = await axios.delete(`${API_ENDPOINT}/${id}`, getAuthHeaders());
    successMessage.value = response.data.message || 'Kategori berhasil dihapus.';
    categories.value = categories.value.filter((c) => c.id !== id);
    autoHideMessages();
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Tidak dapat menghapus kategori ini.');
    autoHideMessages();
  } finally {
    deletingId.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const parsed = new Date(dateString);
  if (Number.isNaN(parsed.getTime())) return '-';
  return parsed.toLocaleDateString(DATE_LOCALE, DATE_FORMAT_OPTIONS);
};

onMounted(fetchCategories);

defineExpose({ fetchCategories });
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.card-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 900px;
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 20px 25px -5px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
  margin: 20px auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.card-header h3 {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.025em;
}

.card-header .subtitle {
  margin: 4px 0 0 0;
  font-size: 0.875rem;
  color: #64748b;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.badge-count {
  background-color: #f1f5f9;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 20px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #ffffff;
  font-family: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
  transition: all 0.2s ease;
}

.nav-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
}

/* Table Styling */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
}

.custom-table th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
}

.custom-table th:first-child {
  border-top-left-radius: 8px;
}

.custom-table th:last-child {
  border-top-right-radius: 8px;
}

.custom-table td {
  padding: 16px;
  font-size: 0.9rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table-row {
  transition: background-color 0.2s ease;
}

.table-row:hover {
  background-color: #f8fafc;
}

/* Text Utility */
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.text-dark { color: #1e293b; }
.text-muted { color: #64748b; }
.text-center { text-align: center; }
.py-8 { padding-top: 32px !important; padding-bottom: 32px !important; }

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-edit {
  background-color: #eff6ff;
  color: #2563eb;
}

.btn-edit:hover {
  background-color: #dbeafe;
  color: #1d4ed8;
}

.btn-delete {
  background-color: #fef2f2;
  color: #e11d48;
}

.btn-delete:hover:not(:disabled) {
  background-color: #fee2e2;
  color: #be123c;
}

.btn-delete:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Alert Styling */
.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 16px;
}

.alert-success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Spinners & Empty State */
.spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #64748b;
  font-size: 0.875rem;
}

.spinner-main {
  width: 24px;
  height: 24px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-small {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(225, 29, 72, 0.3);
  border-top-color: #e11d48;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>