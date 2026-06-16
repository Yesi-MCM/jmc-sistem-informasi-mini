export const useTheme = () => {
  // useState initializes only once per SSR context.
  // During SSR, window is undefined so we always default to "light".
  // During client hydration, we read from localStorage immediately so the
  // initial value matches what will be rendered — preventing hydration mismatch.
  const theme = useState("theme", () => {
    if (typeof window === "undefined") return "light";
    return localStorage.getItem("theme") || "light";
  });

  const initTheme = () => {
    if (typeof window === "undefined") return;
    const saved = localStorage.getItem("theme") || "light";
    theme.value = saved;
    document.documentElement.setAttribute("data-bs-theme", saved);
    document.body.setAttribute("data-bs-theme", saved);
  };

  const toggleTheme = () => {
    const next = theme.value === "dark" ? "light" : "dark";
    theme.value = next;
    document.documentElement.setAttribute("data-bs-theme", next);
    document.body.setAttribute("data-bs-theme", next);
    localStorage.setItem("theme", next);
  };

  const isDark = computed(() => theme.value === "dark");

  return { theme, isDark, initTheme, toggleTheme };
};
