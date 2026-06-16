<template>
  <div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 flex-wrap gap-3">
      <h3 class="card-title fw-bold text-dark m-0">Tunjangan Transport Bulanan</h3>
      
      <div class="d-flex gap-2 ms-auto align-items-center">
        <label class="text-secondary small fw-bold text-nowrap">Pilih Tahun:</label>
        <select v-model="filterYear" class="form-select form-select-sm" style="width: 120px" @change="fetchPeriods">
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <!-- Alert Toast -->
    <div v-if="alertMsg" class="mx-3 mt-3 alert alert-dismissible" :class="alertClass" role="alert">
      <div>{{ alertMsg }}</div>
      <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
    </div>

    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat daftar periode tunjangan...</span>
    </div>

    <div v-else class="table-responsive card-body p-0">
      <table class="table table-vcenter table-striped table-hover mb-0">
        <thead>
          <tr class="text-secondary small fw-bold">
            <th width="5" class="text-center">No</th>
            <th>Nama Bulan</th>
            <th class="text-center">Total Penerima</th>
            <th class="text-end">Total Tunjangan Transport</th>
            <th>Tanggal Dihitung</th>
            <th>Dihitung Oleh</th>
            <th class="text-center" width="200">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in periods" :key="index">
            <td class="text-center">{{ index + 1 }}</td>
            <td><strong>{{ item.month_name }}</strong></td>
            <td class="text-center">
              <span class="badge bg-secondary-subtle text-secondary px-2 py-1" v-if="item.id">
                {{ item.total_recipients }} Pegawai
              </span>
              <span class="text-muted" v-else>-</span>
            </td>
            <td class="text-end fw-bold text-dark">
              {{ item.id ? formatRupiah(item.total_amount) : '-' }}
            </td>
            <td>{{ item.calculated_at }}</td>
            <td>{{ item.calculator_name }}</td>
            <td class="text-center">
              <NuxtLink
                v-if="item.id"
                :to="`/tunjangan/transport/detail/${item.id}`"
                class="btn btn-primary btn-sm px-3"
              >
                Detail
              </NuxtLink>
              <button
                v-else
                class="btn btn-outline-primary btn-sm px-3"
                :disabled="calculating === item.month_num"
                @click="calculatePeriod(item)"
              >
                <span v-if="calculating === item.month_num" class="spinner-border spinner-border-sm me-1" role="status"></span>
                Hitung Tunjangan
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '~/composables/useAuth';
import { formatRupiah } from "~/utils/formatRupiah.js";

definePageMeta({
  title: "Tunjangan Transport",
});

useSeoMeta({
  title: "Tunjangan Transport",
});

const router = useRouter();
const { apiFetch } = useAuth();

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);
const filterYear = ref(currentYear);

const periods = ref([]);
const loading = ref(true);
const calculating = ref(null);

const alertMsg = ref('');
const alertClass = ref('alert-success');

const fetchPeriods = async () => {
  loading.value = true;
  try {
    const res = await apiFetch(`/allowances/periods?year=${filterYear.value}`);
    periods.value = res.periods;
  } catch (error) {
    showToast('Gagal memuat daftar periode tunjangan.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

const calculatePeriod = async (item) => {
  calculating.value = item.month_num;
  try {
    const res = await apiFetch('/allowances/calculate', {
      method: 'POST',
      body: {
        period_year: filterYear.value,
        period_month: item.month_num
      }
    });
    showToast(res.message, 'alert-success');
    
    // Redirect to detail page of the newly created calculation period
    setTimeout(() => {
      router.push(`/tunjangan/transport/detail/${res.data.period_id}`);
    }, 1000);
  } catch (error) {
    showToast(error.data?.message || 'Gagal menghitung tunjangan.', 'alert-danger');
  } finally {
    calculating.value = null;
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
  fetchPeriods();
});
</script>
