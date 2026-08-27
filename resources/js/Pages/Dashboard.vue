<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentReports: Array,
    chartData: Object,
});

const activeTab = ref('overview');
const dateRange = ref('week');

const tabs = [
    { id: 'overview', label: 'Ringkasan' },
    { id: 'reports', label: 'Laporan Terbaru' },
    { id: 'charts', label: 'Grafik' },
];

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout :title="__('Dashboard')" :user="user">
        <Head>
            <title>Dashboard - Kebun Tebu</title>
        </Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600 mt-1">Ringkasan monitoring kejadian perkebunan tebu</p>
            </div>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex gap-4" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                            activeTab === tab.id
                                ? 'border-primary-600 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Laporan</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(stats.total_reports || 0) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Status Open</p>
                                <p class="text-3xl font-bold text-red-600 mt-1">{{ formatNumber(stats.open_reports || 0) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">On Progress</p>
                                <p class="text-3xl font-bold text-yellow-600 mt-1">{{ formatNumber(stats.progress_reports || 0) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Selesai</p>
                                <p class="text-3xl font-bold text-green-600 mt-1">{{ formatNumber(stats.closed_reports || 0) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="card">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">Statistik per Kategori</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="(cat, index) in stats.by_category || []" :key="cat.category_id" class="p-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color_code }"></span>
                                <span class="font-medium text-gray-900">{{ cat.name }}</span>
                            </div>
                            <span class="text-sm text-gray-600">{{ formatNumber(cat.count) }} laporan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div v-if="activeTab === 'reports'" class="card">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Laporan Terbaru</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Laporan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blok</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelapor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="report in recentReports" :key="report.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ report.title }}</div>
                                    <div class="text-sm text-gray-500 truncate max-w-xs">{{ report.description }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: report.category.color_code }"></span>
                                        {{ report.category.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ report.block_code || report.block?.code || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ report.user?.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(report.reported_at) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'badge',
                                        report.status === 'OPEN' ? 'badge-open' :
                                        report.status === 'ON_PROGRESS' ? 'badge-progress' :
                                        'badge-closed'
                                    ]">
                                        {{ report.status === 'OPEN' ? 'Open' : report.status === 'ON_PROGRESS' ? 'On Progress' : 'Closed' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Charts Tab -->
            <div v-if="activeTab === 'charts'" class="grid md:grid-cols-2 gap-6">
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Trend 7 Hari Terakhir</h2>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Status</h2>
                    <div class="h-64">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>