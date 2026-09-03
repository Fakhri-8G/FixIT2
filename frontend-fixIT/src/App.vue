<template>
  <!-- ═══════════════════════════════════════════════════
       TAMPILAN GUEST (belum login) → Navbar di atas
       ═══════════════════════════════════════════════════ -->
  <template v-if="!isLoggedIn">
    <nav class="navbar">
      <div class="navbar-brand">
        <RouterLink to="/">🔧 FixIT </RouterLink>
      </div>

      <div class="navbar-menu">
        <RouterLink to="/"> Beranda</RouterLink>
        <RouterLink to="/cara-kerja">Cara Kerja</RouterLink>
        <RouterLink to="/tentang">Tentang</RouterLink>
        <RouterLink to="/kontak">Kontak</RouterLink>
        <RouterLink to="/login" class="btn-nav-login">Login</RouterLink>
      </div>
    </nav>

    <RouterView />
  </template>

  <!-- ═══════════════════════════════════════════════════
       TAMPILAN SUDAH LOGIN (siswa / admin) → Sidebar kiri
       ═══════════════════════════════════════════════════ -->
  <div v-else class="app-shell">
    <aside class="sidebar" :class="{ 'sidebar-admin': isAdmin }">
      <div class="sidebar-brand">
        <span class="brand-icon">🔧</span>
        <span class="brand-text">FixIT</span>
      </div>

      <nav class="sidebar-menu">
        <!-- Menu SISWA -->
        <template v-if="!isAdmin">
          <RouterLink to="/dashboard" class="menu-item">
            <span class="menu-icon">🏠</span> Dashboard
          </RouterLink>
          <RouterLink to="/buat-laporan" class="menu-item">
            <span class="menu-icon">📝</span> Buat Laporan
          </RouterLink>
          <RouterLink to="/laporan-saya" class="menu-item">
            <span class="menu-icon">📋</span> Laporan Saya
          </RouterLink>
          <RouterLink to="/profile" class="menu-item">
            <span class="menu-icon">👤</span> Profile
          </RouterLink>
        </template>

        <!-- Menu ADMIN -->
        <template v-else>
          <RouterLink to="/dashboard" class="menu-item">
            <span class="menu-icon">🏠</span> Dashboard
          </RouterLink>
          <RouterLink to="/semua-laporan" class="menu-item">
            <span class="menu-icon">📋</span> Semua Laporan
          </RouterLink>
          <RouterLink to="/kategori" class="menu-item">
            <span class="menu-icon">🗂️</span> Kategori
          </RouterLink>
          <RouterLink to="/lokasi" class="menu-item">
            <span class="menu-icon">📍</span> Lokasi
          </RouterLink>
          <RouterLink to="/pengguna" class="menu-item">
            <span class="menu-icon">👥</span> Pengguna
          </RouterLink>
          <RouterLink to="/profile" class="menu-item">
            <span class="menu-icon">👤</span> Profile
          </RouterLink>
        </template>
      </nav>

      <button @click="handleLogout" class="btn-sidebar-logout">
        <span class="menu-icon">🚪</span> Keluar
      </button>
    </aside>

    <div class="main-area">
      <header class="topbar">
        <button class="hamburger" @click="sidebarOpen = !sidebarOpen">☰</button>
        <div class="topbar-right">
          <button class="btn-bell">🔔</button>
          <div class="user-chip">
            <img
              class="avatar"
              src="https://api.dicebear.com/7.x/avataaars/svg?seed=fixit"
              alt="avatar"
            />
            <div class="user-info">
              <span class="user-name">{{ userName }}</span>
              <span class="user-role">{{ isAdmin ? 'Administrator' : 'Siswa' }}</span>
            </div>
          </div>
        </div>
      </header>

      <main class="page-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'
import api from './utils/api'

const router     = useRouter()
const route      = useRoute()
const isLoggedIn = ref(false)
const isAdmin    = ref(false)
const userName   = ref('')
const sidebarOpen = ref(true)

// ─── Fungsi cek status login & role dari localStorage ──────
const cekStatusLogin = () => {
  const token = localStorage.getItem('token')
  const user  = localStorage.getItem('user')

  if (token && user) {
    try {
      const parsedUser = JSON.parse(user)
      isLoggedIn.value = true
      userName.value   = parsedUser.name
      isAdmin.value    = parsedUser.role === 'admin'
    } catch (e) {
      isLoggedIn.value = false
    }
  } else {
    isLoggedIn.value = false
    isAdmin.value    = false
    userName.value   = ''
  }
}

// Cek saat app pertama kali dibuka
onMounted(() => { cekStatusLogin() })

// Cek ulang setiap kali URL berubah → sidebar/navbar langsung update!
watch(() => route.path, () => { cekStatusLogin() })

// ─── Fungsi Logout ───────────────────────────────────────────
const handleLogout = async () => {
  try {
    await api.post('/logout')   // Hapus token di server
  } catch (err) {
    console.warn('Logout API error:', err)
  } finally {
    localStorage.removeItem('token')   // Hapus token di browser
    localStorage.removeItem('user')

    isLoggedIn.value = false           // Update tampilan
    isAdmin.value    = false
    userName.value   = ''

    router.push('/login')              // Redirect ke login
  }
}
</script>

<style>
/* ═══════════════════════════════════════════
   RESET & BASE STYLE
   ═══════════════════════════════════════════ */
body {
  margin: 0;
  padding: 20px;
  background: linear-gradient(135deg, #0062ff 0%, #00c6ff 100%) !important;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  min-height: 100vh;
  box-sizing: border-border-box;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* ═══════════════════════════════════════════
   NAVBAR (Guest)
   ═══════════════════════════════════════════ */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 36px;
  background: #ffffff !important;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
  position: sticky;
  top: 20px;
  z-index: 100;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

.navbar-brand a {
  color: #0f172a !important;
  font-size: 20px;
  font-weight: 800;
  text-decoration: none;
  letter-spacing: -0.5px;
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: 28px;
}

.navbar-menu a {
  color: #64748b !important;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.navbar-menu a:hover,
.navbar-menu a.router-link-active {
  color: #0088ff !important;
}

.btn-nav-login {
  background: #0088ff !important;
  color: #ffffff !important;
  padding: 10px 24px;
  border-radius: 12px;
  font-weight: 600 !important;
  box-shadow: 0 4px 14px rgba(0, 136, 255, 0.35);
  transition: all 0.2s ease !important;
}

.btn-nav-login:hover {
  background: #0070d2 !important;
  transform: translateY(-1px);
}

/* ═══════════════════════════════════════════
   APP SHELL (Logged in: Siswa / Admin)
   ── Visual Floating Container khas FinTech ──
   ═══════════════════════════════════════════ */
.app-shell {
  display: flex;
  width: 100%;
  max-width: 1440px;
  min-height: 88vh;
  background: #f8fafc !important;
  border-radius: 28px;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 30, 80, 0.25) !important;
  margin: auto;
}

/* ── Sidebar ───────────────────────────────── */
.sidebar {
  width: 240px;
  flex-shrink: 0;
  background: #ffffff !important;
  border-right: 1px solid #e2e8f0 !important;
  display: flex;
  flex-direction: column;
  padding: 28px 18px;
  box-sizing: border-box;
}

.sidebar.sidebar-admin {
  background: #0f172a !important; /* Elegant Dark Slate untuk Admin */
  border-right: none !important;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 8px 32px 8px;
  font-size: 20px;
  font-weight: 800;
  color: #0088ff !important;
  letter-spacing: -0.5px;
}

.sidebar-admin .sidebar-brand {
  color: #ffffff !important;
}

.sidebar-menu {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  border-radius: 12px;
  color: #64748b !important;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.sidebar-admin .menu-item {
  color: #94a3b8 !important;
}

.menu-item:hover {
  background: #f1f5f9 !important;
  color: #0f172a !important;
}

.sidebar-admin .menu-item:hover {
  background: rgba(255, 255, 255, 0.06) !important;
  color: #ffffff !important;
}

/* Active State khas Gambar (Soft Blue pill dengan teks/icon terang) */
.menu-item.router-link-active {
  background: #e0f2fe !important;
  color: #0088ff !important;
  font-weight: 700;
}

.sidebar-admin .menu-item.router-link-active {
  background: #0088ff !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(0, 136, 255, 0.3);
}

.menu-icon {
  font-size: 16px;
  width: 18px;
  text-align: center;
}

.btn-sidebar-logout {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  border: none;
  background: transparent;
  color: #ef4444 !important;
  font-size: 14px;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s ease;
  margin-top: auto;
}

.btn-sidebar-logout:hover {
  background: #fef2f2 !important;
}

.sidebar-admin .btn-sidebar-logout:hover {
  background: rgba(239, 68, 68, 0.15) !important;
}

/* ── Main Area ─────────────────────────────── */
.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: #f8fafc !important;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 36px;
  background: transparent !important;
  border-bottom: 1px solid #e2e8f0 !important;
}

.hamburger {
  display: none;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  color: #0f172a !important;
  font-size: 18px;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 10px;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-left: auto;
}

.btn-bell {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  color: #64748b !important;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.btn-bell:hover {
  color: #0088ff !important;
  border-color: #0088ff !important;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 16px 6px 6px;
  border-radius: 30px;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e0f2fe !important;
  object-fit: cover;
}

.user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a !important;
}

.user-role {
  font-size: 11px;
  font-weight: 500;
  color: #94a3b8 !important;
}

.page-content {
  flex: 1;
  padding: 36px;
  overflow-y: auto;
  background: #f8fafc !important;
}

/* ── Responsive ────────────────────────────── */
@media (max-width: 992px) {
  body {
    padding: 0;
  }
  .app-shell {
    border-radius: 0;
    min-height: 100vh;
  }
  .hamburger {
    display: block;
  }
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 200;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    box-shadow: 10px 0 30px rgba(0, 0, 0, 0.15);
  }
}
</style>