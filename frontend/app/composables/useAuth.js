import { useState, useCookie, useRuntimeConfig, navigateTo } from '#app';

export const useAuth = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;

  const user = useState('auth_user', () => null);
  const token = useState('auth_token', () => null);
  const tokenCookie = useCookie('auth_token', { maxAge: 86400, path: '/' });

  // Initialize token from cookie
  if (tokenCookie.value && !token.value) {
    token.value = tokenCookie.value;
  }

  /**
   * Helper to perform API requests with authorization headers
   */
  const apiFetch = async (url, options = {}) => {
    const headers = {
      ...options.headers,
      'Accept': 'application/json',
    };

    if (token.value) {
      headers['Authorization'] = `Bearer ${token.value}`;
    }

    try {
      const response = await $fetch(url, {
        baseURL: apiBase,
        ...options,
        headers,
      });
      return response;
    } catch (error) {
      // If unauthorized (401), clean up session and redirect to login
      if (error.status === 401) {
        clearSession();
        navigateTo('/Login');
      }
      throw error;
    }
  };

  /**
   * Fetch current authenticated user profile
   */
  const fetchUser = async () => {
    if (!token.value) return null;
    try {
      const res = await apiFetch('/auth/me');
      user.value = res.user;
      return res.user;
    } catch (e) {
      clearSession();
      return null;
    }
  };

  /**
   * Stage 1: Check credentials + Captcha -> Sends OTP
   */
  const login = async (loginIdentifier, password, captchaKey, captchaCode, rememberMe) => {
    return await $fetch('/auth/login', {
      baseURL: apiBase,
      method: 'POST',
      body: {
        login_identifier: loginIdentifier,
        password,
        captcha_key: captchaKey,
        captcha_code: captchaCode,
        remember_me: rememberMe
      }
    });
  };

  /**
   * Stage 2: Verify OTP code -> Sets active session
   */
  const verifyOtp = async (tempToken, otpCode) => {
    const res = await $fetch('/auth/verify-otp', {
      baseURL: apiBase,
      method: 'POST',
      body: {
        temp_token: tempToken,
        otp_code: otpCode
      }
    });

    if (res && res.access_token) {
      tokenCookie.value = res.access_token;
      token.value = res.access_token;
      user.value = res.user;
    }

    return res;
  };

  /**
   * Invalidate session and logout
   */
  const logout = async () => {
    if (token.value) {
      try {
        await apiFetch('/auth/logout', { method: 'POST' });
      } catch (e) {
        // Suppress network errors on logout
      }
    }
    clearSession();
    navigateTo('/Login');
  };

  /**
   * Clear session variables locally
   */
  const clearSession = () => {
    tokenCookie.value = null;
    token.value = null;
    user.value = null;
  };

  /**
   * Check if user is logged in
   */
  const isLoggedIn = () => {
    return !!token.value;
  };

  /**
   * Check if user is authorized for a specific module
   */
  const hasModuleAccess = (moduleCode) => {
    if (!user.value || !user.value.role) return false;
    
    const role = user.value.role;
    
    // Superadmin punya akses ke semua modul
    if (role === 'superadmin') {
      return true;
    }
    
    if (role === 'manager_hrd') {
      return ['dashboard', 'profile', 'pegawai', 'presensi', 'tunjangan_transport'].includes(moduleCode);
    }
    
    if (role === 'admin_hrd') {
      return ['dashboard', 'profile', 'pegawai', 'presensi', 'tunjangan_transport', 'setting_tunjangan'].includes(moduleCode);
    }
    
    return false;
  };

  return {
    user,
    token,
    apiFetch,
    fetchUser,
    login,
    verifyOtp,
    logout,
    isLoggedIn,
    hasModuleAccess,
    clearSession
  };
};
