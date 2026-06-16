<template>
  <div>
    <!-- Success / Error Alert -->
    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ errorMessage }}
      <button type="button" class="btn-close" @click="errorMessage = ''" aria-label="Close"></button>
    </div>

    <!-- STAGE 1: Username, Password, Captcha -->
    <form v-if="loginStage === 1" @submit.prevent="handleStage1">
      <!-- Username / Email / Cellphone -->
      <div class="mb-3">
        <label class="form-label text-secondary small fw-bold">Username / Email / Nomor HP</label>
        <input
          v-model="form.login_identifier"
          type="text"
          class="form-control py-2 bg-light text-dark"
          placeholder="Masukkan username, email, atau nomor HP"
          required
        />
      </div>

      <!-- Password -->
      <div class="mb-3">
        <label class="form-label text-secondary small fw-bold">Password</label>
        <input
          v-model="form.password"
          type="password"
          class="form-control py-2 bg-light text-dark"
          placeholder="Masukkan password"
          required
        />
      </div>

      <!-- Captcha -->
      <div class="mb-3">
        <label class="form-label text-secondary small fw-bold">Captcha</label>
        <div class="d-flex gap-2 align-items-center mb-2">
          <div class="bg-white p-1 border rounded" style="min-width: 130px; height: 44px; display: flex; align-items: center; justify-content: center;">
            <img v-if="captchaImage" :src="captchaImage" alt="Captcha" style="height: 38px; border-radius: 4px;" />
            <span v-else class="text-muted small">Loading...</span>
          </div>
          <button type="button" class="btn btn-outline-secondary py-2 px-3" @click="loadCaptcha" title="Muat ulang captcha">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -5v5h5" />
              <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 5v-5h-5" />
            </svg>
          </button>
        </div>
        <input
          v-model="form.captcha_code"
          type="text"
          class="form-control py-2 bg-light text-dark"
          placeholder="Masukkan 5 digit kode di atas"
          required
          maxlength="5"
        />
      </div>

      <!-- Remember Me -->
      <div class="mb-3">
        <label class="form-check">
          <input v-model="form.remember_me" type="checkbox" class="form-check-input" />
          <span class="form-check-label text-secondary">Ingat saya (Lewati batas 3 menit sesi tidak aktif)</span>
        </label>
      </div>

      <!-- Submit Stage 1 -->
      <div class="d-grid mt-4">
        <button class="btn btn-primary text-uppercase shadow py-2 fw-bold" type="submit" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
          Kirim OTP
        </button>
      </div>
    </form>

    <!-- STAGE 2: OTP Code Verification -->
    <form v-else @submit.prevent="handleStage2">
      <div class="alert alert-info py-2 small mb-4">
        {{ otpMessage }}
      </div>

      <div class="mb-3">
        <label class="form-label text-secondary small fw-bold">Kode OTP 4-Digit</label>
        <input
          v-model="form.otp_code"
          type="text"
          class="form-control py-2 text-center fs-2 bg-light text-dark fw-bold"
          placeholder="0000"
          required
          maxlength="4"
          pattern="[0-9]{4}"
          title="Masukkan 4 digit kode OTP angka"
          style="letter-spacing: 12px; padding-left: 24px;"
        />
      </div>

      <!-- Actions -->
      <div class="d-grid gap-2 mt-4">
        <button class="btn btn-primary text-uppercase shadow py-2 fw-bold" type="submit" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
          Verifikasi & Masuk
        </button>
        
        <button type="button" class="btn btn-link text-secondary btn-sm" @click="backToStage1" :disabled="loading">
          Kembali ke Form Login
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '~/composables/useAuth';

const { login, verifyOtp } = useAuth();

const loginStage = ref(1); // 1 = Credentials, 2 = OTP
const loading = ref(false);
const errorMessage = ref('');
const otpMessage = ref('');
const captchaImage = ref('');

const form = ref({
  login_identifier: '',
  password: '',
  captcha_key: '',
  captcha_code: '',
  remember_me: false,
  otp_code: '',
});

const tempToken = ref('');

// Load captcha details from backend
const loadCaptcha = async () => {
  try {
    const config = useRuntimeConfig();
    const res = await $fetch('/auth/captcha', {
      baseURL: config.public.apiBase
    });
    form.value.captcha_key = res.captcha_key;
    captchaImage.value = res.captcha_image;
    form.value.captcha_code = ''; // clear previous input
  } catch (e) {
    errorMessage.value = 'Gagal memuat captcha. Silakan muat ulang halaman.';
  }
};

onMounted(() => {
  loadCaptcha();
});

// Submit Stage 1
const handleStage1 = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await login(
      form.value.login_identifier,
      form.value.password,
      form.value.captcha_key,
      form.value.captcha_code,
      form.value.remember_me
    );

    if (res.status === 'otp_sent') {
      tempToken.value = res.temp_token;
      otpMessage.value = res.message;
      loginStage.value = 2; // Proceed to OTP screen
      
      // Store remember_me state in localStorage for inactivity client-side plugin
      localStorage.setItem('remember_me', form.value.remember_me ? 'true' : 'false');
    }
  } catch (e) {
    if (e.data && e.data.errors) {
      const errs = e.data.errors;
      const firstKey = Object.keys(errs)[0];
      errorMessage.value = errs[firstKey][0];
    } else {
      errorMessage.value = e.data?.message || 'Login gagal. Periksa kembali koneksi Anda.';
    }
    // Refresh captcha on failure
    loadCaptcha();
  } finally {
    loading.value = false;
  }
};

// Submit Stage 2 (OTP code)
const handleStage2 = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    await verifyOtp(tempToken.value, form.value.otp_code);
    // Redirect to dashboard after successful OTP verification
    await navigateTo('/', { replace: true });
  } catch (e) {
    if (e.data && e.data.errors) {
      const errs = e.data.errors;
      const firstKey = Object.keys(errs)[0];
      errorMessage.value = errs[firstKey][0];
    } else {
      errorMessage.value = e.data?.message || 'Verifikasi OTP gagal.';
    }
  } finally {
    loading.value = false;
  }
};

// Back to Stage 1
const backToStage1 = () => {
  loginStage.value = 1;
  form.value.otp_code = '';
  loadCaptcha();
};
</script>
