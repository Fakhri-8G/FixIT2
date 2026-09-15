<template>
  <div class="dashboard-wrapper">
    <!-- Header Dashboard Admin -->
    <header class="dashboard-header">
      <div>
        <div class="brand-tag admin-tag">🔧 FixIT ADMIN & TEKNISI</div>
        <h1 class="title">Panel Kelola Perbaikan Fasilitas</h1>
        <p class="subtitle">Verifikasi laporan, perbarui status pengerjaan, dan berikan catatan teknis.</p>
      </div>
      <div class="user-meta">
        <span class="user-badge">👤 {{ adminUser.name || 'Admin' }}</span>
      </div>
    </header>

    <!-- Stat Cards Summary -->
    <section class="stats-grid">
      <div class="stat-card">
        <span class="stat-icon">📑</span>
        <div>
          <span class="stat-value">{{ stats.total }}</span>
          <span class="stat-label">Total Laporan</span>
        </div>
      </div>
      <div class="stat-card warning">
        <span class="stat-icon">⏳</span>
        <div>
          <span class="stat-value">{{ stats.pending }}</span>
          <span class="stat-label">Menunggu Antrean</span>
        </div>
      </div>
      <div class="stat-card info">
        <span class="stat-icon">🔨</span>
        <div>
          <span class="stat-value">{{ stats.proses }}</span>
          <span class="stat-label">Dalam Perbaikan</span>
        </div>
      </div>
      <div class="stat-card success">
        <span class="stat-icon">✅</span>
        <div>
          <span class="stat-value">{{ stats.selesai }}</span>
          <span class="stat-label">Selesai Dikerjakan</span>
        </div>
      </div>
    </section>

    <!-- Toolbar Filters -->
    <section class="toolbar-section">
      <div class="search-input-group">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari lokasi, barang, atau nama pelapor..."
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

    <!-- State Loading -->
    <div v-if="isLoading" class="state-container">
      <div class="spinner"></div>
      <p>Memuat daftar antrean pekerjaan teknisi...</p>
    </div>

    <!-- State Error -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>🚨 {{ errorMessage }}</p>
      <button class="btn-retry" @click="fetchLaporanAdmin">Coba Lagi</button>
    </div>

    <!-- Admin Feed List -->
    <main v-else-if="laporanList.length > 0" class="reports-feed">
      <article 
        v-for="item in laporanList" 
        :key="item.id" 
        class="report-card"
      >
        <!-- Header Card: Lokasi, Urgensi & ID Laporan -->
        <div class="card-header">
          <div class="header-left">
            <span class="report-id">#FIX-{{ item.id }}</span>
            <span class="location-tag">📍 {{ item.location?.name }}</span>
          </div>
          <span :class="['badge-priority', `priority-${item.tingkat_urgensi}`]">
            {{ formatUrgensi(item.tingkat_urgensi) }}
          </span>
        </div>

        <!-- Body: Foto & Detail Deskripsi -->
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
              <span>👤 Pelapor: <strong>{{ item.user?.name }}</strong></span>
              <span>📅 {{ formatTanggal(item.created_at) }}</span>
            </div>

            <!-- Catatan Teknisi (Jika sudah ada) -->
            <div v-if="item.updates?.length" class="tech-note-box">
              <strong>📝 Catatan Petugas sebelumnya:</strong>
              <p>{{ item.updates[item.updates.length - 1].note || '(tidak ada catatan)' }}</p>
            </div>
          </div>
        </div>

        <!-- Admin Control Footer (Ubah Status & Catatan) -->
        <div class="card-admin-controls">
          <div class="action-row">
            <div class="control-group">
              <label>Update Status:</label>
              <select 
                :value="item.status" 
                @change="updateStatusLaporan(item, $event.target.value)"
                :disabled="updatingId === item.id"
                class="select-status"
              >
                <option value="reported">⏳ Dilaporkan</option>
                <option value="verified">🔍 Diverifikasi</option>
                <option value="processing">🔨 Dalam Perbaikan</option>
                <option value="completed">✅ Selesai Dikerjakan</option>
                <option value="rejected">❌ Ditolak</option>
              </select>
            </div>

            <button 
              class="btn-note" 
              @click="bukaModalCatatan(item)"
            >
              ✏️ {{ item.updates?.length ? 'Edit Catatan' : '+ Catatan Teknisi' }}
            </button>
          </div>
        </div>
      </article>
    </main>

    <!-- Empty State -->
    <div v-else class="state-container empty-box">
      <div class="empty-icon">📂</div>
      <h3>Tidak Ada Antrean Pekerjaan</h3>
      <p>Belum ada laporan kerusakan yang perlu ditangani untuk kategori ini.</p>
    </div>

    <!-- Modal Input/Edit Catatan Teknisi -->
    <div v-if="activeReportForNote" class="modal-backdrop" @click.self="activeReportForNote = null">
      <div class="modal-card">
        <button class="modal-close" @click="activeReportForNote = null">✕</button>
        <h3>Catatan Teknisi: {{ activeReportForNote.title }}</h3>
        <p class="modal-sub">Berikan keterangan proses pengerjaan, estimasi, atau info penggantian sparepart.</p>
        
        <form @submit.prevent="simpanCatatanTeknisi">
          <textarea
            v-model="tempNote"
            rows="4"
            placeholder="Contoh: Saklar sudah diganti baru. Kendala listrik di R.302 sudah normal kembali."
            required
            class="note-textarea"
          ></textarea>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="activeReportForNote = null">Batal</button>
            <button type="submit" class="btn-save" :disabled="isSavingNote">
              {{ isSavingNote ? 'Saving...' : 'Simpan Catatan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()

// ─── State Management ──────────────────────────────────────
const laporanList          = ref([])
const isLoading            = ref(false)
const errorMessage         = ref(null)
const searchQuery          = ref('')
const statusSelected       = ref('semua')
const updatingId           = ref(null)
const activeReportForNote  = ref(null)
const tempNote             = ref('')
const isSavingNote         = ref(false)
const adminUser            = ref({})

const stats = reactive({
  total: 0,
  pending: 0,
  proses: 0,
  selesai: 0
})

const listStatus = [
  { label: 'Semua Antrean', value: 'semua' },
  { label: 'Dilaporkan',    value: 'reported' },
  { label: 'Diverifikasi',  value: 'verified' },
  { label: 'Diproses',      value: 'processing' },
  { label: 'Selesai',       value: 'completed' },
  { label: 'Ditolak',       value: 'rejected' }
]

// ─── Fetch Admin Data & Stats ──────────────────────────────
const fetchLaporanAdmin = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    // Dipanggil menggunakan endpoint admin (dengan Authorization Header terpasang)
    const response = await api.get('/reports', {
      params: {
        keyword: searchQuery.value.trim() || undefined,
        status: statusSelected.value !== 'semua' ? statusSelected.value : undefined
      }
    })

    const rawData = response.data?.data || []
    laporanList.value = rawData

    // Hitung statistik singkat
    calculateStats(rawData)
  } catch (err) {
    if (err.response?.status === 401 || err.response?.status === 403) {
      errorMessage.value = 'Akses ditolak. Silakan login kembali sebagai Admin / Teknisi.'
    } else {
      errorMessage.value = err.response?.data?.message || 'Gagal memuat data antrean pekerjaan FixIT.'
    }
  } finally {
    isLoading.value = false
  }
}

const calculateStats = (data) => {
  stats.total = data.length
  stats.pending = data.filter(i => i.status === 'reported').length
  stats.proses = data.filter(i => i.status === 'processing').length
  stats.selesai = data.filter(i => i.status === 'completed').length
}

// ─── Actions: Update Status & Notes ────────────────────────
const updateStatusLaporan = async (item, newStatus) => {
  if (item.status === newStatus) return

  updatingId.value = item.id
  try {
    await api.patch(`/reports/${item.id}`, {
      status: newStatus
    })

    item.status = newStatus
    calculateStats(laporanList.value)
  } catch (err) {
    alert('Gagal memperbarui status: ' + (err.response?.data?.message || 'Terjadi kesalahan server'))
  } finally {
    updatingId.value = null
  }
}

const bukaModalCatatan = (item) => {
  activeReportForNote.value = item
  const lastUpdate = item.updates?.[item.updates.length - 1]
  tempNote.value = lastUpdate?.note || ''
}

const simpanCatatanTeknisi = async () => {
  if (!activeReportForNote.value) return

  isSavingNote.value = true
  const reportId = activeReportForNote.value.id

  try {
    await api.patch(`/reports/${reportId}`, {
      status: activeReportForNote.value.status,
      note: tempNote.value
    })

    // Update lokal
    await fetchLaporanAdmin()
    activeReportForNote.value = null
  } catch (err) {
    alert('Gagal menyimpan catatan: ' + (err.response?.data?.message || 'Error server'))
  } finally {
    isSavingNote.value = false
  }
}

// ─── Utility & Debounce ───────────────────────────────────
let debounceTimer = null
const handleSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchLaporanAdmin()
  }, 400)
}

const gantiStatusFilter = (status) => {
  if (statusSelected.value === status) return
  statusSelected.value = status
  fetchLaporanAdmin()
}

const handleLogout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

const formatStatus = (status) => {
  const map = {
    reported: '⏳ Dilaporkan',
    verified: '🔍 Diverifikasi',
    processing: '🔨 Diproses',
    completed: '✅ Selesai',
    rejected: '❌ Ditolak'
  }
  return map[status] || status
}

const formatUrgensi = (urgensi) => {
  const map = { rendah: 'Biasa', sedang: 'Sedang', darurat: '🚨 DARURAT' }
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

// ─── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  const savedUser = localStorage.getItem('user')
  if (savedUser) {
    adminUser.value = JSON.parse(savedUser)
  }
  fetchLaporanAdmin()
})
</script>

<style scoped>
/* ─── CSS Variables & Base Setup ──────────────────────────── */
:host {
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --bg-main: #f8fafc;
  --card-bg: #ffffff;
  --text-main: #0f172a;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --radius-lg: 16px;
  --radius-md: 10px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 12px -2px rgba(0,0,0,0.08);
  --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.dashboard-wrapper {
  max-width: 1140px;
  margin: 0 auto;
  padding: 32px 20px;
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: var(--text-main);
  background-color: #f8fafc;
  min-height: 100vh;
}

/* ─── Header Section ──────────────────────────────────────── */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.brand-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 20px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.admin-tag {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.title {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.5px;
  margin: 0;
}

.subtitle {
  color: #64748b;
  font-size: 14px;
  margin-top: 6px;
}

.user-meta {
  display: flex;
  align-items: center;
}

.user-badge {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 8px 16px;
  border-radius: 30px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* ─── Stats Grid ───────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px;
  margin-bottom: 32px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 20px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.stat-icon {
  font-size: 28px;
  background: #f1f5f9;
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  flex-shrink: 0;
}

.stat-value {
  display: block;
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  margin-top: 2px;
}

.stat-card.warning { border-left: 4px solid #f59e0b; }
.stat-card.info { border-left: 4px solid #3b82f6; }
.stat-card.success { border-left: 4px solid #10b981; }

/* ─── Toolbar & Filters ───────────────────────────────────── */
.toolbar-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 28px;
  background: #ffffff;
  padding: 14px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
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
  max-width: 380px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  opacity: 0.6;
}

.search-input-group input {
  width: 100%;
  padding: 10px 14px 10px 40px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  outline: none;
  font-size: 13px;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.search-input-group input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.status-tabs {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 2px;
}

.tab-btn {
  background: transparent;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.tab-btn.active {
  background: #0f172a;
  color: #ffffff;
}

/* ─── Reports Feed Card ───────────────────────────────────── */
.reports-feed {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.report-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.report-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.header-left {
  display: flex;
  gap: 10px;
  align-items: center;
}

.report-id {
  font-size: 11px;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.location-tag {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  background: #f1f5f9;
  padding: 4px 10px;
  border-radius: 6px;
}

.badge-priority {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.priority-rendah { background: #ecfdf5; color: #047857; }
.priority-sedang { background: #fffbeb; color: #b45309; }
.priority-darurat { background: #fef2f2; color: #b91c1c; }

.card-body {
  display: flex;
  gap: 20px;
  flex-direction: column;
}

@media (min-width: 640px) {
  .card-body { flex-direction: row; }
}

.img-wrapper {
  position: relative;
  width: 100%;
  max-width: 180px;
  height: 125px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
}

.img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.report-card:hover .img-wrapper img {
  transform: scale(1.03);
}

.status-pill {
  position: absolute;
  bottom: 8px;
  left: 8px;
  font-size: 10px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  color: white;
  backdrop-filter: blur(4px);
}

.status-reported { background: rgba(217, 119, 6, 0.9); }
.status-verified { background: rgba(124, 58, 237, 0.9); }
.status-processing { background: rgba(37, 99, 235, 0.9); }
.status-completed { background: rgba(16, 185, 129, 0.9); }
.status-rejected { background: rgba(225, 29, 72, 0.9); }

.content-wrapper { flex: 1; }

.item-name {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.description {
  font-size: 14px;
  color: #475569;
  line-height: 1.5;
  margin-bottom: 12px;
}

.meta-info {
  display: flex;
  gap: 18px;
  font-size: 12px;
  color: #64748b;
}

.tech-note-box {
  background: #f8fafc;
  border-left: 3px solid #2563eb;
  padding: 10px 14px;
  border-radius: 0 8px 8px 0;
  margin-top: 14px;
  font-size: 12px;
  color: #334155;
}

.tech-note-box strong {
  color: #1e40af;
}

.tech-note-box p {
  margin: 4px 0 0 0;
}

/* ─── Card Controls (Action Row) ──────────────────────────── */
.card-admin-controls {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.action-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.control-group {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.select-status {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-weight: 600;
  font-size: 12px;
  background: #ffffff;
  color: #0f172a;
  outline: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.select-status:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.btn-note {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-note:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* ─── State Layouts (Loading, Empty, Error) ───────────────── */
.state-container {
  text-align: center;
  padding: 64px 20px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  color: #64748b;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.empty-box h3 {
  color: #0f172a;
  margin-bottom: 6px;
}

.error-box {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

.btn-retry {
  margin-top: 14px;
  padding: 8px 18px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* ─── Modal Glassmorphism ─────────────────────────────────── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 100;
}

.modal-card {
  background: #ffffff;
  border-radius: 20px;
  max-width: 500px;
  width: 100%;
  padding: 28px;
  position: relative;
  box-shadow: var(--shadow-lg);
  animation: modalUp 0.2s ease-out;
}

@keyframes modalUp {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 01; transform: translateY(0); }
}

.modal-close {
  position: absolute;
  right: 20px;
  top: 20px;
  border: none;
  background: #f1f5f9;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-card h3 {
  font-size: 18px;
  color: #0f172a;
  margin: 0;
}

.modal-sub {
  font-size: 13px;
  color: #64748b;
  margin: 6px 0 20px;
}

.note-textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 14px;
  font-size: 14px;
  font-family: inherit;
  outline: none;
  margin-bottom: 20px;
  box-sizing: border-box;
  transition: all 0.2s ease;
}

.note-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel {
  background: #f1f5f9;
  border: none;
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.btn-cancel:hover { background: #e2e8f0; }

.btn-save {
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn-save:hover { background: #1d4ed8; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
</style>