<template>
  <div class="card shadow-sm border-0">
    <div class="card-header bg-transparent py-3">
      <h3 class="card-title fw-bold text-dark m-0">Pengaturan Tunjangan Transport</h3>
    </div>
    
    <div v-if="loading" class="card-body text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2 text-secondary">Memuat data pengaturan...</div>
    </div>

    <template v-else>
      <div class="card-body">
        <!-- Success/Error alert -->
        <div v-if="alertMsg" class="alert alert-dismissible" :class="alertClass" role="alert">
          <div>{{ alertMsg }}</div>
          <button type="button" class="btn-close" @click="alertMsg = ''" aria-label="Close"></button>
        </div>

        <div v-if="errors.length" class="alert alert-danger mb-3">
          <ul class="mb-0 ps-3">
            <li v-for="(err, idx) in errors" :key="idx">{{ err }}</li>
          </ul>
        </div>

        <div class="row g-3">
          <!-- Tarif Dasar -->
          <div class="col-md-6">
            <label class="form-label required">Tarif per KM (Rp)</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input
                type="number"
                min="0"
                class="form-control text-end"
                v-model="form.base_fare"
                :disabled="!canEdit"
                required
              />
            </div>
          </div>

          <!-- Berlaku Mulai -->
          <div class="col-md-6">
            <label class="form-label required">Berlaku Mulai</label>
            <input
              type="date"
              class="form-control"
              v-model="form.effective_start"
              :disabled="!canEdit"
              required
            />
          </div>

          <!-- Minimum Jarak -->
          <div class="col-md-6">
            <label class="form-label required">Batas Jarak Minimum (KM)</label>
            <div class="input-group">
              <input
                type="number"
                min="0"
                class="form-control"
                v-model="form.min_km"
                :disabled="!canEdit"
                required
              />
              <span class="input-group-text">KM</span>
            </div>
          </div>

          <!-- Maksimum Jarak -->
          <div class="col-md-6">
            <label class="form-label required">Batas Jarak Maksimum (KM)</label>
            <div class="input-group">
              <input
                type="number"
                min="0"
                class="form-control"
                v-model="form.max_km"
                :disabled="!canEdit"
                required
              />
              <span class="input-group-text">KM</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card-footer bg-transparent py-3" v-if="canEdit">
        <div class="d-flex gap-2">
          <button
            class="btn btn-primary px-4"
            :disabled="saving || !isFormValid"
            @click="saveSetting"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
            Simpan Konfigurasi
          </button>
          <button class="btn btn-outline-secondary" @click="goBack">
            Kembali
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '~/composables/useAuth';

const router = useRouter();
const { apiFetch, user } = useAuth();

// Loading states
const loading = ref(true);
const saving = ref(false);
const alertMsg = ref('');
const alertClass = ref('alert-success');
const errors = ref([]);

// Role-based authorization client check
const canEdit = computed(() => {
  return user.value?.role === 'admin_hrd';
});

// Form state
const form = ref({
  base_fare: 5000,
  effective_start: '',
  min_km: 5,
  max_km: 25
});

const isFormValid = computed(() => {
  return (
    form.value.base_fare >= 0 &&
    !!form.value.effective_start &&
    form.value.min_km >= 0 &&
    form.value.max_km > form.value.min_km
  );
});

const goBack = () => {
  router.push('/');
};

// Date Format utilities
const formatToBackendDate = (dateStr) => {
  if (!dateStr) return '';
  const [year, month, day] = dateStr.split('-');
  return `${day}/${month}/${year}`;
};

const formatToInputDate = (dateStr) => {
  if (!dateStr) return '';
  if (dateStr.includes('-')) {
    return dateStr.substring(0, 10);
  }
  const parts = dateStr.split('/');
  if (parts.length === 3) {
    const [day, month, year] = parts;
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
  }
  return dateStr;
};

// Load Setting
const fetchSetting = async () => {
  loading.value = true;
  try {
    const res = await apiFetch('/allowance-settings');
    form.value = {
      base_fare: res.base_fare,
      effective_start: formatToInputDate(res.effective_start || res.effective_from),
      min_km: res.min_km || res.min_distance_km || 5,
      max_km: res.max_km || res.max_distance_km || 25
    };
  } catch (e) {
    showToast('Gagal memuat konfigurasi tunjangan.', 'alert-danger');
  } finally {
    loading.value = false;
  }
};

// Save Setting
const saveSetting = async () => {
  saving.value = true;
  alertMsg.value = '';
  errors.value = [];

  try {
    const payload = {
      base_fare: form.value.base_fare,
      effective_start: formatToBackendDate(form.value.effective_start),
      min_km: form.value.min_km,
      max_km: form.value.max_km
    };

    await apiFetch('/allowance-settings', {
      method: 'POST',
      body: payload
    });

    showToast('Pengaturan tunjangan transport berhasil diperbarui.', 'alert-success');
  } catch (error) {
    if (error.data?.errors) {
      errors.value = Object.values(error.data.errors).flat();
    } else {
      errors.value = [error.data?.message || 'Gagal menyimpan pengaturan tunjangan.'];
    }
  } finally {
    saving.value = false;
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
  fetchSetting();
});
</script>
