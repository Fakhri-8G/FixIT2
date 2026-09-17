<template>
  <div class="form-wrapper">
    <div class="form-card">
      <header class="form-header">
        <h2>Buat Laporan Baru</h2>
        <p>Isi formulir kerusakan di bawah ini secara lengkap.</p>
      </header>

      <form @submit.prevent="submitReport" class="form-body">
        <!-- Judul -->
        <div class="form-group">
          <label>Judul Kerusakan</label>
          <input 
            v-model="form.title" 
            type="text" 
            placeholder="Contoh: AC Lab RPL Mati Total" 
            required 
          />
          <span v-if="errors.title" class="field-error">{{ errors.title[0] }}</span>
        </div>

        <!-- Row: Kategori & Lokasi -->
        <div class="form-row">
          <div class="form-group">
            <label>Kategori</label>
            <select v-model="form.category_id" required>
              <option value="" disabled selected>-- Pilih Kategori --</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
            <span v-if="errors.category_id" class="field-error">{{ errors.category_id[0] }}</span>
          </div>

          <div class="form-group">
            <label>Lokasi</label>
            <select v-model="form.location_id" required>
              <option value="" disabled selected>-- Pilih Lokasi --</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                {{ loc.name }}
              </option>
            </select>
            <span v-if="errors.location_id" class="field-error">{{ errors.location_id[0] }}</span>
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="form-group">
          <label>Deskripsi Kerusakan</label>
          <textarea 
            v-model="form.description" 
            rows="4" 
            placeholder="Jelaskan detail kronologi atau kondisi barang..." 
            required
          ></textarea>
          <span v-if="errors.description" class="field-error">{{ errors.description[0] }}</span>
        </div>

        <!-- Upload Foto -->
        <div class="form-group">
          <label>Foto Bukti (1 - 5 foto, Maks. 2MB/file)</label>
          <div class="file-input-wrapper">
            <input 
              type="file" 
              multiple 
              accept="image/png, image/jpeg, image/jpg" 
              @change="handleFileUpload" 
              required 
            />
          </div>
          <small class="hint">Format yang didukung: JPG, JPEG, PNG.</small>
          <span v-if="errors.images" class="field-error">{{ errors.images[0] }}</span>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-submit" :disabled="submitting">
          {{ submitting ? 'Mengirim Laporan...' : 'Kirim Laporan' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const form = ref({
  title: '',
  description: '',
  category_id: '',
  location_id: '',
  images: []
});

const categories = ref([]);
const locations = ref([]);
const errors = ref({});
const submitting = ref(false);

// Tembak API pake Header Token
const token = localStorage.getItem('token') || '';
const api = axios.create({
  baseURL: 'http://10.10.11.145:8000/api',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

// Load Dropdown saat komponen dimuat
const fetchDropdownData = async () => {
  try {
    const [catRes, locRes] = await Promise.all([
      api.get('/categories'),
      api.get('/locations')
    ]);
    categories.value = catRes.data.data || [];
    locations.value = locRes.data.data || [];
  } catch (err) {
    alert('Gagal mengambil data Kategori/Lokasi. Cek koneksi atau token login!');
  }
};

const handleFileUpload = (e) => {
  form.value.images = Array.from(e.target.files);
};

const submitReport = async () => {
  submitting.value = true;
  errors.value = {};

  const formData = new FormData();
  formData.append('title', form.value.title);
  formData.append('description', form.value.description);
  formData.append('category_id', form.value.category_id);
  formData.append('location_id', form.value.location_id);

  form.value.images.forEach((file) => {
    formData.append('images[]', file);
  });

  try {
    const res = await api.post('/reports', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    alert(res.data.message || 'Laporan berhasil terkirim!');
    // Reset Form
    form.value = { title: '', description: '', category_id: '', location_id: '', images: [] };
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
    } else {
      alert(err.response?.data?.message || 'Terjadi kesalahan pada server!');
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(fetchDropdownData);
</script>

<style scoped>
.form-wrapper { display: flex; justify-content: center; padding: 40px 20px; background: linear-gradient(180deg, #ffffff 0%, #06294B 100%); min-height: 100vh; border-radius: 20px; }
.form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; width: 100%; max-width: 640px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.form-header h2 { font-size: 24px; color: #0f172a; font-weight: 700; margin-bottom: 6px; }
.form-header p { font-size: 14px; color: #64748b; margin-bottom: 24px; }

.form-body { display: flex; flex-direction: column; gap: 18px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 14px; font-weight: 600; color: #334155; }

.form-group input[type="text"],
.form-group select,
.form-group textarea {
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
  font-size: 14px;
  transition: border-color 0.2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #4f46e5; }

.hint { font-size: 12px; color: #94a3b8; }
.field-error { color: #ef4444; font-size: 12px; font-weight: 500; }

.btn-submit {
  background: #4f46e5;
  color: #fff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
  transition: background 0.2s;
}
.btn-submit:hover { background: #4338ca; }
.btn-submit:disabled { background: #94a3b8; cursor: not-allowed; }
</style>