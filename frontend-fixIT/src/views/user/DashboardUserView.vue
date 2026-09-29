<template>
  <div class="dashboard-wrapper">
    <!-- Header Dashboard -->
    <header class="dashboard-header animate-fade-in">
      <div>
        <h1 class="title">🛠️ Layanan Pengaduan Fasilitas</h1>
        <p class="subtitle">Pantau statistik, status, dan rekam jejak kerusakan secara realtime.</p>
      </div>
    </header>

    <!-- 📊 SECTION STATS & LIST RINGKASAN 📊 -->
    <section class="summary-section animate-slide-up">
      <!-- Card Statistik Status -->
      <div class="summary-card">
        <h3>📊 Ringkasan Status Laporan</h3>
        <div v-if="isLoading" class="skeleton-container">
          <div class="skeleton-line" v-for="n in 3" :key="n"></div>
        </div>
        <div v-else class="status-list-grid">
          <div class="stat-item reported">
            <span class="stat-label">⏳ Menunggu Antrean</span>
            <span class="stat-value">{{ countByStatus('reported') }}</span>
          </div>
          <div class="stat-item processing">
            <span class="stat-label">🔨 Dalam Perbaikan</span>
            <span class="stat-value">{{ countByStatus('processing') }}</span>
          </div>
          <div class="stat-item completed">
            <span class="stat-label">✅ Selesai Dikerjakan</span>
            <span class="stat-value">{{ countByStatus('completed') }}</span>
          </div>
        </div>
      </div>

      <!-- Card Sebaran Kerusakan per Ruangan -->
      <div class="summary-card">
        <h3>📍 Sebaran Kerusakan per Ruangan</h3>
        <div v-if="isLoading" class="skeleton-container">
          <div class="skeleton-line" v-for="n in 3" :key="n"></div>
        </div>
        <div v-else-if="locationSummaryList.length > 0" class="location-summary-list">
          <div 
            v-for="(loc, index) in locationSummaryList" 
            :key="index" 
            class="location-row"
          >
            <span class="loc-name">📍 {{ loc.name }}</span>
            <span class="loc-badge">{{ loc.total }} Laporan</span>
          </div>
        </div>
        <div v-else class="summary-empty">Belum ada data ruangan.</div>
      </div>
    </section>

    <!-- Filter & Search Toolbar -->
    <section class="toolbar-section animate-slide-up">
      <div class="search-input-group">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari lokasi atau nama barang..."
          @input="handleSearch"
        />
      </div>

      <div class="status-tabs">
        <button
          v-for="tab in listStatus"
          :key="tab.value"
          :class="['tab-btn', { active: statusSelected === tab.value }]"
          @click="gantiStatusFilter(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>
    </section>

    <!-- State Loading Utama (Skeleton Feed) -->
    <div v-if="isLoading" class="state-container">
      <div class="spinner-pulse"></div>
      <p class="loading-text">Sedang menyinkronkan data terbaru...</p>
    </div>

    <!-- State Error Utama -->
    <div v-else-if="errorMessage" class="state-container error-box animate-scale-up">
      <p>🚨 {{ errorMessage }}</p>
      <button class="btn-retry" @click="fetchLaporanBarang">Coba Lagi</button>
    </div>

    <!-- Feed Laporan -->
    <main v-else-if="laporanList.length > 0" class="reports-feed">
      <article
        v-for="(item, index) in laporanList"
        :key="item.id"
        class="report-card animate-stagger"
        :style="{ '--stagger-index': index }"
      >
        <div class="card-header">
          <span class="location-tag">📍 {{ item.location?.name }}</span>
        </div>

        <div class="card-body">
          <div class="img-wrapper">
            <img
              :src="item.images?.[0]?.image_url || '/placeholder-broken.png'"
              :alt="item.title"
              loading="lazy"
            />
            <span :class="['status-pill', `status-${item.status}`]">
              {{ formatStatus(item.status) }}
            </span>
          </div>

          <div class="content-wrapper">
            <h3 class="item-name">{{ item.title }}</h3>
            <p class="description">{{ item.description }}</p>

            <div class="meta-info">
              <span>👤 Pelapor: <strong>{{ item.user?.name || 'Saya' }}</strong></span>
              <span>📅 {{ formatTanggal(item.created_at) }}</span>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <button class="btn-detail" @click="bukaRekamJejak(item)">
            📜 Lihat Rekam Jejak Perubahan →
          </button>
        </div>
      </article>
    </main>

    <!-- Empty State -->
    <div v-else class="state-container empty-box animate-scale-up">
      <div class="empty-icon bounce-animation">📂</div>
      <h3>Belum Ada Laporan Terdata</h3>
      <p>Tidak ada laporan pengaduan pada filter ini.</p>
    </div>

    <!-- MODAL REKAM JEJAK -->
    <div v-if="selectedReport" class="modal-backdrop animate-fade-in" @click.self="tutupModal">
      <div class="modal-card animate-scale-up">
        <button class="modal-close" @click="tutupModal">✕</button>

        <div class="modal-header">
          <h2>📜 Rekam Jejak Penanganan</h2>
          <p class="modal-sub">
            Barang: <strong>{{ selectedReport.title }}</strong> ({{ selectedReport.location?.name }})
          </p>
        </div>

        <div class="modal-body">
          <div v-if="isLoadingHistory" class="modal-state">
            <div class="spinner-pulse"></div>
            <p>Melacak riwayat petugas...</p>
          </div>

          <div v-else-if="historyError" class="modal-state error-box">
            <p>⚠️ {{ historyError }}</p>
          </div>

          <div v-else-if="historyUpdates.length > 0" class="ecommerce-timeline">
            <div
              v-for="(update, index) in historyUpdates"
              :key="update.id"
              :class="['track-item', { 'is-latest': index === 0 }]"
            >
              <div class="track-node">
                <div class="track-dot pulse-dot"></div>
                <div class="track-line" v-if="index !== historyUpdates.length - 1"></div>
              </div>

              <div class="track-detail">
                <div class="track-top">
                  <span :class="['status-pill', `status-${update.status}`]">
                    {{ formatStatus(update.status) }}
                  </span>
                  <span class="track-date">{{ formatTanggalDetail(update.created_at) }}</span>
                </div>

                <p v-if="update.note" class="track-note">
                  "{{ update.note }}"
                </p>

                <!-- 🖼️ FOTO BUKTI DARI ADMIN / TEKNISI -->
                <div v-if="update.images && update.images.length > 0" class="update-images-container">
                  <span class="proof-label">📸 Foto Bukti Pengerjaan:</span>
                  <div class="proof-grid">
                    <img 
                      v-for="img in update.images" 
                      :key="img.id" 
                      :src="img.image_url" 
                      alt="Bukti Perbaikan" 
                      class="proof-thumb"
                      @click="bukaZoomFoto(img.image_url)"
                      title="Klik untuk memperbesar"
                    />
                  </div>
                </div>

                <div class="track-handler">
                  👤 Petugas/Admin: <strong>{{ update.admin?.name || 'Sistem Otomatis' }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="modal-state empty-box">
            <p>ℹ️ Belum ada riwayat perbaikan atau perubahan status untuk laporan ini.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔍 MODAL ZOOM / PERBESAR FOTO -->
    <div v-if="zoomedImgUrl" class="modal-backdrop zoom-backdrop animate-fade-in" @click.self="tutupZoomFoto">
      <div class="zoom-card animate-scale-up">
        <button class="modal-close zoom-close" @click="tutupZoomFoto">✕</button>
        <img :src="zoomedImgUrl" alt="Zoomed Bukti Perbaikan" class="zoomed-image" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()

const laporanList      = ref([])
const rawAllLaporan    = ref([])
const isLoading        = ref(false)
const errorMessage     = ref(null)
const searchQuery      = ref('')
const statusSelected   = ref('semua')

const selectedReport   = ref(null)
const historyUpdates   = ref([])
const isLoadingHistory = ref(false)
const historyError     = ref(null)

// State Zoom Foto
const zoomedImgUrl     = ref(null)

const listStatus = [
  { label: 'Semua',        value: 'semua' },
  { label: 'Dilaporkan',   value: 'reported' },
  { label: 'Diverifikasi', value: 'verified' },
  { label: 'Diproses',     value: 'processing' },
  { label: 'Selesai',      value: 'completed' },
  { label: 'Ditolak',      value: 'rejected' }
]

const fetchLaporanBarang = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await api.get('/reports', {
      params: {
        keyword: searchQuery.value.trim() || undefined,
        status: statusSelected.value !== 'semua' ? statusSelected.value : undefined
      }
    })

    const fetchedData = response.data?.data?.data || []
    laporanList.value = fetchedData

    if (!searchQuery.value && statusSelected.value === 'semua') {
      rawAllLaporan.value = fetchedData
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengambil daftar laporan.'
  } finally {
    isLoading.value = false
  }
}

const countByStatus = (statusName) => {
  const sourceData = rawAllLaporan.value.length > 0 ? rawAllLaporan.value : laporanList.value
  return sourceData.filter(i => i.status === statusName).length
}

const locationSummaryList = computed(() => {
  const sourceData = rawAllLaporan.value.length > 0 ? rawAllLaporan.value : laporanList.value
  const locationCounts = {}
  
  sourceData.forEach(item => {
    const loc = item.location?.name || 'Lainnya'
    locationCounts[loc] = (locationCounts[loc] || 0) + 1
  })

  return Object.keys(locationCounts)
    .map(name => ({ name, total: locationCounts[name] }))
    .sort((a, b) => b.total - a.total)
})

const bukaRekamJejak = async (report) => {
  selectedReport.value = report
  historyUpdates.value = []
  isLoadingHistory.value = true
  historyError.value = null

  try {
    const response = await api.get(`/reports/${report.id}/updates`)
    historyUpdates.value = response.data?.data || []
  } catch (err) {
    if (err.response?.status === 403) {
      historyError.value = 'Anda tidak memiliki akses ke riwayat laporan ini.'
    } else {
      historyError.value = err.response?.data?.message || 'Gagal mengambil riwayat status.'
    }
  } finally {
    isLoadingHistory.value = false
  }
}

const tutupModal = () => {
  selectedReport.value = null
  historyUpdates.value = []
}

// Handler Zoom Foto
const bukaZoomFoto = (url) => {
  zoomedImgUrl.value = url
}

const tutupZoomFoto = () => {
  zoomedImgUrl.value = null
}

let debounceTimer = null
const handleSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchLaporanBarang()
  }, 400)
}

const gantiStatusFilter = (status) => {
  if (statusSelected.value === status) return
  statusSelected.value = status
  fetchLaporanBarang()
}

const formatStatus = (status) => {
  const map = {
    reported: '⏳ Dilaporkan',
    verified: '🔍 Diverifikasi',
    processing: '🔨 Dalam Perbaikan',
    completed: '✅ Selesai Dikerjakan',
    rejected: '❌ Ditolak'
  }
  return map[status] || status
}

const formatTanggal = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatTanggalDetail = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchLaporanBarang()
})
</script>

<style scoped>
.dashboard-wrapper {
  width: 100%;
  box-sizing: border-box;
  color: #f8fafc;
  background-color: transparent;
}

/* Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  border-bottom: 1px solid #334155;
  padding-bottom: 16px;
}
.title { font-size: 24px; font-weight: 800; color: #ffffff; }
.subtitle { color: #94a3b8; font-size: 14px; margin-top: 4px; }

.btn-create {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white; border: none; padding: 10px 20px;
  border-radius: 10px; font-weight: 600; cursor: pointer;
  transition: all 0.2s ease;
}
.btn-create:hover { transform: translateY(-2px); }

/* 📊 CHARTS STYLING 📊 */
.charts-section {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 28px;
}
@media (min-width: 768px) {
  .charts-section { grid-template-columns: 1fr 1fr; }
}

.chart-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 14px;
  padding: 20px;
}
.chart-card h3 {
  font-size: 15px;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 16px;
}
.chart-container {
  position: relative;
  height: 220px;
  width: 100%;
}
.chart-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #64748b;
  font-size: 13px;
}
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary-card {
  background: #1e293b; /* Sesuaikan warna card */
  border-radius: 12px;
  padding: 1.25rem;
  border: 1px solid #334155;
}

.summary-card h3 {
  font-size: 1rem;
  margin-bottom: 1rem;
  color: #f8fafc;
}

.status-list-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #0f172a;
}

.stat-value {
  font-weight: bold;
  font-size: 1.1rem;
  color: #fff;
}

.location-summary-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 160px;
  overflow-y: auto;
}

.location-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: #0f172a;
  border-radius: 6px;
  font-size: 0.9rem;
}

.loc-badge {
  background: #2563eb;
  color: white;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.summary-loading, .summary-empty {
  text-align: center;
  color: #94a3b8;
  font-size: 0.9rem;
  padding: 1rem 0;
}
/* Styling Rekam Jejak Ala E-Commerce / Ekspedisi */
.ecommerce-timeline {
  display: flex;
  flex-direction: column;
  padding: 0.5rem 0;
}

.track-item {
  display: flex;
  gap: 1rem;
  position: relative;
  padding-bottom: 1.5rem;
}

.track-item:last-child {
  padding-bottom: 0;
}

/* Node, Dot, dan Garis Kurir */
.track-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.track-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #64748b; /* Warna default titik */
  border: 2px solid #1e293b;
  z-index: 2;
  margin-top: 4px;
}

/* Status paling baru (index 0) dikasih warna hijau khas paket sukses/proses aktif */
.is-latest .track-dot {
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
  width: 14px;
  height: 14px;
}

.track-line {
  width: 2px;
  background: #334155;
  position: absolute;
  top: 16px;
  bottom: -16px;
  left: 50%;
  transform: translateX(-50%);
}

/* Kotak Detail Tracking */
.track-detail {
  flex: 1;
  background: #0f172a;
  border: 1px solid #334155;
  padding: 0.85rem 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.is-latest .track-detail {
  border-color: #22c55e55;
  background: #0f172a88;
}

.track-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.track-date {
  font-size: 0.75rem;
  color: #94a3b8;
}

.track-note {
  font-size: 0.85rem;
  color: #e2e8f0;
  margin: 0.2rem 0;
  background: #1e293b;
  padding: 0.5rem;
  border-radius: 6px;
  border-left: 3px solid #3b82f6;
}

.track-handler {
  font-size: 0.75rem;
  color: #94a3b8;
}

/* --- ANIMASI GARIS PANJANG / TRACK LINE BERJALAN --- */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleUp {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

@keyframes pulseGlow {
  0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
  70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
  100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

@keyframes bounceSlow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
.animate-slide-up { animation: slideUp 0.4s ease-out forwards; }
.animate-scale-up { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

@keyframes growLine {
  from {
    height: 0;
    opacity: 0;
  }
  to {
    height: 100%;
    opacity: 1;
  }
}

@keyframes dropDot {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  60% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Modifikasi bagian Node & Garis Kurir */
.track-item {
  display: flex;
  gap: 1rem;
  position: relative;
  padding-bottom: 1.5rem;
}

.track-item:last-child {
  padding-bottom: 0;
}

.track-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

/* Titik / Dot dikasih efek pop-up berurutan */
.track-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #64748b;
  border: 2px solid #1e293b;
  z-index: 2;
  margin-top: 4px;
  animation: dropDot 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.is-latest .track-dot {
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
  width: 14px;
  height: 14px;
}

/* ANIMASI GARIS PANJANG NYA DISINI, BRO! */
.track-line {
  width: 2px;
  background: linear-gradient(to bottom, #22c55e, #334155); /* Efek gradasi jalur aktif */
  position: absolute;
  top: 18px;
  bottom: -16px;
  left: 50%;
  transform: translateX(-50%);
  animation: growLine 0.6s ease-out forwards;
  transform-origin: top;
}

/* Kotak Detail Tracking dikasih efek geser dikit biar manis */
.track-detail {
  flex: 1;
  background: #0f172a;
  border: 1px solid #334155;
  padding: 0.85rem 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  animation: slideUp 0.4s ease-out forwards;
}

.is-latest .track-detail {
  border-color: #22c55e55;
  background: #0f172a88;
}

.track-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.track-date {
  font-size: 0.75rem;
  color: #94a3b8;
}

.track-note {
  font-size: 0.85rem;
  color: #e2e8f0;
  margin: 0.2rem 0;
  background: #1e293b;
  padding: 0.5rem;
  border-radius: 6px;
  border-left: 3px solid #3b82f6;
}

.track-handler {
  font-size: 0.75rem;
  color: #94a3b8;
}
/* Toolbar */
.toolbar-section { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
@media (min-width: 768px) {
  .toolbar-section { flex-direction: row; justify-content: space-between; }
}

.search-input-group { position: relative; flex: 1; max-width: 450px; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; }
.search-input-group input {
  width: 100%; padding: 10px 14px 10px 40px; border: 1px solid #334155;
  border-radius: 10px; background: #0f172a; color: #ffffff; font-size: 14px;
}

.status-tabs { display: flex; gap: 8px; overflow-x: auto; }
.tab-btn {
  background: #0f172a; border: 1px solid #334155; padding: 8px 16px;
  border-radius: 20px; font-size: 13px; font-weight: 600; color: #94a3b8; cursor: pointer;
}
.tab-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }

/* Feed & Cards */
.reports-feed { display: flex; flex-direction: column; gap: 16px; }
.report-card {
  background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 20px;
}

.card-header { display: flex; justify-content: space-between; margin-bottom: 14px; }
.location-tag { font-size: 12px; font-weight: 700; color: #94a3b8; background: #0f172a; padding: 4px 10px; border-radius: 6px; }

.badge-priority { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; }
.priority-rendah { background: rgba(20, 184, 166, 0.15); color: #2dd4bf; }
.priority-sedang { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.priority-darurat { background: rgba(239, 68, 68, 0.15); color: #f87171; }

.card-body { display: flex; gap: 20px; flex-direction: column; }
@media (min-width: 640px) { .card-body { flex-direction: row; } }

.img-wrapper { position: relative; width: 100%; max-width: 180px; height: 120px; flex-shrink: 0; }
.img-wrapper img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

.status-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px; color: white; }
.status-pending { background: #d97706; }
.status-proses { background: #2563eb; }
.status-selesai { background: #16a34a; }

.content-wrapper { flex: 1; }
.item-name { font-size: 18px; font-weight: 700; color: #ffffff; margin-bottom: 6px; }
.description { font-size: 14px; color: #94a3b8; margin-bottom: 12px; }
.meta-info { display: flex; gap: 24px; font-size: 12px; color: #64748b; }

.card-footer { display: flex; justify-content: flex-end; margin-top: 14px; border-top: 1px solid #334155; padding-top: 12px; }
.btn-detail { background: none; border: none; color: #60a5fa; font-weight: 600; font-size: 13px; cursor: pointer; }

/* Modal & Timeline */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(11, 15, 25, 0.8);
  backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center;
  padding: 16px; z-index: 200;
}
.modal-card {
  background: #1e293b; border: 1px solid #334155; border-radius: 16px;
  max-width: 600px; width: 100%; padding: 24px; position: relative; color: #ffffff;
  max-height: 85vh; display: flex; flex-direction: column;
}
.modal-close { position: absolute; right: 16px; top: 16px; border: none; background: none; font-size: 18px; color: #94a3b8; cursor: pointer; }
.modal-header { margin-bottom: 16px; border-bottom: 1px solid #334155; padding-bottom: 12px; }
.modal-sub { font-size: 13px; color: #94a3b8; margin-top: 4px; }

.modal-body { overflow-y: auto; flex: 1; }
.modal-state { text-align: center; padding: 32px 16px; color: #94a3b8; }

.timeline { display: flex; flex-direction: column; gap: 16px; position: relative; padding-left: 20px; }
.timeline::before {
  content: ''; position: absolute; left: 6px; top: 8px; bottom: 8px; width: 2px; background: #334155;
}
.timeline-item { position: relative; }
.timeline-dot {
  position: absolute; left: -20px; top: 4px; width: 10px; height: 10px;
  border-radius: 50%; background: #3b82f6; border: 2px solid #1e293b;
}
.timeline-content {
  background: #0f172a; border: 1px solid #334155; padding: 12px; border-radius: 10px;
}
.timeline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.timeline-date { font-size: 11px; color: #64748b; }
.timeline-note { font-size: 13px; color: #cbd5e1; margin-bottom: 8px; font-style: italic; }
.timeline-admin { font-size: 11px; color: #94a3b8; border-top: 1px dashed #334155; padding-top: 6px; }

.spinner {
  width: 24px; height: 24px; border: 3px solid #334155; border-top-color: #3b82f6;
  border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 8px;
}

/* Styling Grid Foto Bukti Pengerjaan di Timeline */
.update-images-container {
  margin-top: 0.5rem;
  background: #1e293b;
  padding: 0.6rem 0.8rem;
  border-radius: 6px;
  border: 1px dashed #475569;
}

.proof-label {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-bottom: 0.4rem;
  font-weight: 500;
}

.proof-grid {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.proof-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #334155;
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease;
}

.proof-thumb:hover {
  transform: scale(1.05);
  border-color: #3b82f6;
}

/* Modal Zoom / Perbesar Foto */
.zoom-backdrop {
  background: rgba(0, 0, 0, 0.85) !important;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.zoom-card {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  padding: 1rem;
}

.zoomed-image {
  max-width: 100%;
  max-height: 85vh;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
  display: block;
  margin: 0 auto;
}

.zoom-close {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>