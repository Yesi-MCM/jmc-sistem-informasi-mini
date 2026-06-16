<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '~/composables/useAuth';

definePageMeta({
  title: "Dashboard",
});

useSeoMeta({
  title: "Dashboard",
});

const { apiFetch, user } = useAuth();

const stats = ref(null);
const loading = ref(true);

const statusPegawaiSeries = ref([0, 0, 0]);
const genderPegawaiSeries = ref([0, 0]);
const newestEmployees = ref([]);

const statusPegawaiOptions = {
  chart: { type: "donut", height: 200 },
  labels: ["PKWT", "PKWTT", "Magang"],
  colors: [
    "rgba(84, 128, 199, 1)",
    "rgba(43, 80, 142, 1)",
    "rgba(254, 126, 0, 1)",
  ],
  legend: { position: "bottom" },
  dataLabels: { enabled: true },
};

const genderPegawaiOptions = {
  chart: { type: "donut", height: 200 },
  labels: ["Laki-laki", "Perempuan"],
  colors: ["rgba(43, 80, 142, 1)", "rgba(254, 126, 0, 1)"],
  legend: { position: "bottom" },
  dataLabels: { enabled: true },
};

const fetchDashboardStats = async () => {
  if (user.value?.role !== 'manager_hrd') {
    loading.value = false;
    return;
  }
  
  try {
    const res = await apiFetch('/employees/dashboard/stats');
    stats.value = res;
    statusPegawaiSeries.value = [res.total_pkwt, res.total_pkwtt, res.total_magang];
    genderPegawaiSeries.value = [res.gender.male, res.gender.female];
    newestEmployees.value = res.newest_employees;
  } catch (e) {
    console.error('Error loading dashboard stats:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  if (!user.value) {
    const { fetchUser } = useAuth();
    await fetchUser();
  }
  fetchDashboardStats();
});

// Helper for display roles label
const getRoleLabel = (role) => {
  switch (role) {
    case 'superadmin': return 'Superadmin';
    case 'manager_hrd': return 'Manager HRD';
    case 'admin_hrd': return 'Admin HRD';
    default: return role;
  }
};
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat data dashboard...</span>
    </div>

    <!-- Main Content -->
    <div v-else class="row g-3">
      <!-- 1. GREETING CARD FOR ALL ROLES -->
      <div :class="user?.role === 'manager_hrd' ? 'col-md-3' : 'col-12'">
        <div class="card bg-dark h-100 position-relative text-white border-0 shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="text-center my-3" v-if="user?.role === 'manager_hrd'">
              <img
                src="~/assets/images/greeting-img.svg"
                alt="Greeting"
                class="img-fluid mb-3"
                style="max-height: 120px;"
              />
            </div>
            <div>
              <h3 class="card-title text-white fs-3 mb-2">
                Selamat Datang {{ user?.name }}
              </h3>
              <p class="badge bg-primary px-3 py-2 fs-5 mb-3 text-uppercase">
                Role: {{ getRoleLabel(user?.role) }}
              </p>
              <p class="text-white-50 fw-light fst-italic mb-0 small">
                "Fokuskan tujuan yang ingin didapat, jangan biarkan faktor lain menghalangi tujuan Anda"
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. WIDGETS AND CHARTS FOR MANAGER HRD ONLY -->
      <template v-if="user?.role === 'manager_hrd'">
        <div class="col-md-9">
          <div class="row g-3">
            
            <!-- Cards Total Counters -->
            <div class="col-12">
              <div class="card shadow-sm border-0">
                <div class="card-body">
                  <div class="row g-3">
                    <!-- Total Pegawai -->
                    <div class="col-md-6 col-lg-3">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="d-flex rounded-circle bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="m-auto icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                              <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                              <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                            </svg>
                          </div>
                        </div>
                        <div class="col">
                          <h3 class="fs-2 mb-0 fw-bold">{{ stats?.total_active }}</h3>
                          <p class="text-secondary small mb-0">Total Pegawai</p>
                        </div>
                      </div>
                    </div>

                    <!-- Total Pegawai Kontrak -->
                    <div class="col-md-6 col-lg-3">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="d-flex rounded-circle bg-info-subtle text-info" style="width: 48px; height: 48px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="m-auto icon icon-tabler icon-tabler-file-certificate" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                              <path d="M5 8v-3a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5" />
                              <path d="M6 14m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                              <path d="M4.5 17l-1.5 5l3 -1.5l3 1.5l-1.5 -5" />
                            </svg>
                          </div>
                        </div>
                        <div class="col">
                          <h3 class="fs-2 mb-0 fw-bold">{{ stats?.total_pkwt }}</h3>
                          <p class="text-secondary small mb-0">Pegawai Kontrak (PKWT)</p>
                        </div>
                      </div>
                    </div>

                    <!-- Total Pegawai Tetap -->
                    <div class="col-md-6 col-lg-3">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="d-flex rounded-circle bg-success-subtle text-success" style="width: 48px; height: 48px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="m-auto icon icon-tabler icon-tabler-shield-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M9 12l2 2l4 -4" />
                              <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -9 11a12 12 0 0 1 -9 -11a12 12 0 0 0 8.5 -3z" />
                            </svg>
                          </div>
                        </div>
                        <div class="col">
                          <h3 class="fs-2 mb-0 fw-bold">{{ stats?.total_pkwtt }}</h3>
                          <p class="text-secondary small mb-0">Pegawai Tetap (PKWTT)</p>
                        </div>
                      </div>
                    </div>

                    <!-- Total Peserta Magang -->
                    <div class="col-md-6 col-lg-3">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <div class="d-flex rounded-circle bg-warning-subtle text-warning" style="width: 48px; height: 48px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="m-auto icon icon-tabler icon-tabler-school" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
                              <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
                            </svg>
                          </div>
                        </div>
                        <div class="col">
                          <h3 class="fs-2 mb-0 fw-bold">{{ stats?.total_magang }}</h3>
                          <p class="text-secondary small mb-0">Peserta Magang</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Chart Status Kontrak -->
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                  <h3 class="card-title fw-bold text-dark mb-3">Status Kontrak Pegawai</h3>
                  <ClientOnly>
                    <apexchart
                      type="donut"
                      height="220"
                      :options="statusPegawaiOptions"
                      :series="statusPegawaiSeries"
                    />
                  </ClientOnly>
                </div>
              </div>
            </div>

            <!-- Chart Gender -->
            <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                  <h3 class="card-title fw-bold text-dark mb-3">Rasio Gender Pegawai</h3>
                  <ClientOnly>
                    <apexchart
                      type="donut"
                      height="220"
                      :options="genderPegawaiOptions"
                      :series="genderPegawaiSeries"
                    />
                  </ClientOnly>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- 5 Newest Employees -->
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
              <h3 class="card-title fw-bold text-dark mb-0">5 Pegawai Baru Terdaftar</h3>
            </div>
            <div class="table-responsive">
              <table class="table table-vcenter table-striped card-table mb-0">
                <thead>
                  <tr class="text-secondary small fw-bold">
                    <th class="w-1 text-center">No</th>
                    <th>NIP</th>
                    <th>Nama Lengkap</th>
                    <th>Tanggal Masuk</th>
                    <th>Jabatan</th>
                    <th>Status Kontrak</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in newestEmployees" :key="item.nip">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td><strong class="text-dark">{{ item.nip }}</strong></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img
                          :src="item.photo ? `/storage/${item.photo}` : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjgiIGZpbGw9IiNhYWEiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiPkF2YXRhcjwvdGV4dD48L3N2Zz4='"
                          alt="Avatar"
                          style="width: 32px; height: 32px; object-fit: cover;"
                          class="rounded-circle border"
                        />
                        <span class="text-dark fw-bold">{{ item.name }}</span>
                      </div>
                    </td>
                    <td>{{ item.joined_at }}</td>
                    <td>{{ item.position }}</td>
                    <td>
                      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                        {{ item.employment_type }}
                      </span>
                    </td>
                    <td>
                      <!-- Manager HRD has read access to pegawai, so they can access detail page -->
                      <NuxtLink
                        :to="`/pegawai`"
                        class="btn btn-outline-primary btn-sm px-3"
                      >
                        Detail Pegawai
                      </NuxtLink>
                    </td>
                  </tr>
                  <tr v-if="newestEmployees.length === 0">
                    <td colspan="7" class="text-center text-muted py-4">Belum ada data pegawai yang terdaftar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
