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

        <!-- Upload Foto Modern -->
        <div class="form-group">
          <label>Foto Bukti (1 - 5 foto, Maks. 2MB/file)</label>
          
          <!-- Dropzone Box -->
          <div 
            class="dropzone-box"
            :class="{ 'is-dragover': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
          >
            <input 
              ref="fileInputRef"
              type="file" 
              multiple 
              accept="image/png, image/jpeg, image/jpg" 
              class="hidden-file-input"
              @change="handleFileUpload" 
            />
            
            <div class="dropzone-content">
              <div class="upload-icon">📸</div>
              <p class="dropzone-text">
                <strong>Klik untuk unggah</strong> atau seret foto ke sini
              </p>
              <span class="dropzone-hint">Format: JPG, JPEG, PNG (Maksimal 5 foto)</span>
            </div>
          </div>

          <!-- Preview Grid -->
          <div v-if="imagePreviews.length > 0" class="preview-grid">
            <div 
              v-for="(img, index) in imagePreviews" 
              :key="index" 
              class="preview-item"
            >
              <img :src="img.url" :alt="'Preview ' + index" class="preview-img" />
              <button 
                type="button" 
                class="btn-remove-img" 
                title="Hapus Foto"
                @click.stop="removeImage(index)"
              >
                &times;
              </button>
              <span class="file-size-badge">{{ img.size }}</span>
            </div>
          </div>

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
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const form = ref({
  title: '',
  description: '',
  category_id: '',
  location_id: '',
  images: []
});

// State Khusus Handling Upload Foto
const fileInputRef = ref(null);
const imagePreviews = ref([]);
const isDragging = ref(false);

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

// Functions untuk Upload Foto
const triggerFileInput = () => {
  fileInputRef.value.click();
};

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const processFiles = (files) => {
  const selectedFiles = Array.from(files);
  
  if (form.value.images.length + selectedFiles.length > 5) {
    alert('Maksimal hanya bisa mengunggah 5 foto!');
    return;
  }

  selectedFiles.forEach((file) => {
    if (file.size > 2 * 1024 * 1024) {
      alert(`File "${file.name}" melebihi batas 2MB!`);
      return;
    }

    form.value.images.push(file);
    imagePreviews.value.push({
      url: URL.createObjectURL(file),
      size: formatSize(file.size)
    });
  });
};

const handleFileUpload = (e) => {
  processFiles(e.target.files);
  e.target.value = '';
};

const handleDrop = (e) => {
  isDragging.value = false;
  if (e.dataTransfer.files) {
    processFiles(e.dataTransfer.files);
  }
};

const removeImage = (index) => {
  URL.revokeObjectURL(imagePreviews.value[index].url);
  imagePreviews.value.splice(index, 1);
  form.value.images.splice(index, 1);
};

const submitReport = async () => {
  if (form.value.images.length === 0) {
    alert('Wajib melampirkan minimal 1 foto bukti!');
    return;
  }

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
    
    // Cleanup Preview URLs & Reset Form
    imagePreviews.value.forEach(img => URL.revokeObjectURL(img.url));
    imagePreviews.value = [];
    form.value = { title: '', description: '', category_id: '', location_id: '', images: [] };

    // Redirect otomatis
    router.push('/kelola-laporan'); 
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

/* Styles Input Foto Modern */
.hidden-file-input { display: none; }

.dropzone-box {
  border: 2px dashed #cbd5e1;
  background-color: #f8fafc;
  border-radius: 12px;
  padding: 24px 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dropzone-box:hover,
.dropzone-box.is-dragover {
  border-color: #6366f1;
  background-color: #eef2ff;
}

.dropzone-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.upload-icon { font-size: 28px; }
.dropzone-text { font-size: 13px; color: #334155; margin: 0; }
.dropzone-text strong { color: #4f46e5; }
.dropzone-hint { font-size: 11px; color: #94a3b8; }

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.preview-item {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.preview-img { width: 100%; height: 100%; object-fit: cover; }

.btn-remove-img {
  position: absolute;
  top: 4px;
  right: 4px;
  background-color: rgba(239, 68, 68, 0.9);
  color: #ffffff;
  border: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove-img:hover { background-color: #dc2626; }

.file-size-badge {
  position: absolute;
  bottom: 4px;
  left: 4px;
  background-color: rgba(15, 23, 42, 0.75);
  color: #ffffff;
  font-size: 9px;
  padding: 1px 4px;
  border-radius: 4px;
}
</style>