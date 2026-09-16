<template>
  <div class="my-reports-container">
    <!-- Header Page -->
    <header class="page-header">
      <div class="header-title">
        <h2>Dashboard Laporan Saya</h2>
        <p>Pantau progres dan kelola laporan kerusakan yang telah kamu ajukan.</p>
      </div>
      
      <!-- Filter & Search Bar Modern -->
      <div class="filter-wrapper">
        <div class="search-input-group">
          <span class="search-icon">🔍</span>
          <input 
            v-model="filters.keyword" 
            type="text" 
            placeholder="Cari judul atau lokasi..." 
            @input="debouncedFetch"
          />
        </div>
        <select v-model="filters.status" @change="fetchMyReports" class="status-select">
          <option value="">Semua Status</option>
          <option value="reported">Reported</option>
          <option value="verified">Verified</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </header>

    <!-- State Loading -->
    <div v-if="loading" class="state-card">
      <div class="spinner"></div>
      <p>Mengambil data laporan kamu...</p>
    </div>

    <!-- State Kosong -->
    <div v-else-if="reports.length === 0" class="state-card">
      <div class="empty-icon">📂</div>
      <h3>Belum Ada Laporan</h3>
      <p>Kamu belum pernah membuat laporan kerusakan apapun.</p>
    </div>

    <!-- Grid List Laporan -->
    <div v-else class="reports-grid">
      <div v-for="report in reports" :key="report.id" class="report-card">
        <div>
          <div class="card-top">
            <span :class="['badge-status', report.status]">
              <span class="status-dot"></span>
              {{ report.status }}
            </span>
            <span class="report-id">#{{ report.id }}</span>
          </div>

          <h3 class="report-title">{{ report.title }}</h3>
          <p class="report-desc">{{ report.description }}</p>

          <div class="report-meta">
            <div class="meta-item">
              <span class="meta-icon">📍</span>
              <span>{{ report.location?.name || '-' }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-icon">🏷️</span>
              <span>{{ report.category?.name || '-' }}</span>
            </div>
          </div>

          <!-- Thumbnail Foto Preview -->
          <div v-if="report.images && report.images.length" class="thumb-list">
            <div v-for="img in report.images" :key="img.id" class="thumb-wrapper" @click="openDetail(report.id)">
              <img :src="img.image_url || img.image" alt="Bukti" />
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="card-actions">
          <button class="btn-detail" @click="openDetail(report.id)">
            <span>Detail & Log</span>
            <span>→</span>
          </button>
          
          <!-- Hapus Laporan (Hanya jika status 'reported') -->
          <button 
            v-if="report.status === 'reported'" 
            class="btn-delete" 
            @click="deleteReport(report.id)"
            title="Hapus Laporan"
          >
            🗑️
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Detail & History -->
    <div v-if="selectedReport" class="modal-backdrop" @click.self="selectedReport = null">
      <div class="modal-content">
        <button class="modal-close" @click="selectedReport = null">✕</button>
        
        <div class="modal-header">
          <span :class="['badge-status', selectedReport.status]">
            <span class="status-dot"></span>
            {{ selectedReport.status }}
          </span>
          <span class="report-id">#{{ selectedReport.id }}</span>
        </div>

        <h3 class="modal-title">{{ selectedReport.title }}</h3>
        <p class="modal-desc">{{ selectedReport.description }}</p>

        <!-- Dynamic Relasi -->
        <div class="modal-info-grid">
          <div class="info-box">
            <small>Kategori</small>
            <strong>{{ selectedReport.category?.name || '-' }}</strong>
          </div>
          <div class="info-box">
            <small>Lokasi</small>
            <strong>{{ selectedReport.location?.name || '-' }}</strong>
          </div>
        </div>

        <!-- Foto & Hapus Per-Gambar -->
        <div class="section-block">
          <h4>Foto Bukti Terlampir ({{ selectedReport.images?.length || 0 }})</h4>
          <div class="image-grid">
            <div v-for="img in selectedReport.images" :key="img.id" class="image-item">
              <img :src="img.image_url" alt="Foto Laporan" />
              <button class="btn-remove-img" @click="deleteSingleImage(img.id)">
                Hapus Foto
              </button>
            </div>
          </div>
          <p v-if="!selectedReport.images?.length" class="empty-text">Tidak ada foto terlampir.</p>
        </div>

        <!-- Log Updates Status dari Admin -->
        <div class="section-block">
          <h4>Riwayat Penanganan Admin</h4>
          <div v-if="selectedReport.updates && selectedReport.updates.length" class="timeline">
            <div v-for="log in selectedReport.updates" :key="log.id" class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-body">
                <div class="timeline-header">
                  <span :class="['badge-status', log.status]">{{ log.status }}</span>
                  <small class="log-time">{{ formatDate(log.created_at) }}</small>
                </div>
                <p v-if="log.note" class="log-note">"{{ log.note }}"</p>
                <span class="log-admin">Oleh: {{ log.admin?.name || 'Admin' }}</span>
              </div>
            </div>
          </div>
          <p v-else class="empty-text">Belum ada pembaruan dari tim admin.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const reports = ref([]);
const selectedReport = ref(null);
const loading = ref(false);

const filters = ref({
  keyword: '',
  status: ''
});

const token = localStorage.getItem('token') || '';
const api = axios.create({
  baseURL: 'http://10.10.11.20:8000/api',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const fetchMyReports = async () => {
  loading.value = true;
  try {
    const res = await api.get('/reports', { params: filters.value });
    reports.value = res.data.data || [];
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengambil data laporan.');
  } finally {
    loading.value = false;
  }
};

let timer = null;
const debouncedFetch = () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    fetchMyReports();
  }, 400);
};

const openDetail = async (id) => {
  try {
    const res = await api.get(`/reports/${id}`);
    selectedReport.value = res.data.data;
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memuat detail laporan.');
  }
};

const deleteReport = async (id) => {
  if (!confirm('Yakin mau hapus laporan ini? Action ini gak bisa dikembalikan.')) return;
  try {
    const res = await api.delete(`/reports/${id}`);
    alert(res.data.message || 'Laporan berhasil dihapus.');
    fetchMyReports();
  } catch (err) {
    alert(err.response?.data?.message || 'Laporan gagal dihapus.');
  }
};

const deleteSingleImage = async (imageId) => {
  if (!confirm('Hapus foto ini dari laporan?')) return;
  try {
    const res = await api.delete(`/report-images/${imageId}`);
    alert(res.data.message || 'Gambar berhasil dihapus.');
    selectedReport.value.images = selectedReport.value.images.filter(img => img.id !== imageId);
    fetchMyReports();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus foto.');
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short'
  });
};

onMounted(() => {
  fetchMyReports();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.my-reports-container {
  max-width: 2500px;
  margin: auto;
  padding: 40px 24px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #0f172a;
  background-color: #f8fafc;
  min-height: 100vh;
  border-radius: 25px;
}

/* Header Section */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 32px;
  gap: 20px;
  flex-wrap: wrap;
}

.header-title h2 {
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.5px;
  margin: 0 0 6px 0;
}

.header-title p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

/* Filter Bar Modern */
.filter-wrapper {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.search-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 14px;
  font-size: 13px;
  opacity: 0.6;
}

.search-input-group input {
  padding: 10px 14px 10px 38px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  outline: none;
  font-size: 14px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  transition: all 0.2s;
  width: 220px;
}

.search-input-group input:focus, .status-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.status-select {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  outline: none;
  font-size: 14px;
  color: #334155;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  transition: all 0.2s;
}

/* Grid Layout Laporan */
/* Card Wrapper Modern (Tinggal ganti class .report-card lama pake ini) */
.report-card {
  position: relative;
  background: linear-gradient(180deg, #ffffff 0%, #06294B 100%);
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Garis Aksen Warna di Top Card (Bikin Gak Monoton) */
.report-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: #cbd5e1; /* Default Color */
  transition: background 0.3s ease;
}

/* Dinamis Warna Line Atas Mengikuti Status (Opsional, pasang style ini) */
.report-card:has(.badge-status.reported)::before { background: #94a3b8; }
.report-card:has(.badge-status.verified)::before { background: #0284c7; }
.report-card:has(.badge-status.processing)::before { background: #f59e0b; }
.report-card:has(.badge-status.completed)::before { background: #10b981; }
.report-card:has(.badge-status.rejected)::before { background: #f43f5e; }

/* Interactive Hover Effect */
.report-card:hover {
  transform: translateY(-6px);
  border-color: #cbd5e1;
  box-shadow: 0 16px 28px -6px rgba(15, 23, 42, 0.08), 0 6px 10px -4px rgba(15, 23, 42, 0.04);
}

/* Penyesuaian Detail Text & Layout */
.report-title {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  margin: 4px 0 8px 0;
  line-height: 1.4;
}

.report-desc {
  font-size: 13.5px;
  color: #475569;
  margin: 0 0 16px 0;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Meta Pills Box (Kategori & Lokasi) */
.report-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  background: rgba(241, 245, 249, 0.7);
  backdrop-filter: blur(4px);
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid #f1f5f9;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}

/* Badge Status Lebih Stand Out */
.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  text-transform: uppercase;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 30px;
  letter-spacing: 0.5px;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);
}

.badge-status.reported { background: #f1f5f9; color: #475569; }
.badge-status.verified { background: #e0f2fe; color: #0369a1; }
.badge-status.processing { background: #fef3c7; color: #b45309; }
.badge-status.completed { background: #dcfce7; color: #15803d; }
.badge-status.rejected { background: #ffe4e6; color: #be123c; }

/* Photo Preview Wrapper */
.thumb-list {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  padding-top: 4px;
}

.thumb-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s ease;
}

.thumb-wrapper:hover {
  transform: scale(1.1) rotate(2deg);
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

/* Action Area Divider */
.card-actions {
  display: flex;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px dashed #e2e8f0;
}
.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
}

/* Status Badges */
.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  text-transform: uppercase;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 0.5px;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.badge-status.reported { background: #f1f5f9; color: #64748b; }
.badge-status.verified { background: #e0f2fe; color: #0284c7; }
.badge-status.processing { background: #fef3c7; color: #d97706; }
.badge-status.completed { background: #dcfce7; color: #16a34a; }
.badge-status.rejected { background: #ffe4e6; color: #e11d48; }

/* Thumbnail List */
.thumb-list {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
}

.thumb-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: transform 0.2s;
}

.thumb-wrapper:hover {
  transform: scale(1.08);
}

.thumb-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Action Buttons */
.card-actions {
  display: flex;
  gap: 10px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.btn-detail {
  width: 30%;
  background: #4f46e5;
  color: #ffffff;
  border: none;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  justify-content: space-between;
  align-items: center;
  transition: background 0.2s;
}

.btn-detail:hover {
  background: #4338ca;
}

.btn-delete {
  background: #fff1f2;
  border: 1px solid #ffe4e6;
  color: #e11d48;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-delete:hover {
  background: #ffe4e6;
  color: #be123c;
}

/* State Loading & Empty */
.state-card {
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
  padding: 60px 20px;
  text-align: center;
  color: #64748b;
}

.empty-icon { font-size: 40px; margin-bottom: 12px; }
.state-card h3 { color: #1e293b; margin: 0 0 6px 0; font-size: 18px; }

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Modal Design Modern */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99;
  padding: 20px;
}

.modal-content {
  background: #ffffff;
  border-radius: 20px;
  max-width: 580px;
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
  padding: 32px;
  position: relative;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-close {
  position: absolute;
  top: 24px;
  right: 24px;
  border: none;
  background: #f1f5f9;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 14px;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:hover { background: #e2e8f0; color: #0f172a; }

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.modal-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
.modal-desc { color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }

.modal-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 24px;
}

.info-box {
  background: #f8fafc;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.info-box small { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
.info-box strong { font-size: 14px; color: #1e293b; }

.section-block { margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.section-block h4 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 14px 0; }

.image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 10fr)); gap: 20px; }
.image-item { display: flex; flex-direction: column; gap: 6px; }
.image-item img { width: 100%; height: 90px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; }
.btn-remove-img { background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; font-size: 11px; font-weight: 600; padding: 4px; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
.btn-remove-img:hover { background: #ffe4e6; }

/* Timeline Log Admin */
.timeline { border-left: 2px solid #e2e8f0; padding-left: 18px; margin-left: 6px; display: flex; flex-direction: column; gap: 18px; }
.timeline-item { position: relative; }
.timeline-dot { position: absolute; left: -24px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #6366f1; border: 2px solid #ffffff; box-shadow: 0 0 0 2px #e0e7ff; }
.timeline-header { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.log-time { font-size: 12px; color: #94a3b8; }
.log-note { margin: 6px 0; font-size: 13px; color: #334155; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border-left: 3px solid #6366f1; }
.log-admin { font-size: 11px; color: #64748b; font-weight: 500; }
.empty-text { font-size: 13px; color: #94a3b8; margin: 0; }
</style>