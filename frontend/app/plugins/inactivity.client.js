import { defineNuxtPlugin } from '#app';

export default defineNuxtPlugin((nuxtApp) => {
  if (process.server) return;

  const { logout, isLoggedIn } = useAuth();
  
  let lastActivity = Date.now();
  const timeoutLimit = 3 * 60 * 1000; // 3 minutes in milliseconds

  // Reset activity timer
  const resetTimer = () => {
    lastActivity = Date.now();
  };

  // List of events to listen to
  const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

  // Register listeners
  events.forEach((event) => {
    window.addEventListener(event, resetTimer, { passive: true });
  });

  // Check for inactivity every 5 seconds
  const interval = setInterval(() => {
    if (!isLoggedIn()) {
      return;
    }

    // Check if 'remember_me' is set in localStorage
    const rememberMe = localStorage.getItem('remember_me') === 'true';
    if (rememberMe) {
      return; // Skip auto-logout if Remember Me is active
    }

    const inactiveDuration = Date.now() - lastActivity;
    if (inactiveDuration >= timeoutLimit) {
      clearInterval(interval);
      
      // Clear remember_me flag
      localStorage.removeItem('remember_me');

      // Trigger logout
      logout().then(() => {
        alert('Sesi Anda berakhir karena tidak ada aktivitas selama 3 menit.');
      });
    }
  }, 5000);

  // Clean up on hot reload / unload
  nuxtApp.hook('app:unmounted', () => {
    events.forEach((event) => {
      window.removeEventListener(event, resetTimer);
    });
    clearInterval(interval);
  });
});
