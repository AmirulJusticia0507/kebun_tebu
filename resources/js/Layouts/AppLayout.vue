<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/Stores/auth';

const props = defineProps({
    title: String,
    user: Object,
});

const authStore = useAuthStore();

const navigate = (url) => {
    window.location.href = url;
};

const logout = () => {
    axios.post(route('logout')).then(() => {
        navigate('/');
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head>
            <title>{{ title }}</title>
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <link rel="icon" href="/favicon.ico" />
            <link rel="manifest" href="/manifest.webmanifest" />
            <meta name="theme-color" content="#16a34a" />
        </Head>

        <!-- Offline Indicator -->
        <div v-if="!navigator.onLine" class="offline-indicator" role="alert">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Anda sedang offline. Data akan disinkronkan saat online kembali.</span>
        </div>

        <!-- Navigation for authenticated users -->
        <nav v-if="user" class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/map" class="flex items-center gap-2">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xl font-bold text-gray-900">Kebun Tebu</span>
                        </Link>
                        
                        <div class="hidden md:ml-8 md:flex md:space-x-4">
                            <Link href="/map" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Peta</Link>
                            <Link href="/reports/create" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Lapor</Link>
                            <Link v-if="user.is_admin" href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Dashboard</Link>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-3">
                            <span class="text-sm text-gray-600">{{ user.name }}</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                :class="user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                                {{ user.role === 'admin' ? 'Admin' : 'Petugas' }}
                            </span>
                        </div>
                        
                        <button @click="logout" class="btn btn-secondary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Keluar
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Navigation -->
        <nav v-if="user" class="md:hidden bg-white border-b border-gray-200 fixed bottom-0 left-0 right-0 z-50">
            <div class="flex justify-around py-2">
                <Link href="/map" class="flex flex-col items-center gap-1 text-gray-600 hover:text-primary-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                    <span class="text-xs">Peta</span>
                </Link>
                <Link href="/reports/create" class="flex flex-col items-center gap-1 text-gray-600 hover:text-primary-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs">Lapor</span>
                </Link>
                <Link v-if="user.is_admin" href="/dashboard" class="flex flex-col items-center gap-1 text-gray-600 hover:text-primary-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="text-xs">Dashboard</span>
                </Link>
            </div>
        </nav>

        <main class="pt-4 pb-20 md:pb-4">
            <slot />
        </main>
    </div>
</template>