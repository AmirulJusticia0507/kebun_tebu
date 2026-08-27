<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    report: Object,
});

const statusForm = useForm({
    status: props.report.status,
    admin_note: props.report.admin_note || '',
});

const updateStatus = () => {
    statusForm.patch(route('reports.status', props.report.id));
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const statusLabel = (s) => s === 'OPEN' ? 'Open' : s === 'ON_PROGRESS' ? 'On Progress' : 'Closed';
const statusClass = (s) => s === 'OPEN' ? 'badge-open' : s === 'ON_PROGRESS' ? 'badge-progress' : 'badge-closed';
</script>

<template>
    <AppLayout title="Detail Laporan" :user="user">
        <Head>
            <title>Detail Laporan #{{ report.id }} - Kebun Tebu</title>
        </Head>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back -->
            <div class="mb-4">
                <Link :href="route('map')" class="text-sm text-primary-600 hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Peta
                </Link>
            </div>

            <div class="card">
                <!-- Header -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ report.title }}</h1>
                            <p class="text-sm text-gray-500 mt-1">Laporan #{{ report.id }} · {{ formatDate(report.reported_at) }}</p>
                        </div>
                        <span :class="['badge', statusClass(report.status)]">
                            {{ statusLabel(report.status) }}
                        </span>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-6 grid sm:grid-cols-2 gap-4 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Kategori</p>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: report.category?.color_code }"></span>
                            <span class="font-medium">{{ report.category?.name }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Blok</p>
                        <span class="font-medium">{{ report.block_code || report.block?.code || '-' }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Pelapor</p>
                        <span class="font-medium">{{ report.user?.name }}</span>
                        <span class="text-xs text-gray-500 ml-1">({{ report.user?.phone_number || '-' }})</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Koordinat</p>
                        <span class="font-mono text-sm">{{ report.latitude }}, {{ report.longitude }}</span>
                    </div>
                    <div v-if="report.sla_deadline">
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Deadline SLA</p>
                        <span :class="['font-medium', report.is_overdue ? 'text-red-600' : 'text-gray-900']">
                            {{ formatDate(report.sla_deadline) }}
                        </span>
                    </div>
                    <div v-if="report.handled_by">
                        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Ditangani Oleh</p>
                        <span class="font-medium">{{ report.handler?.name }}</span>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div v-if="report.description" class="p-6 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-2">Deskripsi</p>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ report.description }}</p>
                </div>

                <!-- Checklist -->
                <div v-if="report.checklist_answers && Object.keys(report.checklist_answers).length > 0" class="p-6 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-3">Checklist</p>
                    <div class="space-y-2">
                        <div v-for="(val, key) in report.checklist_answers" :key="key" class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ key }}</span>
                            <span class="font-medium">
                                {{ val === true ? '✅ Ya' : val === false ? '❌ Tidak' : val }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Foto -->
                <div v-if="report.photo_url" class="p-6 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-3">Foto Bukti</p>
                    <a :href="report.photo_url" target="_blank">
                        <img :src="report.photo_url" alt="Foto bukti" class="w-full max-h-96 object-cover rounded-lg hover:opacity-90 transition-opacity" />
                    </a>
                </div>

                <!-- Catatan Admin -->
                <div v-if="report.admin_note" class="p-6 border-b border-gray-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-2">Catatan Admin</p>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ report.admin_note }}</p>
                </div>

                <!-- Update Status (Admin) -->
                <div v-if="user?.role === 'admin'" class="p-6">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-4">Update Status</p>
                    <form @submit.prevent="updateStatus" class="space-y-3">
                        <div>
                            <label class="label">Status Penanganan</label>
                            <select v-model="statusForm.status" class="input">
                                <option value="OPEN">🔴 Open</option>
                                <option value="ON_PROGRESS">🟡 On Progress</option>
                                <option value="CLOSED">🟢 Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Catatan (opsional)</label>
                            <textarea v-model="statusForm.admin_note" class="input h-20" rows="3" placeholder="Tulis catatan tindak lanjut..."></textarea>
                        </div>
                        <button type="submit" :disabled="statusForm.processing" class="btn-primary w-full">
                            {{ statusForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
