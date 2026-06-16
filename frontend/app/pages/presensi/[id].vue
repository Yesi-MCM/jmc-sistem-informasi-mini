<template>
  <NuxtLayout name="default">
    <!-- Breadcrumbs -->
    <div class="mb-3">
      <NuxtLink to="/presensi" class="btn btn-outline-secondary btn-sm">
        Kembali ke Rekap Presensi
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center bg-white rounded shadow-sm">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat log presensi...</span>
    </div>

    <!-- Details View -->
    <template v-else>
      <!-- Employee Profile Header -->
      <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="small text-secondary fw-bold">Nama Pegawai</div>
              <div class="fw-bold text-dark fs-3">{{ employee?.name }}</div>
              <div class="text-secondary small">NIP: {{ employee?.nip }}</div>
            </div>
            <div class="col-md-3">
              <div class="small text-secondary fw-bold">Jabatan</div>
              <div class="text-dark">{{ employee?.position }}</div>
            </div>
            <div class="col-md-3">
              <div class="small text-secondary fw-bold">Departemen</div>
              <div class="text-dark">{{ employee?.department }}</div>
            </div>
            <!-- Filter Period -->
            <div class="col-md-3 d-flex flex-column justify-content-end align-items-md-end">
              <div class="d-flex gap-2">
                <select v-model="filterMonth" class="form-select form-select-sm" style="width: 120px" @change="fetchLogs">
                  <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">
                    {{ name }}
                  </option>
                </select>
                <select v-model="filterYear" class="form-select form-select-sm" style="width: 90px" @change="fetchLogs">
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent py-3">
          <h3 class="card-title fw-bold m-0 text-dark">Log Kehadiran Harian</h3>
        </div>
        <div class="table-responsive card-body p-0">
          <table class="table table-vcenter table-striped table-hover mb-0">
            <thead>
              <tr class="text-secondary small fw-bold">
                <th width="100">Tanggal</th>
                <th width="100" class="text-center">Waktu Masuk</th>
                <th width="100" class="text-center">Waktu Pulang</th>
                <th>Lokasi Check-in</th>
                <th>Lokasi Check-out</th>
                <th class="text-center" width="120">Kehadiran</th>
                <th class="text-center" width="120">Durasi (Hadir)</th>
                <th class="text-center" width="130">Status Harian</th>
                <th class="text-center" width="120">Verifikasi</th>
                <th class="text-center" width="100">Verifikator</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs" :key="log.date">
                <td>{{ log.date }}</td>
                <td class="text-center"><code>{{ log.checkin_at }}</code></td>
                <td class="text-center"><code>{{ log.checkout_at }}</code></td>
                <td>{{ log.checkin_location }}</td>
                <td>{{ log.checkout_location }}</td>
                <td class="text-center">
                  <span :class="getKehadiranBadgeClass(log.attendance_type)">
                    {{ log.attendance_type }}
                  </span>
                </td>
                <td class="text-center fw-bold text-dark">{{ log.duration.toFixed(1) }} jam</td>
                <td class="text-center">
                  <span :class="log.status === 'Terpenuhi' ? 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1' : 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1'">
                    {{ log.status }}
                  </span>
                </td>
                <td class="text-center">
                  <span :class="log.verification_status === 'Disetujui' ? 'badge bg-green' : 'badge bg-red'">
                    {{ log.verification_status }}
                  </span>
                </td>
                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ log.verified_by_role }}</span></td>
                <td><small class="text-secondary">{{ log.remarks }}</small></td>
              </tr>
              <tr v-if="logs.length === 0">
                <td colspan="11" class="text-center py-4 text-muted">
                  Tidak ada catatan log kehadiran harian untuk periode ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </NuxtLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '~/composables/useAuth';

definePageMeta({
  title: "Detail Presensi",
  layout: false,
});

useSeoMeta({
  title: "Detail Presensi",
});

const route = useRoute();
const { apiFetch } = useAuth();

// Month & Year setups
const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);

// Filter period from route query or defaults
const filterMonth = ref(parseInt(route.query.month) || new Date().getMonth() + 1);
const filterYear = ref(parseInt(route.query.year) || currentYear);

// Main States
const employee = ref(null);
const logs = ref([]);
const loading = ref(true);

const fetchLogs = async () => {
  loading.value = true;
  try {
    const empId = route.params.id;
    const res = await apiFetch(`/attendances/employee/${empId}?year=${filterYear.value}&month=${filterMonth.value}`);
    employee.value = res.employee;
    logs.value = res.logs;
  } catch (error) {
    console.error('Error fetching logs:', error);
  } finally {
    loading.value = false;
  }
};

const getKehadiranBadgeClass = (type) => {
  if (!type) return 'badge bg-secondary';
  switch (type.toLowerCase()) {
    case 'hadir': return 'badge bg-success';
    case 'cuti': return 'badge bg-warning text-dark';
    case 'izin': return 'badge bg-info text-dark';
    case 'unpaid_leave': return 'badge bg-danger';
    default: return 'badge bg-secondary';
  }
};

onMounted(() => {
  fetchLogs();
});
</script>
