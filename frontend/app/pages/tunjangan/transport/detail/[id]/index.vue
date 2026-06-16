<template>
  <div>
    <!-- Back Button -->
    <div class="mb-3 d-flex align-items-center gap-2">
      <NuxtLink to="/tunjangan/transport" class="btn btn-outline-secondary btn-sm">
        Kembali ke Daftar Periode
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center bg-white rounded shadow-sm">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat detail tunjangan...</span>
    </div>

    <template v-else>
      <!-- Title Period -->
      <h3 class="card-title fw-bold fs-2 mb-3 text-dark">
        Periode {{ monthNames[periodInfo?.month - 1] }} {{ periodInfo?.year }}
      </h3>

      <!-- Alert Toast -->
      <div v-if="alertMsg" class="alert alert-dismissible" :class="alertClass" role="alert">
        <div>{{ alertMsg }}</div>
        <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
      </div>

      <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
        <div class="card-body">
          <div class="row text-center text-md-start g-3">
            <div class="col-md-4">
              <div class="text-white-50 small">Total Penerima Tunjangan</div>
              <div class="fs-2 fw-bold">{{ periodInfo?.total_recipients }} Pegawai</div>
            </div>
            <div class="col-md-4 border-start-md border-white-10">
              <div class="text-white-50 small">Total Pengeluaran Tunjangan</div>
              <div class="fs-2 fw-bold">{{ formatRupiah(periodInfo?.total_amount) }}</div>
            </div>
            <div class="col-md-4 border-start-md border-white-10">
              <div class="text-white-50 small">Status Periode</div>
              <div class="mt-1">
                <span class="badge bg-success" v-if="periodInfo?.status === 'calculated'">Calculated</span>
                <span class="badge bg-warning text-dark" v-else-if="periodInfo?.status === 'draft'">Draft</span>
                <span class="badge bg-danger" v-else>Locked</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent py-3 flex-wrap gap-2">
          <!-- Recalculate Button (Available for Admin HRD) -->
          <button
            class="btn btn-primary"
            v-if="canCalculate"
            :disabled="recalculating || periodInfo?.status === 'locked'"
            @click="recalculate"
          >
            <span v-if="recalculating" class="spinner-border spinner-border-sm me-1" role="status"></span>
            Hitung Tunjangan
          </button>
          
          <div class="ms-auto d-flex gap-2">
            <!-- Search -->
            <div class="input-group" style="width: 250px">
              <input
                type="text"
                class="form-control"
                placeholder="Cari Penerima..."
                v-model="searchQuery"
              />
              <button class="btn" type="button">
                <IconSearch stroke="{2}" />
              </button>
            </div>
          </div>
        </div>

        <div class="table-responsive card-body p-0">
          <table class="table table-vcenter table-striped table-hover mb-0">
            <thead>
              <tr class="text-secondary small fw-bold">
                <th width="5" class="text-center">No</th>
                <th class="cursor-pointer" @click="toggleSort('employee_name')">
                  Nama Penerima
                  <span v-if="sortBy === 'employee_name'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th>Jabatan</th>
                <th class="text-center cursor-pointer" @click="toggleSort('rounded_km')">
                  Jarak (KM)
                  <span v-if="sortBy === 'rounded_km'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th class="text-center cursor-pointer" @click="toggleSort('attendance_days')">
                  Jumlah Hari Hadir
                  <span v-if="sortBy === 'attendance_days'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th class="text-end cursor-pointer" @click="toggleSort('nominal')">
                  Nominal Tunjangan
                  <span v-if="sortBy === 'nominal'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th>Status Kelayakan / Catatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filteredDetails" :key="index">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="fw-bold text-dark">{{ item.employee_name }}</div>
                  <div class="small text-secondary">NIP: {{ item.nip }} ({{ item.employment_type }})</div>
                </td>
                <td>{{ item.position }}</td>
                <td class="text-center">
                  <span v-if="item.eligibility_status === 'eligible'">
                    <strong>{{ item.rounded_km }} KM</strong>
                    <div class="small text-secondary fst-italic">Asli: {{ item.original_km }} km</div>
                  </span>
                  <span class="text-muted" v-else>-</span>
                </td>
                <td class="text-center">
                  <span class="fw-bold text-dark" v-if="item.eligibility_status === 'eligible'">
                    {{ item.attendance_days }} hari
                  </span>
                  <span class="text-muted" v-else>-</span>
                </td>
                <td class="text-end fw-bold text-success">
                  {{ item.nominal > 0 ? formatRupiah(item.nominal) : '-' }}
                </td>
                <td>
                  <span :class="getEligibilityBadgeClass(item.eligibility_status)">
                    {{ getEligibilityLabel(item.eligibility_status) }}
                  </span>
                  <div class="small text-secondary mt-1">{{ item.calculation_note }}</div>
                </td>
              </tr>
              <tr v-if="filteredDetails.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">Data penerima tidak ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '~/composables/useAuth';
import { IconSearch } from "@tabler/icons-vue";
import { formatRupiah } from "~/utils/formatRupiah.js";

definePageMeta({
  title: "Detail Perhitungan Tunjangan",
});

useSeoMeta({
  title: "Detail Perhitungan Tunjangan",
});

const route = useRoute();
const { apiFetch, user: currentUser } = useAuth();

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Authorization checks: Admin HRD can run calculations.
const canCalculate = computed(() => {
  return currentUser.value?.role !== 'manager_hrd';
});

// States
const periodInfo = ref(null);
const details = ref([]);
const loading = ref(true);
const recalculating = ref(false);
const searchQuery = ref('');

const sortBy = ref('nominal');
const sortOrder = ref('desc');

const alertMsg = ref('');
const alertClass = ref('alert-success');

const fetchPeriodDetails = async () => {
  loading.value = true;
  try {
    const periodId = route.params.id;
    const res = await apiFetch(`/allowances/periods/${periodId}`);
    periodInfo.value = res.period;
    details.value = res.details;
  } catch (error) {
    showToast('Gagal memuat detail perhitungan tunjangan.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

const recalculate = async () => {
  if (!periodInfo.value) return;
  recalculating.value = true;
  try {
    const res = await apiFetch('/allowances/calculate', {
      method: 'POST',
      body: {
        period_year: periodInfo.value.year,
        period_month: periodInfo.value.month
      }
    });
    showToast(res.message, 'alert-success');
    
    // Reload details
    const periodId = res.data.period_id;
    const detailsRes = await apiFetch(`/allowances/periods/${periodId}`);
    periodInfo.value = detailsRes.period;
    details.value = detailsRes.details;
  } catch (error) {
    showToast(error.data?.message || 'Gagal menghitung tunjangan.', 'alert-danger');
  } finally {
    recalculating.value = false;
  }
};

// Filter & Sort details locally
const filteredDetails = computed(() => {
  let list = [...details.value];

  // Search filter
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(item => 
      item.employee_name.toLowerCase().includes(q) || 
      item.nip.toLowerCase().includes(q)
    );
  }

  // Local sorting
  const field = sortBy.value;
  const order = sortOrder.value === 'asc' ? 1 : -1;

  list.sort((a, b) => {
    let valA = a[field];
    let valB = b[field];

    if (typeof valA === 'string') {
      return valA.localeCompare(valB) * order;
    }
    
    return (valA - valB) * order;
  });

  return list;
});

const toggleSort = (field) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'asc';
  }
};

const getEligibilityBadgeClass = (status) => {
  if (!status) return '';
  switch (status.toLowerCase()) {
    case 'eligible': return 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
    case 'ineligible_presence': return 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
    case 'ineligible_distance': return 'badge bg-info-subtle text-info border border-info-subtle px-2 py-1';
    default: return 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
  }
};

const getEligibilityLabel = (status) => {
  if (!status) return '';
  switch (status.toLowerCase()) {
    case 'eligible': return 'Memenuhi Syarat';
    case 'ineligible_presence': return 'Kehadiran Kurang';
    case 'ineligible_distance': return 'Jarak Kurang';
    case 'ineligible_employment_type': return 'Bukan Pegawai Tetap';
    default: return 'Tidak Layak';
  }
};

const showToast = (msg, cls) => {
  alertMsg.value = msg;
  alertClass.value = cls;
  setTimeout(() => {
    alertMsg.value = '';
  }, 5000);
};

onMounted(() => {
  fetchPeriodDetails();
});
</script>
