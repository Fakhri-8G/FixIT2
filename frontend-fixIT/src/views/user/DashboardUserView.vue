<template>
  <div class="dashboard-wrapper">
    <!-- Header Dashboard -->
    <header class="dashboard-header">
      <div>
        <h1 class="title">🛠️ Layanan Pengaduan Fasilitas</h1>
        <p class="subtitle">Pantau statistik, status, dan rekam jejak kerusakan secara realtime.</p>
      </div>
      
    </header>

    <!-- 📊 SECTION CHARTS & GRAPHS 📊 -->
    <section class="charts-section">
      <div class="chart-card">
        <h3>📊 Ringkasan Status Laporan</h3>
        <div class="chart-container">
          <Doughnut v-if="!isLoading" :data="doughnutChartData" :options="chartOptions" />
          <div v-else class="chart-loading">Memuat grafik...</div>
        </div>
      </div>

      <div class="chart-card">
        <h3>📍 Sebaran Kerusakan per Ruangan</h3>
        <div class="chart-container">
          <Bar v-if="!isLoading" :data="barChartData" :options="barOptions" />
          <div v-else class="chart-loading">Memuat grafik...</div>
        </div>
      </div>
    </section>

    <!-- Filter & Search Toolbar -->
    <section class="toolbar-section">
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

    <!-- State Loading Utama -->
    <div v-if="isLoading" class="state-container">
      <div class="spinner"></div>
      <p>Mengambil data laporan...</p>
    </div>

    <!-- State Error Utama -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>🚨 {{ errorMessage }}</p>
      <button class="btn-retry" @click="fetchLaporanBarang">Coba Lagi</button>
    </div>

    <!-- Feed Laporan -->
    <main v-else-if="laporanList.length > 0" class="reports-feed">
      <article 
        v-for="item in laporanList" 
        :key="item.id" 
        class="report-card"
      >
        <div class="card-header">
          <span class="location-tag">📍 {{ item.lokasi_ruangan }}</span>
          <span :class="['badge-priority', `priority-${item.tingkat_urgensi}`]">
            {{ formatUrgensi(item.tingkat_urgensi) }}
          </span>
        </div>

        <div class="card-body">
          <div class="img-wrapper">
            <img 
              :src="item.foto_bukti || '/placeholder-broken.png'" 
              :alt="item.nama_barang"
              loading="lazy" 
            />
            <span :class="['status-pill', `status-${item.status}`]">
              {{ formatStatus(item.status) }}
            </span>
          </div>

          <div class="content-wrapper">
            <h3 class="item-name">{{ item.nama_barang }}</h3>
            <p class="description">{{ item.deskripsi_kerusakan }}</p>

            <div class="meta-info">
              <span>👤 Pelapor: <strong>{{ item.nama_pelapor || 'Saya' }}</strong></span>
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
    <div v-else class="state-container empty-box">
      <div class="empty-icon">📂</div>
      <h3>Belum Ada Laporan Terdata</h3>
      <p>Tidak ada laporan pengaduan pada filter ini.</p>
    </div>

    <!-- MODAL REKAM JEJAK -->
    <div v-if="selectedReport" class="modal-backdrop" @click.self="tutupModal">
      <div class="modal-card">
        <button class="modal-close" @click="tutupModal">✕</button>
        
        <div class="modal-header">
          <h2>📜 Rekam Jejak Penanganan</h2>
          <p class="modal-sub">Barang: <strong>{{ selectedReport.nama_barang }}</strong> ({{ selectedReport.lokasi_ruangan }})</p>
        </div>

        <div class="modal-body">
          <div v-if="isLoadingHistory" class="modal-state">
            <div class="spinner"></div>
            <p>Mengambil riwayat dari server...</p>
          </div>

          <div v-else-if="historyError" class="modal-state error-box">
            <p>⚠️ {{ historyError }}</p>
          </div>

          <div v-else-if="historyUpdates.length > 0" class="timeline">
            <div 
              v-for="update in historyUpdates" 
              :key="update.id" 
              class="timeline-item"
            >
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span :class="['status-pill', `status-${update.status}`]">
                    {{ formatStatus(update.status) }}
                  </span>
                  <span class="timeline-date">{{ formatTanggalDetail(update.created_at) }}</span>
                </div>
                
                <p v-if="update.catatan || update.note" class="timeline-note">
                  "{{ update.catatan || update.note }}"
                </p>
                
                <div class="timeline-admin">
                  🛠️ Diproses oleh Admin: <strong>{{ update.admin?.name || update.admin?.nama || 'Petugas System' }}</strong>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../../utils/api' // Sesuaikan path axios instance lu

// --- Import Chart.js Dependencies ---
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js'
import { Doughnut, Bar } from 'vue-chartjs'

// Register Module Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const router = useRouter()

// ─── State Management ──────────────────────────────────────
const laporanList      = ref([])
const rawAllLaporan    = ref([]) // Untuk menyimpan semua data mentah buat Chart
const isLoading        = ref(false)
const errorMessage     = ref(null)
const searchQuery      = ref('')
const statusSelected   = ref('semua')

// State Modal Rekam Jejak
const selectedReport   = ref(null)
const historyUpdates   = ref([])
const isLoadingHistory = ref(false)
const historyError     = ref(null)

const listStatus = [
  { label: 'Semua', value: 'semua' },
  { label: 'Menunggu', value: 'pending' },
  { label: 'Diproses', value: 'proses' },
  { label: 'Selesai', value: 'selesai' }
]

// ─── Fetch List Laporan Utama ──────────────────────────────
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

    const fetchedData = response.data?.data?.data || response.data?.data || []
    laporanList.value = fetchedData
    
    // Simpan data master jika pencarian kosong untuk kalkulasi grafik
    if (!searchQuery.value && statusSelected.value === 'semua') {
      rawAllLaporan.value = fetchedData
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengambil daftar laporan.'
  } finally {
    isLoading.value = false
  }
}

// ─── COMPUTED DATA FOR CHARTS ──────────────────────────────
// 1. Doughnut Chart: Ringkasan Status
const doughnutChartData = computed(() => {
  const sourceData = rawAllLaporan.value.length > 0 ? rawAllLaporan.value : laporanList.value
  
  const pendingCount = sourceData.filter(i => i.status === 'pending').length
  const prosesCount  = sourceData.filter(i => i.status === 'proses').length
  const selesaiCount = sourceData.filter(i => i.status === 'selesai').length

  return {
    labels: ['Menunggu Antrean', 'Dalam Perbaikan', 'Selesai Dikerjakan'],
    datasets: [
      {
        backgroundColor: ['#d97706', '#2563eb', '#16a34a'],
        hoverBackgroundColor: ['#f59e0b', '#3b82f6', '#22c55e'],
        borderWidth: 0,
        data: [pendingCount, prosesCount, selesaiCount]
      }
    ]
  }
})

// 2. Bar Chart: Kerusakan per Ruangan
const barChartData = computed(() => {
  const sourceData = rawAllLaporan.value.length > 0 ? rawAllLaporan.value : laporanList.value
  
  // Hitung frekuensi laporan berdasarkan lokasi_ruangan
  const locationCounts = {}
  sourceData.forEach(item => {
    const loc = item.lokasi_ruangan || 'Lainnya'
    locationCounts[loc] = (locationCounts[loc] || 0) + 1
  })

  return {
    labels: Object.keys(locationCounts),
    datasets: [
      {
        label: 'Jumlah Kerusakan',
        backgroundColor: '#3b82f6',
        borderRadius: 6,
        data: Object.values(locationCounts)
      }
    ]
  }
})

// Chart Options (Dark Theme Supported)
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: '#94a3b8', font: { size: 12 } }
    }
  }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
    y: { 
      ticks: { color: '#94a3b8', stepSize: 1 }, 
      grid: { color: '#334155' },
      beginAtZero: true 
    }
  }
}

// ─── Fetch Rekam Jejak ─────────────────────────────────────
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

// ─── Filter & Handlers ─────────────────────────────────────
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

const bukaFormLaporan = () => {
  router.push('/lapor-kerusakan')
}

// ─── Formatters ────────────────────────────────────────────
const formatStatus = (status) => {
  const map = {
    pending: '⏳ Menunggu Antrean',
    proses: '🔨 Dalam Perbaikan',
    selesai: '✅ Selesai Dikerjakan'
  }
  return map[status] || status
}

const formatUrgensi = (urgensi) => {
  const map = {
    rendah: 'Biasa',
    sedang: 'Sedang',
    darurat: '🚨 DARURAT'
  }
  return map[urgensi] || urgensi
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
@keyframes spin { to { transform: rotate(360deg); } }
</style>