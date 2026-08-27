<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <AppLayout :title="__('Welcome')" :user="null">
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
            <div class="max-w-4xl w-full">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-100 mb-6">
                        <svg class="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">Kebun Tebu</h1>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Peta Monitoring Kejadian Lapangan untuk Perkebunan Tebu.
                        Laporkan kebakaran, hama, penyakit, banjir, dan kendala lainnya secara real-time.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 mb-12">
                    <div class="card p-6 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Peta Interaktif</h3>
                        <p class="text-gray-600">Visualisasi kejadian dengan clustering, heatmap, dan filter spasial</p>
                    </div>

                    <div class="card p-6 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Laporan Mudah</h3>
                        <p class="text-gray-600">Form mobile-friendly dengan GPS otomatis, foto, dan checklist dinamis</p>
                    </div>

                    <div class="card p-6 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Offline-First</h3>
                        <p class="text-gray-600">PWA dengan IndexedDB & Background Sync untuk area tanpa sinyal</p>
                    </div>
                </div>

                <div class="card p-8">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link v-if="props.canLogin" href="/login" class="btn-primary px-8 py-3 text-lg">
                            Masuk
                        </Link>
                        <Link v-if="props.canRegister" href="/register" class="btn-secondary px-8 py-3 text-lg">
                            Daftar
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>