<template>
  <!-- ═══════════════════════════════════════════════════
       1. TAMPILAN GUEST (BELUM LOGIN) - Modern Top Navbar
       ═══════════════════════════════════════════════════ -->
  <template v-if="!isLoggedIn">
    <section class="hero-section">
      <div class="hero-content">
        <span class="hero-badge">Portal Pengaduan Fasilitas Sekolah</span>
        <h1 class="hero-title">Sistem Laporan Kerusakan & Fasilitas</h1>
        <p class="hero-subtitle">
          Temukan masalah fasilitas di SMK Assalaam Bandung? Laporkan secara real-time dan pantau proses perbaikannya langsung di sini.
        </p>
      </div>
    </section>

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
            <p>Tim teknisi akan merespons & memperbaiki.</p>
          </div>
        </div>
      </div>
    </section>

    <main class="guest-container">
      <RouterView />
    </main>
  </template>

  <!-- ═══════════════════════════════════════════════════
       2. TAMPILAN LOGGED IN (SISWA / ADMIN) - Full App Shell
       ═══════════════════════════════════════════════════ -->
  <div v-else class="app-layout" :class="{ 'sidebar-collapsed': isCollapsed }">
    <!-- Overlay Mobile Backdrop -->
    <div 
      v-if="isMobileOpen" 
      class="mobile-backdrop" 
      @click="isMobileOpen = false"
    ></div>

    <!-- ── SIDEBAR ────────────────────────────────────────── -->
    <aside 
      class="app-sidebar" 
      :class="{ 
        'is-admin': isAdmin, 
        'mobile-show': isMobileOpen 
      }"
    >
      <div class="sidebar-header">
        <div class="brand-box">
          <div class="brand-logo">🛠️</div>
          <span class="brand-text">Fix<span class="highlight">IT</span></span>
        </div>
        <span v-if="isAdmin" class="admin-badge">ADMIN</span>
      </div>

      <!-- Menu Section -->
      <nav class="sidebar-nav">
        <div class="nav-group">
          <span class="group-label">MENU UTAMA</span>
          
          <!-- Menu Siswa -->
          <template v-if="!isAdmin">
            <RouterLink to="/dashboard" class="nav-item" @click="closeMobile">
              <span class="nav-icon">🏠</span>
              <span class="nav-label">Dashboard</span>
            </RouterLink>
            <RouterLink to="/buat-laporan" class="nav-item" @click="closeMobile">
              <span class="nav-icon">➕</span>
              <span class="nav-label">Buat Laporan</span>
            </RouterLink>
            <RouterLink to="/laporan-saya" class="nav-item" @click="closeMobile">
              <span class="nav-icon">📂</span>
              <span class="nav-label">Laporan Saya</span>
            </RouterLink>
          </template>

          <!-- Menu Admin -->
          <template v-else>
            <RouterLink to="/dashboard" class="nav-item" @click="closeMobile">
              <span class="nav-icon">📊</span>
              <span class="nav-label">Dashboard</span>
            </RouterLink>
            <RouterLink to="/semua-laporan" class="nav-item" @click="closeMobile">
              <span class="nav-icon">📑</span>
              <span class="nav-label">Semua Laporan</span>
            </RouterLink>
            <RouterLink to="/kategori" class="nav-item" @click="closeMobile">
              <span class="nav-icon">🗂️</span>
              <span class="nav-label">Kategori</span>
            </RouterLink>
            <RouterLink to="/lokasi" class="nav-item" @click="closeMobile">
              <span class="nav-icon">📍</span>
              <span class="nav-label">Lokasi Ruangan</span>
            </RouterLink>
            <RouterLink to="/pengguna" class="nav-item" @click="closeMobile">
              <span class="nav-icon">👥</span>
              <span class="nav-label">Kelola Pengguna</span>
            </RouterLink>
          </template>
        </div>

        <div class="nav-group">
          <span class="group-label">PENGATURAN</span>
          <RouterLink to="/profile" class="nav-item" @click="closeMobile">
            <span class="nav-icon">👤</span>
            <span class="nav-label">Profil Saya</span>
          </RouterLink>
        </div>
      </nav>

      <!-- Sidebar Footer / Logout Button -->
      <div class="sidebar-footer">
        <button @click="handleLogout" class="btn-logout">
          <span class="nav-icon">🚪</span>
          <span class="nav-label">Keluar Sesi</span>
        </button>
      </div>
    </aside>

    <!-- ── MAIN AREA ──────────────────────────────────────── -->
    <div class="app-main">
      <!-- TOPBAR HEADER -->
      <header class="app-topbar">
        <div class="topbar-left">
          <button class="toggle-btn" @click="toggleSidebar" title="Toggle Sidebar">
            ☰
          </button>
          <div class="breadcrumb">
            <span class="system-status">● Realtime Sync</span>
          </div>
        </div>

        <div class="topbar-right">
          <button class="btn-icon-action" title="Notifikasi">
            🔔
            <span class="dot-indicator"></span>
          </button>

          <div class="divider"></div>

          <div class="user-profile-chip">
            <img
              class="avatar-img"
              :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${userName || 'user'}`"
              alt="User Avatar"
            />
            <div class="user-details">
              <span class="user-display-name">{{ userName || 'Pengguna' }}</span>
              <span class="user-role-badge">{{ isAdmin ? 'Administrator' : 'Siswa / Pelapor' }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT INJECTION -->
      <main class="app-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'
import api from '../../utils/api'

const router = useRouter()
const route = useRoute()

const isLoggedIn = ref(false)
const isAdmin = ref(false)
const userName = ref('')

const isCollapsed = ref(false)
const isMobileOpen = ref(false)

const cekStatusLogin = () => {
  const token = localStorage.getItem('token')
  const user = localStorage.getItem('user')

  if (token && user) {
    try {
      const parsedUser = JSON.parse(user)
      isLoggedIn.value = true
      userName.value = parsedUser.name || parsedUser.username
      isAdmin.value = parsedUser.role === 'admin'
    } catch (e) {
      isLoggedIn.value = false
    }
  } else {
    isLoggedIn.value = false
    isAdmin.value = false
    userName.value = ''
  }
}

onMounted(() => {
  cekStatusLogin()
})

watch(() => route.path, () => {
  cekStatusLogin()
})

const toggleSidebar = () => {
  if (window.innerWidth <= 1024) {
    isMobileOpen.value = !isMobileOpen.value
  } else {
    isCollapsed.value = !isCollapsed.value
  }
}

const closeMobile = () => {
  isMobileOpen.value = false
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.warn('Logout API error:', err)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    isLoggedIn.value = false
    isAdmin.value = false
    userName.value = ''

    router.push('/login')
  }
}
</script>

<style>
/* ════════════════════════════════════════════════════════════
   SYSTEM RESET & UPGRADED DESIGN TOKENS
   ════════════════════════════════════════════════════════════ */
:root {
  --bg-main: #090d16;
  --bg-surface: rgba(18, 26, 43, 0.75);
  --sidebar-bg: #0d1322;
  --sidebar-width: 260px;
  --sidebar-collapsed-width: 78px;
  --topbar-height: 70px;
  
  --primary: #3b82f6;
  --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  --primary-glow: rgba(59, 130, 246, 0.35);
  --accent-cyan: #06b6d4;
  
  --text-main: #f1f5f9;
  --text-muted: #94a3b8;
  --border-color: rgba(255, 255, 255, 0.08);
  --border-hover: rgba(255, 255, 255, 0.18);
  
  --card-bg: rgba(20, 30, 48, 0.5);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html, body {
  width: 100%;
  height: 100%;
  font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
  background-color: var(--bg-main);
  background-image: 
    radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
    radial-gradient(at 100% 100%, rgba(14, 165, 233, 0.08) 0px, transparent 50%);
  background-attachment: fixed;
  color: var(--text-main);
  -webkit-font-smoothing: antialiased;
}

/* ════════════════════════════════════════════════════════════
   1. GUEST LAYOUT IMPROVEMENTS
   ════════════════════════════════════════════════════════════ */
.hero-section {
  padding: 60px 24px 30px 24px;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
}

.hero-badge {
  display: inline-block;
  padding: 6px 16px;
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #60a5fa;
  font-size: 13px;
  font-weight: 700;
  border-radius: 30px;
  margin-bottom: 20px;
  letter-spacing: 0.5px;
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.15);
}

.hero-title {
  font-size: 42px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -1px;
  background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 16px;
}

.hero-subtitle {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.6;
  max-width: 650px;
  margin: 0 auto;
}

.how-it-works {
  max-width: 1100px;
  margin: 40px auto 20px auto;
  padding: 0 24px;
}

.section-title {
  font-size: 22px;
  font-weight: 700;
  text-align: center;
  margin-bottom: 28px;
  color: #f8fafc;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.step-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: var(--card-bg);
  backdrop-filter: blur(12px);
  padding: 24px;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-card:hover {
  transform: translateY(-4px);
  border-color: var(--border-hover);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}

.step-num {
  background: var(--primary-gradient);
  color: white;
  font-weight: 800;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 15px;
  box-shadow: 0 4px 12px var(--primary-glow);
}

.step-text strong {
  display: block;
  font-size: 15px;
  color: #ffffff;
  margin-bottom: 6px;
}

.step-text p {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.5;
}

.guest-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px 24px 60px 24px;
}

/* ════════════════════════════════════════════════════════════
   2. APP SHELL LAYOUT (FULL WIDTH & MODERN UI)
   ════════════════════════════════════════════════════════════ */
.app-layout {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background-color: var(--bg-main);
}

/* ── SIDEBAR STYLING ──────────────────────────────────────── */
.app-sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 100;
  transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-header {
  height: var(--topbar-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  border-bottom: 1px solid var(--border-color);
}

.brand-box {
  display: flex;
  align-items: center;
  gap: 12px;
  overflow: hidden;
}

.brand-logo {
  font-size: 20px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.brand-text {
  font-weight: 800;
  font-size: 19px;
  color: #ffffff;
  letter-spacing: -0.5px;
  white-space: nowrap;
}

.highlight {
  color: var(--primary);
}

.admin-badge {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(96, 165, 250, 0.3);
  font-size: 10px;
  font-weight: 800;
  padding: 4px 8px;
  border-radius: 6px;
  letter-spacing: 0.8px;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 14px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  overflow-y: auto;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.group-label {
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  padding: 0 12px 6px 12px;
  letter-spacing: 1px;
  white-space: nowrap;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border-radius: 12px;
  text-decoration: none;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
  white-space: nowrap;
  border: 1px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.04);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.05);
}

.nav-item.router-link-active {
  background: var(--primary-gradient);
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 20px var(--primary-glow);
  border-color: rgba(255, 255, 255, 0.2);
}

.nav-icon {
  font-size: 18px;
  width: 24px;
  display: flex;
  justify-content: center;
}

.sidebar-footer {
  padding: 16px 14px;
  border-top: 1px solid var(--border-color);
}

.btn-logout {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: rgba(239, 68, 68, 0.08);
  color: #fca5a5;
  font-size: 14px;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: rgba(239, 68, 68, 0.25);
  color: #ffffff;
  border-color: rgba(239, 68, 68, 0.5);
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
}

/* ── COLLAPSED SIDEBAR STATE ──────────────────────────────── */
.sidebar-collapsed .app-sidebar {
  width: var(--sidebar-collapsed-width);
}

.sidebar-collapsed .brand-text,
.sidebar-collapsed .admin-badge,
.sidebar-collapsed .group-label,
.sidebar-collapsed .nav-label {
  display: none;
}

.sidebar-collapsed .nav-item {
  justify-content: center;
  padding: 12px;
}

.sidebar-collapsed .app-main {
  margin-left: var(--sidebar-collapsed-width);
  width: calc(100% - var(--sidebar-collapsed-width));
}

/* ── MAIN CONTENT AREA ────────────────────────────────────── */
.app-main {
  flex: 1;
  margin-left: var(--sidebar-width);
  display: flex;
  flex-direction: column;
  min-width: 0;
  width: calc(100% - var(--sidebar-width));
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.app-topbar {
  height: var(--topbar-height);
  background: var(--bg-surface);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 90;
  width: 100%;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.toggle-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  width: 40px;
  height: 40px;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}

.toggle-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  border-color: var(--border-hover);
}

.system-status {
  font-size: 12px;
  font-weight: 600;
  color: #4ade80;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.25);
  padding: 5px 12px;
  border-radius: 20px;
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.15);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-icon-action {
  position: relative;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  font-size: 16px;
  cursor: pointer;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon-action:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--border-hover);
}

.dot-indicator {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  box-shadow: 0 0 8px #ef4444;
}

.divider {
  width: 1px;
  height: 24px;
  background: var(--border-color);
}

.user-profile-chip {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color);
  padding: 6px 14px 6px 8px;
  border-radius: 30px;
  transition: all 0.2s;
}

.user-profile-chip:hover {
  border-color: var(--border-hover);
  background: rgba(255, 255, 255, 0.06);
}

.avatar-img {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #1e293b;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-display-name {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
}

.user-role-badge {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 500;
}

.app-content {
  flex: 1;
  padding: 28px;
  background: transparent;
  width: 100%;
}

/* RESPONSIVE MOBILE BREAKPOINT */
@media (max-width: 1024px) {
  .app-sidebar {
    transform: translateX(-100%);
  }
  
  .app-sidebar.mobile-show {
    transform: translateX(0);
    box-shadow: 10px 0 30px rgba(0, 0, 0, 0.6);
  }

  .app-main {
    margin-left: 0 !important;
    width: 100% !important;
  }

  .mobile-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 13, 22, 0.8);
    backdrop-filter: blur(6px);
    z-index: 95;
  }

  .hero-title {
    font-size: 32px;
  }
}
</style>