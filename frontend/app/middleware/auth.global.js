import { defineNuxtRouteMiddleware, navigateTo } from '#app';

export default defineNuxtRouteMiddleware(async (to, from) => {
  if (process.server) return;

  const { isLoggedIn, fetchUser, user, hasModuleAccess } = useAuth();

  const isAuthPage = to.path.toLowerCase().startsWith('/login');

  // Jika sudah login tapi user data belum dimuat, fetch dulu sebelum RBAC check
  if (isLoggedIn() && !user.value) {
    await fetchUser();
  }

  // 1. Jika belum login dan mencoba akses halaman protected
  if (!isLoggedIn() && !isAuthPage) {
    return navigateTo('/Login');
  }

  // 2. Jika sudah login dan mencoba akses halaman login
  if (isLoggedIn() && isAuthPage) {
    return navigateTo('/');
  }

  // 3. RBAC permission check — hanya jika user sudah terisi
  if (isLoggedIn() && user.value) {
    const path = to.path.toLowerCase();

    if (path.startsWith('/pegawai') && !hasModuleAccess('pegawai')) {
      return navigateTo('/');
    }

    if (path.startsWith('/tunjangan') && !hasModuleAccess('tunjangan_transport')) {
      return navigateTo('/');
    }

    if (path.startsWith('/user') && !hasModuleAccess('user')) {
      return navigateTo('/');
    }

    if (path.startsWith('/log') && !hasModuleAccess('log')) {
      return navigateTo('/');
    }
  }
});

