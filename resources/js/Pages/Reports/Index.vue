<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    user: Object,
    reports: Object,   // paginated
    categories: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const selectedStatus = ref(props.filters?.status ?? '');
const selectedCategory = ref(props.filters?.category_id ?? '');

const applyFilters = debounce(() => {
    router.get(route('reports.index'), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        category_id: selectedCategory.value || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, selectedStatus, selectedCategory], applyFilters);

const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const statusBadge = (s) => {
    if (s === 'OPEN') return 'bg-rose-950/80 text-rose-400 border border-rose-800/50';
    if (s === 'ON_PROGRESS') return 'bg-amber-950/80 text-amber-400 border border-amber-800/50';
    return 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50';
};
const statusLabel = (s) => s === 'OPEN' ? '🔴 Open' : s === 'ON_PROGRESS' ? '🟡 On Progress' : '🟢 Closed';
</script>

<template>
    <AppLayout title="Riwayat Laporan" :user="user">
        <Head><title>Riwayat Laporan - Kebun Tebu</title></Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 glass-panel p-6 rounded-2xl border border-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-2">
                        📋 Riwayat Laporan
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ user.role === 'admin' ? 'Semua laporan dari seluruh petugas' : 'Laporan yang Anda kirimkan' }}
                    </p>
                </div>
                <Link :href="route('reports.create')" class="btn-primary flex items-center gap-2 self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    + Buat Laporan
                </Link>
            </div>

            <!-- Filters Bar -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-wrap gap-3 items-center">
                <div class="flex-1 min-w-[200px] relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Cari judul laporan..." class="input pl-9 text-sm" />
                </div>
                <select v-model="selectedStatus" class="input text-sm w-auto min-w-[140px]">
                    <option value="">Semua Status</option>
                    <option value="OPEN">🔴 Open</option>
                    <option value="ON_PROGRESS">🟡 On Progress</option>
                    <option value="CLOSED">🟢 Closed</option>
                </select>
                <select v-model="selectedCategory" class="input text-sm w-auto min-w-[160px]">
                    <option value="">Semua Kategori</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <span class="text-xs text-slate-500 ml-auto">
                    {{ reports.total }} laporan ditemukan
                </span>
            </div>

            <!-- Reports Table -->
            <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-900/80 border-b border-slate-800 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-4">#ID</th>
                                <th class="px-5 py-4">Judul Laporan</th>
                                <th class="px-5 py-4">Kategori</th>
                                <th class="px-5 py-4">Blok</th>
                                <th v-if="user.role === 'admin'" class="px-5 py-4">Pelapor</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">SLA</th>
                                <th class="px-5 py-4">Waktu</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="r in reports.data" :key="r.id" class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-400">#{{ r.id }}</td>
                                <td class="px-5 py-3.5 font-semibold text-slate-100 max-w-xs truncate">{{ r.title }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: r.category?.color_code }"></span>
                                        {{ r.category?.name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-300 font-mono text-xs">
                                    {{ r.block_code || r.block?.code || '-' }}
                                </td>
                                <td v-if="user.role === 'admin'" class="px-5 py-3.5 text-slate-300">
                                    {{ r.user?.name ?? '-' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="['px-2.5 py-1 rounded-full text-xs font-bold', statusBadge(r.status)]">
                                        {{ statusLabel(r.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="r.sla_deadline" class="text-xs" :class="r.is_overdue ? 'text-rose-400 font-semibold' : 'text-slate-400'">
                                        {{ r.is_overdue ? '⚠️ Overdue' : formatDate(r.sla_deadline) }}
                                    </span>
                                    <span v-else class="text-slate-600 text-xs">-</span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-400 whitespace-nowrap">
                                    {{ formatDate(r.reported_at) }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <Link :href="route('reports.show', r.id)" class="btn btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="reports.data.length === 0">
                                <td :colspan="user.role === 'admin' ? 9 : 8" class="px-6 py-16 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="text-4xl">📭</span>
                                        <p class="text-sm">Belum ada laporan yang ditemukan.</p>
                                        <Link :href="route('reports.create')" class="btn-primary text-xs py-2 px-4">
                                            Buat Laporan Pertama
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reports.last_page > 1" class="border-t border-slate-800 px-5 py-4 flex items-center justify-between text-sm text-slate-400">
                    <span>Halaman {{ reports.current_page }} dari {{ reports.last_page }} ({{ reports.total }} total)</span>
                    <div class="flex gap-2">
                        <Link v-if="reports.prev_page_url" :href="reports.prev_page_url" class="btn btn-secondary text-xs py-1.5 px-3">
                            ← Sebelumnya
                        </Link>
                        <Link v-if="reports.next_page_url" :href="reports.next_page_url" class="btn btn-secondary text-xs py-1.5 px-3">
                            Berikutnya →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
