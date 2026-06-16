<template>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title fw-bold">Daftar Role</h3>
      <div class="d-flex gap-2 ms-auto">
        <!-- Search -->
        <div class="input-group" style="width: 250px">
          <input
            type="text"
            class="form-control"
            placeholder="Cari Role ..."
            v-model="searchQuery"
          />
          <button class="btn" type="button" @click="fetchRoles">
            <IconSearch stroke="{2}" />
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
      <div class="spinner-border text-primary me-2" role="status"></div>
      <span>Memuat daftar role...</span>
    </div>

    <div v-else class="table-responsive card-body p-0">
      <table class="table table-vcenter table-striped">
        <thead>
          <tr>
            <th width="5" class="text-center">No</th>
            <th>Role</th>
            <th>Deskripsi</th>
            <th class="text-center" width="150">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredRoles" :key="item.id">
            <td class="text-center">{{ index + 1 }}</td>
            <td><strong>{{ item.name }}</strong> <span class="badge bg-secondary-subtle text-secondary ms-1">{{ item.code }}</span></td>
            <td>{{ item.description }}</td>
            <td class="text-center">
              <NuxtLink
                :to="`/user/role/hak-akses/${item.id}`"
                class="btn btn-sm btn-primary px-3"
              >
                Hak Akses
              </NuxtLink>
            </td>
          </tr>
          <tr v-if="filteredRoles.length === 0">
            <td colspan="4" class="text-center py-4 text-muted">Role tidak ditemukan.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '~/composables/useAuth';
import { IconSearch } from "@tabler/icons-vue";

definePageMeta({
  title: "Manajemen Role",
});

useSeoMeta({
  title: "Manajemen Role",
});

const { apiFetch } = useAuth();
const roles = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchRoles = async () => {
  loading.value = true;
  try {
    const res = await apiFetch('/users/roles');
    roles.value = res;
  } catch (error) {
    console.error('Error fetching roles:', error);
  } finally {
    loading.value = false;
  }
};

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value;
  const q = searchQuery.value.toLowerCase();
  return roles.value.filter(role => 
    role.name.toLowerCase().includes(q) || 
    role.code.toLowerCase().includes(q) ||
    (role.description && role.description.toLowerCase().includes(q))
  );
});

onMounted(() => {
  fetchRoles();
});
</script>
