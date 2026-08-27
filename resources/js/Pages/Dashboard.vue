<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentReports: Array,
});

const activeTab = ref('overview');

const tabs = [
    { id: 'overview', label: '📊 Ringkasan Data' },
    { id: 'reports', label: '📋 Laporan Terbaru' },
];

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AppLayout title="Dashboard Monitoring" :user="user">
        <Head>
            <title>Dashboard Monitoring - Kebun Tebu MVP</title>
        </Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-white tracking-tight">Dashboard Monitoring</h1>
                    <p class="text-slate-400 text-sm mt-1">Ringkasan status kejadian & statistik spasial perkebunan tebu</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/reports/export/csv" download class="btn btn-secondary text-xs px-4 py-2.5">
                        📥 Download CSV
                    </a>
                    <a href="/reports/export/geojson" download class="btn btn-outline text-xs px-4 py-2.5">
                        🌐 Export GeoJSON
                    </a>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-8 border-b border-slate-800">
                <nav class="flex gap-4" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-5 py-3 text-sm font-bold border-b-2 transition-all duration-200',
                            activeTab === tab.id
                                ? 'border-emerald-400 text-emerald-400 bg-emerald-950/20'
                                : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-8">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Total -->
                    <div class="glass-card-hover p-6 border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Laporan</p>
                                <p class="font-display text-3xl font-extrabold text-white mt-2">{{ formatNumber(stats.total_reports) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-glow-emerald">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Open -->
                    <div class="glass-card-hover p-6 border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-rose-400">Status Open</p>
                                <p class="font-display text-3xl font-extrabold text-rose-400 mt-2">{{ formatNumber(stats.open_reports) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 shadow-glow-rose">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- On Progress -->
                    <div class="glass-card-hover p-6 border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">On Progress</p>
                                <p class="font-display text-3xl font-extrabold text-amber-400 mt-2">{{ formatNumber(stats.progress_reports) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-glow-amber">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Closed -->
                    <div class="glass-card-hover p-6 border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Selesai (Closed)</p>
                                <p class="font-display text-3xl font-extrabold text-emerald-400 mt-2">{{ formatNumber(stats.closed_reports) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-glow-emerald">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="glass-card overflow-hidden">
                    <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                        <h2 class="font-display text-lg font-bold text-white">Statistik Kategori Kejadian</h2>
                        <span class="text-xs font-semibold text-slate-400">Distribusi per Jenis Risiko</span>
                    </div>
                    <div class="divide-y divide-slate-800/60">
                        <div v-for="cat in stats.by_category || []" :key="cat.category_id" class="p-5 flex items-center justify-between hover:bg-slate-800/40 transition-colors">
                            <div class="flex items-center gap-3.5">
                                <span class="w-4 h-4 rounded-full shadow-md" :style="{ backgroundColor: cat.color_code }"></span>
                                <span class="font-bold text-slate-200 text-sm">{{ cat.name }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-32 hidden sm:block bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" :style="{ backgroundColor: cat.color_code, width: Math.min(100, (cat.count / (stats.total_reports || 1)) * 100) + '%' }"></div>
                                </div>
                                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700/80">
                                    {{ formatNumber(cat.count) }} Laporan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div v-if="activeTab === 'reports'" class="glass-card overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h2 class="font-display text-lg font-bold text-white">10 Laporan Terkini</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/90 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-800">
                                <th class="p-4 font-bold">Judul & Deskripsi</th>
                                <th class="p-4 font-bold">Kategori</th>
                                <th class="p-4 font-bold">Blok</th>
                                <th class="p-4 font-bold">Pelapor</th>
                                <th class="p-4 font-bold">Waktu</th>
                                <th class="p-4 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-sm">
                            <tr v-for="report in recentReports" :key="report.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-white">{{ report.title }}</div>
                                    <div class="text-xs text-slate-400 truncate max-w-xs mt-0.5">{{ report.description || 'Tidak ada deskripsi' }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-slate-800 text-xs font-semibold border border-slate-700">
                                        <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: report.category?.color_code }"></span>
                                        {{ report.category?.name || '-' }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-300 font-semibold">{{ report.block_code || report.block?.code || '-' }}</td>
                                <td class="p-4 text-slate-300">{{ report.user?.name || '-' }}</td>
                                <td class="p-4 text-xs text-slate-400">{{ formatDate(report.reported_at) }}</td>
                                <td class="p-4">
                                    <span :class="[
                                        'badge',
                                        report.status === 'OPEN' ? 'badge-open' :
                                        report.status === 'ON_PROGRESS' ? 'badge-progress' :
                                        'badge-closed'
                                    ]">
                                        {{ report.status === 'OPEN' ? 'OPEN' : report.status === 'ON_PROGRESS' ? 'PROGRESS' : 'CLOSED' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>