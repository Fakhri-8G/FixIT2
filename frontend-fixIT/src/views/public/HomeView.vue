<template>
  <div class="dashboard-wrapper">
    <br><br><br>
    <!-- 1. HERO SECTION (Khusus Guest / Belum Login) -->
    <section class="hero-section">
      <div class="hero-content">
        <span class="hero-badge">📢 Portal Layanan Publik</span>
        <h1 class="hero-title">Pantau & Laporkan Kerusakan Fasilitas</h1>
        <p class="hero-subtitle">
          Temukan masalah di area Sekolah SMK Assalaam Bandung? Pantau status perbaikan secara transparan di bawah ini. Login untuk mulai buat laporan baru.
        </p>
      </div>
    </section>

    <br><br><br><br><br>

    <!-- 3. HOW IT WORKS (Edukasi Cara Kerja App) -->
    <section class="how-it-works">
      <h2 class="section-title">Bagaimana Cara Melaporkan?</h2>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-num">1</div>
          <div class="step-text">
            <strong>Masuk Akun</strong>
            <p>Login atau buat akun terverifikasi kamu.</p>
          </div>
        </div>
        <div class="step-card">
          <div class="step-num">2</div>
          <div class="step-text">
            <strong>Foto & Deskripsikan</strong>
            <p>Upload foto kerusakan dan sertakan lokasinya.</p>
          </div>
        </div>
        <div class="step-card">
          <div class="step-num">3</div>
          <div class="step-text">
            <strong>Pantau Progress</strong>
            <p>Tim Admin akan merespons & memperbaiki.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. TOOLBAR & FILTERS -->
    <div class="toolbar-section">

      <div class="status-tabs">
        <button 
          v-for="status in listStatus" 
          :key="status.value"
          class="tab-btn"
          :class="{ active: statusSelected === status.value }"
          @click="gantiStatusFilter(status.value)"
        >
          {{ status.label }}
        </button>
      </div>
    </div>

    <!-- 5. FEED LAPORAN PUBLIK -->
    <div v-if="isLoading" class="state-container">
      <p>🔄 Memuat data pengaduan publik...</p>
    </div>

    <div v-else-if="errorMessage" class="state-container error-box">
      <p>⚠️ {{ errorMessage }}</p>
      <button class="btn-retry" @click="fetchLaporanBarang">Coba Lagi</button>
    </div>


    <div v-else class="reports-feed">
      <div 
        v-for="item in laporanList" 
        :key="item.id" 
        class="report-card"
      >
        <div class="card-header">
          <span class="location-tag">📍 {{ item.lokasi || 'Lokasi umum' }}</span>
          <span :class="['badge-priority', `priority-${item.urgensi}`]">
            {{ formatUrgensi(item.urgensi) }}
          </span>
        </div>

        <div class="card-body">
          <div class="img-wrapper">
            <img :src="item.foto_url || 'https://via.placeholder.com/300x200?text=No+Image'" :alt="item.nama_barang" />
            <span :class="['status-pill', `status-${item.status}`]">
              {{ formatStatus(item.status) }}
            </span>
          </div>

          <div class="content-wrapper">
            <h3 class="item-name">{{ item.nama_barang }}</h3>
            <p class="description">{{ item.deskripsi }}</p>
            
            <div class="meta-info">
              <span>👤 {{ item.pelapor || 'Pelapor' }}</span>
              <span>📅 {{ formatTanggal(item.created_at) }}</span>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <button class="btn-detail" @click="pilihDetailLaporan(item)">
            Lihat Detail →
          </button>
        </div>
      </div>
    </div>

    <!-- 6. MODAL DETAIL (Public Read-Only) -->
    <div v-if="selectedReport" class="modal-backdrop" @click.self="selectedReport = null">
      <div class="modal-card">
        <button class="modal-close" @click="selectedReport = null">✕</button>
        <h3>Detail Laporan</h3>
        
        <div class="modal-body">
          <img :src="selectedReport.foto_url || 'https://via.placeholder.com/400x250'" class="modal-img" />
          <h4>{{ selectedReport.nama_barang }}</h4>
          <p>{{ selectedReport.deskripsi }}</p>
          
          <div class="tech-note">
            <p><strong>Status:</strong> {{ formatStatus(selectedReport.status) }}</p>
            <p><strong>Urgensi:</strong> {{ formatUrgensi(selectedReport.urgensi) }}</p>
            <p><strong>Lokasi:</strong> {{ selectedReport.lokasi }}</p>
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



// ─── Fetch API Laporan Barang ─────────────────────────────


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
/* Hero Section Actions */
.hero-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.btn-primary {
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-primary:hover { background: #1d4ed8; }

.btn-secondary {
  background: transparent;
  color: #f8fafc;
  border: 1px solid #334155;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-secondary:hover {
  background: #0f172a;
  border-color: #64748b;
}

/* How It Works Section */
.how-it-works {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 32px;
}
.section-title {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 16px;
}
.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
.step-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #0f172a;
  padding: 14px;
  border-radius: 10px;
  border: 1px solid #334155;
}
.step-num {
  background: #2563eb;
  color: white;
  font-weight: 800;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 13px;
}
.step-text strong {
  display: block;
  font-size: 14px;
  color: #ffffff;
  margin-bottom: 2px;
}
.step-text p {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.4;
}
</style>