<template>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3">
      <h3 class="card-title fw-bold text-dark m-0">
        {{ isEditMode ? 'Edit Data Pegawai' : 'Tambah Data Pegawai' }}
      </h3>
    </div>
    <div class="card-body">
      <!-- Error messages -->
      <div v-if="errors.length" class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3">
          <li v-for="(err, index) in errors" :key="index">{{ err }}</li>
        </ul>
      </div>

      <form @submit.prevent="saveEmployee" enctype="multipart/form-data">
        <div class="row g-4">
          <!-- LEFT COLUMN: PERSONAL DATA -->
          <div class="col-lg-6">
            <h4 class="fw-bold text-secondary mb-3 border-bottom pb-2">Data Diri</h4>
            
            <div class="row g-3">
              <!-- Photo Upload & NIP & Name -->
              <div class="col-12">
                <div class="row align-items-center g-3">
                  <div class="col-auto text-center">
                    <img
                      :src="photoPreview || '/images/avatar-placeholder.svg'"
                      alt="Avatar"
                      class="foto-profil border shadow-sm mb-2"
                      style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;"
                    />
                    <div>
                      <label
                        for="unggah-foto"
                        class="btn btn-outline-primary btn-sm px-3 cursor-pointer"
                      >
                        Pilih Foto
                      </label>
                      <input
                        id="unggah-foto"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg"
                        hidden
                        @change="onPhotoChange"
                      />
                    </div>
                  </div>

                  <div class="col">
                    <!-- NIP -->
                    <div class="mb-3">
                      <label class="form-label required">NIP</label>
                      <input
                        type="text"
                        class="form-control"
                        placeholder="NIP (angka saja, min 8 digit)"
                        v-model="form.nip"
                        @input="validateNIP"
                        required
                      />
                      <div v-if="nipError" class="text-danger small mt-1">{{ nipError }}</div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                      <label class="form-label required">Nama Lengkap</label>
                      <input
                        type="text"
                        class="form-control"
                        placeholder="Nama Lengkap"
                        v-model="form.name"
                        @input="validateName"
                        required
                      />
                      <div v-if="nameError" class="text-danger small mt-1">{{ nameError }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Email -->
              <div class="col-md-6">
                <label class="form-label required">Email</label>
                <input
                  type="email"
                  class="form-control"
                  placeholder="name@company.com"
                  v-model="form.email"
                  required
                />
              </div>

              <!-- No HP -->
              <div class="col-md-6">
                <label class="form-label required">Nomor HP</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Contoh: +62822..."
                  v-model="form.phone"
                  required
                />
              </div>

              <!-- Tempat Lahir -->
              <div class="col-md-5">
                <label class="form-label required">Tempat Lahir</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Tempat Lahir"
                  v-model="form.birth_place"
                  required
                />
              </div>

              <!-- Tanggal Lahir -->
              <div class="col-md-5">
                <label class="form-label required">Tanggal Lahir</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.birth_date"
                  required
                />
              </div>

              <!-- Usia -->
              <div class="col-md-2">
                <label class="form-label">Usia</label>
                <input
                  type="number"
                  class="form-control bg-light"
                  :value="calculatedAge"
                  readonly
                  disabled
                />
              </div>

              <!-- Gender -->
              <div class="col-md-6">
                <div class="form-label required">Jenis Kelamin</div>
                <div class="d-flex gap-3">
                  <label class="form-check">
                    <input
                      class="form-check-input"
                      type="radio"
                      value="pria"
                      v-model="form.gender"
                    />
                    <span class="form-check-label">Pria</span>
                  </label>
                  <label class="form-check">
                    <input
                      class="form-check-input"
                      type="radio"
                      value="wanita"
                      v-model="form.gender"
                    />
                    <span class="form-check-label">Wanita</span>
                  </label>
                </div>
              </div>

              <!-- Status Pernikahan -->
              <div class="col-md-6">
                <div class="form-label required">Status Pernikahan</div>
                <div class="d-flex gap-3">
                  <label class="form-check">
                    <input
                      class="form-check-input"
                      type="radio"
                      value="tidak kawin"
                      v-model="form.marital_status"
                    />
                    <span class="form-check-label">Belum Menikah</span>
                  </label>
                  <label class="form-check">
                    <input
                      class="form-check-input"
                      type="radio"
                      value="kawin"
                      v-model="form.marital_status"
                    />
                    <span class="form-check-label">Menikah</span>
                  </label>
                </div>
              </div>

              <!-- Jumlah Anak -->
              <div class="col-md-6" v-if="form.marital_status === 'kawin'">
                <label class="form-label required">Jumlah Anak</label>
                <input
                  type="number"
                  min="0"
                  max="99"
                  class="form-control"
                  v-model="form.children_count"
                  required
                />
              </div>

              <!-- Alamat Lengkap -->
              <div class="col-12">
                <label class="form-label required">Alamat Lengkap</label>
                <textarea
                  class="form-control"
                  rows="3"
                  placeholder="Alamat Lengkap jalan, RT/RW..."
                  v-model="form.full_address"
                  required
                ></textarea>
              </div>

              <!-- Kecamatan Autocomplete -->
              <div class="col-md-4 position-relative">
                <label class="form-label required">Kecamatan</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Ketik kecamatan..."
                  v-model="districtSearch"
                  @input="onDistrictSearchInput"
                  @focus="showDistrictSuggestions = true"
                  required
                />
                <!-- Autocomplete suggestions dropdown -->
                <ul
                  v-if="showDistrictSuggestions && districtSuggestions.length > 0"
                  class="dropdown-menu show w-100 shadow-sm overflow-auto"
                  style="max-height: 200px; z-index: 1050; display: block;"
                >
                  <li v-for="d in districtSuggestions" :key="d.district_id">
                    <button
                      type="button"
                      class="dropdown-item py-2"
                      @click="selectDistrict(d)"
                    >
                      <div class="text-dark">{{ d.district_name }}</div>
                      <div class="small text-secondary">{{ d.regency_name }}, {{ d.province_name }}</div>
                    </button>
                  </li>
                </ul>
              </div>

              <!-- Kabupaten (Readonly) -->
              <div class="col-md-4">
                <label class="form-label">Kabupaten</label>
                <input
                  type="text"
                  class="form-control bg-light"
                  :value="form.regency_name"
                  readonly
                  disabled
                />
              </div>

              <!-- Provinsi (Readonly) -->
              <div class="col-md-4">
                <label class="form-label">Provinsi</label>
                <input
                  type="text"
                  class="form-control bg-light"
                  :value="form.province_name"
                  readonly
                  disabled
                />
              </div>

              <!-- Jarak Rumah - Kantor -->
              <div class="col-md-6">
                <label class="form-label required">Jarak Rumah - Kantor (KM)</label>
                <div class="input-group">
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="99"
                    class="form-control"
                    placeholder="Jarak"
                    v-model="form.distance_km"
                    required
                  />
                  <span class="input-group-text">KM</span>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: KEPEGAWAIAN & PENDIDIKAN -->
          <div class="col-lg-6">
            <h4 class="fw-bold text-secondary mb-3 border-bottom pb-2">Data Kepegawaian</h4>

            <div class="row g-3 mb-4">
              <!-- Tanggal Masuk -->
              <div class="col-12">
                <label class="form-label required">Tanggal Masuk</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.joined_at"
                  required
                />
              </div>

              <!-- Jabatan -->
              <div class="col-md-6">
                <label class="form-label required">Jabatan</label>
                <select v-model="form.position_id" class="form-select" required>
                  <option value="" disabled>Pilih jabatan</option>
                  <option v-for="pos in positions" :key="pos.id" :value="pos.id">
                    {{ pos.name }}
                  </option>
                </select>
              </div>

              <!-- Departemen -->
              <div class="col-md-6">
                <label class="form-label required">Departemen</label>
                <select v-model="form.department_id" class="form-select" required>
                  <option value="" disabled>Pilih departemen</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
              </div>

              <!-- Status Kontrak -->
              <div class="col-md-6">
                <label class="form-label required">Jenis Kontrak (Status Kerja)</label>
                <select v-model="form.employment_type" class="form-select" required>
                  <option value="" disabled>Pilih jenis kontrak</option>
                  <option value="pkwtt">PKWTT (Tetap)</option>
                  <option value="pkwt">PKWT (Kontrak)</option>
                  <option value="magang">Magang</option>
                </select>
              </div>

              <!-- Status Aktif/Nonaktif -->
              <div class="col-md-6">
                <label class="form-label">Status Keaktifan</label>
                <label class="form-check form-switch form-switch-3 mt-2">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    v-model="form.isActive"
                  />
                  <span class="form-check-label">{{ form.isActive ? 'Aktif' : 'Nonaktif' }}</span>
                </label>
              </div>
            </div>

            <!-- RIWAYAT PENDIDIKAN SUBFORM -->
            <h4 class="fw-bold text-secondary mb-3 border-bottom pb-2">Riwayat Pendidikan</h4>
            
            <div class="card shadow-none border mb-3">
              <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                  <thead>
                    <tr class="small text-secondary fw-bold">
                      <th>Jenjang</th>
                      <th>Nama Sekolah / Kampus</th>
                      <th width="120">Tahun Lulus</th>
                      <th width="40"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(edu, idx) in form.educations" :key="idx">
                      <td>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="SD/SMP/SMA/S1"
                          v-model="edu.education_level"
                          required
                        />
                      </td>
                      <td>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="Nama Sekolah"
                          v-model="edu.school_name"
                          required
                        />
                      </td>
                      <td>
                        <input
                          type="number"
                          min="1900"
                          max="2100"
                          class="form-control form-control-sm"
                          placeholder="Tahun"
                          v-model="edu.graduation_year"
                          required
                        />
                      </td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn btn-icon btn-ghost-danger btn-sm rounded-circle"
                          @click="removeEducation(idx)"
                          title="Hapus baris"
                        >
                          <IconXboxXFilled size="18" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="form.educations.length === 0">
                      <td colspan="4" class="text-center py-3 text-muted small">
                        Belum ada riwayat pendidikan yang ditambahkan.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="card-footer bg-transparent text-center py-2">
                <button
                  type="button"
                  class="btn btn-outline-primary btn-sm px-3"
                  @click="addEducation"
                >
                  Tambah Pendidikan
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SAVE / BACK BUTTONS -->
        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="goBack"
          >
            Kembali
          </button>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="saving || !isFormValid"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
            Simpan Data Pegawai
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '~/composables/useAuth';
import { IconXboxXFilled } from "@tabler/icons-vue";

const route = useRoute();
const router = useRouter();
const { apiFetch } = useAuth();

// Modes & Meta lists
const isEditMode = ref(false);
const positions = ref([]);
const departments = ref([]);
const loading = ref(true);
const saving = ref(false);
const errors = ref([]);

// Input validation errors
const nipError = ref('');
const nameError = ref('');

// Autocomplete State
const districtSearch = ref('');
const showDistrictSuggestions = ref(false);
const districtSuggestions = ref([]);
let districtDebounce = null;

// Photo State
const photoFile = ref(null);
const photoPreview = ref(null);

// Form
const form = ref({
  nip: '',
  name: '',
  email: '',
  phone: '',
  birth_place: '',
  birth_date: '',
  gender: 'pria',
  marital_status: 'tidak kawin',
  children_count: 0,
  joined_at: '',
  position_id: '',
  department_id: '',
  employment_type: 'pkwt',
  isActive: true,
  distance_km: '',
  district_id: '',
  regency_name: '-',
  province_name: '-',
  full_address: '',
  educations: []
});

// Helper for back navigation
const goBack = () => {
  router.push('/pegawai');
};

// Calculate age dynamically
const calculatedAge = computed(() => {
  if (!form.value.birth_date) return 0;
  const birthDate = new Date(form.value.birth_date);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const m = today.getMonth() - birthDate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age >= 0 ? age : 0;
});

// Validate NIP (number only)
const validateNIP = () => {
  const nip = form.value.nip;
  if (!nip) {
    nipError.value = 'NIP wajib diisi.';
    return;
  }
  if (nip.length < 8) {
    nipError.value = 'NIP minimal 8 digit.';
    return;
  }
  if (!/^[0-9]+$/.test(nip)) {
    nipError.value = 'NIP hanya boleh berisi angka.';
    return;
  }
  nipError.value = '';
};

// Validate Name
const validateName = () => {
  const name = form.value.name;
  if (!name) {
    nameError.value = 'Nama wajib diisi.';
    return;
  }
  // Allow letters, numbers, spaces, single quote
  const regex = /^[a-zA-Z0-9\s\']+$/;
  if (!regex.test(name)) {
    nameError.value = "Nama hanya boleh berupa huruf, angka, tanda petik satu ('), dan spasi.";
    return;
  }
  nameError.value = '';
};

// Photo Upload preview
const onPhotoChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran foto tidak boleh melebihi 2MB.');
    return;
  }

  photoFile.value = file;
  photoPreview.value = URL.createObjectURL(file);
};

// Autocomplete district
const onDistrictSearchInput = () => {
  clearTimeout(districtDebounce);
  showDistrictSuggestions.value = true;

  if (districtSearch.value.length < 3) {
    districtSuggestions.value = [];
    return;
  }

  districtDebounce = setTimeout(async () => {
    try {
      const res = await apiFetch(`/regions/districts?q=${encodeURIComponent(districtSearch.value)}`);
      districtSuggestions.value = res.data;
    } catch (e) {
      console.error(e);
    }
  }, 300);
};

const selectDistrict = (d) => {
  form.value.district_id = d.district_id;
  form.value.regency_name = d.regency_name;
  form.value.province_name = d.province_name;
  districtSearch.value = d.district_name;
  showDistrictSuggestions.value = false;
  districtSuggestions.value = [];
};

// Education history additions
const addEducation = () => {
  form.value.educations.push({
    education_level: '',
    school_name: '',
    graduation_year: new Date().getFullYear(),
    sort_order: form.value.educations.length
  });
};

const removeEducation = (index) => {
  form.value.educations.splice(index, 1);
};

// Format YYYY-MM-DD back to d/m/Y
const formatToBackendDate = (dateStr) => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const [year, month, day] = parts;
  return `${day}/${month}/${year}`;
};

// Format d/m/Y or Y-m-d to YYYY-MM-DD
const formatToInputDate = (dateStr) => {
  if (!dateStr) return '';
  if (dateStr.includes('-')) {
    return dateStr.substring(0, 10);
  }
  // Try mapping d/m/Y to YYYY-MM-DD
  const parts = dateStr.split('/');
  if (parts.length === 3) {
    const [day, month, year] = parts;
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
  }
  return dateStr;
};

// Form Validation Getter
const isFormValid = computed(() => {
  return (
    !!form.value.nip &&
    !nipError.value &&
    !!form.value.name &&
    !nameError.value &&
    !!form.value.email &&
    !!form.value.phone &&
    !!form.value.birth_place &&
    !!form.value.birth_date &&
    !!form.value.joined_at &&
    !!form.value.position_id &&
    !!form.value.department_id &&
    !!form.value.district_id &&
    !!form.value.full_address &&
    form.value.educations.every(edu => edu.education_level && edu.school_name && edu.graduation_year)
  );
});

// Form Save
const saveEmployee = async () => {
  errors.value = [];
  saving.value = true;

  try {
    const formData = new FormData();
    formData.append('nip', form.value.nip);
    formData.append('name', form.value.name);
    formData.append('email', form.value.email);
    formData.append('phone', form.value.phone);
    formData.append('birth_place', form.value.birth_place);
    formData.append('birth_date', formatToBackendDate(form.value.birth_date));
    formData.append('gender', form.value.gender);
    formData.append('marital_status', form.value.marital_status);
    formData.append('children_count', form.value.marital_status === 'kawin' ? form.value.children_count : 0);
    formData.append('joined_at', formatToBackendDate(form.value.joined_at));
    formData.append('position_id', form.value.position_id);
    formData.append('department_id', form.value.department_id);
    formData.append('employment_type', form.value.employment_type);
    formData.append('status', form.value.isActive ? 'active' : 'inactive');
    formData.append('distance_km', form.value.distance_km);
    formData.append('district_id', form.value.district_id);
    formData.append('full_address', form.value.full_address);

    if (photoFile.value) {
      formData.append('photo', photoFile.value);
    }

    formData.append('educations', JSON.stringify(form.value.educations));

    let url = '/employees';
    if (isEditMode.value) {
      // Laravel requires standard POST to process photo uploads properly with multipart form data
      url = `/employees/${route.params.id}`;
    }

    await apiFetch(url, {
      method: 'POST',
      body: formData
    });

    router.push('/pegawai');
  } catch (error) {
    if (error.data?.errors) {
      errors.value = Object.values(error.data.errors).flat();
    } else {
      errors.value = [error.data?.message || 'Terjadi kesalahan sistem saat menyimpan data.'];
    }
  } finally {
    saving.value = false;
  }
};

// Initial setup
onMounted(async () => {
  // Close suggestions on document click
  if (typeof document !== 'undefined') {
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.position-relative')) {
        showDistrictSuggestions.value = false;
      }
    });
  }

  // Load select option options metadata
  try {
    const meta = await apiFetch('/employees/form-meta');
    positions.value = meta.positions;
    departments.value = meta.departments;
  } catch (e) {
    console.error('Error loading metadata:', e);
  }

  // Check if we are in Edit Mode
  if (route.params.id) {
    isEditMode.value = true;
    try {
      const empId = route.params.id;
      const emp = await apiFetch(`/employees/${empId}`);
      
      form.value = {
        nip: emp.nip,
        name: emp.name,
        email: emp.email,
        phone: emp.phone,
        birth_place: emp.birth_place,
        birth_date: formatToInputDate(emp.birth_date),
        gender: emp.gender || 'pria',
        marital_status: emp.marital_status || 'tidak kawin',
        children_count: emp.children_count || 0,
        joined_at: formatToInputDate(emp.joined_at),
        position_id: emp.position_id,
        department_id: emp.department_id,
        employment_type: emp.employment_type || 'pkwt',
        isActive: emp.status === 'active',
        distance_km: emp.distance_km,
        district_id: emp.district_id,
        regency_name: emp.district?.regency?.name || '-',
        province_name: emp.district?.regency?.province?.name || '-',
        full_address: emp.full_address || '',
        educations: emp.educations || []
      };

      districtSearch.value = emp.district?.name || '';

      if (emp.photo_path) {
        photoPreview.value = `/storage/${emp.photo_path}`;
      }
    } catch (e) {
      console.error('Error loading employee:', e);
      errors.value = ['Gagal memuat profil detail pegawai.'];
    }
  }
  
  loading.value = false;
});
</script>

<style scoped>
.foto-profil {
  border-radius: 50%;
  border: 3px solid #f1f5f9;
}
</style>
