<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <AppLayout title="Selamat Datang" :user="null">
        <div class="relative min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-16 overflow-hidden">
            <!-- Background Radial Glows -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-[140px] pointer-events-none"></div>
            <div class="absolute bottom-10 right-10 w-[400px] h-[400px] bg-teal-500/10 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="relative max-w-5xl w-full mx-auto">
                <!-- Hero Badge -->
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-950/80 border border-emerald-500/30 text-emerald-400 text-xs font-semibold uppercase tracking-widest shadow-glow-emerald mb-6">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>Sistem Monitoring Spasial Lapangan MVP</span>
                    </div>

                    <!-- Title & Subtitle -->
                    <h1 class="font-display text-4xl sm:text-6xl font-extrabold text-white tracking-tight mb-6 leading-tight">
                        Monitoring Perkebunan Tebu <br class="hidden sm:inline" />
                        <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
                            Interaktif & Offline-First
                        </span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed">
                        Platform GIS modern untuk mengelola laporan kejadian kebakaran, hama, banjir, dan kendala blok kebun tebu secara real-time maupun offline di area minim jaringan.
                    </p>
                </div>

                <!-- Feature Grid -->
                <div class="grid md:grid-cols-3 gap-6 mb-12">
                    <div class="glass-card-hover p-8 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-emerald-500/20 to-teal-500/10 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white mb-2">Peta Spasial GIS</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Visualisasi layer polygon blok kebun, heatmap kejadian, cluster titik laporan, serta batas wilayah interaktif.
                        </p>
                    </div>

                    <div class="glass-card-hover p-8 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-teal-500/20 to-cyan-500/10 rounded-2xl flex items-center justify-center mb-6 border border-teal-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white mb-2">PWA Offline-First</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Form laporan tetap berjalan tanpa jaringan internet. Simpan draft di IndexedDB dan sinkronkan otomatis saat online.
                        </p>
                    </div>

                    <div class="glass-card-hover p-8 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-amber-500/20 to-orange-500/10 rounded-2xl flex items-center justify-center mb-6 border border-amber-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white mb-2">Otomasi & SLA</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Pengawasan estimasi tenggat SLA jam penanganan, notifikasi real-time, dan ekspor data CSV & GeoJSON cepat.
                        </p>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="glass-card p-10 text-center border-slate-800/80 shadow-2xl relative overflow-hidden">
                    <div class="relative z-10 max-w-xl mx-auto">
                        <h2 class="font-display text-2xl font-bold text-white mb-3">Mulai Gunakan Kebun Tebu MVP</h2>
                        <p class="text-slate-400 text-sm mb-8">Masuk menggunakan akun petugas atau admin untuk mengakses peta dan form laporan.</p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link href="/login" class="btn-primary text-base px-8 py-3 shadow-glow-emerald">
                                🔑 Masuk ke Aplikasi
                            </Link>
                            <Link v-if="props.canRegister" href="/register" class="btn-secondary text-base px-8 py-3">
                                📝 Registrasi Akun Baru
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>