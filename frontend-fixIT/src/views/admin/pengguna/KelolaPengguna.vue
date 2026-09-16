<template>
  <div class="card-container">
    <!-- Header Section -->
    <div class="card-header">
      <div>
        <h3>Daftar Pengguna</h3>
        <p class="subtitle">Daftar akun pengguna yang terdaftar dan aktivitas pelaporannya.</p>
      </div>
      <div class="user-count-badge">
        <span>Total User: <strong>{{ usersList.length }}</strong></span>
      </div>
    </div>

    <!-- 🆕 Toolbar Search & Filter Role -->
    <div class="toolbar-section">
      <div class="search-input-group">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nama atau email..."
          @input="handleSearch"
        />
      </div>

      <div class="role-tabs">
        <button
          v-for="tab in roleTabs"
          :key="tab.value"
          :class="['tab-btn', { active: roleSelected === tab.value }]"
          @click="gantiRoleFilter(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Alert Notifications -->
    <transition name="fade">
      <div v-if="errorMessage" class="alert alert-error" role="alert">
        {{ errorMessage }}
      </div>
    </transition>

    <!-- Loading State -->
    <div v-if="fetching" class="loading-state">
      <div class="spinner-main"></div>
      <p>Mengambil data pengguna dari server...</p>
    </div>

    <!-- Data Table Section -->
    <div v-else class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 70px;">No</th>
            <th>Pengguna</th>
            <th>Email</th>
            <th>Role</th>
            <th style="text-align: center;">Total Laporan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="usersList.length === 0">
            <td colspan="5" class="empty-state">Belum ada data pengguna.</td>
          </tr>
          <!-- 🆕 Klik row untuk buka detail -->
          <tr
            v-for="(user, index) in usersList"
            :key="user.id"
            @click="openUserDetail(user.id)"
            class="clickable-row"
          >
            <td>{{ index + 1 }}</td>
            <td>
              <div class="user-profile">
                <div class="avatar">{{ getInitials(user.name) }}</div>
                <span class="user-name">{{ user.name }}</span>
              </div>
            </td>
            <td class="email-text">{{ user.email }}</td>
            <td>
              <span class="role-badge" :class="user.role">
                {{ user.role }}
              </span>
            </td>
            <td style="text-align: center;">
              <span class="report-count">{{ user.reports_count }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 🆕 Modal Detail User -->
    <div v-if="selectedUser" class="modal-backdrop" @click.self="selectedUser = null">
      <div class="modal-card">
        <button class="modal-close" @click="selectedUser = null">✕</button>

        <div v-if="loadingDetail" class="loading-state">
          <div class="spinner-main"></div>
          <p>Memuat detail pengguna...</p>
        </div>

        <div v-else-if="selectedUser">
          <div class="user-profile" style="margin-bottom: 16px;">
            <div class="avatar">{{ getInitials(selectedUser.name) }}</div>
            <div>
              <h3>{{ selectedUser.name }}</h3>
              <p class="subtitle">{{ selectedUser.email }} · {{ selectedUser.role }}</p>
            </div>
          </div>

          <h4>Riwayat Laporan ({{ selectedUser.reports_count }})</h4>

          <div v-if="selectedUser.reports?.length === 0" class="empty-state">
            Pengguna ini belum pernah membuat laporan.
          </div>

          <div v-for="report in selectedUser.reports" :key="report.id" class="report-mini-card">
            <strong>{{ report.title }}</strong>
            <p>{{ report.category?.name }} · {{ report.location?.name }}</p>
            <span class="status-pill" :class="`status-${report.status}`">{{ report.status }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../../utils/api';

const fetching = ref(true);
const errorMessage = ref('');
const usersList = ref([]);

// 🆕 State search & filter
const searchQuery = ref('');
const roleSelected = ref('semua');
const roleTabs = [
  { label: 'Semua', value: 'semua' },
  { label: 'Admin', value: 'admin' },
  { label: 'User', value: 'user' },
];

// 🆕 State detail user
const selectedUser = ref(null);
const loadingDetail = ref(false);

const getInitials = (name) => {
  if (!name) return 'U';
  return name.split(' ').map((n) => n[0]).join('').substring(0, 2).toUpperCase();
};

const handleApiError = (error, defaultMsg) => {
  if (error?.response?.data?.message) return error.response.data.message;
  return defaultMsg;
};

const fetchUsers = async () => {
  fetching.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/users', {
      params: {
        keyword: searchQuery.value.trim() || undefined,
        role: roleSelected.value !== 'semua' ? roleSelected.value : undefined,
      },
    });
    usersList.value = response.data?.data || [];
  } catch (error) {
    if (error?.response?.status === 401 || error?.response?.status === 403) {
      errorMessage.value = 'Akses ditolak. Halaman ini hanya untuk Admin.';
    } else {
      errorMessage.value = handleApiError(error, 'Gagal mengambil daftar pengguna.');
    }
  } finally {
    fetching.value = false;
  }
};

// 🆕 Search dengan debounce
let debounceTimer = null;
const handleSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchUsers();
  }, 400);
};

const gantiRoleFilter = (role) => {
  if (roleSelected.value === role) return;
  roleSelected.value = role;
  fetchUsers();
};

// 🆕 Buka detail user
const openUserDetail = async (userId) => {
  selectedUser.value = {}; // buka modal dulu (kosong), sambil loading
  loadingDetail.value = true;

  try {
    const response = await api.get(`/users/${userId}`);
    selectedUser.value = response.data?.data || null;
  } catch (error) {
    alert('Gagal memuat detail pengguna: ' + handleApiError(error, 'Terjadi kesalahan'));
    selectedUser.value = null;
  } finally {
    loadingDetail.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.card-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 1500px;
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  margin: 0 auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.user-count-badge {
  background-color: #f1f5f9;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #475569;
}

.user-count-badge strong {
  color: #6366f1;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 14px 16px;
  font-size: 0.875rem;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #ffffff;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-name {
  font-weight: 600;
  color: #0f172a;
}

.email-text {
  color: #4479c2;
}

.role-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.role-badge.admin {
  background-color: #e0e7ff;
  color: #4338ca;
}

.role-badge.user {
  background-color: #f1f5f9;
  color: #475569;
}

.report-count {
  display: inline-block;
  padding: 2px 10px;
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.8rem;
  color: #0f172a;
}

.empty-state {
  text-align: center;
  color: #94a3b8;
  padding: 30px 0;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 16px;
}

.alert-error {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 0;
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
/* 🆕 Toolbar Search & Filter Role */
.toolbar-section {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.search-input-group {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 320px;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
}
.search-input-group input {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  outline: none;
  box-sizing: border-box;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 0.875rem;
}
.search-input-group input:focus {
  border-color: #6366f1;
}
.role-tabs { display: flex; gap: 8px; }
.tab-btn {
  background: #f1f5f9;
  border: none;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.tab-btn.active { background: #6366f1; color: white; }

/* 🆕 Row yang bisa diklik untuk buka detail */
.clickable-row { cursor: pointer; transition: background 0.15s; }
.clickable-row:hover { background: #f8fafc; }

/* 🆕 Modal Detail User — INI YANG PALING PENTING */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 1000;
}
.modal-card {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  padding: 28px;
  position: relative;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.modal-close {
  position: absolute;
  right: 20px;
  top: 20px;
  border: none;
  background: none;
  font-size: 18px;
  cursor: pointer;
  color: #94a3b8;
}
.modal-card h3 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.modal-card h4 { font-size: 0.875rem; font-weight: 700; margin: 20px 0 12px; color: #334155; }

/* 🆕 Kartu laporan di dalam modal */
.report-mini-card {
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 10px;
  background: #f8fafc;
}
.report-mini-card strong { font-size: 0.875rem; color: #0f172a; }
.report-mini-card p { font-size: 0.8rem; color: #64748b; margin: 4px 0 8px; }

/* 🆕 Badge status laporan */
.status-pill {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 6px;
  color: white;
}
.status-reported { background: #f59e0b; }
.status-verified { background: #8b5cf6; }
.status-processing { background: #3b82f6; }
.status-completed { background: #10b981; }
.status-rejected { background: #ef4444; }

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>