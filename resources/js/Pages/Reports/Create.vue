<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import axios from 'axios';
import { saveDraft, getDrafts, deleteDraft, clearDrafts } from '@/Utils/offlineDb';

const props = defineProps({
    user: Object,
    categories: Array,
    blocks: Array,
});

const form = useForm({
    title: '',
    category_id: '',
    block_id: '',
    block_code: '',
    description: '',
    latitude: '',
    longitude: '',
    photo: null,
    checklist_answers: {},
});

const gpsStatus = ref('idle'); // idle | loading | success | error | denied
const gpsMessage = ref('');
const photoPreview = ref(null);
const mapContainer = ref(null);
const miniMap = ref(null);
const marker = ref(null);
const offlineDrafts = ref([]);
const isSyncing = ref(false);

const loadOfflineDrafts = async () => {
    try {
        offlineDrafts.value = await getDrafts();
    } catch (e) {
        console.error('Failed to load offline drafts:', e);
    }
};

const handleSaveDraft = async () => {
    if (!form.title || !form.category_id || !form.latitude || !form.longitude) {
        alert('Mohon isi Judul, Kategori, dan Lokasi GPS sebelum menyimpan draft.');
        return;
    }

    try {
        await saveDraft({
            title: form.title,
            category_id: form.category_id,
            block_id: form.block_id,
            description: form.description,
            latitude: form.latitude,
            longitude: form.longitude,
            checklist_answers: form.checklist_answers,
        });
        alert('Draft offline berhasil disimpan di perangkat Anda! Akan disinkronkan saat koneksi online.');
        form.reset();
        await loadOfflineDrafts();
    } catch (e) {
        alert('Gagal menyimpan draft offline.');
    }
};

const syncOfflineDrafts = async () => {
    const drafts = await getDrafts();
    if (drafts.length === 0) return;

    isSyncing.value = true;
    try {
        const res = await axios.post(route('reports.sync'), { drafts });
        await clearDrafts();
        await loadOfflineDrafts();
        alert(res.data.message || 'Draft offline berhasil disinkronkan ke server!');
        router.visit(route('map'));
    } catch (e) {
        console.error('Offline sync failed:', e);
        alert('Gagal menyinkronkan draft offline. Pastikan koneksi internet stabil.');
    } finally {
        isSyncing.value = false;
    }
};

const selectedCategory = computed(() =>
    props.categories.find(c => c.id == form.category_id)
);

const checklistTemplate = computed(() =>
    selectedCategory.value?.checklist_template || []
);

// GPS
const getLocation = () => {
    if (!navigator.geolocation) {
        gpsStatus.value = 'error';
        gpsMessage.value = 'Browser Anda tidak mendukung GPS.';
        return;
    }

    gpsStatus.value = 'loading';
    gpsMessage.value = 'Mengambil lokasi GPS...';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude.toFixed(8);
            form.longitude = pos.coords.longitude.toFixed(8);
            gpsStatus.value = 'success';
            gpsMessage.value = `Akurasi: ±${Math.round(pos.coords.accuracy)}m`;
            updateMapMarker(parseFloat(form.latitude), parseFloat(form.longitude));
        },
        (err) => {
            if (err.code === err.PERMISSION_DENIED) {
                gpsStatus.value = 'denied';
                gpsMessage.value = 'Izin GPS ditolak. Silakan pilih titik di peta secara manual.';
            } else {
                gpsStatus.value = 'error';
                gpsMessage.value = 'GPS belum aktif. Aktifkan GPS atau pilih titik di peta.';
            }
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const updateMapMarker = (lat, lng) => {
    if (!miniMap.value) return;

    miniMap.value.setView([lat, lng], 16);

    if (marker.value) {
        marker.value.setLatLng([lat, lng]);
    } else {
        marker.value = L.marker([lat, lng], { draggable: true })
            .addTo(miniMap.value)
            .bindPopup('Geser untuk menyesuaikan lokasi')
            .openPopup();

        marker.value.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            form.latitude = pos.lat.toFixed(8);
            form.longitude = pos.lng.toFixed(8);
        });
    }
};

const initMiniMap = () => {
    miniMap.value = L.map(mapContainer.value, {
        center: [-7.7956, 110.3695],
        zoom: 13,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(miniMap.value);

    miniMap.value.on('click', (e) => {
        form.latitude = e.latlng.lat.toFixed(8);
        form.longitude = e.latlng.lng.toFixed(8);
        gpsStatus.value = 'success';
        gpsMessage.value = 'Lokasi dipilih secara manual.';
        updateMapMarker(e.latlng.lat, e.latlng.lng);
    });
};

// Photo
const handlePhoto = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.photo = file;

    const reader = new FileReader();
    reader.onload = (ev) => {
        photoPreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
};

const submit = () => {
    form.post(route('reports.store'), {
        forceFormData: true,
        onError: (errors) => console.error(errors),
    });
};

onMounted(() => {
    initMiniMap();
    getLocation();
});
</script>

<template>
    <AppLayout title="Buat Laporan" :user="user">
        <Head>
            <title>Buat Laporan Baru - Kebun Tebu</title>
        </Head>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-28">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Buat Laporan Baru</h1>
                <p class="text-gray-600 mt-1">Laporkan kejadian di lapangan</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Kategori -->
                <div class="card p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Informasi Kejadian</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="label" for="category_id">Kategori Kejadian <span class="text-red-500">*</span></label>
                            <select id="category_id" v-model="form.category_id" class="input" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</p>
                        </div>

                        <div>
                            <label class="label" for="title">Judul Singkat <span class="text-red-500">*</span></label>
                            <input id="title" type="text" v-model="form.title" class="input" placeholder="Contoh: Kebakaran di Blok A-12 sektor barat" required />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="label" for="block_id">Blok / Wilayah</label>
                            <select id="block_id" v-model="form.block_id" class="input">
                                <option value="">-- Pilih Blok (opsional) --</option>
                                <option v-for="block in blocks" :key="block.id" :value="block.id">
                                    {{ block.code }} - {{ block.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="label" for="description">Keterangan Tambahan</label>
                            <textarea id="description" v-model="form.description" class="input h-24" rows="3" placeholder="Jelaskan kronologi atau detail kejadian..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Checklist Dinamis -->
                <div v-if="checklistTemplate.length > 0" class="card p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Checklist Kejadian</h2>
                    <div class="space-y-3">
                        <div v-for="(item, idx) in checklistTemplate" :key="idx">
                            <label class="label">{{ item.label }}</label>
                            <template v-if="item.type === 'boolean'">
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <input type="radio" :name="`cl_${idx}`" :value="true" v-model="form.checklist_answers[item.label]" />
                                        <span class="text-sm">Ya</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="radio" :name="`cl_${idx}`" :value="false" v-model="form.checklist_answers[item.label]" />
                                        <span class="text-sm">Tidak</span>
                                    </label>
                                </div>
                            </template>
                            <template v-else-if="item.type === 'number'">
                                <input type="number" step="0.01" class="input" v-model="form.checklist_answers[item.label]" />
                            </template>
                            <template v-else>
                                <input type="text" class="input" v-model="form.checklist_answers[item.label]" />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Lokasi GPS -->
                <div class="card p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Lokasi Kejadian <span class="text-red-500">*</span></h2>

                    <!-- GPS status -->
                    <div :class="[
                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm mb-3',
                        gpsStatus === 'success' ? 'bg-green-50 text-green-700' :
                        gpsStatus === 'loading' ? 'bg-blue-50 text-blue-700' :
                        gpsStatus === 'error' || gpsStatus === 'denied' ? 'bg-yellow-50 text-yellow-700' :
                        'bg-gray-50 text-gray-600'
                    ]">
                        <svg v-if="gpsStatus === 'loading'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        <span>{{ gpsMessage || 'Klik tombol untuk ambil lokasi GPS' }}</span>
                        <button type="button" @click="getLocation" class="ml-auto text-xs underline">Refresh GPS</button>
                    </div>

                    <!-- Mini map -->
                    <div ref="mapContainer" class="h-52 rounded-lg overflow-hidden border border-gray-200 mb-3"></div>

                    <!-- Manual coordinate input -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs">Latitude</label>
                            <input type="number" step="0.00000001" v-model="form.latitude" class="input text-sm font-mono" placeholder="-7.7956" required />
                        </div>
                        <div>
                            <label class="label text-xs">Longitude</label>
                            <input type="number" step="0.00000001" v-model="form.longitude" class="input text-sm font-mono" placeholder="110.3695" required />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">💡 Anda juga bisa klik langsung di peta untuk memilih lokasi</p>
                    <p v-if="form.errors.latitude || form.errors.longitude" class="text-red-500 text-xs mt-1">
                        {{ form.errors.latitude || form.errors.longitude }}
                    </p>
                </div>

                <!-- Upload Foto -->
                <div class="card p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Foto Bukti</h2>

                    <div v-if="photoPreview" class="mb-3">
                        <img :src="photoPreview" alt="Preview" class="w-full max-h-64 object-cover rounded-lg" />
                        <button type="button" @click="photoPreview = null; form.photo = null"
                            class="text-xs text-red-500 mt-2 underline">Hapus foto</button>
                    </div>

                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-colors">
                        <div class="flex flex-col items-center gap-2 text-gray-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm">Klik untuk ambil/pilih foto</span>
                            <span class="text-xs text-gray-400">JPG, PNG, WebP — Maks. 5MB</span>
                        </div>
                        <input type="file" class="hidden" accept="image/*" capture="environment" @change="handlePhoto" />
                    </label>

                    <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
                </div>

                <!-- Offline Draft Banner -->
                <div v-if="offlineDrafts.length > 0" class="p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-800">Ada {{ offlineDrafts.length }} draft tersimpan offline</p>
                        <p class="text-xs text-amber-600">Klik sinkronkan untuk mengunggah ke server.</p>
                    </div>
                    <button type="button" @click="syncOfflineDrafts" :disabled="isSyncing" class="btn bg-amber-600 text-white hover:bg-amber-700 text-xs py-2 px-3">
                        {{ isSyncing ? 'Sinkronisasi...' : 'Sinkronkan Sekarang' }}
                    </button>
                </div>

                <!-- Submit & Draft Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-3">
                        <span v-if="form.processing">Mengirim...</span>
                        <span v-else>Kirim Laporan</span>
                    </button>
                    <button type="button" @click="handleSaveDraft" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 py-3 px-4 text-sm">
                        💾 Simpan Draft Offline
                    </button>
                    <a :href="route('map')" class="btn btn-secondary py-3 px-6 text-center">Batal</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
