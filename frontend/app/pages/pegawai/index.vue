<template>
  <NuxtLayout name="default">
    <template #actions>
      <NuxtLink to="/pegawai/form" class="btn btn-primary" v-if="canManage">
        <IconPlus stroke="{3}" size="20" />Tambah Pegawai
      </NuxtLink>
    </template>

    <div class="card shadow-sm border-0">
      <!-- FILTERS & BULK ACTIONS PANEL -->
      <div class="card-header bg-transparent py-3 flex-wrap gap-2">
        <!-- Bulk Actions (Visible when rows selected) -->
        <div v-if="selectedIds.length > 0 && canManage" class="d-flex align-items-center gap-2 me-auto bg-light p-2 rounded">
          <span class="text-secondary small fw-bold">{{ selectedIds.length }} Terpilih:</span>
          
          <select v-model="bulkStatus" class="form-select form-select-sm" style="width: 140px">
            <option value="" disabled>Ubah Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>

          <button class="btn btn-sm btn-outline-primary" @click="applyBulkStatus" :disabled="!bulkStatus || bulkUpdating">
            Ubah Status
          </button>
          
          <button class="btn btn-sm btn-danger" @click="confirmBulkDelete" :disabled="bulkDeleting">
            Hapus Massal
          </button>
        </div>

        <div v-else class="d-flex align-items-center me-auto">
          <h3 class="card-title fw-bold m-0 text-dark">Daftar Pegawai</h3>
        </div>

        <!-- Filters Form -->
        <div class="d-flex gap-2 align-items-center flex-wrap ms-auto">
          <!-- Masa Kerja Filter -->
          <div class="d-flex align-items-center gap-1 border rounded p-1">
            <span class="text-secondary small text-nowrap px-1">Masa Kerja (Thn)</span>
            <input type="number" class="form-control form-control-sm" style="width: 50px" placeholder="Min" v-model="minTenure" @change="fetchEmployees(1)" />
            <span class="text-muted">-</span>
            <input type="number" class="form-control form-control-sm" style="width: 50px" placeholder="Max" v-model="maxTenure" @change="fetchEmployees(1)" />
          </div>

          <!-- Filter Jabatan -->
          <select v-model="filterPositionId" class="form-select form-select-sm" style="width: 160px" @change="fetchEmployees(1)">
            <option value="">Semua Jabatan</option>
            <option v-for="pos in positions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
          </select>

          <!-- Filter Kontrak -->
          <select v-model="filterContract" class="form-select form-select-sm" style="width: 150px" @change="fetchEmployees(1)">
            <option value="">Status Kontrak</option>
            <option value="pkwtt">PKWTT (Tetap)</option>
            <option value="pkwt">PKWT (Kontrak)</option>
            <option value="magang">Magang</option>
          </select>

          <!-- Filter Status Keaktifan -->
          <select v-model="filterStatus" class="form-select form-select-sm" style="width: 130px" @change="fetchEmployees(1)">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>

          <!-- Search NIP/Name -->
          <div class="input-group" style="width: 200px">
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Cari NIP/Nama..."
              v-model="searchQuery"
              @keyup.enter="fetchEmployees(1)"
            />
            <button class="btn btn-sm btn-outline-secondary" type="button" @click="fetchEmployees(1)">
              <IconSearch size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- EXPORT PANEL -->
      <div class="card-body py-2 border-bottom d-flex gap-2 justify-content-end bg-light-subtle">
        <button class="btn btn-sm btn-outline-success" @click="triggerExcelExport">
          Download Excel
        </button>
        <button class="btn btn-sm btn-outline-danger" @click="triggerPdfExport">
          Download PDF Daftar
        </button>
      </div>

      <!-- Alert Notification -->
      <div v-if="alertMsg" class="mx-3 mt-3 alert alert-dismissible" :class="alertClass" role="alert">
        <div>{{ alertMsg }}</div>
        <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
        <div class="spinner-border text-primary me-2" role="status"></div>
        <span>Memuat data pegawai...</span>
      </div>

      <!-- Table -->
      <div v-else class="table-responsive card-body p-0">
        <table class="table table-vcenter table-striped table-hover mb-0">
          <thead>
            <tr>
              <th width="30" class="text-center" v-if="canManage">
                <input class="form-check-input" type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" />
              </th>
              <th width="5" class="text-center">No</th>
              <th width="120" class="text-center">Aksi</th>
              <th class="cursor-pointer" @click="toggleSort('nip')">
                NIP
                <span v-if="sortBy === 'nip'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="cursor-pointer" @click="toggleSort('name')">
                Nama
                <span v-if="sortBy === 'name'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="cursor-pointer" @click="toggleSort('position_id')">
                Jabatan
                <span v-if="sortBy === 'position_id'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="cursor-pointer" @click="toggleSort('joined_at')">
                Tanggal Masuk
                <span v-if="sortBy === 'joined_at'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th>Masa Kerja</th>
              <th width="60" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in employees" :key="item.id">
              <td class="text-center" v-if="canManage">
                <input class="form-check-input" type="checkbox" :value="item.id" v-model="selectedIds" />
              </td>
              <td class="text-center">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="text-center text-nowrap">
                <div class="d-flex justify-content-center gap-1">
                  <!-- Detail Profile -->
                  <NuxtLink :to="`/pegawai/${item.id}`" class="btn btn-icon btn-ghost-dark btn-sm rounded-circle" title="Detail">
                    <IconFileDescription size="18" />
                  </NuxtLink>

                  <!-- Aksi Edit -->
                  <NuxtLink :to="`/pegawai/form/${item.id}`" class="btn btn-icon btn-ghost-dark btn-sm rounded-circle" title="Edit" v-if="canManage">
                    <IconPencil size="18" />
                  </NuxtLink>

                  <!-- Download PDF Profile -->
                  <button class="btn btn-icon btn-ghost-dark btn-sm rounded-circle" @click="triggerDetailPdfExport(item)" title="Download PDF Profil">
                    <IconCloudDownload size="18" />
                  </button>

                  <!-- Aksi Hapus -->
                  <button class="btn btn-icon btn-ghost-danger btn-sm rounded-circle" @click="confirmDelete(item)" title="Hapus" v-if="canManage" :disabled="item.user?.role?.code === 'superadmin'">
                    <IconTrash size="18" />
                  </button>
                </div>
              </td>
              <td><code>{{ item.nip }}</code></td>
              <td>
                <span class="fw-bold text-dark">{{ item.name }}</span>
              </td>
              <td>{{ item.position?.name || '-' }}</td>
              <td>{{ formatDate(item.joined_at) }}</td>
              <td>{{ item.work_tenure }}</td>
              <td class="text-center">
                <span :class="item.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ item.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
            </tr>
            <tr v-if="employees.length === 0">
              <td colspan="9" class="text-center py-4 text-muted">Data pegawai tidak ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="card-footer d-flex align-items-center bg-transparent py-3">
        <ul class="pagination ms-auto m-0">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="fetchEmployees(currentPage - 1)">prev</button>
          </li>
          <li
            v-for="page in totalPages"
            :key="page"
            class="page-item"
            :class="{ active: currentPage === page }"
          >
            <button class="page-link" @click="fetchEmployees(page)">{{ page }}</button>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <button class="page-link" @click="fetchEmployees(page + 1)">next</button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Modal Hapus Single Pegawai -->
    <div class="modal fade" id="modal-hapus-pegawai" tabindex="-1" aria-hidden="true" ref="deleteModalElement">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-status bg-danger"></div>
          <div class="modal-body text-center py-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="48"
              height="48"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="text-danger mb-3"
            >
              <path d="M12 9v4"></path>
              <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
              <path d="M12 16h.01"></path>
            </svg>
            <h3 class="mb-1 fw-bold">Hapus Pegawai</h3>
            <div class="text-secondary">
              Apakah Anda yakin ingin menghapus data pegawai <strong>{{ selectedForDelete?.name }}</strong>?
            </div>
          </div>
          <div class="modal-footer">
            <div class="w-100">
              <div class="row">
                <div class="col">
                  <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                    Batal
                  </button>
                </div>
                <div class="col">
                  <button type="button" class="btn btn-danger w-100" :disabled="deleting" @click="deleteEmployee">
                    <span v-if="deleting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Hapus Massal Confirmation -->
    <div class="modal fade" id="modal-bulk-delete" tabindex="-1" aria-hidden="true" ref="bulkDeleteModalElement">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-status bg-danger"></div>
          <div class="modal-body text-center py-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="48"
              height="48"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="text-danger mb-3"
            >
              <path d="M12 9v4"></path>
              <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
              <path d="M12 16h.01"></path>
            </svg>
            <h3 class="mb-1 fw-bold">Hapus Massal</h3>
            <div class="text-secondary">
              Apakah Anda yakin ingin menghapus <strong>{{ selectedIds.length }}</strong> data pegawai terpilih?
            </div>
          </div>
          <div class="modal-footer">
            <div class="w-100">
              <div class="row">
                <div class="col">
                  <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                    Batal
                  </button>
                </div>
                <div class="col">
                  <button type="button" class="btn btn-danger w-100" :disabled="bulkDeleting" @click="applyBulkDelete">
                    <span v-if="bulkDeleting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Hapus Semua
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuth } from '~/composables/useAuth';
import {
  IconPencil,
  IconPlus,
  IconSearch,
  IconTrash,
  IconFileDescription,
  IconCloudDownload,
} from "@tabler/icons-vue";

definePageMeta({
  title: "Data Pegawai",
  layout: false,
});

useSeoMeta({
  title: "Data Pegawai",
});

const { apiFetch, user: currentUser, token } = useAuth();
const config = useRuntimeConfig();

// Access check: Admin HRD can write/CRUD. Manager HRD can only Read/View.
const canManage = computed(() => {
  return currentUser.value?.role !== 'manager_hrd';
});

// State
const employees = ref([]);
const positions = ref([]);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const bulkUpdating = ref(false);
const bulkDeleting = ref(false);

// Filter States
const searchQuery = ref('');
const filterPositionId = ref('');
const filterContract = ref('');
const filterStatus = ref('');
const minTenure = ref('');
const maxTenure = ref('');
const sortBy = ref('created_at');
const sortOrder = ref('desc');

// Pagination
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(10);

// Selection
const selectedIds = ref([]);
const bulkStatus = ref('');

// Alert State
const alertMsg = ref('');
const alertClass = ref('alert-success');

// Modal Elements
const deleteModalElement = ref(null);
const bulkDeleteModalElement = ref(null);
let deleteBootstrapModal = null;
let bulkDeleteBootstrapModal = null;

const selectedForDelete = ref(null);

// Getters
const isAllSelected = computed(() => {
  return employees.value.length > 0 && selectedIds.value.length === employees.value.length;
});

const toggleSelectAll = (e) => {
  if (e.target.checked) {
    selectedIds.value = employees.value.map(emp => emp.id);
  } else {
    selectedIds.value = [];
  }
};

// Fetch Employees Data
const fetchEmployees = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  selectedIds.value = []; // Reset selected items on page transition
  try {
    let url = `/employees?page=${page}&per_page=${perPage.value}&sort_by=${sortBy.value}&sort_order=${sortOrder.value}`;
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`;
    }
    if (filterPositionId.value) {
      url += `&positions=[${filterPositionId.value}]`;
    }
    if (filterContract.value) {
      url += `&employment_type=${filterContract.value}`;
    }
    if (filterStatus.value) {
      url += `&status=${filterStatus.value}`;
    }
    if (minTenure.value !== '') {
      url += `&min_tenure=${minTenure.value}`;
    }
    if (maxTenure.value !== '') {
      url += `&max_tenure=${maxTenure.value}`;
    }

    const res = await apiFetch(url);
    employees.value = res.data;
    totalPages.value = res.last_page;
  } catch (error) {
    showToast(error.data?.message || 'Gagal memuat data pegawai.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

// Fetch Metadata (Positions)
const fetchPositions = async () => {
  try {
    const meta = await apiFetch('/employees/form-meta');
    positions.value = meta.positions;
  } catch (e) {
    console.error('Error fetching position metadata:', e);
  }
};

// Sorting
const toggleSort = (field) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'asc';
  }
  fetchEmployees(1);
};

// Action Delete Single
const confirmDelete = (item) => {
  selectedForDelete.value = item;
  if (deleteBootstrapModal) {
    deleteBootstrapModal.show();
  }
};

const deleteEmployee = async () => {
  if (!selectedForDelete.value) return;
  deleting.value = true;
  try {
    await apiFetch(`/employees/${selectedForDelete.value.id}`, { method: 'DELETE' });
    showToast('Data pegawai berhasil dihapus.', 'alert-success');
    if (deleteBootstrapModal) {
      deleteBootstrapModal.hide();
    }
    fetchEmployees(currentPage.value);
  } catch (error) {
    showToast(error.data?.message || 'Gagal menghapus data pegawai.', 'alert-danger');
    if (deleteBootstrapModal) {
      deleteBootstrapModal.hide();
    }
  } finally {
    deleting.value = false;
    selectedForDelete.value = null;
  }
};

// Action Bulk Status
const applyBulkStatus = async () => {
  if (!bulkStatus.value || selectedIds.value.length === 0) return;
  bulkUpdating.value = true;
  try {
    await apiFetch('/employees/bulk-status', {
      method: 'POST',
      body: {
        ids: selectedIds.value,
        status: bulkStatus.value
      }
    });
    showToast('Status keaktifan pegawai terpilih berhasil diupdate.', 'alert-success');
    selectedIds.value = [];
    bulkStatus.value = '';
    fetchEmployees(currentPage.value);
  } catch (e) {
    showToast(e.data?.message || 'Gagal mengupdate status secara massal.', 'alert-danger');
  } finally {
    bulkUpdating.value = false;
  }
};

// Action Bulk Delete
const confirmBulkDelete = () => {
  if (bulkDeleteBootstrapModal) {
    bulkDeleteBootstrapModal.show();
  }
};

const applyBulkDelete = async () => {
  if (selectedIds.value.length === 0) return;
  bulkDeleting.value = true;
  try {
    await apiFetch('/employees/bulk-delete', {
      method: 'POST',
      body: {
        ids: selectedIds.value
      }
    });
    showToast('Data pegawai terpilih berhasil dihapus.', 'alert-success');
    selectedIds.value = [];
    if (bulkDeleteBootstrapModal) {
      bulkDeleteBootstrapModal.hide();
    }
    fetchEmployees(currentPage.value);
  } catch (e) {
    showToast(e.data?.message || 'Gagal menghapus data pegawai secara massal.', 'alert-danger');
    if (bulkDeleteBootstrapModal) {
      bulkDeleteBootstrapModal.hide();
    }
  } finally {
    bulkDeleting.value = false;
  }
};

// Downloads & Exports
const triggerExcelExport = async () => {
  let queryParams = `?token=${token.value}`;
  if (searchQuery.value) queryParams += `&search=${encodeURIComponent(searchQuery.value)}`;
  if (filterPositionId.value) queryParams += `&positions=[${filterPositionId.value}]`;
  if (filterContract.value) queryParams += `&employment_type=${filterContract.value}`;
  if (filterStatus.value) queryParams += `&status=${filterStatus.value}`;

  await downloadFile(`/employees/export/excel${queryParams}`, 'data_pegawai.xlsx');
};

const triggerPdfExport = async () => {
  let queryParams = `?token=${token.value}`;
  if (searchQuery.value) queryParams += `&search=${encodeURIComponent(searchQuery.value)}`;
  if (filterPositionId.value) queryParams += `&positions=[${filterPositionId.value}]`;
  if (filterContract.value) queryParams += `&employment_type=${filterContract.value}`;
  if (filterStatus.value) queryParams += `&status=${filterStatus.value}`;

  await downloadFile(`/employees/export/pdf${queryParams}`, 'daftar_pegawai.pdf');
};

const triggerDetailPdfExport = async (emp) => {
  await downloadFile(`/employees/${emp.id}/export/pdf?token=${token.value}`, `detail_pegawai_${emp.nip}.pdf`);
};

const downloadFile = async (url, filename) => {
  try {
    const res = await fetch(config.public.apiBase + url, {
      headers: {
        'Authorization': `Bearer ${token.value}`
      }
    });
    if (!res.ok) throw new Error('Network response not ok.');
    const blob = await res.blob();
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(link.href);
  } catch (error) {
    showToast('Gagal mengunduh file.', 'alert-danger');
    console.error('Download error:', error);
  }
};

// Utilities
const showToast = (msg, cls) => {
  alertMsg.value = msg;
  alertClass.value = cls;
  setTimeout(() => {
    alertMsg.value = '';
  }, 5000);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  // dateStr is YYYY-MM-DD
  const parts = dateStr.substring(0, 10).split('-');
  if (parts.length !== 3) return dateStr;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

onMounted(() => {
  if (typeof window !== 'undefined' && window.bootstrap) {
    deleteBootstrapModal = new window.bootstrap.Modal(deleteModalElement.value);
    bulkDeleteBootstrapModal = new window.bootstrap.Modal(bulkDeleteModalElement.value);
  }
  fetchPositions();
  fetchEmployees();
});
</script>
