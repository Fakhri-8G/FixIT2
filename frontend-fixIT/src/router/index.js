import { createRouter, createWebHistory } from 'vue-router'

// ─── Import semua halaman (views) ─────────────────────────
// Halaman Publik (bisa diakses tanpa login)
import HomeView     from '../public/HomeView.vue'

const routes = [
  // Halaman publik (bisa diakses tanpa login)
  { path: '/home',           component: HomeView,   name: 'home' },

]

const router = createRouter({
  history: createWebHistory(),  // Pakai URL biasa (bukan /#/)
  routes,
})

export default router
