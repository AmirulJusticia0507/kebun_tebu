<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AppLayout :title="__('Forgot Password')" :user="null">
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
            <div class="w-full max-w-md">
                <div class="card p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Lupa Kata Sandi</h1>
                        <p class="mt-2 text-gray-600">Masukkan email untuk menerima tautan reset kata sandi</p>
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

                        <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
                            <span v-if="form.processing">Mengirim...</span>
                            <span v-else>Kirim Tautan Reset</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-600">
                        <Link :href="route('login')" class="text-primary-600 hover:underline">
                            Kembali ke Masuk
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>