<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    user: Object,
});
</script>

<template>
    <AppLayout title="Kebijakan Privasi & Ketentuan Layanan" :user="user">
        <Head><title>Kebijakan Privasi & Cookie - Kebun Tebu MVP</title></Head>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
            <div class="glass-panel p-8 rounded-3xl border border-slate-800 space-y-6">
                <div class="border-b border-slate-800 pb-6">
                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest bg-emerald-950/80 px-3 py-1 rounded-full border border-emerald-800/50">Privasi & Keamanan Data</span>
                    <h1 class="text-3xl font-extrabold text-slate-100 mt-3">Kebijakan Privasi & Penggunaan Cookie</h1>
                    <p class="text-xs text-slate-400 mt-2">Terakhir Diperbarui: 27 Agustus 2026</p>
                </div>

                <div class="prose prose-invert max-w-none space-y-6 text-sm text-slate-300 leading-relaxed">
                    <section class="space-y-2">
                        <h2 class="text-lg font-bold text-slate-100">1. Pengumpulan Informasi & Data Spasial</h2>
                        <p>Aplikasi Kebun Tebu MVP mengumpulkan informasi yang diperlukan untuk pemantauan kesehatan kebun dan pelaporan insiden lapangan, meliputi:</p>
                        <ul class="list-disc list-inside space-y-1 pl-2 text-slate-300">
                            <li>Data Pengguna: Nama, Alamat Email, Nomor Telepon, dan Role Spatie.</li>
                            <li>Data Lokasi & Geospasial: Koordinat GPS (Latitude & Longitude) saat pengiriman laporan insiden.</li>
                            <li>Lampiran Foto Bukti Lapangan untuk verifikasi tim manajemen.</li>
                        </ul>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-lg font-bold text-slate-100">2. Kebijakan Cookie & Penyimpanan Lokal (Local Storage)</h2>
                        <p>Sistem ini menggunakan Cookie dan Browser LocalStorage untuk tujuan operasional berikut:</p>
                        <ul class="list-disc list-inside space-y-1 pl-2 text-slate-300">
                            <li><strong>Cookie Sesi (Laravel Session & XSRF-TOKEN):</strong> Menjaga keamanan autentikasi pengguna dan mencegah serangan CSRF.</li>
                            <li><strong>Offline Draft Storage (IndexedDB / LocalStorage):</strong> Menyimpan laporan yang dibuat dalam mode offline sebelum disinkronkan ke server.</li>
                            <li><strong>Preferensi Tema:</strong> Menyimpan mode tampilan (Dark/Light mode).</li>
                        </ul>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-lg font-bold text-slate-100">3. Keamanan Data & Hak Akses Role</h2>
                        <p>Seluruh transmisi data dilindungi enkripsi standar dan dikontrol ketat melalui Spatie Laravel Permission middleware. Data geospasial blok kebun dan laporan hanya dapat diakses oleh petugas terautentikasi dan pihak manajemen yang berwenang.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-lg font-bold text-slate-100">4. Kontak & Bantuan</h2>
                        <p>Jika Anda memiliki pertanyaan mengenai perlindungan data pribadi dan Cookie pada sistem Kebun Tebu MVP, silakan hubungi tim administrator melalui email <a href="mailto:admin@kebuntebu.id" class="text-emerald-400 font-semibold underline">admin@kebuntebu.id</a>.</p>
                    </section>
                </div>

                <div class="pt-6 border-t border-slate-800 flex justify-between items-center">
                    <Link href="/map" class="btn-primary text-xs py-2.5 px-4 flex items-center gap-2">
                        ← Kembali ke Peta Monitoring
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
