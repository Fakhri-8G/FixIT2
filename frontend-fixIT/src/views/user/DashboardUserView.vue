<template>
  <div class="dashboard-wrapper">
    <!-- Header Dashboard -->
    <header class="dashboard-header">
      <div>
        <h1 class="title">🛠️ Layanan Pengaduan Fasilitas</h1>
        <p class="subtitle">Pantau dan laporkan kerusakan sarana prasarana sekolah secara realtime.</p>
      </div>
      <button class="btn btn-create" @click="bukaFormLaporan">
        ➕ Buat Laporan Baru
      </button>
    </header>

    <!-- Filter & Toolbar Stats -->
    <section class="toolbar-section">
      <div class="search-input-group">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari lokasi, nama barang, atau pelapor..."
          @input="handleSearch"
        />
      </div>

      <!-- Filter Tabs Status -->
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

    <!-- State Loading -->
    <div v-if="isLoading" class="state-container">
      <div class="spinner"></div>
      <p>Mengambil data laporan fasilitas...</p>
    </div>

    <!-- State Error -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>🚨 {{ errorMessage }}</p>
      <button class="btn-retry" @click="fetchLaporanBarang">Coba Lagi</button>
    </div>

    <!-- Main List / Data Feed -->
    <main v-else-if="laporanList.length > 0" class="reports-feed">
      <article 
        v-for="item in laporanList" 
        :key="item.id" 
        class="report-card"
      >
        <!-- Header Card: Lokasi & Priority -->
        <div class="card-header">
          <span class="location-tag">📍 {{ item.lokasi_ruangan }}</span>
          <span :class="['badge-priority', `priority-${item.tingkat_urgensi}`]">
            {{ formatUrgensi(item.tingkat_urgensi) }}
          </span>
        </div>

        <!-- Thumbnail & Preview -->
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
              <span>👤 Pelapor: <strong>{{ item.nama_pelapor }}</strong></span>
              <span>📅 {{ formatTanggal(item.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Footer Action Card -->
        <div class="card-footer">
          <button class="btn-detail" @click="pilihDetailLaporan(item)">
            Lihat Rekam Jejak →
          </button>
        </div>
      </article>
    </main>

    <!-- Empty State -->
    <div v-else class="state-container empty-box">
      <div class="empty-icon">📂</div>
      <h3>Belum Ada Laporan Terdata</h3>
      <p>Tidak ada pengaduan barang rusak pada kategori atau pencarian ini.</p>
    </div>

    <!-- Modal Quick Detail (Mencegah Redirect Berlebih) -->
    <div v-if="selectedReport" class="modal-backdrop" @click.self="selectedReport = null">
      <div class="modal-card">
        <button class="modal-close" @click="selectedReport = null">✕</button>
        <h2>Detail Kerusakan: {{ selectedReport.nama_barang }}</h2>
        <div class="modal-body">
          <img :src="selectedReport.foto_bukti" class="modal-img" />
          <div class="modal-details">
            <p><strong>Lokasi:</strong> {{ selectedReport.lokasi_ruangan }}</p>
            <p><strong>Status Terakhir:</strong> {{ formatStatus(selectedReport.status) }}</p>
            <p><strong>Deskripsi Lengkap:</strong></p>
            <p class="modal-desc">{{ selectedReport.deskripsi_kerusakan }}</p>
            <p v-if="selectedReport.catatan_teknisi" class="tech-note">
              <strong>Catatan Teknisi:</strong> {{ selectedReport.catatan_teknisi }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()

// ─── State Management ──────────────────────────────────────
const laporanList   = ref([])
const isLoading     = ref(false)
const errorMessage  = ref(null)
const searchQuery   = ref('')
const statusSelected = ref('semua')
const selectedReport = ref(null)

const listStatus = [
  { label: 'Semua', value: 'semua' },
  { label: 'Menunggu', value: 'pending' },
  { label: 'Diproses', value: 'proses' },
  { label: 'Selesai', value: 'selesai' }
]

// ─── Fetch API Laporan Barang ─────────────────────────────
const fetchLaporanBarang = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await api.get('/public/laporan-kerusakan', {
      params: {
        keyword: searchQuery.value.trim() || undefined,
        status: statusSelected.value !== 'semua' ? statusSelected.value : undefined
      }
    })

    // Struktur response Laravel yang fleksibel
    laporanList.value = response.data?.data?.data || response.data?.data || []
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal terhubung ke server pengaduan.'
    console.error('Fetch Error:', err)
  } finally {
    isLoading.value = false
  }
}

// ─── Custom Debounce Handler ───────────────────────────────
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

// ─── Modal & Action Handler ────────────────────────────────
const pilihDetailLaporan = (item) => {
  selectedReport.value = item
}

const bukaFormLaporan = () => {
  router.push('/lapor-kerusakan')
}

// ─── Utility Formatters ────────────────────────────────────
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
    darurat: '🚨 BAHAYA / DARURAT'
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

// ─── Lifecycle Hook ───────────────────────────────────────
onMounted(() => {
  fetchLaporanBarang()
})
</script>

<style scoped>
/* Reset & Full Width Area (Menyesuaikan Dark Mode App.vue) */
.dashboard-wrapper {
  width: 100%;
  box-sizing: border-box;
  font-family: inherit;
  color: var(--text-main, #f8fafc);
  background-color: transparent; /* Ikut background App.vue (#0b0f19) */
}

/* Header Section */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  border-bottom: 1px solid #334155;
  padding-bottom: 16px;
  width: 100%;
}
.title { 
  font-size: 24px; 
  font-weight: 800; 
  color: #ffffff; 
  letter-spacing: -0.5px;
}
.subtitle { 
  color: #94a3b8; 
  font-size: 14px; 
  margin-top: 4px; 
}

.btn-create {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}
.btn-create:hover { 
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}

/* Toolbar & Filters */
.toolbar-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
  width: 100%;
}
@media (min-width: 768px) {
  .toolbar-section {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.search-input-group {
  position: relative;
  flex: 1;
  max-width: 450px;
}
.search-icon { 
  position: absolute; 
  left: 14px; 
  top: 50%; 
  transform: translateY(-50%); 
  color: #64748b;
}
.search-input-group input {
  width: 100%;
  padding: 10px 14px 10px 40px;
  border: 1px solid #334155;
  border-radius: 10px;
  outline: none;
  background: #0f172a;
  color: #ffffff;
  font-size: 14px;
  transition: all 0.2s;
}
.search-input-group input::placeholder {
  color: #64748b;
}
.search-input-group input:focus { 
  border-color: #3b82f6; 
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); 
}

.status-tabs { 
  display: flex; 
  gap: 8px; 
  overflow-x: auto; 
  padding-bottom: 4px;
}
.tab-btn {
  background: #0f172a;
  border: 1px solid #334155;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}
.tab-btn:hover {
  color: #ffffff;
  border-color: #64748b;
}
.tab-btn.active { 
  background: #3b82f6; 
  color: white; 
  border-color: #3b82f6;
}

/* Feed Layout & Cards */
.reports-feed { 
  display: flex; 
  flex-direction: column; 
  gap: 16px; 
  width: 100%;
}

.report-card {
  background: #1e293b; /* Match dengan var(--bg-surface) App.vue */
  border: 1px solid #334155;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
  width: 100%;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.report-card:hover {
  border-color: #475569;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.location-tag { 
  font-size: 12px; 
  font-weight: 700; 
  color: #94a3b8; 
  background: #0f172a; 
  border: 1px solid #334155;
  padding: 4px 10px; 
  border-radius: 6px; 
}

/* Urgensi Badges */
.badge-priority { 
  font-size: 11px; 
  font-weight: 700; 
  padding: 3px 10px; 
  border-radius: 6px; 
}
.priority-rendah { background: rgba(20, 184, 166, 0.15); color: #2dd4bf; border: 1px solid rgba(45, 212, 191, 0.3); }
.priority-sedang { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
.priority-darurat { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }

.card-body { display: flex; gap: 20px; flex-direction: column; }
@media (min-width: 640px) {
  .card-body { flex-direction: row; align-items: flex-start; }
}

.img-wrapper { 
  position: relative; 
  width: 100%; 
  max-width: 200px; 
  height: 130px; 
  flex-shrink: 0; 
}
.img-wrapper img { 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
  border-radius: 10px; 
  background: #0f172a;
}

.status-pill {
  position: absolute;
  bottom: 8px;
  left: 8px;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.4);
}
.status-pending { background: #d97706; }
.status-proses { background: #2563eb; }
.status-selesai { background: #16a34a; }

.content-wrapper { flex: 1; }
.item-name { 
  font-size: 18px; 
  font-weight: 700; 
  color: #ffffff; 
  margin-bottom: 6px; 
}
.description { 
  font-size: 14px; 
  color: #94a3b8; 
  line-height: 1.5; 
  margin-bottom: 12px; 
}

.meta-info { 
  display: flex; 
  gap: 24px; 
  font-size: 12px; 
  color: #64748b; 
}

.card-footer { 
  display: flex; 
  justify-content: flex-end; 
  margin-top: 14px; 
  border-top: 1px solid #334155; 
  padding-top: 12px; 
}
.btn-detail { 
  background: none; 
  border: none; 
  color: #60a5fa; 
  font-weight: 600; 
  font-size: 13px; 
  cursor: pointer; 
  transition: color 0.2s;
}
.btn-detail:hover { 
  color: #93c5fd; 
  text-decoration: underline; 
}

/* States Components */
.state-container { text-align: center; padding: 64px 24px; color: #64748b; width: 100%; }
.error-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; color: #f87171; }
.btn-retry { margin-top: 12px; padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(11, 15, 25, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 200;
}
.modal-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  max-width: 550px;
  width: 100%;
  padding: 24px;
  position: relative;
  color: #ffffff;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}
.modal-close { 
  position: absolute; 
  right: 16px; 
  top: 16px; 
  border: none; 
  background: none; 
  font-size: 18px; 
  color: #94a3b8; 
  cursor: pointer; 
}
.modal-close:hover { color: #ffffff; }
.modal-body { margin-top: 16px; display: flex; flex-direction: column; gap: 12px; }
.modal-img { width: 100%; max-height: 250px; object-fit: cover; border-radius: 10px; }
.tech-note { 
  background: rgba(59, 130, 246, 0.1); 
  border: 1px solid rgba(59, 130, 246, 0.2); 
  padding: 12px; 
  border-radius: 8px; 
  font-size: 13px; 
  margin-top: 8px; 
  color: #93c5fd; 
}
</style>