<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center bg-white rounded shadow-sm">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat data hak akses...</span>
    </div>

    <template v-else>
      <!-- Breadcrumbs & Back Button -->
      <div class="mb-3 d-flex align-items-center gap-2">
        <NuxtLink to="/user/role" class="btn btn-outline-secondary btn-sm">
          Kembali ke Daftar Role
        </NuxtLink>
      </div>

      <!-- Role Details -->
      <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Nama Role</label>
              <input type="text" class="form-control bg-light" :value="role?.name" readonly disabled />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Deskripsi</label>
              <input type="text" class="form-control bg-light" :value="role?.description" readonly disabled />
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions Table -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent py-3">
          <h3 class="card-title fw-bold m-0 text-dark">Konfigurasi Hak Akses Modul</h3>
        </div>
        <div class="table-responsive card-body p-0">
          <table class="table table-vcenter table-striped table-hover mb-0">
            <thead>
              <tr class="text-secondary small fw-bold">
                <th width="5" class="text-center">No</th>
                <th>Modul / Fitur</th>
                <th class="text-center" width="100">Akses</th>
                <th class="text-center" width="100">Create</th>
                <th class="text-center" width="120">Read</th>
                <th class="text-center" width="120">Update</th>
                <th class="text-center" width="120">Delete</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in role?.permissions" :key="item.id">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="fw-bold text-dark">{{ item.module?.name }}</div>
                  <div class="small text-secondary">{{ item.module?.description }}</div>
                </td>
                <td class="text-center">
                  <IconCircleCheckFilled
                    v-if="item.can_access"
                    class="text-success fs-3"
                  />
                  <IconXboxXFilled v-else class="text-danger fs-3" />
                </td>
                <td class="text-center">
                  <IconCircleCheckFilled
                    v-if="item.can_create"
                    class="text-success fs-3"
                  />
                  <IconXboxXFilled v-else class="text-danger fs-3" />
                </td>
                <td>
                  <span :class="getScopeClass(item.read_scope)">
                    {{ formatScope(item.read_scope) }}
                  </span>
                </td>
                <td>
                  <span :class="getScopeClass(item.update_scope)">
                    {{ formatScope(item.update_scope) }}
                  </span>
                </td>
                <td>
                  <span :class="getScopeClass(item.delete_scope)">
                    {{ formatScope(item.delete_scope) }}
                  </span>
                </td>
              </tr>
              <tr v-if="!role?.permissions || role.permissions.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">Belum ada modul yang terkonfigurasi.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '~/composables/useAuth';
import { IconCircleCheckFilled, IconXboxXFilled } from "@tabler/icons-vue";

definePageMeta({
  title: "Hak Akses Role",
});

useSeoMeta({
  title: "Hak Akses Role",
});

const route = useRoute();
const { apiFetch } = useAuth();
const role = ref(null);
const loading = ref(true);

const fetchRoleDetails = async () => {
  loading.value = true;
  try {
    const roleId = route.params.id;
    const res = await apiFetch(`/users/roles/${roleId}`);
    role.value = res;
  } catch (error) {
    console.error('Error fetching role details:', error);
  } finally {
    loading.value = false;
  }
};

const formatScope = (scope) => {
  if (!scope) return '-';
  switch (scope.toLowerCase()) {
    case 'all': return 'Semua Data (All)';
    case 'own': return 'Milik Sendiri (Own)';
    case 'no': return 'Tidak Ada (No)';
    default: return scope;
  }
};

const getScopeClass = (scope) => {
  if (!scope) return '';
  switch (scope.toLowerCase()) {
    case 'all': return 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
    case 'own': return 'badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1';
    case 'no': return 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
    default: return 'badge bg-secondary-subtle text-secondary px-2 py-1';
  }
};

onMounted(() => {
  fetchRoleDetails();
});
</script>
