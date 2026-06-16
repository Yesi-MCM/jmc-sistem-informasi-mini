<template>
  <NuxtLayout name="default">
    <template #actions>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" @click="downloadTemplate">
          Download Template Excel
        </button>
        <button class="btn btn-primary" @click="openImportModal" v-if="canManage">
          Import Presensi
        </button>
      </div>
    </template>

    <div class="card shadow-sm border-0">
      <!-- Header Filters -->
      <div class="card-header bg-transparent py-3 flex-wrap gap-3">
        <h3 class="card-title fw-bold m-0 text-dark">Rekapitulasi Presensi Bulanan</h3>
        
        <div class="d-flex gap-2 ms-auto align-items-center">
          <label class="text-secondary small fw-bold text-nowrap">Periode:</label>
          
          <!-- Month select -->
          <select v-model="filterMonth" class="form-select form-select-sm" style="width: 140px" @change="fetchSummaries">
            <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">
              {{ name }}
            </option>
          </select>

          <!-- Year select -->
          <select v-model="filterYear" class="form-select form-select-sm" style="width: 100px" @change="fetchSummaries">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
      </div>

      <!-- Alert Notification -->
      <div v-if="alertMsg" class="mx-3 mt-3 alert alert-dismissible" :class="alertClass" role="alert">
        <div>{{ alertMsg }}</div>
        <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
      </div>

      <!-- Polling Status Alert (Background process tracker) -->
      <div v-if="polling" class="mx-3 mt-3 alert alert-info d-flex align-items-center gap-2">
        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
        <div>
          Sedang memproses rekap absensi: 
          <strong>{{ pollProgress.processed }} dari {{ pollProgress.total }} baris</strong>...
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
        <div class="spinner-border text-primary me-2" role="status"></div>
        <span>Memuat data rekap presensi...</span>
      </div>

      <!-- Table View -->
      <div v-else class="table-responsive card-body p-0">
        <table class="table table-vcenter table-striped table-hover mb-0">
          <thead>
            <tr class="text-secondary small fw-bold">
              <th width="5" class="text-center">No</th>
              <th>Nama Pegawai</th>
              <th>Jabatan</th>
              <th class="text-center">Hadir (Hari)</th>
              <th class="text-center">Status Kehadiran</th>
              <th class="text-center">Cuti</th>
              <th class="text-center">Kuota Cuti</th>
              <th class="text-center">Izin</th>
              <th class="text-center">Kuota Izin</th>
              <th class="text-center">Unpaid Leave</th>
              <th class="text-center">Kuota Unpaid</th>
              <th class="text-center" width="100">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in summaries" :key="item.employee_id">
              <td class="text-center">{{ item.no }}</td>
              <td>
                <div class="fw-bold text-dark">{{ item.name }}</div>
                <div class="small text-secondary">NIP: {{ item.nip }}</div>
              </td>
              <td>{{ item.position }}</td>
              <td class="text-center fw-bold text-dark">{{ item.hadir.toFixed(1) }}</td>
              <td class="text-center">
                <span :class="item.status_hadir === 'Terpenuhi' ? 'badge bg-success-subtle text-success border border-success-subtle' : 'badge bg-danger-subtle text-danger border border-danger-subtle'">
                  {{ item.status_hadir }}
                </span>
              </td>
              <td class="text-center">{{ item.cuti.toFixed(1) }}</td>
              <td class="text-center text-muted">{{ item.kuota_cuti.toFixed(1) }}</td>
              <td class="text-center">{{ item.izin.toFixed(1) }}</td>
              <td class="text-center text-muted">{{ item.kuota_izin.toFixed(1) }}</td>
              <td class="text-center">{{ item.unpaid_leave.toFixed(1) }}</td>
              <td class="text-center text-muted">{{ item.kuota_unpaid_leave.toFixed(1) }}</td>
              <td class="text-center">
                <NuxtLink :to="`/presensi/${item.employee_id}?year=${filterYear}&month=${filterMonth}`" class="btn btn-outline-primary btn-sm px-2">
                  Detail Logs
                </NuxtLink>
              </td>
            </tr>
            <tr v-if="summaries.length === 0">
              <td colspan="12" class="text-center py-4 text-muted">
                Belum ada data rekap presensi untuk periode {{ monthNames[filterMonth - 1] }} {{ filterYear }}.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Import Excel -->
    <div class="modal fade" id="modal-import-presensi" tabindex="-1" aria-hidden="true" ref="importModalElement">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Import File Rekap Presensi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info small">
              <ul class="mb-0 ps-3">
                <li>File harus berformat Excel (.xlsx atau .xls).</li>
                <li>NIP di dalam file harus sesuai dengan data pegawai terdaftar.</li>
                <li>Proses kalkulasi harian akan dihitung di background.</li>
              </ul>
            </div>

            <div v-if="uploadError" class="alert alert-danger">{{ uploadError }}</div>

            <!-- Periode upload -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label required">Bulan Rekap</label>
                <select v-model="uploadMonth" class="form-select">
                  <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">
                    {{ name }}
                  </option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label required">Tahun Rekap</label>
                <select v-model="uploadYear" class="form-select">
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>

            <!-- File Input -->
            <div class="mb-3">
              <label class="form-label required">File Excel (.xlsx/.xls)</label>
              <input type="file" class="form-control" accept=".xlsx,.xls" @change="onFileChange" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" :disabled="uploading || !uploadFile" @click="submitImport">
              <span v-if="uploading" class="spinner-border spinner-border-sm me-1" role="status"></span>
              Upload & Import
            </button>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup>
import { ref, onMounted, computed, onBeforeUnmount } from 'vue';
import { useAuth } from '~/composables/useAuth';

definePageMeta({
  title: "Rekap Presensi",
  layout: false,
});

useSeoMeta({
  title: "Rekap Presensi",
});

const { apiFetch, user: currentUser, token } = useAuth();
const config = useRuntimeConfig();

// Access permissions: Manager HRD cannot upload/import, only view rekap.
const canManage = computed(() => {
  return currentUser.value?.role !== 'manager_hrd';
});

// Month & Year setups
const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);

// Filter period (Default is N-1)
const defaultMonth = new Date().getMonth() === 0 ? 12 : new Date().getMonth();
const defaultYear = new Date().getMonth() === 0 ? currentYear - 1 : currentYear;

const filterMonth = ref(defaultMonth);
const filterYear = ref(defaultYear);

// Main States
const summaries = ref([]);
const loading = ref(true);
const alertMsg = ref('');
const alertClass = ref('alert-success');

// Upload Form States
const importModalElement = ref(null);
let importBootstrapModal = null;
const uploading = ref(false);
const uploadFile = ref(null);
const uploadError = ref(null);
const uploadMonth = ref(defaultMonth);
const uploadYear = ref(defaultYear);

// Polling States
const polling = ref(false);
const pollProgress = ref({ processed: 0, total: 0 });
let pollInterval = null;

const openImportModal = () => {
  uploadFile.value = null;
  uploadError.value = null;
  uploadMonth.value = filterMonth.value;
  uploadYear.value = filterYear.value;
  if (importBootstrapModal) {
    importBootstrapModal.show();
  }
};

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  uploadFile.value = file;
};

// Fetch summaries list
const fetchSummaries = async () => {
  loading.value = true;
  try {
    const res = await apiFetch(`/attendances?year=${filterYear.value}&month=${filterMonth.value}`);
    summaries.value = res.data;
  } catch (error) {
    showToast('Gagal memuat rekap presensi.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

// Download Template Excel
const downloadTemplate = async () => {
  try {
    const res = await fetch(config.public.apiBase + '/attendances/template', {
      headers: {
        'Authorization': `Bearer ${token.value}`
      }
    });
    if (!res.ok) throw new Error();
    const blob = await res.blob();
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'template_presensi.xlsx';
    link.click();
    window.URL.revokeObjectURL(link.href);
  } catch (e) {
    showToast('Gagal mengunduh template.', 'alert-danger');
  }
};

// Submit Import
const submitImport = async () => {
  if (!uploadFile.value) return;
  uploading.value = true;
  uploadError.value = null;

  try {
    const formData = new FormData();
    formData.append('file', uploadFile.value);
    formData.append('period_year', uploadYear.value);
    formData.append('period_month', uploadMonth.value);

    const res = await apiFetch('/attendances/import', {
      method: 'POST',
      body: formData
    });

    if (importBootstrapModal) {
      importBootstrapModal.hide();
    }

    showToast(res.message, 'alert-success');
    
    // Start background process tracker
    startPollingImportStatus(res.import_id);
  } catch (error) {
    uploadError.value = error.data?.message || 'Gagal mengunggah file presensi.';
  } finally {
    uploading.value = false;
  }
};

// Polling for status updates
const startPollingImportStatus = (importId) => {
  if (pollInterval) clearInterval(pollInterval);
  polling.value = true;
  
  pollInterval = setInterval(async () => {
    try {
      const statusRes = await apiFetch(`/attendances/import/${importId}/status`);
      pollProgress.value.processed = statusRes.processed;
      pollProgress.value.total = statusRes.total;

      if (statusRes.status === 'completed') {
        clearInterval(pollInterval);
        polling.value = false;
        showToast('Rekap absensi berhasil diproses seluruhnya.', 'alert-success');
        // Refresh period matches the imported period
        filterMonth.value = uploadMonth.value;
        filterYear.value = uploadYear.value;
        fetchSummaries();
      } else if (statusRes.status === 'failed') {
        clearInterval(pollInterval);
        polling.value = false;
        showToast(`Proses import gagal: ${statusRes.error}`, 'alert-danger');
      }
    } catch (e) {
      console.error('Error checking status:', e);
    }
  }, 2000);
};

const showToast = (msg, cls) => {
  alertMsg.value = msg;
  alertClass.value = cls;
  setTimeout(() => {
    alertMsg.value = '';
  }, 5000);
};

onMounted(() => {
  if (typeof window !== 'undefined' && window.bootstrap) {
    importBootstrapModal = new window.bootstrap.Modal(importModalElement.value);
  }
  fetchSummaries();
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>
