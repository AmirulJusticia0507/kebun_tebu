<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        preserveScroll: true,
        onError: (errors) => {
            console.log('Login error:', errors);
        },
    });
};
</script>

<template>
    <AppLayout :title="__('Login')" :user="null">
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
            <div class="w-full max-w-md">
                <div class="card p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Masuk ke Akun</h1>
                        <p class="mt-2 text-gray-600">Masuk untuk melaporkan kejadian lapangan</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="email" class="label">Email</label>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="input"
                                autocomplete="email"
                                required
                                autofocus
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="password" class="label">Kata Sandi</label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                class="input"
                                autocomplete="current-password"
                                required
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-600">Ingat saya</span>
                            </label>
                            
                            <Link :href="route('password.request')" class="text-sm text-primary-600 hover:underline">
                                Lupa kata sandi?
                            </Link>
                        </div>

                        <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Masuk...
                            </span>
                            <span v-else>Masuk</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-600">
                        Belum punya akun? 
                        <Link :href="route('register')" class="text-primary-600 hover:underline font-medium">
                            Daftar di sini
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>