<script setup>
import { ref } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    users: Array,
});

const showCreateModal = ref(false);
const showResetModal = ref(false);
const editingUser = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'field_officer',
    phone_number: '',
});

const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetModal = (u) => {
    editingUser.value = u;
    showResetModal.value = true;
    resetForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const submitReset = () => {
    resetForm.patch(route('admin.users.reset-password', editingUser.value.id), {
        onSuccess: () => {
            showResetModal.value = false;
            editingUser.value = null;
        },
    });
};

const deleteUser = (u) => {
    if (confirm(`Hapus petugas ${u.name}?`)) {
        router.delete(route('admin.users.destroy', u.id));
    }
};

const roleLabel = (r) => r === 'admin' ? 'Admin' : 'Petugas Lapangan';
const roleClass = (r) => r === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
</script>

<template>
    <AppLayout title="Manajemen Pengguna" :user="user">
        <Head><title>Manajemen Pengguna - Kebun Tebu</title></Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
                    <p class="text-gray-600 mt-1">Kelola akun petugas lapangan & admin</p>
                </div>
                <button @click="showCreateModal = true" class="btn-primary">
                    + Tambah Petugas
                </button>
            </div>

            <div class="card overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telepon</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ u.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ u.email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ u.phone_number || '-' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', roleClass(u.role)]">
                                    {{ roleLabel(u.role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Link :href="route('admin.users.edit', u.id)" class="text-sm text-blue-600 hover:underline">Edit</Link>
                                    <button @click="openResetModal(u)" class="text-sm text-yellow-600 hover:underline">Reset PW</button>
                                    <button @click="deleteUser(u)" v-if="u.id !== user.id" class="text-sm text-red-600 hover:underline">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada pengguna</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="card w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Tambah Petugas Baru</h2>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-3">
                    <div><label class="label">Nama</label><input type="text" v-model="createForm.name" class="input" required /></div>
                    <div><label class="label">Email</label><input type="email" v-model="createForm.email" class="input" required /></div>
                    <div><label class="label">Telepon</label><input type="tel" v-model="createForm.phone_number" class="input" /></div>
                    <div>
                        <label class="label">Role</label>
                        <select v-model="createForm.role" class="input">
                            <option value="field_officer">Petugas Lapangan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div><label class="label">Password</label><input type="password" v-model="createForm.password" class="input" required /></div>
                    <div><label class="label">Konfirmasi Password</label><input type="password" v-model="createForm.password_confirmation" class="input" required /></div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" :disabled="createForm.processing" class="btn-primary flex-1">Simpan</button>
                        <button type="button" @click="showCreateModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div v-if="showResetModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="card w-full max-w-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Reset Password</h2>
                    <button @click="showResetModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Reset password untuk <strong>{{ editingUser?.name }}</strong></p>
                <form @submit.prevent="submitReset" class="space-y-3">
                    <div><label class="label">Password Baru</label><input type="password" v-model="resetForm.password" class="input" required /></div>
                    <div><label class="label">Konfirmasi Password</label><input type="password" v-model="resetForm.password_confirmation" class="input" required /></div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" :disabled="resetForm.processing" class="btn-primary flex-1">Reset</button>
                        <button type="button" @click="showResetModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
