<template>
  <NuxtLayout name="default">
    <template #actions>
      <button
        class="btn btn-primary"
        @click="openAddModal"
      >
        <IconPlus stroke="{3}" size="20" />Tambah User
      </button>
    </template>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-transparent py-3">
        <h3 class="card-title fw-bold m-0 text-dark">Manajemen User</h3>
        <div class="d-flex gap-2 ms-auto align-items-center">
          <!-- Filter Role -->
          <select v-model="filterRoleId" class="form-select" style="width: 180px" @change="fetchUsers(1)">
            <option value="">Semua Role</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>

          <!-- Search -->
          <div class="input-group" style="width: 250px">
            <input
              type="text"
              class="form-control"
              placeholder="Cari User..."
              v-model="searchQuery"
              @keyup.enter="fetchUsers(1)"
            />
            <button class="btn" type="button" @click="fetchUsers(1)">
              <IconSearch stroke="{2}" />
            </button>
          </div>
        </div>
      </div>

      <!-- Alert Notification -->
      <div v-if="alertMsg" class="mx-3 mt-3 alert alert-dismissible" :class="alertClass" role="alert">
        <div>{{ alertMsg }}</div>
        <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="d-flex p-5 align-items-center justify-content-center">
        <div class="spinner-border text-primary me-2" role="status"></div>
        <span>Memuat data user...</span>
      </div>

      <!-- Table View -->
      <div v-else class="table-responsive card-body p-0">
        <table class="table table-vcenter table-striped table-hover mb-0">
          <thead>
            <tr>
              <th width="5" class="text-center">No</th>
              <th width="120" class="text-center">Action</th>
              <th class="cursor-pointer" @click="toggleSort('name')">
                Nama Pengguna
                <span v-if="sortBy === 'name'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="cursor-pointer" @click="toggleSort('username')">
                Username
                <span v-if="sortBy === 'username'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th>Jabatan</th>
              <th>Departemen</th>
              <th>Role</th>
              <th class="text-center cursor-pointer" @click="toggleSort('status')">
                Status
                <span v-if="sortBy === 'status'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in usersData" :key="item.id">
              <td class="text-center">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="text-center text-nowrap">
                <div class="d-flex justify-content-center gap-1">
                  <!-- Aksi Edit -->
                  <button
                    class="btn btn-icon btn-ghost-dark btn-sm rounded-circle"
                    @click="openEditModal(item)"
                    title="Edit"
                  >
                    <IconPencil size="18" />
                  </button>

                  <!-- Aksi Hapus -->
                  <button
                    class="btn btn-icon btn-ghost-danger btn-sm rounded-circle"
                    @click="confirmDelete(item)"
                    title="Hapus"
                    :disabled="item.id === currentUser?.id"
                  >
                    <IconTrash size="18" />
                  </button>
                </div>
              </td>
              <td>
                <span class="fw-bold text-dark">{{ item.name }}</span>
                <span v-if="item.id === currentUser?.id" class="badge bg-blue-subtle text-blue ms-1">Anda</span>
              </td>
              <td><code>{{ item.username }}</code></td>
              <td>{{ item.employee?.position?.name || '-' }}</td>
              <td>{{ item.employee?.department?.name || '-' }}</td>
              <td>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                  {{ item.role?.name }}
                </span>
              </td>
              <td class="text-center">
                <IconCircleCheckFilled
                  v-if="item.status === 'active'"
                  class="text-success fs-3"
                  title="Aktif"
                />
                <IconXboxXFilled
                  v-else
                  class="text-danger fs-3"
                  title="Nonaktif"
                />
              </td>
            </tr>
            <tr v-if="usersData.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">Data user tidak ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="card-footer d-flex align-items-center bg-transparent py-3">
        <ul class="pagination ms-auto m-0">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="fetchUsers(currentPage - 1)">prev</button>
          </li>
          <li
            v-for="page in totalPages"
            :key="page"
            class="page-item"
            :class="{ active: currentPage === page }"
          >
            <button class="page-link" @click="fetchUsers(page)">{{ page }}</button>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <button class="page-link" @click="fetchUsers(currentPage + 1)">next</button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Modal Form (Tambah/Edit User) -->
    <div class="modal fade" id="modal-user" tabindex="-1" aria-hidden="true" ref="modalElement">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">
              {{ isEditMode ? 'Form Edit User' : 'Form Tambah User' }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <!-- Form Errors -->
            <div v-if="formErrors.length" class="alert alert-danger">
              <ul class="mb-0 ps-3">
                <li v-for="(err, index) in formErrors" :key="index">{{ err }}</li>
              </ul>
            </div>

            <!-- NAMA PENGGUNA (Autosuggest) -->
            <div class="mb-3 position-relative">
              <label class="form-label required">Nama Pengguna (Pegawai)</label>
              <template v-if="isEditMode">
                <input type="text" class="form-control bg-light" :value="form.employee_name" readonly disabled />
              </template>
              <template v-else>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Ketik nama atau NIP pegawai..."
                  v-model="employeeSearch"
                  @input="onEmployeeSearchInput"
                  @focus="showSuggestions = true"
                />
                <!-- Autocomplete suggestions dropdown -->
                <ul
                  v-if="showSuggestions && suggestions.length > 0"
                  class="dropdown-menu show w-100 shadow-sm overflow-auto"
                  style="max-height: 200px; z-index: 1050; display: block;"
                >
                  <li v-for="emp in suggestions" :key="emp.employee_id">
                    <button
                      type="button"
                      class="dropdown-item py-2"
                      @click="selectEmployee(emp)"
                    >
                      <div class="fw-bold text-dark">{{ emp.name }}</div>
                      <div class="small text-secondary">
                        NIP: {{ emp.nip }} | {{ emp.position_name }} ({{ emp.department_name }})
                      </div>
                    </button>
                  </li>
                </ul>
                <div v-if="employeeSearch && suggestions.length === 0 && !searchingEmployees" class="text-danger small mt-1">
                  Pegawai tidak ditemukan atau sudah memiliki akun.
                </div>
              </template>
            </div>

            <!-- USERNAME -->
            <div class="mb-3">
              <label class="form-label required">Username</label>
              <input
                type="text"
                class="form-control"
                placeholder="Minimal 6 karakter, huruf kecil & angka saja"
                v-model="form.username"
                :disabled="isEditMode"
                @keyup="validateUsername"
              />
              <div v-if="usernameError" class="text-danger small mt-1">
                {{ usernameError }}
              </div>
            </div>

            <!-- JABATAN & DEPARTEMEN (Readonly info from selected employee) -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Jabatan</label>
                <input type="text" class="form-control bg-light" :value="form.position_name" readonly disabled />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Departemen</label>
                <input type="text" class="form-control bg-light" :value="form.department_name" readonly disabled />
              </div>
            </div>

            <!-- ROLE -->
            <div class="mb-3">
              <label class="form-label required">Role</label>
              <select v-model="form.role_id" class="form-select">
                <option value="" disabled>Pilih terlebih dahulu</option>
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>

            <!-- PASSWORD -->
            <div class="mb-3" v-if="!isEditMode">
              <label class="form-label required">Password</label>
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  v-model="form.password"
                  @keyup="validatePassword"
                  placeholder="Min 8 karakter, huruf besar/kecil & karakter khusus"
                />
                <button type="button" class="btn btn-outline-secondary" @click="triggerPasswordGeneration">
                  Generate
                </button>
              </div>
              <div v-if="passwordError" class="text-danger small mt-1">
                {{ passwordError }}
              </div>
            </div>

            <!-- STATUS -->
            <div>
              <label class="form-label">Status</label>
              <label class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  v-model="form.isActive"
                  :disabled="isEditMode && form.id === currentUser?.id"
                />
                <span class="form-check-label">Aktif</span>
              </label>
              <div v-if="isEditMode && form.id === currentUser?.id" class="text-secondary small">
                Anda tidak diperkenankan menonaktifkan akun Anda sendiri.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="d-flex gap-2 ms-auto">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Kembali
              </button>
              <button
                type="button"
                class="btn btn-primary"
                :disabled="saving || !isFormValid"
                @click="saveUser"
              >
                <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                Simpan
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Hapus Confirmation -->
    <div class="modal fade" id="modal-hapus-user" tabindex="-1" aria-hidden="true" ref="deleteModalElement">
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
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
            <h3 class="mb-1 fw-bold">Hapus User</h3>
            <div class="text-secondary">
              Apakah Anda yakin ingin menghapus akun <strong>{{ selectedUserForDelete?.username }}</strong>?
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
                  <button
                    type="button"
                    class="btn btn-danger w-100"
                    :disabled="deleting"
                    @click="deleteUser"
                  >
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
  IconCircleCheckFilled,
  IconXboxXFilled
} from "@tabler/icons-vue";

definePageMeta({
  title: "Manajemen User",
  layout: false,
});

useSeoMeta({
  title: "Manajemen User",
});

const { apiFetch, user: currentUser } = useAuth();

// State
const usersData = ref([]);
const roles = ref([]);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);

// Query Params / Filter
const searchQuery = ref('');
const filterRoleId = ref('');
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(10);

// Alert state
const alertMsg = ref('');
const alertClass = ref('alert-success');

// Modal Elements
const modalElement = ref(null);
const deleteModalElement = ref(null);
let bootstrapModal = null;
let deleteBootstrapModal = null;

// Form State
const isEditMode = ref(false);
const formErrors = ref([]);
const employeeSearch = ref('');
const showSuggestions = ref(false);
const suggestions = ref([]);
const searchingEmployees = ref(false);
let searchDebounce = null;

const form = ref({
  id: null,
  employee_id: '',
  employee_name: '',
  username: '',
  position_name: '',
  department_name: '',
  role_id: '',
  password: '',
  isActive: true
});

// Real-time username / password validation errors
const usernameError = ref('');
const passwordError = ref('');

// Getters for form validation
const isFormValid = computed(() => {
  if (isEditMode.value) {
    return !!form.value.role_id;
  }
  return (
    !!form.value.employee_id &&
    !!form.value.username &&
    !usernameError.value &&
    !!form.value.role_id &&
    !!form.value.password &&
    !passwordError.value
  );
});

// Fetch Users List
const fetchUsers = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  try {
    let url = `/users?page=${page}&per_page=${perPage.value}&sort_by=${sortBy.value}&sort_order=${sortOrder.value}`;
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`;
    }
    if (filterRoleId.value) {
      url += `&role_id=${filterRoleId.value}`;
    }
    const res = await apiFetch(url);
    usersData.value = res.data;
    totalPages.value = res.last_page;
  } catch (error) {
    showToast(error.data?.message || 'Gagal memuat data user.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

// Fetch Roles
const fetchRoles = async () => {
  try {
    const res = await apiFetch('/users/roles');
    roles.value = res;
  } catch (error) {
    console.error('Error fetching roles:', error);
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
  fetchUsers(1);
};

// Autosuggestions Employee
const onEmployeeSearchInput = () => {
  clearTimeout(searchDebounce);
  showSuggestions.value = true;
  
  if (employeeSearch.value.length < 2) {
    suggestions.value = [];
    return;
  }

  searchingEmployees.value = true;
  searchDebounce = setTimeout(async () => {
    try {
      const res = await apiFetch(`/users/search-employees?q=${encodeURIComponent(employeeSearch.value)}`);
      suggestions.value = res.data;
    } catch (e) {
      console.error(e);
    } finally {
      searchingEmployees.value = false;
    }
  }, 300);
};

const selectEmployee = (emp) => {
  form.value.employee_id = emp.employee_id;
  form.value.employee_name = emp.name;
  form.value.position_name = emp.position_name;
  form.value.department_name = emp.department_name;
  employeeSearch.value = emp.name;
  showSuggestions.value = false;
  suggestions.value = [];
};

// Username validation
const validateUsername = () => {
  const username = form.value.username;
  if (!username) {
    usernameError.value = 'Username wajib diisi.';
    return;
  }
  if (username.length < 6) {
    usernameError.value = 'Username minimal 6 karakter.';
    return;
  }
  // lower alphanumeric only
  const regex = /^[a-z0-9]+$/;
  if (!regex.test(username)) {
    usernameError.value = 'Username hanya boleh terdiri dari huruf kecil dan angka, tanpa spasi.';
    return;
  }
  usernameError.value = '';
};

// Password validation
const validatePassword = () => {
  const pass = form.value.password;
  if (!pass) {
    passwordError.value = 'Password wajib diisi.';
    return;
  }
  if (pass.length < 8) {
    passwordError.value = 'Password minimal 8 karakter.';
    return;
  }
  if (/\s/.test(pass)) {
    passwordError.value = 'Password tidak boleh mengandung spasi.';
    return;
  }
  if (!/[A-Z]/.test(pass)) {
    passwordError.value = 'Password harus mengandung minimal 1 huruf besar.';
    return;
  }
  if (!/[a-z]/.test(pass)) {
    passwordError.value = 'Password harus mengandung minimal 1 huruf kecil.';
    return;
  }
  if (!/[!@#$%^&*(),.?":{}|<>]/.test(pass)) {
    passwordError.value = 'Password harus mengandung minimal 1 karakter khusus.';
    return;
  }
  passwordError.value = '';
};

// Generate Password automatically
const triggerPasswordGeneration = () => {
  const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const lowercase = 'abcdefghijklmnopqrstuvwxyz';
  const numbers = '0123456789';
  const special = '!@#$%^&*()_+-=[]{}|;:,./<>?';
  
  let password = '';
  password += uppercase[Math.floor(Math.random() * uppercase.length)];
  password += lowercase[Math.floor(Math.random() * lowercase.length)];
  password += numbers[Math.floor(Math.random() * numbers.length)];
  password += special[Math.floor(Math.random() * special.length)];
  
  const allChars = uppercase + lowercase + numbers + special;
  for (let i = 4; i < 12; i++) {
    password += allChars[Math.floor(Math.random() * allChars.length)];
  }
  
  // Shuffle password characters
  form.value.password = password.split('').sort(() => 0.5 - Math.random()).join('');
  validatePassword();
};

// Modal Controls
const openAddModal = () => {
  isEditMode.value = false;
  formErrors.value = [];
  usernameError.value = '';
  passwordError.value = '';
  employeeSearch.value = '';
  showSuggestions.value = false;
  suggestions.value = [];
  
  form.value = {
    id: null,
    employee_id: '',
    employee_name: '',
    username: '',
    position_name: '-',
    department_name: '-',
    role_id: '',
    password: '',
    isActive: true
  };

  triggerPasswordGeneration();

  if (bootstrapModal) {
    bootstrapModal.show();
  }
};

const openEditModal = (item) => {
  isEditMode.value = true;
  formErrors.value = [];
  usernameError.value = '';
  passwordError.value = '';

  form.value = {
    id: item.id,
    employee_id: item.employee_id,
    employee_name: item.name,
    username: item.username,
    position_name: item.employee?.position?.name || '-',
    department_name: item.employee?.department?.name || '-',
    role_id: item.role_id,
    password: '',
    isActive: item.status === 'active'
  };

  if (bootstrapModal) {
    bootstrapModal.show();
  }
};

// Save User
const saveUser = async () => {
  formErrors.value = [];
  saving.value = true;

  try {
    const payload = {
      role_id: form.value.role_id,
      status: form.value.isActive ? 'active' : 'inactive'
    };

    let res;
    if (isEditMode.value) {
      res = await apiFetch(`/users/${form.value.id}`, {
        method: 'PUT',
        body: payload
      });
      showToast('User berhasil diperbarui.', 'alert-success');
    } else {
      payload.employee_id = form.value.employee_id;
      payload.username = form.value.username;
      payload.password = form.value.password;
      res = await apiFetch('/users', {
        method: 'POST',
        body: payload
      });
      showToast('User berhasil ditambahkan.', 'alert-success');
    }

    if (bootstrapModal) {
      bootstrapModal.hide();
    }
    fetchUsers(currentPage.value);
  } catch (error) {
    if (error.data?.errors) {
      formErrors.value = Object.values(error.data.errors).flat();
    } else {
      formErrors.value = [error.data?.message || 'Terjadi kesalahan sistem.'];
    }
  } finally {
    saving.value = false;
  }
};

// Delete User
const selectedUserForDelete = ref(null);

const confirmDelete = (item) => {
  selectedUserForDelete.value = item;
  if (deleteBootstrapModal) {
    deleteBootstrapModal.show();
  }
};

const deleteUser = async () => {
  if (!selectedUserForDelete.value) return;
  deleting.value = true;

  try {
    await apiFetch(`/users/${selectedUserForDelete.value.id}`, {
      method: 'DELETE'
    });
    showToast('User berhasil dihapus.', 'alert-success');
    if (deleteBootstrapModal) {
      deleteBootstrapModal.hide();
    }
    fetchUsers(currentPage.value);
  } catch (error) {
    showToast(error.data?.message || 'Gagal menghapus user.', 'alert-danger');
    if (deleteBootstrapModal) {
      deleteBootstrapModal.hide();
    }
  } finally {
    deleting.value = false;
    selectedUserForDelete.value = null;
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

onMounted(() => {
  // Initialize Bootstrap Modals
  if (typeof window !== 'undefined' && window.bootstrap) {
    bootstrapModal = new window.bootstrap.Modal(modalElement.value);
    deleteBootstrapModal = new window.bootstrap.Modal(deleteModalElement.value);
  }

  fetchRoles();
  fetchUsers();
});
</script>
