// ============================================================
// main.js — Titik awal/entry point aplikasi Vue 3
// ============================================================
// Di sinilah aplikasi Vue "dinyalakan" dan ditempelkan ke HTML
// ============================================================

import { createApp } from 'vue'    // Import fungsi untuk membuat aplikasi Vue
import App from './App.vue'        // Import komponen root (induk semua halaman)
import router from './router'      // Import konfigurasi routing/navigasi
import './style.css'               // Import CSS global

// Buat aplikasi Vue, pasangkan router, lalu mount ke elemen #app di index.html
createApp(App).use(router).mount('#app')