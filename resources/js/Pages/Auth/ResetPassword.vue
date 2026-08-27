<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps(['token', 'email']);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'));
};
</script>

<template>
    <AppLayout :title="__('Reset Password')" :user="null">
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
            <div class="w-full max-w-md">
                <div class="card p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Reset Kata Sandi</h1>
                        <p class="mt-2 text-gray-600">Masukkan kata sandi baru Anda</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="password" class="label">Kata Sandi Baru</label>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                class="input"
                                autocomplete="new-password"
                                required
                                autofocus
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
                            <span v-if="form.processing">Mereset...</span>
                            <span v-else>Reset Kata Sandi</span>
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