<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import CookieConsent from "@/Components/CookieConsent.vue";

const props = defineProps({
  title: String,
  user: Object,
});

const unreadCount = ref(0);
const notifications = ref([]);
const showNotifications = ref(false);
const mobileMenuOpen = ref(false);
const isOffline = ref(
  typeof window !== "undefined" ? !navigator.onLine : false,
);
const isDark = ref(true);
const pushEnabled = ref(false);
let removeNavigationListener;

const updateOnlineStatus = () => {
  isOffline.value = typeof window !== "undefined" ? !navigator.onLine : false;
};

const toggleTheme = () => {
  isDark.value = !isDark.value;
  if (typeof window !== "undefined") {
    if (isDark.value) {
      document.documentElement.classList.add("dark");
      document.documentElement.classList.remove("light");
      localStorage.setItem("theme", "dark");
    } else {
      document.documentElement.classList.remove("dark");
      document.documentElement.classList.add("light");
      localStorage.setItem("theme", "light");
    }
  }
};

const initTheme = () => {
  if (typeof window !== "undefined") {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
      isDark.value = false;
      document.documentElement.classList.remove("dark");
      document.documentElement.classList.add("light");
    } else {
      isDark.value = true;
      document.documentElement.classList.add("dark");
      document.documentElement.classList.remove("light");
    }
  }
};

const fetchUnreadCount = async () => {
  if (props.user) {
    try {
      const res = await axios.get("/notifications");
      unreadCount.value = res.data.unread_count || 0;
      notifications.value = res.data.notifications || [];
    } catch (e) {
      // Ignore error if unauthenticated
    }
  }
};

const enablePush = async () => {
  if (
    !("serviceWorker" in navigator) ||
    !("PushManager" in window) ||
    Notification.permission === "denied"
  )
    return;

  const permission =
    Notification.permission === "granted"
      ? "granted"
      : await Notification.requestPermission();
  if (permission !== "granted") return;

  const registration = await navigator.serviceWorker.ready;
  let subscription = await registration.pushManager.getSubscription();
  if (!subscription) {
    const { data } = await axios.get("/push/key");
    if (!data.public_key) return;
    const padding = "=".repeat((4 - (data.public_key.length % 4)) % 4);
    const base64 = (data.public_key + padding)
      .replace(/-/g, "+")
      .replace(/_/g, "/");
    const applicationServerKey = Uint8Array.from(atob(base64), (char) =>
      char.charCodeAt(0),
    );
    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey,
    });
  }

  const payload = subscription.toJSON();
  payload.contentEncoding = (PushManager.supportedContentEncodings || [
    "aes128gcm",
  ])[0];
  await axios.post("/push/subscriptions", payload);
  pushEnabled.value = true;
};

const refreshNotifications = async () => {
  await fetchUnreadCount();
  showNotifications.value = !showNotifications.value;
  await enablePush();
};

const openNotification = async (notification) => {
  if (!notification.read_at)
    await axios.post(`/notifications/${notification.id}/read`);
  window.location.href = notification.data?.url || "/reports";
};

const markAllNotificationsRead = async () => {
  await axios.post("/notifications/read-all");
  notifications.value = notifications.value.map((notification) => ({
    ...notification,
    read_at: notification.read_at || new Date().toISOString(),
  }));
  unreadCount.value = 0;
};

onMounted(() => {
  initTheme();
  fetchUnreadCount();
  navigator.serviceWorker?.ready
    .then((registration) => registration.pushManager?.getSubscription())
    .then((subscription) => {
      pushEnabled.value = Boolean(subscription);
    });
  if (typeof window !== "undefined") {
    window.addEventListener("online", updateOnlineStatus);
    window.addEventListener("offline", updateOnlineStatus);
    removeNavigationListener = router.on("finish", () => {
      mobileMenuOpen.value = false;
      showNotifications.value = false;
      window.scrollTo({ top: 0, left: 0, behavior: "auto" });
    });
  }
});

onUnmounted(() => {
  if (typeof window !== "undefined") {
    window.removeEventListener("online", updateOnlineStatus);
    window.removeEventListener("offline", updateOnlineStatus);
    removeNavigationListener?.();
  }
});

const confirmLogout = () => {
  Swal.fire({
    title: "Konfirmasi Keluar",
    text: "Apakah Anda yakin ingin keluar dari sistem Kebun Tebu MVP?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#10b981",
    cancelButtonColor: "#64748b",
    confirmButtonText: "Ya, Keluar",
    cancelButtonText: "Batal",
    background: "#0f172a",
    color: "#f8fafc",
    customClass: {
      popup:
        "border border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl bg-slate-900/95",
      title: "text-slate-100 font-bold",
      confirmButton:
        "px-4 py-2 rounded-xl text-sm font-semibold shadow-lg shadow-emerald-950/50",
      cancelButton: "px-4 py-2 rounded-xl text-sm font-semibold",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      axios.post("/logout").then(async () => {
        if ("caches" in window) await caches.delete("app-pages-cache");
        window.location.href = "/";
      });
    }
  });
};
</script>

<template>
  <div
    class="min-h-screen bg-slate-950 text-slate-100 font-sans bg-mesh-gradient bg-fixed antialiased transition-colors duration-300"
  >
    <Head>
      <title>
        {{ title ? title + " - Kebun Tebu MVP" : "Kebun Tebu MVP" }}
      </title>
    </Head>

    <!-- Cookie Consent Banner -->
    <CookieConsent />

    <!-- Offline Banner -->
    <div v-if="isOffline" class="offline-indicator" role="alert">
      <svg
        class="w-6 h-6 text-slate-950"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <span class="text-sm"
        >Mode Offline Aktif. Draft tersimpan secara lokal.</span
      >
    </div>

    <!-- Main Navigation Bar -->
    <header
      v-if="user"
      class="glass-nav relative z-50 border-b border-slate-800/80"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Brand & Links -->
          <div class="flex min-w-0 items-center gap-3 md:gap-8">
            <Link
              href="/map"
              class="flex shrink-0 items-center gap-2 sm:gap-3 group"
            >
              <div
                class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-950/40 group-hover:scale-105 transition-transform duration-200"
              >
                <div
                  class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center overflow-hidden"
                >
                  <img
                    src="/logo-kebun-tebu.png"
                    alt="Kebun Tebu Logo"
                    class="w-8 h-8 object-contain"
                  />
                </div>
              </div>
              <div class="hidden min-[360px]:flex flex-col leading-tight">
                <span
                  class="whitespace-nowrap font-display text-lg font-bold bg-gradient-to-r from-emerald-400 via-teal-200 to-white bg-clip-text text-transparent"
                  >Kebun Tebu</span
                >
                <span
                  class="whitespace-nowrap text-[10px] font-semibold text-emerald-500 tracking-wider uppercase"
                  >GIS Monitoring</span
                >
              </div>
            </Link>

            <nav class="hidden lg:flex items-center gap-1">
              <Link
                href="/map"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url.startsWith('/map')
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                🗺️ Peta Monitoring
              </Link>
              <Link
                href="/reports/create"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url.startsWith('/reports/create')
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                ➕ Buat Laporan
              </Link>
              <Link
                href="/reports"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url === '/reports' ||
                  ($page.url.startsWith('/reports/') &&
                    !$page.url.startsWith('/reports/create'))
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                📋 Riwayat Laporan
              </Link>
              <Link
                v-if="user.role === 'admin'"
                href="/dashboard"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url === '/dashboard'
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                📊 Dashboard
              </Link>
              <Link
                v-if="user.role === 'admin'"
                href="/dashboard/users"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url.startsWith('/dashboard/users')
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                👥 Kelola Pengguna
              </Link>
              <Link
                v-if="user.role === 'admin'"
                href="/dashboard/blocks"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="
                  $page.url.startsWith('/dashboard/blocks')
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                "
              >
                Blok Kebun
              </Link>
            </nav>
          </div>

          <!-- Right Controls & Profile -->
          <div class="flex shrink-0 items-center gap-1.5 sm:gap-4">
            <!-- Adaptive Dark / Light Mode Toggle Button -->
            <button
              @click="toggleTheme"
              class="hidden lg:flex p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/80 transition-all duration-200 items-center justify-center border border-slate-700/60 bg-slate-900/60 shadow-sm"
              :title="
                isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'
              "
            >
              <!-- Sun icon for Dark Mode -->
              <svg
                v-if="isDark"
                class="w-5 h-5 text-amber-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
              <!-- Moon icon for Light Mode -->
              <svg
                v-else
                class="w-5 h-5 text-indigo-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                />
              </svg>
            </button>

            <!-- Notification Badge -->
            <div class="relative">
              <button
                @click="refreshNotifications"
                :title="
                  pushEnabled ? 'Notifikasi aktif' : 'Aktifkan notifikasi'
                "
                class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/80 transition-colors border border-slate-700/60 bg-slate-900/60 relative"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                  />
                </svg>
                <span
                  v-if="unreadCount > 0"
                  class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center animate-pulse shadow-md shadow-rose-900"
                >
                  {{ unreadCount }}
                </span>
              </button>
              <div
                v-if="showNotifications"
                class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
              >
                <div
                  class="flex items-center justify-between border-b border-slate-800 px-4 py-3"
                >
                  <span class="text-sm font-bold text-slate-100"
                    >Notifikasi</span
                  >
                  <button
                    v-if="unreadCount"
                    @click="markAllNotificationsRead"
                    class="text-xs text-emerald-400 hover:text-emerald-300"
                  >
                    Tandai semua dibaca
                  </button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                  <button
                    v-for="notification in notifications"
                    :key="notification.id"
                    @click="openNotification(notification)"
                    class="block w-full border-b border-slate-800 px-4 py-3 text-left hover:bg-slate-800/70"
                    :class="
                      notification.read_at ? 'opacity-70' : 'bg-emerald-950/20'
                    "
                  >
                    <span class="block text-sm font-semibold text-slate-100">{{
                      notification.data?.title || "Notifikasi"
                    }}</span>
                    <span class="mt-1 block text-xs text-slate-400">{{
                      notification.data?.message
                    }}</span>
                  </button>
                  <p
                    v-if="!notifications.length"
                    class="px-4 py-8 text-center text-sm text-slate-500"
                  >
                    Belum ada notifikasi.
                  </p>
                </div>
              </div>
            </div>

            <!-- User Profile Dropdown & SweetAlert Logout -->
            <div class="relative hidden lg:flex items-center gap-3">
              <div class="hidden sm:flex flex-col items-end">
                <span class="text-sm font-bold text-slate-200">{{
                  user.name
                }}</span>
                <span
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase"
                  :class="
                    user.role === 'admin'
                      ? 'bg-purple-950/80 text-purple-400 border border-purple-800/50'
                      : 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50'
                  "
                >
                  {{ user.role === "admin" ? "Admin Kebun" : "Petugas" }}
                </span>
              </div>

              <button
                @click="confirmLogout"
                class="btn btn-secondary text-xs py-2 px-2 sm:px-3 hover:border-rose-500/50 hover:text-rose-400"
              >
                <svg
                  class="w-4 h-4 sm:mr-1.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  />
                </svg>
                <span class="hidden sm:inline">Keluar</span>
              </button>
            </div>

            <button
              type="button"
              class="lg:hidden flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700/60 bg-slate-900/60 text-slate-200 transition hover:border-emerald-500/50 hover:text-emerald-400"
              :aria-expanded="mobileMenuOpen"
              aria-controls="mobile-navigation"
              :aria-label="mobileMenuOpen ? 'Tutup menu' : 'Buka menu'"
              @click="mobileMenuOpen = !mobileMenuOpen"
            >
              <svg
                v-if="!mobileMenuOpen"
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
              <svg
                v-else
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div
        v-show="mobileMenuOpen"
        id="mobile-navigation"
        class="lg:hidden border-t border-slate-800 bg-slate-950/95 px-4 py-4 shadow-2xl backdrop-blur-xl"
      >
        <nav class="mx-auto grid max-w-7xl gap-1" aria-label="Navigasi mobile">
          <Link
            href="/map"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url.startsWith('/map')
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Peta Monitoring
          </Link>
          <Link
            href="/reports/create"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url.startsWith('/reports/create')
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Buat Laporan
          </Link>
          <Link
            href="/reports"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url === '/reports' ||
              ($page.url.startsWith('/reports/') &&
                !$page.url.startsWith('/reports/create'))
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Riwayat Laporan
          </Link>
          <Link
            v-if="user.role === 'admin'"
            href="/dashboard"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url === '/dashboard'
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Dashboard
          </Link>
          <Link
            v-if="user.role === 'admin'"
            href="/dashboard/users"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url.startsWith('/dashboard/users')
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Kelola Pengguna
          </Link>
          <Link
            v-if="user.role === 'admin'"
            href="/dashboard/blocks"
            class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors"
            :class="
              $page.url.startsWith('/dashboard/blocks')
                ? 'bg-emerald-500/10 text-emerald-400'
                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            "
          >
            Blok Kebun
          </Link>
          <div
            class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-800 pt-3"
          >
            <button
              type="button"
              class="rounded-xl border border-slate-700 px-4 py-3 text-sm font-semibold text-slate-300 hover:border-emerald-500/50 hover:text-white"
              @click="toggleTheme"
            >
              {{ isDark ? "Mode Terang" : "Mode Gelap" }}
            </button>
            <button
              type="button"
              class="rounded-xl border border-rose-900/70 px-4 py-3 text-sm font-semibold text-rose-400 hover:bg-rose-950/50"
              @click="confirmLogout"
            >
              Keluar
            </button>
          </div>
        </nav>
      </div>
    </header>

    <main class="pb-6">
      <slot />
    </main>
  </div>
</template>
