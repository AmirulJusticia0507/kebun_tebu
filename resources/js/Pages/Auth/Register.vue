<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'field_officer',
    phone_number: '',
});

const submit = () => {
    form.post(route('register'));
};
</script>

<template>
    <AppLayout :title="__('Register')" :user="null">
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="card p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Buat Akun Baru</h1>
                        <p class="mt-2 text-gray-600">Daftar sebagai petugas lapangan atau admin</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="name" class="label">Nama Lengkap</label>
                            <input
                                id="name"
                                type="text"
                                v-model="form.name"
                                class="input"
                                autocomplete="name"
                                required
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="label">Email</label>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="input"
                                autocomplete="email"
                                required
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="phone_number" class="label">Nomor WhatsApp</label>
                            <input
                                id="phone_number"
                                type="tel"
                                v-model="form.phone_number"
                                class="input"
                                autocomplete="tel"
                                placeholder="08xxxxxxxxxx"
                            />
                            <p v-if="form.errors.phone_number" class="mt-1 text-sm text-red-600">{{ form.errors.phone_number }}</p>
                        </div>

                        <div>
                            <label for="role" class="label">Peran</label>
                            <select id="role" v-model="form.role" class="input">
                                <option value="field_officer">Petugas Lapangan</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                        </div>

                        <div>
                            <label for="password" class="label">Kata Sandi</label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                class="input"
                                autocomplete="new-password"
                                required
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="label">Konfirmasi Kata Sandi</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                class="input"
                                autocomplete="new-password"
                                required
                            />
                        </div>

                        <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Mendaftar...
                            </span>
                            <span v-else>Daftar</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-600">
                        Sudah punya akun? 
                        <Link :href="route('login')" class="text-primary-600 hover:underline font-medium">
                            Masuk di sini
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>