<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const isAccepted = ref(true);

onMounted(() => {
    if (typeof window !== 'undefined') {
        const consent = localStorage.getItem('cookie_consent_accepted');
        if (!consent) {
            isAccepted.value = false;
        }
    }
});

const acceptCookies = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('cookie_consent_accepted', 'true');
        isAccepted.value = true;
    }
};
</script>

<template>
    <div 
        v-if="!isAccepted"
        class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 glass-panel p-5 rounded-2xl border border-slate-700/80 shadow-2xl space-y-3 animate-fade-in"
    >
        <div class="flex items-start gap-3">
            <span class="text-2xl">🍪</span>
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-slate-100">Pemberitahuan Cookie & Privasi</h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Sistem Kebun Tebu MVP menggunakan cookie esensial dan local storage untuk otentikasi aman serta fitur laporan offline geospasial.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-800">
            <Link href="/privacy-policy" class="text-xs text-emerald-400 font-semibold hover:underline">
                Kebijakan Privasi
            </Link>
            <button @click="acceptCookies" class="btn-primary text-xs py-1.5 px-4 shadow-md">
                Setuju & Lanjutkan
            </button>
        </div>
    </div>
</template>
