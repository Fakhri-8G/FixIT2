<template>
  <div class="contact-wrapper">
    <!-- Header Area -->
    <div class="header-section">
      <div class="brand-pill">
        <span class="live-pulse"></span> FixIt Help & Operations
      </div>
      <h1 class="main-title">Pusat Informasi <span>& Support</span></h1>
      <p class="sub-title">Akses cepat ke semua jalur komunikasi resmi, lokasi, dan panduan kendala teknis.</p>
    </div>

    <!-- Full Vertical Layout Flow -->
    <div class="vertical-flow">
      
      <!-- Section 1: Jalur Komunikasi Utama -->
      <div class="section-block">
        <h2 class="section-title"> Jalur Komunikasi Resmi</h2>
        
        <div class="accordion-list">
          <!-- Accordion 1: WhatsApp Hotline -->
          <div class="acc-card wa-theme">
            <div class="acc-header">
              <div class="header-left">
                <div class="icon-avatar">💬</div>
                <div>
                  <h3>WhatsApp Fast Response</h3>
                  <span class="sub-label">Rekomendasi utama untuk kendala teknis mendesak</span>
                </div>
              </div>
              <span class="badge-status online">Active Now</span>
            </div>
            <div class="acc-body">
              <div class="body-inner">
                <p class="body-desc">Gunakan WhatsApp untuk konsultasi tentang kerja sistem dan laporan bug secara realtime dengan tim support FixIt.</p>
                <div class="copy-box">
                  <span class="copy-val">{{ waNumber }}</span>
                  <button @click="copyText(waNumber, 'wa')" class="copy-btn">
                    {{ copiedType === 'wa' ? '✓ Copied' : 'Copy Text' }}
                  </button>
                </div>
                <a href="https://wa.me/6281234567890" target="_blank" class="action-btn btn-wa">
                  Buka Chat WhatsApp &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- Accordion 2: Email Support -->
          <div class="acc-card email-theme">
            <div class="acc-header">
              <div class="header-left">
                <div class="icon-avatar">✉️</div>
                <div>
                  <h3>Official Helpdesk Email</h3>
                  <span class="sub-label">Laporan bug sistem, proposal, & administrasi resmi</span>
                </div>
              </div>
              <span class="badge-status neutral">24/7 Monitored</span>
            </div>
            <div class="acc-body">
              <div class="body-inner">
                <p class="body-desc">Gunakan email untuk laporan yang membutuhkan dokumen pendukung, berkas transaksi, atau lampiran screenshot detail.</p>
                <div class="copy-box">
                  <span class="copy-val">{{ emailAddr }}</span>
                  <button @click="copyText(emailAddr, 'email')" class="copy-btn">
                    {{ copiedType === 'email' ? '✓ Copied' : 'Copy Text' }}
                  </button>
                </div>
                <a href="mailto:support@fixit.id" class="action-btn btn-email">
                  Kirim Email Support &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- Accordion 3: HQ Office -->
          <div class="acc-card office-theme">
            <div class="acc-header">
              <div class="header-left">
                <div class="icon-avatar">📍</div>
                <div>
                  <h3>Kantor Pusat Operasional</h3>
                  <span class="sub-label">SMK Assalaam Bandung.</span>
                </div>
              </div>
              <span :class="['badge-status', isOfficeOpen ? 'online' : 'offline']">
                {{ isOfficeOpen ? 'OPEN NOW' : 'CLOSED' }}
              </span>
            </div>
            <div class="acc-body">
              <div class="body-inner">
                <p class="body-desc">Lokasi fisik kantor operasional FixIt untuk konsultasi tatap muka dan pengurusan administrasi kemitraan.</p>
                <div class="office-details">
                  <div class="detail-row">
                    <span>📍 Alamat Lengkap:</span>
                    <strong>Jl. Situ Tarate - Terusan Cibaduyut, Desa Cangkuang Kulon, Kecamatan Dayeuhkolot, Kabupaten Bandung, Jawa Barat 40239. </strong>
                  </div>
                  <div class="detail-row">
                    <span>🕒 Jam Operasional Kantor:</span>
                    <strong>Senin - Jumat (08.00 - 17.00 WIB)</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Quick FAQ / Solusi Cepat -->
      <div class="section-block">
        <h2 class="section-title"> Solusi Cepat & Pertanyaan Umum (FAQ)</h2>
        
        <div class="faq-list">
          <details v-for="(faq, index) in faqs" :key="index" class="faq-item">
            <summary class="faq-question">
              <span>{{ faq.q }}</span>
              <span class="arrow">&rsaquo;</span>
            </summary>
            <div class="faq-answer">
              <p>{{ faq.a }}</p>
            </div>
          </details>
        </div>
      </div>

      <!-- Section 3: Status System Banner -->
      <div class="section-block">
        <div class="info-banner">
          <div class="banner-icon">💡</div>
          <div class="banner-content">
            <h4>Jadwal Pemeliharaan Server & Info Sistem</h4>
            <p>Jadwal rutin pemeliharaan server FixIt dilakukan secara otomatis setiap hari Minggu pukul 02.00 WIB. Selama proses pemeliharaan, beberapa fungsi sistem mungkin mengalami keterlambatan respon sementara.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../utils/api'

// Data Kontak
const waNumber = ref('+62 812-3456-7890')
const emailAddr = ref('support@fixit.id')
const copiedType = ref(null)

// Fitur Copy Text
const copyText = (text, type) => {
  navigator.clipboard.writeText(text)
  copiedType.value = type
  setTimeout(() => {
    copiedType.value = null
  }, 2000)
}

// Fitur Auto Check Jam Operasional
const isOfficeOpen = computed(() => {
  const now = new Date()
  const day = now.getDay()
  const hour = now.getHours()
  return day >= 1 && day <= 5 && hour >= 8 && hour < 17
})

// Data FAQ
const faqs = ref([
  {
    q: 'Berapa lama estimasi respon support WhatsApp?',
    a: 'Pesan akan dibalas secara realtime pada jam kerja operasional (08.00 - 15.00 WIB).'
  },
  {
    q: 'Bagaimana jika mengalami masalah di luar jam kerja?',
    a: 'Tetep bisa kirim email ke support@fixit.id. Tim kami bakal langsung merespon di jam kerja operasional berikutnya.'
  },
  {
    q: 'Apakah layanan konsultasi kendala teknis ini berbayar?',
    a: 'Tidak, layanan bantuan teknis awal dan penanganan kendala standar bersifat bebas biaya.'
  },
  {
    q: 'Apakah bisa melakukan pertemuan fisik di kantor pusat?',
    a: 'Bisa, datang langsung ke kantor pusat kami di Bandung sesuai jam kerja operasional yang berlaku.'
  }
])
</script>

<style scoped>
.contact-wrapper {
  max-width: 820px;
  margin: 0 auto;
  padding: 40px 20px 80px 20px;
  font-family: system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

/* Header Section */
.header-section {
  text-align: center;
  margin-bottom: 40px;
}

.brand-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background-color: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}

.live-pulse {
  width: 8px;
  height: 8px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.main-title {
  font-size: 36px;
  font-weight: 800;
  margin: 0;
  letter-spacing: -1px;
}

.main-title span {
  color: #4f46e5;
}

.sub-title {
  color: #64748b;
  font-size: 15px;
  margin-top: 8px;
}

/* Vertical Flow Container */
.vertical-flow {
  display: flex;
  flex-direction: column;
  gap: 36px;
}

.section-block {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #FFFFFF;
  margin: 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #f1f5f9;
}

/* Accordions */
.accordion-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.acc-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.acc-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
}

.acc-header {
  padding: 20px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  background-color: #ffffff;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.icon-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.acc-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.sub-label {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
  display: block;
}

.badge-status {
  font-size: 11px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 6px;
  text-transform: uppercase;
}

.badge-status.online { background-color: #dcfce7; color: #15803d; }
.badge-status.neutral { background-color: #e0e7ff; color: #4338ca; }
.badge-status.offline { background-color: #fee2e2; color: #b91c1c; }

/* Accordion Body */
.acc-body {
  max-height: 0;
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background-color: #fafafa;
}

.acc-card:hover .acc-body {
  max-height: 260px;
  opacity: 1;
  padding: 24px;
  border-top: 1px solid #f1f5f9;
}

.body-desc {
  font-size: 14px;
  color: #475569;
  margin: 0 0 16px 0;
  line-height: 1.6;
}

/* Copy Box */
.copy-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 16px;
}

.copy-val {
  font-family: monospace;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.copy-btn {
  background-color: #f1f5f9;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: background-color 0.2s;
}

.copy-btn:hover {
  background-color: #e2e8f0;
}

.action-btn {
  display: inline-block;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  color: #ffffff;
  transition: opacity 0.2s;
}

.btn-wa { background-color: #16a34a; }
.btn-email { background-color: #4f46e5; }
.action-btn:hover { opacity: 0.9; }

/* Office Details Area */
.office-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  font-size: 14px;
  background-color: #ffffff;
  padding: 16px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.detail-row span {
  color: #64748b;
  display: block;
  font-size: 12px;
  margin-bottom: 2px;
}

.detail-row strong {
  color: #0f172a;
}

/* FAQ Section */
.faq-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.faq-item {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  transition: border-color 0.2s;
}

.faq-item[open] {
  border-color: #cbd5e1;
}

.faq-question {
  padding: 16px 20px;
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  list-style: none;
}

.faq-question::-webkit-details-marker {
  display: none;
}

.arrow {
  font-size: 20px;
  transition: transform 0.2s;
}

.faq-item[open] .arrow {
  transform: rotate(90deg);
}

.faq-answer {
  padding: 0 20px 16px 20px;
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
}

.faq-answer p {
  margin: 0;
}

/* Info Banner Full Width */
.info-banner {
  background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
  border: 1px solid #c7d2fe;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.banner-icon {
  font-size: 26px;
}

.banner-content h4 {
  margin: 0 0 6px 0;
  font-size: 15px;
  font-weight: 700;
  color: #1e1b4b;
}

.banner-content p {
  margin: 0;
  font-size: 13px;
  color: #3730a3;
  line-height: 1.5;
}

/* Responsive Mobile Fallback */
@media (max-width: 640px) {
  .acc-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .badge-status {
    align-self: flex-start;
  }
}
</style>