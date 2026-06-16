<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center bg-white rounded shadow-sm">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat detail pegawai...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      <div>{{ error }}</div>
      <button class="btn btn-outline-danger btn-sm mt-2" @click="goBack">Kembali</button>
    </div>

    <!-- Details View -->
    <div v-else class="row g-3">
      <div class="col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-3">
            <h3 class="card-title fw-bold text-dark m-0">Data Diri</h3>
          </div>
          <div class="card-body">
            <div class="row g-4">
              <div class="col-12">
                <div class="row align-items-center g-3">
                  <!-- Foto -->
                  <div class="col-auto">
                    <img
                      :src="employee.photo_path ? `/storage/${employee.photo_path}` : '/images/avatar-placeholder.svg'"
                      alt="Avatar"
                      class="foto-profil border shadow-sm"
                      style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;"
                    />
                  </div>

                  <div class="col">
                    <!-- NIP -->
                    <div class="datagrid-item mb-2">
                      <div class="datagrid-title fw-bold text-secondary small">NIP</div>
                      <div class="datagrid-content fw-bold fs-3 text-dark"><code>{{ employee.nip }}</code></div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="datagrid-item">
                      <div class="datagrid-title fw-bold text-secondary small">Nama Lengkap</div>
                      <div class="datagrid-content fw-bold fs-4 text-dark">{{ employee.name }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Email -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Email</div>
                  <div class="datagrid-content">{{ employee.email }}</div>
                </div>
              </div>

              <!-- No HP -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Nomor HP</div>
                  <div class="datagrid-content">{{ employee.phone }}</div>
                </div>
              </div>

              <!-- Tempat Lahir -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Tempat Lahir</div>
                  <div class="datagrid-content">{{ employee.birth_place }}</div>
                </div>
              </div>

              <!-- Tanggal Lahir -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Tanggal Lahir</div>
                  <div class="datagrid-content">{{ formatDate(employee.birth_date) }}</div>
                </div>
              </div>

              <!-- Usia -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Usia</div>
                  <div class="datagrid-content">{{ employee.age }} tahun</div>
                </div>
              </div>

              <!-- Jenis Kelamin -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Jenis Kelamin</div>
                  <div class="datagrid-content text-capitalize">{{ employee.gender || '-' }}</div>
                </div>
              </div>

              <!-- Status Pernikahan -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Status Pernikahan</div>
                  <div class="datagrid-content">
                    {{ employee.marital_status === 'kawin' ? 'Menikah' : 'Belum Menikah' }}
                  </div>
                </div>
              </div>

              <!-- Jumlah Anak -->
              <div class="col-md-6" v-if="employee.marital_status === 'kawin'">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Jumlah Anak</div>
                  <div class="datagrid-content">{{ employee.children_count || 0 }} anak</div>
                </div>
              </div>

              <!-- Jarak Rumah ke Kantor -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Jarak Rumah - Kantor</div>
                  <div class="datagrid-content">{{ employee.distance_km }} KM</div>
                </div>
              </div>

              <!-- Alamat Lengkap -->
              <div class="col-12">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Alamat Lengkap</div>
                  <div class="datagrid-content">{{ employee.full_address }}</div>
                </div>
              </div>

              <!-- Wilayah -->
              <div class="col-md-4">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Kecamatan</div>
                  <div class="datagrid-content">{{ employee.district?.name || '-' }}</div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Kabupaten</div>
                  <div class="datagrid-content">{{ employee.district?.regency?.name || '-' }}</div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Provinsi</div>
                  <div class="datagrid-content">{{ employee.district?.regency?.province?.name || '-' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 d-flex flex-column gap-3">
        <!-- KEPEGAWAIAN CARD -->
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent py-3">
            <h3 class="card-title fw-bold text-dark m-0">Data Kepegawaian</h3>
          </div>
          <div class="card-body">
            <div class="row g-4">
              <!-- Tanggal Masuk -->
              <div class="col-12">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Tanggal Masuk</div>
                  <div class="datagrid-content">{{ formatDate(employee.joined_at) }}</div>
                </div>
              </div>

              <!-- Masa Kerja -->
              <div class="col-12">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Masa Kerja</div>
                  <div class="datagrid-content fw-bold text-dark">{{ employee.work_tenure }}</div>
                </div>
              </div>

              <!-- Jabatan -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Jabatan</div>
                  <div class="datagrid-content">{{ employee.position?.name || '-' }}</div>
                </div>
              </div>

              <!-- Departemen -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Departemen</div>
                  <div class="datagrid-content">{{ employee.department?.name || '-' }}</div>
                </div>
              </div>

              <!-- Kontrak -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Status Kontrak</div>
                  <div class="datagrid-content text-uppercase">{{ employee.employment_type }}</div>
                </div>
              </div>

              <!-- Status -->
              <div class="col-md-6">
                <div class="datagrid-item">
                  <div class="datagrid-title text-secondary small">Status Keaktifan</div>
                  <div class="datagrid-content">
                    <span :class="employee.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'">
                      {{ employee.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PENDIDIKAN CARD -->
        <div class="card shadow-sm border-0 flex-grow-1">
          <div class="card-header bg-transparent py-3">
            <h3 class="card-title fw-bold text-dark m-0">Riwayat Pendidikan</h3>
          </div>
          <div class="table-responsive card-body p-0">
            <table class="table table-vcenter table-striped table-hover mb-0">
              <thead>
                <tr class="text-secondary small fw-bold">
                  <th width="80">Jenjang</th>
                  <th>Nama Sekolah / Kampus</th>
                  <th width="120" class="text-center">Tahun Lulus</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="edu in employee.educations" :key="edu.id">
                  <td><span class="badge bg-secondary-subtle text-secondary">{{ edu.education_level }}</span></td>
                  <td class="fw-bold text-dark">{{ edu.school_name }}</td>
                  <td class="text-center">{{ edu.graduation_year }}</td>
                </tr>
                <tr v-if="!employee.educations || employee.educations.length === 0">
                  <td colspan="3" class="text-center py-4 text-muted small">
                    Tidak ada riwayat pendidikan yang terdaftar.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer d-flex bg-transparent">
            <div class="ms-auto">
              <button class="btn btn-outline-primary" @click="goBack">
                Kembali
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '~/composables/useAuth';

definePageMeta({
  title: "Detail Pegawai",
});

useSeoMeta({
  title: "Detail Pegawai",
});

const route = useRoute();
const router = useRouter();
const { apiFetch } = useAuth();

const employee = ref(null);
const loading = ref(true);
const error = ref(null);

const goBack = () => {
  router.push('/pegawai');
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const parts = dateStr.substring(0, 10).split('-');
  if (parts.length !== 3) return dateStr;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

onMounted(async () => {
  loading.value = true;
  try {
    const empId = route.params.nipp;
    const res = await apiFetch(`/employees/${empId}`);
    employee.value = res;
  } catch (err) {
    console.error(err);
    error.value = err.data?.message || 'Gagal memuat profil detail pegawai.';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.foto-profil {
  border: 3px solid #f1f5f9;
}
</style>
