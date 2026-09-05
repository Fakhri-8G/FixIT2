import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '../views/public/HomeView.vue' 
import LoginView from '../views/Auth/LoginView.vue'
import RegisterView from '../views/Auth/RegisterView.vue'
import KelolaKategoriView from '../views/admin/kategori/KelolaKategoriView.vue'
import TambahKategoriView from '../views/admin/kategori/TambahKategoriView.vue'

import DashboardAdmin from '../views/admin/DashboardKerusakanView.vue'
import DashboardUser from '../views/user/DashboardUserView.vue'

const routes = [
  { 
    path: '/', 
    name: 'home',
    component: HomeView 
  },
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
    path: '/kategori', 
    name: 'kategori',
    component: KelolaKategoriView 
  },
  { 
    path: '/TambahKategori', 
    name: 'TambahKategori',
    component: TambahKategoriView 
  },

  { 
    path: '/admin/dashboard-kerusakan', 
    name: 'admin-dashboard',
    component: DashboardAdmin 
  },
  { 
    path: '/dashboard', 
    name: 'user-dashboard',
    component: DashboardUser 
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

// ─── Navigation Guard (FIX SYNTAX RETURN) ─────────────────
// Hapus parameter 'next', kita cuma pakai (to, from)
router.beforeEach((to, from) => {
  const token = localStorage.getItem('token')

  // Kalau user mau ke halaman login/register tapi kondisi udah punya token
  if ((to.name === 'login' || to.name === 'register') && token) {
    try {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const role = user?.role ? String(user.role).toLowerCase() : ''

      if (role === 'admin' || role === 'petugas') {
        return '/admin/dashboard-kerusakan' // Pengganti next('/admin/dashboard-kerusakan')
      }
      return '/dashboard' // Pengganti next('/dashboard')
    } catch (e) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      return true // Izinkan lanjut ke halaman tujuan jika data corrupt
    }
  }

  // Tanpa return apa-apa atau return true berarti mengizinkan navigasi biasa
  return true
})

export default router