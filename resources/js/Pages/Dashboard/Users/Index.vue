<script setup>
import { ref } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

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
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengguna/Petugas baru berhasil ditambahkan.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                background: '#0f172a',
                color: '#f8fafc',
            });
        },
    });
};

const submitReset = () => {
    resetForm.patch(route('admin.users.reset-password', editingUser.value.id), {
        onSuccess: () => {
            showResetModal.value = false;
            editingUser.value = null;
            Swal.fire({
                title: 'Password Direset!',
                text: 'Password pengguna berhasil diperbarui.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                background: '#0f172a',
                color: '#f8fafc',
            });
        },
    });
};

const deleteUser = (u) => {
    Swal.fire({
        title: 'Hapus Petugas?',
        text: `Apakah Anda yakin ingin menghapus akun ${u.name} (${u.email})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
        customClass: {
            popup: 'border border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl bg-slate-900/95',
            title: 'text-slate-100 font-bold',
            confirmButton: 'px-4 py-2 rounded-xl text-sm font-semibold shadow-lg shadow-rose-950/50',
            cancelButton: 'px-4 py-2 rounded-xl text-sm font-semibold',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.users.destroy', u.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Akun petugas telah berhasil dihapus.',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        background: '#0f172a',
                        color: '#f8fafc',
                    });
                }
            });
        }
    });
};

const roleLabel = (r) => r === 'admin' ? 'Admin Kebun' : 'Petugas Lapangan';
const roleClass = (r) => r === 'admin' ? 'bg-purple-950/80 text-purple-400 border border-purple-800/50' : 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50';
</script>

<template>
    <AppLayout title="Manajemen Pengguna & Roles" :user="user">
        <Head><title>Manajemen Pengguna - Kebun Tebu</title></Head>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 glass-panel p-6 rounded-2xl border border-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-2">
                        <span>👥 Manajemen Pengguna & Permission</span>
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Kelola hak akses role admin dan petugas lapangan Spatie</p>
                </div>
                <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2 self-start sm:self-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>+ Tambah Petugas</span>
                </button>
            </div>

            <!-- Users Table Card -->
            <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-900/80 border-b border-slate-800 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama Lengkap</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">No. Telepon</th>
                                <th class="px-6 py-4">Role Spatie</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-sm">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-100 flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center font-bold text-emerald-400 border border-slate-700">
                                        {{ u.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div>{{ u.name }}</div>
                                        <span v-if="u.id === user.id" class="text-[10px] text-emerald-400 font-normal">(Akun Anda)</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-mono text-xs">{{ u.email }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ u.phone_number || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-bold uppercase shadow-sm', roleClass(u.role)]">
                                        {{ roleLabel(u.role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openResetModal(u)" class="btn btn-secondary text-xs py-1.5 px-3 text-amber-400 hover:border-amber-500/50">
                                            🔑 Reset PW
                                        </button>
                                        <button @click="deleteUser(u)" v-if="u.id !== user.id" class="btn btn-danger text-xs py-1.5 px-3">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada pengguna terdaftar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-md p-6 rounded-2xl border border-slate-800 shadow-2xl">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                    <h2 class="text-lg font-bold text-slate-100">Tambah Petugas Baru</h2>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-200">✕</button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-3">
                    <div>
                        <label class="label">Nama Lengkap</label>
                        <input type="text" v-model="createForm.name" class="input" placeholder="Masukkan nama lengkap" required />
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input type="email" v-model="createForm.email" class="input" placeholder="petugas@kebuntebu.id" required />
                    </div>
                    <div>
                        <label class="label">No. Telepon (WhatsApp)</label>
                        <input type="tel" v-model="createForm.phone_number" class="input" placeholder="08123456789" />
                    </div>
                    <div>
                        <label class="label">Role Spatie</label>
                        <select v-model="createForm.role" class="input">
                            <option value="field_officer">Petugas Lapangan</option>
                            <option value="admin">Admin Kebun</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Password</label>
                        <input type="password" v-model="createForm.password" class="input" placeholder="Minimal 8 karakter" required />
                    </div>
                    <div>
                        <label class="label">Konfirmasi Password</label>
                        <input type="password" v-model="createForm.password_confirmation" class="input" placeholder="Ulangi password" required />
                    </div>
                    <div class="flex gap-2 pt-4 border-t border-slate-800">
                        <button type="submit" :disabled="createForm.processing" class="btn-primary flex-1">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Petugas' }}
                        </button>
                        <button type="button" @click="showCreateModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div v-if="showResetModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-sm p-6 rounded-2xl border border-slate-800 shadow-2xl">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                    <h2 class="text-lg font-bold text-slate-100">Reset Password</h2>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-200">✕</button>
                </div>
                <p class="text-xs text-slate-400 mb-4">Set password baru untuk akun <strong class="text-slate-200">{{ editingUser?.name }}</strong></p>
                <form @submit.prevent="submitReset" class="space-y-3">
                    <div>
                        <label class="label">Password Baru</label>
                        <input type="password" v-model="resetForm.password" class="input" placeholder="Minimal 8 karakter" required />
                    </div>
                    <div>
                        <label class="label">Konfirmasi Password Baru</label>
                        <input type="password" v-model="resetForm.password_confirmation" class="input" placeholder="Ulangi password" required />
                    </div>
                    <div class="flex gap-2 pt-4 border-t border-slate-800">
                        <button type="submit" :disabled="resetForm.processing" class="btn-primary flex-1">
                            {{ resetForm.processing ? 'Resetting...' : 'Reset Password' }}
                        </button>
                        <button type="button" @click="showResetModal = false" class="btn-secondary flex-1">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
