<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    categories: Array,
});

const showModal = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name: '',
    icon_marker: '',
    color_code: '#6b7280',
    sla_hours: 24,
    checklist_template: [],
});

const openCreate = () => {
    editingCategory.value = null;
    form.reset();
    form.color_code = '#6b7280';
    form.sla_hours = 24;
    showModal.value = true;
};

const openEdit = (cat) => {
    editingCategory.value = cat;
    form.name = cat.name;
    form.icon_marker = cat.icon_marker || '';
    form.color_code = cat.color_code;
    form.sla_hours = cat.sla_hours || 24;
    form.checklist_template = cat.checklist_template || [];
    showModal.value = true;
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm(`Hapus kategori "${cat.name}"?`)) {
        router.delete(route('admin.categories.destroy', cat.id));
    }
};
</script>

<template>
    <AppLayout title="Kategori Kejadian" :user="user">
        <Head><title>Kategori Kejadian - Kebun Tebu</title></Head>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Kategori Kejadian</h1>
                    <p class="text-gray-600 mt-1">Kelola jenis kategori dan SLA</p>
                </div>
                <button @click="openCreate" class="btn-primary">+ Tambah Kategori</button>
            </div>

            <div class="card overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warna</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SLA (Jam)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jml Laporan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ cat.name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full border" :style="{ backgroundColor: cat.color_code }"></span>
                                    <span class="font-mono text-xs text-gray-500">{{ cat.color_code }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ cat.sla_hours || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ cat.reports_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button @click="openEdit(cat)" class="text-sm text-blue-600 hover:underline">Edit</button>
                                    <button @click="deleteCategory(cat)" class="text-sm text-red-600 hover:underline">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada kategori</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="card w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="space-y-3">
                    <div><label class="label">Nama Kategori</label><input type="text" v-model="form.name" class="input" required /></div>
                    <div>
                        <label class="label">Warna</label>
                        <div class="flex gap-2 items-center">
                            <input type="color" v-model="form.color_code" class="h-10 w-16 rounded cursor-pointer border border-gray-300" />
                            <input type="text" v-model="form.color_code" class="input flex-1 font-mono" placeholder="#ef4444" />
                        </div>
                    </div>
                    <div><label class="label">SLA (jam)</label><input type="number" v-model="form.sla_hours" class="input" min="1" /></div>
                    <div><label class="label">Icon Marker (opsional)</label><input type="text" v-model="form.icon_marker" class="input" placeholder="fire.png" /></div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" :disabled="form.processing" class="btn-primary flex-1">Simpan</button>
                        <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
