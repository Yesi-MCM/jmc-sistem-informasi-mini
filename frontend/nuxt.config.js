// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // SPA mode: disable SSR entirely.
  // This admin app relies heavily on localStorage (auth, theme, session) which
  // is not available during Server-Side Rendering, causing hydration mismatches.
  // As an internal tool, SEO is not a concern, so SPA mode is the right choice.
  ssr: false,

  future: {
    compatibilityVersion: 4,
  },


  compatibilityDate: "2024-11-01",

  devtools: { enabled: true },

  runtimeConfig: {
    public: {
      appName: process.env.APP_NAME,
      appClient: process.env.APP_CLIENT,
      recaptchaSiteKey: process.env.NUXT_PUBLIC_RECAPTCHA_SITE_KEY,
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
    },
  },

  css: [
    "@tabler/core/dist/css/tabler.min.css",
    // '@tabler/core/dist/css/tabler-icons.min.css',
    // "~/assets/css/main.css",
    "~/assets/css/backend.css",
  ],

  app: {
    head: {
      charset: "utf-8",
      viewport: "width=device-width, initial-scale=1",
      link: [{ rel: "icon", type: "image/x-icon", href: "/favicon.png" }],
      script: [
        {
          src: "https://www.google.com/recaptcha/api.js",
          async: true,
          defer: true,
        },
      ],
    },
  },

  plugins: [
    "~/plugins/jquery.client.js",
    "~/plugins/tabler.client.js",
    "~/plugins/apexcharts.client.js",
    "~/plugins/inactivity.client.js",
  ],

  vite: {
    optimizeDeps: {
      include: ["apexcharts"],
    },
  },
});
