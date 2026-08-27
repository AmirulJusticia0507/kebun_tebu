<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    title: String,
    user: Object,
});

const unreadCount = ref(0);
const isOffline = ref(typeof window !== 'undefined' ? !navigator.onLine : false);
const isDark = ref(true);

const updateOnlineStatus = () => {
    isOffline.value = typeof window !== 'undefined' ? !navigator.onLine : false;
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (typeof window !== 'undefined') {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('theme', 'light');
        }
    }
};

const initTheme = () => {
    if (typeof window !== 'undefined') {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        } else {
            isDark.value = true;
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        }
    }
};

const fetchUnreadCount = async () => {
    if (props.user) {
        try {
            const res = await axios.get('/notifications');
            unreadCount.value = res.data.unread_count || 0;
        } catch (e) {
            // Ignore error if unauthenticated
        }
    }
};

onMounted(() => {
    initTheme();
    fetchUnreadCount();
    if (typeof window !== 'undefined') {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    }
});

const confirmLogout = () => {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari sistem Kebun Tebu MVP?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
        customClass: {
            popup: 'border border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl bg-slate-900/95',
            title: 'text-slate-100 font-bold',
            confirmButton: 'px-4 py-2 rounded-xl text-sm font-semibold shadow-lg shadow-emerald-950/50',
            cancelButton: 'px-4 py-2 rounded-xl text-sm font-semibold',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post('/logout').then(() => {
                window.location.href = '/';
            });
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans bg-mesh-gradient bg-fixed antialiased transition-colors duration-300">
        <Head>
            <title>{{ title ? title + ' - Kebun Tebu MVP' : 'Kebun Tebu MVP' }}</title>
        </Head>

        <!-- Offline Banner -->
        <div v-if="isOffline" class="offline-indicator" role="alert">
            <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm">Mode Offline Aktif. Draft tersimpan secara lokal.</span>
        </div>

        <!-- Main Navigation Bar -->
        <header v-if="user" class="glass-nav border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Brand & Links -->
                    <div class="flex items-center gap-8">
                        <Link href="/map" class="flex items-center gap-3 group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-950/40 group-hover:scale-105 transition-transform duration-200">
                                <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h1.5a2.5 2.5 0 002.5-2.5V7.435M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-display text-lg font-bold bg-gradient-to-r from-emerald-400 via-teal-200 to-white bg-clip-text text-transparent">Kebun Tebu</span>
                                <span class="text-[10px] font-semibold text-emerald-500 tracking-wider uppercase">GIS Monitoring</span>
                            </div>
                        </Link>
                        
                        <nav class="hidden md:flex items-center gap-1">
                            <Link href="/map" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200" :class="$page.url.startsWith('/map') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'">
                                🗺️ Peta Monitoring
                            </Link>
                            <Link href="/reports/create" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200" :class="$page.url.startsWith('/reports/create') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'">
                                ➕ Buat Laporan
                            </Link>
                            <Link v-if="user.role === 'admin'" href="/dashboard" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200" :class="$page.url.startsWith('/dashboard') && !$page.url.startsWith('/dashboard/users') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'">
                                📊 Dashboard
                            </Link>
                            <Link v-if="user.role === 'admin'" href="/dashboard/users" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-200" :class="$page.url.startsWith('/dashboard/users') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'">
                                👥 Kelola Pengguna
                            </Link>
                        </nav>
                    </div>

                    <!-- Right Controls & Profile -->
                    <div class="flex items-center gap-3 sm:gap-4">
                        <!-- Adaptive Dark / Light Mode Toggle Button -->
                        <button @click="toggleTheme" class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/80 transition-all duration-200 flex items-center justify-center border border-slate-700/60 bg-slate-900/60 shadow-sm" :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'">
                            <!-- Sun icon for Dark Mode -->
                            <svg v-if="isDark" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon icon for Light Mode -->
                            <svg v-else class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- Notification Badge -->
                        <div class="relative">
                            <button @click="fetchUnreadCount" class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/80 transition-colors border border-slate-700/60 bg-slate-900/60 relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center animate-pulse shadow-md shadow-rose-900">
                                    {{ unreadCount }}
                                </span>
                            </button>
                        </div>

                        <!-- User Profile Dropdown & SweetAlert Logout -->
                        <div class="relative flex items-center gap-3">
                            <div class="hidden sm:flex flex-col items-end">
                                <span class="text-sm font-bold text-slate-200">{{ user.name }}</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase" :class="user.role === 'admin' ? 'bg-purple-950/80 text-purple-400 border border-purple-800/50' : 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50'">
                                    {{ user.role === 'admin' ? 'Admin Kebun' : 'Petugas' }}
                                </span>
                            </div>

                            <button @click="confirmLogout" class="btn btn-secondary text-xs py-2 px-3 hover:border-rose-500/50 hover:text-rose-400">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Bottom Nav -->
        <div v-if="user" class="md:hidden glass-nav border-t border-slate-800 fixed bottom-0 left-0 right-0 z-50 py-1.5 px-4">
            <div class="flex justify-around items-center">
                <Link href="/map" class="flex flex-col items-center gap-1 p-2 rounded-xl transition-colors" :class="$page.url.startsWith('/map') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span class="text-[10px]">Peta</span>
                </Link>
                <Link href="/reports/create" class="flex flex-col items-center gap-1 p-2 rounded-xl transition-colors" :class="$page.url.startsWith('/reports/create') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[10px]">Lapor</span>
                </Link>
                <Link v-if="user.role === 'admin'" href="/dashboard" class="flex flex-col items-center gap-1 p-2 rounded-xl transition-colors" :class="$page.url.startsWith('/dashboard') && !$page.url.startsWith('/dashboard/users') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="text-[10px]">Dashboard</span>
                </Link>
                <Link v-if="user.role === 'admin'" href="/dashboard/users" class="flex flex-col items-center gap-1 p-2 rounded-xl transition-colors" :class="$page.url.startsWith('/dashboard/users') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="text-[10px]">Users</span>
                </Link>
            </div>
        </div>

        <main class="pb-24 md:pb-6">
            <slot />
        </main>
    </div>
</template>