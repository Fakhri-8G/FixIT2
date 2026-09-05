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
            <span class="location-tag">📍 {{ item.lokasi_ruangan }}</span>
          </div>
          <span :class="['badge-priority', `priority-${item.tingkat_urgensi}`]">
            {{ formatUrgensi(item.tingkat_urgensi) }}
          </span>
        </div>

        <!-- Body: Foto & Detail Deskripsi -->
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

            <!-- Catatan Teknisi (Jika sudah ada) -->
            <div v-if="item.catatan_teknisi" class="tech-note-box">
              <strong>📝 Catatan Petugas sebelumnya:</strong>
              <p>{{ item.catatan_teknisi }}</p>
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
                <option value="pending">⏳ Menunggu Antrean</option>
                <option value="proses">🔨 Dalam Perbaikan</option>
                <option value="selesai">✅ Selesai Dikerjakan</option>
              </select>
            </div>

            <button 
              class="btn-note" 
              @click="bukaModalCatatan(item)"
            >
              ✏️ {{ item.catatan_teknisi ? 'Edit Catatan' : '+ Catatan Teknisi' }}
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
        <h3>Catatan Teknisi: {{ activeReportForNote.nama_barang }}</h3>
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
  { label: 'Menunggu', value: 'pending' },
  { label: 'Diproses', value: 'proses' },
  { label: 'Selesai', value: 'selesai' }
]

// ─── Fetch Admin Data & Stats ──────────────────────────────
const fetchLaporanAdmin = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    // Dipanggil menggunakan endpoint admin (dengan Authorization Header terpasang)
    const response = await api.get('/admin/laporan-kerusakan', {
      params: {
        keyword: searchQuery.value.trim() || undefined,
        status: statusSelected.value !== 'semua' ? statusSelected.value : undefined
      }
    })

    const rawData = response.data?.data?.data || response.data?.data || []
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
  stats.pending = data.filter(i => i.status === 'pending').length
  stats.proses = data.filter(i => i.status === 'proses').length
  stats.selesai = data.filter(i => i.status === 'selesai').length
}

// ─── Actions: Update Status & Notes ────────────────────────
const updateStatusLaporan = async (item, newStatus) => {
  if (item.status === newStatus) return

  updatingId.value = item.id
  try {
    await api.patch(`/admin/laporan-kerusakan/${item.id}/status`, {
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
  tempNote.value = item.catatan_teknisi || ''
}

const simpanCatatanTeknisi = async () => {
  if (!activeReportForNote.value) return

  isSavingNote.value = true
  const reportId = activeReportForNote.value.id

  try {
    await api.patch(`/admin/laporan-kerusakan/${reportId}/catatan`, {
      catatan_teknisi: tempNote.value
    })

    // Update lokal
    activeReportForNote.value.catatan_teknisi = tempNote.value
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
  const map = { pending: '⏳ Menunggu', proses: '🔨 Diproses', selesai: '✅ Selesai' }
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
.dashboard-wrapper {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px;
  font-family: system-ui, -apple-system, sans-serif;
  color: #2d3748;
}

/* Header & Admin Tag */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  border-bottom: 2px solid #edf2f7;
  padding-bottom: 16px;
}
.brand-tag {
  display: inline-block;
  background: #ebf8ff;
  color: #2b6cb0;
  font-size: 11px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 6px;
  letter-spacing: 1px;
  margin-bottom: 6px;
}
.admin-tag { background: #feebc8; color: #744210; }
.title { font-size: 26px; font-weight: 800; color: #1a202c; }
.subtitle { color: #718096; font-size: 14px; margin-top: 4px; }

.user-meta { display: flex; align-items: center; gap: 12px; }
.user-badge { font-size: 13px; font-weight: 700; color: #4a5568; background: #edf2f7; padding: 6px 12px; border-radius: 6px; }
.btn-logout { background: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-logout:hover { background: #fed7d7; }

/* Stats Bar */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.stat-icon { font-size: 24px; }
.stat-value { display: block; font-size: 20px; font-weight: 800; color: #1a202c; }
.stat-label { font-size: 12px; color: #718096; font-weight: 600; }

.stat-card.warning { border-left: 4px solid #dd6b20; }
.stat-card.info { border-left: 4px solid #3182ce; }
.stat-card.success { border-left: 4px solid #38a169; }

/* Toolbar */
.toolbar-section { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
@media (min-width: 768px) {
  .toolbar-section { flex-direction: row; justify-content: space-between; align-items: center; }
}

.search-input-group { position: relative; flex: 1; max-width: 400px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); }
.search-input-group input { width: 100%; padding: 10px 12px 10px 38px; border: 1px solid #cbd5e0; border-radius: 8px; outline: none; }

.status-tabs { display: flex; gap: 8px; overflow-x: auto; }
.tab-btn { background: #edf2f7; border: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #4a5568; cursor: pointer; }
.tab-btn.active { background: #2b6cb0; color: white; }

/* Card Feed */
.reports-feed { display: flex; flex-direction: column; gap: 16px; }
.report-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.header-left { display: flex; gap: 8px; align-items: center; }
.report-id { font-size: 11px; font-weight: 800; color: #a0aec0; }
.location-tag { font-size: 12px; font-weight: 700; color: #4a5568; background: #f7fafc; padding: 4px 8px; border-radius: 4px; }

.badge-priority { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
.priority-rendah { background: #e6fffa; color: #234e52; }
.priority-sedang { background: #feebc8; color: #744210; }
.priority-darurat { background: #fed7d7; color: #9b2c2c; }

.card-body { display: flex; gap: 16px; flex-direction: column; }
@media (min-width: 640px) { .card-body { flex-direction: row; } }

.img-wrapper { position: relative; width: 100%; max-width: 160px; height: 110px; flex-shrink: 0; }
.img-wrapper img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }

.status-pill { position: absolute; bottom: 6px; left: 6px; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; color: white; }
.status-pending { background: #dd6b20; }
.status-proses { background: #3182ce; }
.status-selesai { background: #38a169; }

.content-wrapper { flex: 1; }
.item-name { font-size: 16px; font-weight: 700; color: #2d3748; margin-bottom: 6px; }
.description { font-size: 13px; color: #718096; line-height: 1.4; margin-bottom: 10px; }
.meta-info { display: flex; gap: 16px; font-size: 12px; color: #a0aec0; }

.tech-note-box { background: #f7fafc; border-left: 3px solid #3182ce; padding: 8px 12px; border-radius: 4px; margin-top: 10px; font-size: 12px; color: #2d3748; }

/* Admin Control Panel inside Card */
.card-admin-controls { margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e2e8f0; }
.action-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

.control-group { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #4a5568; }
.select-status { padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e0; font-weight: 600; font-size: 12px; background: white; outline: none; }

.btn-note { background: #edf2f7; border: 1px solid #cbd5e0; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; color: #2d3748; cursor: pointer; }
.btn-note:hover { background: #e2e8f0; }

/* States Components */
.state-container { text-align: center; padding: 48px; color: #a0aec0; }
.error-box { background: #fff5f5; border-radius: 8px; color: #e53e3e; }
.btn-retry { margin-top: 12px; padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 4px; cursor: pointer; }

/* Modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; padding: 16px; z-index: 100; }
.modal-card { background: white; border-radius: 12px; max-width: 480px; width: 100%; padding: 24px; position: relative; }
.modal-close { position: absolute; right: 16px; top: 16px; border: none; background: none; font-size: 18px; cursor: pointer; }
.modal-sub { font-size: 12px; color: #718096; margin: 4px 0 16px; }

.note-textarea { width: 100%; border: 1px solid #cbd5e0; border-radius: 8px; padding: 12px; font-size: 13px; font-family: inherit; outline: none; margin-bottom: 16px; box-sizing: border-box; }
.note-textarea:focus { border-color: #3182ce; }

.modal-actions { display: flex; justify-content: flex-end; gap: 8px; }
.btn-cancel { background: #edf2f7; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-save { background: #2b6cb0; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
</style>