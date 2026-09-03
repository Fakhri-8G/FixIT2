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
import api from '../utils/api'

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
/* Reset & Theme Variables Layout */
.dashboard-wrapper {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px;
  font-family: system-ui, -apple-system, sans-serif;
  color: #2d3748;
}

/* Header Section */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  border-bottom: 2px solid #edf2f7;
  padding-bottom: 16px;
}
.title { font-size: 26px; font-weight: 800; color: #1a202c; }
.subtitle { color: #718096; font-size: 14px; margin-top: 4px; }

.btn-create {
  background: #2b6cb0;
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-create:hover { background: #2c5282; }

/* Toolbar & Filters */
.toolbar-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
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
  max-width: 400px;
}
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); }
.search-input-group input {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #cbd5e0;
  border-radius: 8px;
  outline: none;
}
.search-input-group input:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49,130,206,0.2); }

.status-tabs { display: flex; gap: 8px; overflow-x: auto; }
.tab-btn {
  background: #edf2f7;
  border: none;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #4a5568;
  cursor: pointer;
}
.tab-btn.active { background: #3182ce; color: white; }

/* Feed Layout (Flex/List Style, bukan Pure Grid Film) */
.reports-feed { display: flex; flex-direction: column; gap: 16px; }

.report-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.location-tag { font-size: 12px; font-weight: 700; color: #4a5568; background: #f7fafc; padding: 4px 8px; border-radius: 4px; }

/* Urgensi Badges */
.badge-priority { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
.priority-rendah { background: #e6fffa; color: #234e52; }
.priority-sedang { background: #feebc8; color: #744210; }
.priority-darurat { background: #fed7d7; color: #9b2c2c; }

.card-body { display: flex; gap: 16px; flex-direction: column; }
@media (min-width: 640px) {
  .card-body { flex-direction: row; }
}

.img-wrapper { position: relative; width: 100%; max-width: 160px; height: 110px; flex-shrink: 0; }
.img-wrapper img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }

.status-pill {
  position: absolute;
  bottom: 6px;
  left: 6px;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  color: white;
}
.status-pending { background: #dd6b20; }
.status-proses { background: #3182ce; }
.status-selesai { background: #38a169; }

.content-wrapper { flex: 1; }
.item-name { font-size: 16px; font-weight: 700; color: #2d3748; margin-bottom: 6px; }
.description { font-size: 13px; color: #718096; line-height: 1.4; margin-bottom: 12px; }

.meta-info { display: flex; gap: 16px; font-size: 12px; color: #a0aec0; }

.card-footer { display: flex; justify-content: flex-end; margin-top: 12px; border-top: 1px dashed #edf2f7; padding-top: 8px; }
.btn-detail { background: none; border: none; color: #3182ce; font-weight: 600; font-size: 12px; cursor: pointer; }

/* States Components */
.state-container { text-align: center; padding: 48px; color: #a0aec0; }
.error-box { background: #fff5f5; border-radius: 8px; color: #e53e3e; }
.btn-retry { margin-top: 12px; padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 4px; cursor: pointer; }

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 100;
}
.modal-card {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  padding: 20px;
  position: relative;
}
.modal-close { position: absolute; right: 16px; top: 16px; border: none; background: none; font-size: 18px; cursor: pointer; }
.modal-body { margin-top: 16px; display: flex; flex-direction: column; gap: 12px; }
.modal-img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; }
.tech-note { background: #f7fafc; padding: 8px; border-left: 3px solid #3182ce; font-size: 12px; margin-top: 8px; }
</style>