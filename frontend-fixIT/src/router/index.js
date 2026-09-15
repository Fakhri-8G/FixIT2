import { createRouter, createWebHistory } from 'vue-router'


//Auth
import HomeView from '../views/public/HomeView.vue' 
import LoginView from '../views/Auth/LoginView.vue'
import RegisterView from '../views/Auth/RegisterView.vue'

//Kategori
import KelolaKategoriView from '../views/admin/kategori/KelolaKategoriView.vue'
import TambahKategoriView from '../views/admin/kategori/TambahKategoriView.vue'
import EditKategoriView from '../views/admin/kategori/EditKategoriView.vue'

//Lokasi
import KelolaLokasi from '../views/admin/lokasi/KelolaLokasi.vue'
import TambahLokasi from '../views/admin/lokasi/TambahLokasi.vue'
import EditLokasi from '../views/admin/lokasi/EditLokasi.vue'

//pengguna
import KelolaPengguna from '../views/admin/pengguna/KelolaPengguna.vue'

//laopran
import KelolaLaporan from '../views/user/laporan/KelolaLaporan.vue'
import TambahLaporan from '../views/user/laporan/TambahLaporan.vue'

//Dashboard
import DashboardAdmin from '../views/admin/DashboardKerusakanView.vue'
import DashboardUser from '../views/user/DashboardUserView.vue'


import Tentang from '../views/user/Tentang.vue'

const routes = [
  { 
    path: '/login', 
    name: 'login',
    component: LoginView 
  },
  { 
    path: '/register', 
    name: 'register',
    component: RegisterView 
  },

  {
    path: '/tentang',
    name: 'tentang',
    component: Tentang
  },

  // Halaman Admin & Kategori (Khusus Admin/Petugas)
  { 
    path: '/kategori', 
    name: 'kategori',
    component: KelolaKategoriView,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },
  { 
    path: '/TambahKategori', 
    name: 'TambahKategori',
    component: TambahKategoriView,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },
  { 
    path: '/edit-kategori/:id', 
    name: 'edit-kategori',
    component: EditKategoriView,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },

  //Lokasi
  { 
    path: '/lokasi', 
    name: 'lokasi',
    component: KelolaLokasi,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },
  { 
    path: '/TambahLokasi', 
    name: 'TambahLokasi',
    component: TambahLokasi,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },
  { 
    path: '/edit-lokasi/:id', 
    name: 'edit-lokasi',
    component: EditLokasi,
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },

  //pengguna
  {
    path: '/pengguna',
    name: 'pengguna',
    component: KelolaPengguna,
    meta: { requiresAuth: true, requiresAdmin: true }
  },

  { 
    path: '/admin/dashboard-kerusakan', 
    name: 'admin-dashboard',
    component: DashboardAdmin, 
    meta: { requiresAuth: true, requiresAdmin: true } // TAMBAH META REGISTRATION ADMIN
  },

  // Halaman User Biasa
  { 
    path: '/dashboard', 
    name: 'user-dashboard',
    component: DashboardUser,
    meta: { requiresAuth: true }
  },

  //laporan
  {
    path: '/kelola-laporan',
    name: 'kelola-laporan',
    component: KelolaLaporan,
    meta: { requiresAuth: true }
  },
  {
    path: '/tambah-laporan',
    name: 'tambah-laporan',
    component: TambahLaporan,
    meta: { requiresAuth: true }
  },

  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ─── NAVIGATION GUARD SECURE ─────────────────
router.beforeEach((to) => {
  const token = localStorage.getItem('token')

  // Helper parsing user biar gak berulang-ulang try-catch
  let user = null
  let role = ''
  if (token) {
    try {
      user = JSON.parse(localStorage.getItem('user') || '{}')
      role = user?.role ? String(user.role).toLowerCase() : ''
    } catch (e) {
      // Data corrupt? Bersihin & minta re-login!
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      return '/login'
    }
  }

  // 1. Kalau BELUM LOGIN tapi mau akses halaman berproteksi (requiresAuth)
  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  // 2. Kalau KEDAPATAN MAU AKSES HALAMAN ADMIN tapi BUKAN ADMIN/PETUGAS
  if (to.meta.requiresAdmin) {
    const isAdminOrPetugas = role === 'admin' || role === 'petugas'
    if (!isAdminOrPetugas) {
      // User biasa nekat masuk ke /admin? Lempar ke dashboard user!
      return '/dashboard' 
    }
  }

  // 3. Kalau UDAH LOGIN tapi iseng buka halaman /login atau /register
  if ((to.name === 'login' || to.name === 'register') && token) {
    if (role === 'admin' || role === 'petugas') {
      return '/admin/dashboard-kerusakan'
    }
    return '/dashboard'
  }

  // Lolos semua proteksi
  return true
})

export default router