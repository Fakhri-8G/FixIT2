<template>
  <div class="about-container">
    <!-- Hero Section -->
    <header class="hero-section">
      <div class="badge-tag">Tentang Kami</div>
      <h1 class="hero-title">Mengenal Lebih Dekat <span class="brand-text">FixIT</span></h1>
      <p class="hero-subtitle">
        Membangun solusi digital yang intuitif, presisi, dan dapat diandalkan untuk efisiensi sistem pengelolaan data.
      </p>
    </header>

    <!-- Main Story Section -->
    <section class="grid-section">
      <div class="story-card">
        <h3>Misi & Visi FixIT</h3>
        <p>
          Platform terpadu untuk melaporkan, memantau, dan mempercepat penanganan kerusakan fasilitas di area SMk Assalaam Bandung .
        </p>
        <p>
          <strong>Visi Kami:</strong> Menjadi platform tanggap cepat utama dalam menjaga kelayakan, kenyamanan, dan keandalan seluruh sarana serta prasarana fasilitas.
        </p>
      </div>

      <div class="stats-card">
        <div class="stat-item">
          <span class="stat-number">99.9%</span>
          <span class="stat-label">Keandalan Sistem</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-number">Fast</span>
          <span class="stat-label">Respon & Performa</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-number">Secure</span>
          <span class="stat-label">Proteksi Data</span>
        </div>
      </div>
    </section>

    <!-- Core Team Section -->
    <section class="team-section">
      <div class="section-header">
        <h3>Tim Di Balik FixIT</h3>
        <p>Orang-orang berdedikasi di balik pengembangan dan inovasi sistem.</p>
      </div>

      <div class="team-grid">
        <div v-for="member in teamMembers" :key="member.id" class="team-card">
          <div class="avatar-circle">
            {{ getInitials(member.name) }}
          </div>
          <h4>{{ member.name }}</h4>
          <span class="role-badge">{{ member.role }}</span>
          <p class="member-desc">{{ member.desc }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const teamMembers = ref([
  {
    id: 1,
    name: 'Fadil',
    role: 'Lead Developer',
    desc: 'Merancang antarmuka yang bersih, konsisten, dan mudah digunakan.'
  },
  {
    id: 2,
    name: 'Fakhri',
    role: 'System Architect',
    desc: 'Mengelola integrasi database, performa backend, dan keamanan.'
  },
]);

const getInitials = (name) => {
  if (!name) return 'D';
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.about-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  max-width: 950px;
  margin: 40px auto;
  padding: 0 20px;
  color: #0f172a;
}

/* Hero Section */
.hero-section {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
  border-radius: 20px;
  color: #ffffff;
  margin-bottom: 30px;
  box-shadow: 0 10px 25px -5px rgba(49, 46, 129, 0.2);
}

.badge-tag {
  display: inline-block;
  padding: 4px 12px;
  background-color: rgba(255, 255, 255, 0.15);
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.hero-title {
  font-size: 2rem;
  font-weight: 800;
  margin: 0 0 12px 0;
  letter-spacing: -0.025em;
}

.brand-text {
  color: #818cf8;
}

.hero-subtitle {
  font-size: 0.95rem;
  color: #c7d2fe;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Story & Stats Grid */
.grid-section {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 20px;
  margin-bottom: 30px;
}

.story-card, .stats-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
}

.story-card h3 {
  margin: 0 0 14px 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
}

.story-card p {
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 12px;
}

.story-card p:last-child {
  margin-bottom: 0;
}

/* Stats Card */
.stats-card {
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  background: #f8fafc;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 1.8rem;
  font-weight: 800;
  color: #6366f1;
}

.stat-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
}

.stat-divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 12px 0;
}

/* Team Section */
.team-section {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px 28px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
}

.section-header {
  text-align: center;
  margin-bottom: 28px;
}

.section-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
}

.section-header p {
  margin: 4px 0 0 0;
  font-size: 0.85rem;
  color: #64748b;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.team-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.team-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
}

.avatar-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #ffffff;
  font-size: 1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.team-card h4 {
  margin: 0 0 4px 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.role-badge {
  display: inline-block;
  padding: 2px 8px;
  background-color: #e0e7ff;
  color: #4338ca;
  font-size: 0.725rem;
  font-weight: 600;
  border-radius: 6px;
  margin-bottom: 10px;
}

.member-desc {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .grid-section {
    grid-template-columns: 1fr;
  }
}
</style>