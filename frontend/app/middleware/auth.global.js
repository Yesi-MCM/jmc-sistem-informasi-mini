import { defineNuxtRouteMiddleware, navigateTo } from '#app';

export default defineNuxtRouteMiddleware(async (to, from) => {
  if (process.server) return;

  const { isLoggedIn, fetchUser, user, hasModuleAccess } = useAuth();

  const isAuthPage = to.path.toLowerCase().startsWith('/login');

  // If token exists, try fetching the user profile if not loaded
  if (isLoggedIn() && !user.value) {
    await fetchUser();
  }

  // 1. If not logged in and trying to access protected page
  if (!isLoggedIn() && !isAuthPage) {
    return navigateTo('/Login');
  }

  // 2. If logged in and trying to access login page
  if (isLoggedIn() && isAuthPage) {
    return navigateTo('/');
  }

  // 3. Route-based RBAC permission checks
  if (isLoggedIn()) {
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
