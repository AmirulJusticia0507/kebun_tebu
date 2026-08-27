<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    blocks: Array,
});

const showModal = ref(false);
const editingBlock = ref(null);

const form = useForm({
    code: '',
    name: '',
    hectare: '',
    pic_user_id: '',
    is_active: true,
});

const openCreate = () => {
    editingBlock.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (block) => {
    editingBlock.value = block;
    form.code = block.code;
    form.name = block.name;
    form.hectare = block.hectare || '';
    form.pic_user_id = block.pic_user_id || '';
    form.is_active = block.is_active;
    showModal.value = true;
};

const submit = () => {
    if (editingBlock.value) {
        form.put(route('admin.blocks.update', editingBlock.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.blocks.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const toggleActive = (block) => {
    router.delete(route('admin.blocks.destroy', block.id));
};
</script>

<template>
    <AppLayout title="Blok Kebun" :user="user">
        <Head><title>Manajemen Blok Kebun - Kebun Tebu</title></Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Blok / Wilayah Kebun</h1>
                    <p class="text-gray-600 mt-1">Kelola data blok perkebunan</p>
                </div>
                <button @click="openCreate" class="btn-primary">+ Tambah Blok</button>
            </div>

            <div class="card overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Blok</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Luas (Ha)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PIC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Laporan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="block in blocks" :key="block.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono font-medium text-gray-900">{{ block.code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ block.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ block.hectare ? `${block.hectare} Ha` : '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ block.pic?.name || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ block.reports_count }}</td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    block.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'
                                ]">
                                    {{ block.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button @click="openEdit(block)" class="text-sm text-blue-600 hover:underline">Edit</button>
                                    <button @click="toggleActive(block)" class="text-sm text-yellow-600 hover:underline">
                                        {{ block.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="blocks.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada blok</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="card w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">{{ editingBlock ? 'Edit Blok' : 'Tambah Blok Baru' }}</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="space-y-3">
                    <div><label class="label">Kode Blok</label><input type="text" v-model="form.code" class="input font-mono" placeholder="BLOK-A12" required /></div>
                    <div><label class="label">Nama Blok</label><input type="text" v-model="form.name" class="input" required /></div>
                    <div><label class="label">Luas (Hektar)</label><input type="number" step="0.01" v-model="form.hectare" class="input" /></div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" :disabled="form.processing" class="btn-primary flex-1">Simpan</button>
                        <button type="button" @click="showModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
