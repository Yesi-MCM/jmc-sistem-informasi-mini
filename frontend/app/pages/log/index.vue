<template>
  <div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3 flex-wrap gap-2">
      <h3 class="card-title fw-bold text-dark m-0">Log Aktivitas Sistem</h3>
      <div class="ms-auto d-flex gap-2">
        <select v-model="perPage" class="form-select form-select-sm" style="width: 100px" @change="fetchLogs(1)">
          <option :value="10">10 Baris</option>
          <option :value="20">20 Baris</option>
          <option :value="50">50 Baris</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat data log...</span>
    </div>

    <!-- Table View -->
    <div v-else class="table-responsive card-body p-0">
      <table class="table table-vcenter table-striped table-hover mb-0">
        <thead>
          <tr class="text-secondary small fw-bold">
            <th width="5" class="text-center">No</th>
            <th>Nama User</th>
            <th>Modul</th>
            <th>Aksi</th>
            <th>Deskripsi Aktivitas</th>
            <th>IP Address</th>
            <th>Waktu Kejadian (Timestamp)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in logs" :key="item.id">
            <td class="text-center">{{ (currentPage - 1) * perPage + index + 1 }}</td>
            <td>
              <div class="fw-bold text-dark">{{ item.user_name }}</div>
              <div class="small text-secondary">Username: <code>{{ item.username }}</code></div>
            </td>
            <td>
              <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                {{ item.module }}
              </span>
            </td>
            <td>
              <span :class="getActionClass(item.action)">
                {{ item.action }}
              </span>
            </td>
            <td><small class="text-dark">{{ item.description }}</small></td>
            <td><code>{{ item.ip_address || '127.0.0.1' }}</code></td>
            <td>{{ item.created_at }}</td>
          </tr>
          <tr v-if="logs.length === 0">
            <td colspan="7" class="text-center py-4 text-muted">Belum ada log aktivitas yang tercatat.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="card-footer d-flex align-items-center bg-transparent py-3">
      <ul class="pagination ms-auto m-0">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <button class="page-link" @click="fetchLogs(currentPage - 1)">prev</button>
        </li>
        <li
          v-for="page in totalPages"
          :key="page"
          class="page-item"
          :class="{ active: currentPage === page }"
        >
          <button class="page-link" @click="fetchLogs(page)">{{ page }}</button>
        </li>
        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <button class="page-link" @click="fetchLogs(currentPage + 1)">next</button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '~/composables/useAuth';

definePageMeta({
  title: "Log Aktivitas",
});

useSeoMeta({
  title: "Log Aktivitas",
});

const { apiFetch } = useAuth();

const logs = ref([]);
const loading = ref(true);
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(20);

const fetchLogs = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  try {
    const res = await apiFetch(`/logs?page=${page}&per_page=${perPage.value}`);
    logs.value = res.data;
    totalPages.value = res.last_page;
  } catch (error) {
    console.error('Error fetching logs:', error);
  } finally {
    loading.value = false;
  }
};

const getActionClass = (action) => {
  if (!action) return 'badge bg-secondary';
  switch (action.toUpperCase()) {
    case 'CREATE': return 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
    case 'UPDATE': return 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
    case 'DELETE': return 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
    case 'LOGIN': return 'badge bg-blue-subtle text-blue border border-blue-subtle px-2 py-1';
    case 'LOGOUT': return 'badge bg-purple-subtle text-purple border border-purple-subtle px-2 py-1';
    default: return 'badge bg-secondary px-2 py-1';
  }
};

onMounted(() => {
  fetchLogs();
});
</script>
