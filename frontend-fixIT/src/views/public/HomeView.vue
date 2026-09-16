<template>
  <!-- ═══════════════════════════════════════════════════
       1. TAMPILAN GUEST (BELUM LOGIN) - Modern Top Navbar
       ═══════════════════════════════════════════════════ -->
  <template v-if="!isLoggedIn">
    <!-- Ambient Glow Background -->
    <div class="glow-bg"></div>

    <section class="hero-section">
      <div class="hero-content">
        <span class="hero-badge">
          <span class="pulse-dot"></span> Portal Pengaduan Fasilitas
        </span>
        <h1 class="hero-title">
          Sistem Laporan Kerusakan <br />
          & <span class="text-gradient">Fasilitas Sekolah</span>
        </h1>
        <p class="hero-subtitle">
          Temukan masalah fasilitas di SMK Assalaam Bandung? Laporkan secara real-time, transparan, dan pantau proses perbaikannya langsung di sini.
        </p>
      </div>
    </section>

    <section class="how-it-works">
      <div class="section-header">
        <h2 class="section-title">Bagaimana Cara Melaporkan?</h2>
        <p class="section-subtext">3 langkah mudah untuk membantu menjaga fasilitas sekolah kita bersama.</p>
      </div>

      <div class="steps-grid">
        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-num">01</div>
          </div>
          <div class="step-text">
            <strong>Masuk Akun</strong>
            <p>Login atau buat akun terverifikasi kamu dalam hitungan detik.</p>
          </div>
        </div>

        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-num">02</div>
          </div>
          <div class="step-text">
            <strong>Foto & Deskripsikan</strong>
            <p>Upload bukti foto kerusakan dan sertakan lokasi detailnya.</p>
          </div>
        </div>

        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-num">03</div>
          </div>
          <div class="step-text">
            <strong>Pantau Progress</strong>
            <p>Tim Admin akan merespons & menangani laporanmu.</p>
          </div>
        </div>
      </div>
    </section>

    <main class="guest-container">
      <RouterView />
    </main>
  </template>

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
   SYSTEM RESET & DESIGN TOKENS
   ════════════════════════════════════════════════════════════ */
:root {
  --bg-main: #090d16;
  --bg-surface: #111827;
  --bg-card: rgba(30, 41, 59, 0.4);
  --sidebar-bg: #0f172a;
  --sidebar-width: 260px;
  --sidebar-collapsed-width: 78px;
  --topbar-height: 68px;
  
  --primary: #3b82f6;
  --primary-glow: rgba(59, 130, 246, 0.25);
  --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  
  --text-main: #f8fafc;
  --text-muted: #94a3b8;
  --border-color: rgba(255, 255, 255, 0.08);
  --border-hover: rgba(59, 130, 246, 0.4);
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
  color: var(--text-main);
  -webkit-font-smoothing: antialiased;
  overflow-x: hidden;
}

/* ════════════════════════════════════════════════════════════
   1. GUEST LAYOUT & HERO STYLING
   ════════════════════════════════════════════════════════════ */
.glow-bg {
  position: absolute;
  top: -100px;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 300px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
  pointer-events: none;
  z-index: 0;
}

.hero-section {
  position: relative;
  z-index: 1;
  padding: 80px 24px 40px;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  border-radius: 99px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 24px;
  backdrop-filter: blur(8px);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background-color: #3b82f6;
  border-radius: 50%;
  box-shadow: 0 0 10px #3b82f6;
}

.hero-title {
  font-size: 48px;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -1.2px;
  color: #ffffff;
  margin-bottom: 20px;
}

.text-gradient {
  background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  font-size: 18px;
  line-height: 1.6;
  color: var(--text-muted);
  max-width: 680px;
  margin: 0 auto;
}

/* HOW IT WORKS */
.how-it-works {
  max-width: 1200px;
  margin: 40px auto 60px;
  padding: 0 24px;
  position: relative;
  z-index: 1;
}

.section-header {
  text-align: center;
  margin-bottom: 40px;
}

.section-title {
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.5px;
  color: #ffffff;
  margin-bottom: 8px;
}

.section-subtext {
  color: var(--text-muted);
  font-size: 15px;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.step-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: var(--bg-card);
  backdrop-filter: blur(16px);
  padding: 28px;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.step-card:hover {
  transform: translateY(-4px);
  border-color: var(--border-hover);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3), 0 0 20px var(--primary-glow);
}

.step-icon-wrapper {
  display: flex;
  align-items: center;
}

.step-num {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.3);
  font-weight: 800;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
}

.step-text strong {
  display: block;
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 6px;
}

.step-text p {
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.5;
}

.guest-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px 60px;
}

/* ════════════════════════════════════════════════════════════
   2. APP SHELL LAYOUT (FULL DASHBOARD LAYOUT)
   ════════════════════════════════════════════════════════════ */
.app-layout {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background-color: var(--bg-main);
}

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

.brand-text {
  font-weight: 800;
  font-size: 18px;
  color: #ffffff;
  letter-spacing: -0.5px;
  white-space: nowrap;
}

.highlight {
  color: var(--primary);
}

.admin-badge {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  border: 1px solid rgba(96, 165, 250, 0.3);
  font-size: 10px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
  letter-spacing: 0.5px;
}

.sidebar-nav {
  flex: 1;
  padding: 24px 14px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  overflow-y: auto;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.group-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  padding: 0 12px 8px 12px;
  letter-spacing: 0.8px;
  white-space: nowrap;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.nav-item.router-link-active {
  background: var(--primary-gradient);
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
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
  padding: 10px 14px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: rgba(239, 68, 68, 0.05);
  color: #f87171;
  font-size: 14px;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #ffffff;
  border-color: rgba(239, 68, 68, 0.4);
}

/* COLLAPSED SIDEBAR STATE */
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

/* MAIN CONTENT AREA */
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
  background: #0f172a;
  border: 1px solid var(--border-color);
  color: var(--text-main);
  width: 38px;
  height: 38px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}

.toggle-btn:hover {
  background: #334155;
  color: #ffffff;
}

.system-status {
  font-size: 12px;
  font-weight: 600;
  color: #4ade80;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-icon-action {
  position: relative;
  background: #0f172a;
  border: 1px solid var(--border-color);
  color: var(--text-main);
  font-size: 16px;
  cursor: pointer;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon-action:hover {
  background: #334155;
}

.dot-indicator {
  position: absolute;
  top: 6px;
  right: 6px;
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
  background: #0f172a;
  border: 1px solid var(--border-color);
  padding: 6px 14px 6px 8px;
  border-radius: 30px;
}

.avatar-img {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #334155;
  object-fit: cover;
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
  padding: 24px;
  background: var(--bg-main);
  width: 100%;
}

/* RESPONSIVE MOBILE */
@media (max-width: 1024px) {
  .app-sidebar {
    transform: translateX(-100%);
  }
  
  .app-sidebar.mobile-show {
    transform: translateX(0);
    box-shadow: 10px 0 30px rgba(0, 0, 0, 0.5);
  }

  .app-main {
    margin-left: 0 !important;
    width: 100% !important;
  }

  .mobile-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(11, 15, 25, 0.7);
    backdrop-filter: blur(4px);
    z-index: 95;
  }
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 32px;
  }
  .hero-subtitle {
    font-size: 15px;
  }
  .hero-section {
    padding-top: 40px;
  }
}
</style>