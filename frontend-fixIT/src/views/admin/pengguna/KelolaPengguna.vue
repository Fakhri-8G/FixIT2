<template>
  <div class="card-container">
    <!-- Header Section -->
    <div class="card-header">
      <div>
        <h3>Daftar Pengguna</h3>
        <p class="subtitle">Daftar akun pengguna yang terdaftar dan aktivitas pelaporannya.</p>
      </div>
      <div class="user-count-badge">
        <span>Total User: <strong>{{ uniqueUsers.length }}</strong></span>
      </div>
    </div>

    <!-- Alert Notifications -->
    <transition name="fade">
      <div v-if="errorMessage" class="alert alert-error" role="alert">
        {{ errorMessage }}
      </div>
    </transition>

    <!-- Loading State Pas Ambil Data -->
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
          <tr v-if="uniqueUsers.length === 0">
            <td colspan="5" class="empty-state">Belum ada data pengguna.</td>
          </tr>
          <tr v-for="(user, index) in uniqueUsers" :key="user.id">
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
              <span class="report-count">{{ user.total_reports }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../../utils/api'; // Menggunakan helper axios instans terpusat

const fetching = ref(true);
const errorMessage = ref('');
const reportsData = ref([]);

// Ekstraksi data pengguna unik & hitung total laporannya dari `GET /api/reports`
const uniqueUsers = computed(() => {
  const usersMap = new Map();

  reportsData.value.forEach((report) => {
    if (report.user && report.user.id) {
      const u = report.user;
      if (!usersMap.has(u.id)) {
        usersMap.set(u.id, {
          id: u.id,
          name: u.name || 'Tanpa Nama',
          email: u.email || '-',
          role: u.role || 'user',
          total_reports: 1
        });
      } else {
        const existing = usersMap.get(u.id);
        existing.total_reports += 1;
      }
    }
  });

  return Array.from(usersMap.values());
});

// Generator Inisial Avatar
const getInitials = (name) => {
  if (!name) return 'U';
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
};

const handleApiError = (error, defaultMsg) => {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }
  return defaultMsg;
};

// Fetch Laporan (Khusus Role Admin, response include relation 'user')
const fetchReportsAndUsers = async () => {
  fetching.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/reports');
    // Laravel ApiResponse trait mengembalikan struktur: { success: true, data: [...] }
    reportsData.value = response.data?.data || response.data || [];
  } catch (error) {
    errorMessage.value = handleApiError(error, 'Gagal mengambil daftar pengguna.');
  } finally {
    fetching.value = false;
  }
};

onMounted(() => {
  fetchReportsAndUsers();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.card-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 900px;
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
  color: #64748b;
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>