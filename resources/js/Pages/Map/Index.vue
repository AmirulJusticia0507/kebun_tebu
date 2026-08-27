<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet.markercluster';

const props = defineProps({
    user: Object,
    reports: Array,
    categories: Array,
    blocks: Array,
    filters: Object,
});

const mapContainer = ref(null);
const map = ref(null);
const markers = ref(null);
const selectedReport = ref(null);
const isFilterSidebarOpen = ref(true);
const isDetailSidebarOpen = ref(false);

const filterForm = useForm({
    category_id: props.filters.category_id || '',
    block_id: props.filters.block_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    status: props.filters.status || '',
}, {
    preserveState: true,
    preserveScroll: true,
});

const baseLayers = {
    osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19,
    }),
    terrain: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenTopoMap contributors',
        maxZoom: 17,
    }),
};

const categoryIcons = {};
props.categories.forEach(cat => {
    if (cat.icon_marker) {
        categoryIcons[cat.id] = L.icon({
            iconUrl: `/icons/${cat.icon_marker}`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32],
        });
    }
});

const defaultIcon = L.icon({
    iconUrl: '/icons/default-marker.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32],
});

const getCategoryColor = (categoryId) => {
    const cat = props.categories.find(c => c.id === categoryId);
    return cat?.color_code || '#16a34a';
};

const toggleFilterSidebar = () => {
    isFilterSidebarOpen.value = !isFilterSidebarOpen.value;
    nextTick(() => {
        if (map.value) {
            setTimeout(() => map.value.invalidateSize(), 300);
        }
    });
};

const initMap = () => {
    if (map.value) return;

    map.value = L.map(mapContainer.value, {
        center: [-7.7956, 110.3695],
        zoom: 13,
        layers: [baseLayers.osm],
        zoomControl: false,
    });

    L.control.zoom({ position: 'topright' }).addTo(map.value);

    markers.value = L.markerClusterGroup({
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 50,
        iconCreateFunction: (cluster) => {
            const count = cluster.getChildCount();
            let className = 'marker-cluster-small';
            if (count >= 100) className = 'marker-cluster-large';
            else if (count >= 10) className = 'marker-cluster-medium';
            
            return L.divIcon({
                html: `<div><span>${count}</span></div>`,
                className: className,
                iconSize: L.point(40, 40),
            });
        },
    });

    map.value.addLayer(markers.value);
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map.value);

    loadMarkers();
    loadBlockLayers();
};

const loadMarkers = () => {
    if (!markers.value) return;
    
    markers.value.clearLayers();

    props.reports.forEach(report => {
        if (!report.latitude || !report.longitude) return;

        const icon = categoryIcons[report.category_id] || defaultIcon;
        const marker = L.marker([report.latitude, report.longitude], { icon });
        
        const statusLabel = report.status === 'OPEN' ? 'Open' : report.status === 'ON_PROGRESS' ? 'On Progress' : 'Closed';
        const statusColor = report.status === 'OPEN' ? '#ef4444' : report.status === 'ON_PROGRESS' ? '#f59e0b' : '#22c55e';

        const popupContent = `
            <div class="p-3 min-w-[240px]">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ${getCategoryColor(report.category_id)}"></span>
                    <strong class="text-sm text-slate-100">${report.title}</strong>
                </div>
                <p class="text-xs text-slate-300 mb-2 line-clamp-2">${report.description || 'Tidak ada deskripsi'}</p>
                <div class="text-[11px] text-slate-400 space-y-1 mb-3">
                    <div>Kategori: <span class="text-slate-200">${report.category?.name || '-'}</span></div>
                    <div>Blok: <span class="text-slate-200">${report.block_code || report.block?.code || '-'}</span></div>
                    <div>Pelapor: <span class="text-slate-200">${report.user?.name || '-'}</span></div>
                    <div>Status: <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" style="background-color: ${statusColor}25; color: ${statusColor}; border: 1px solid ${statusColor}50;">${statusLabel}</span></div>
                </div>
                <button 
                    onclick="window.dispatchEvent(new CustomEvent('map-report-click', { detail: ${report.id} }))"
                    class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-xs py-1.5 px-3 rounded-lg shadow-md transition-all"
                >
                    Lihat Detail Laporan
                </button>
            </div>
        `;

        marker.bindPopup(popupContent);
        markers.value.addLayer(marker);
    });
};

const loadBlockLayers = () => {
    if (!map.value) return;

    const existingBlockLayer = map.value._blockLayer;
    if (existingBlockLayer) {
        map.value.removeLayer(existingBlockLayer);
    }

    if (props.blocks.length === 0) return;

    const geojson = {
        type: 'FeatureCollection',
        features: props.blocks
            .filter(b => b.polygon)
            .map(block => ({
                type: 'Feature',
                properties: {
                    id: block.id,
                    code: block.code,
                    name: block.name,
                    hectare: block.hectare,
                    pic: block.pic?.name,
                },
                geometry: block.polygon,
            })),
    };

    if (geojson.features.length === 0) return;

    const blockLayer = L.geoJSON(geojson, {
        style: (feature) => ({
            color: '#10b981',
            weight: 2,
            opacity: 0.8,
            fillColor: '#10b981',
            fillOpacity: 0.15,
        }),
        onEachFeature: (feature, layer) => {
            layer.bindPopup(`
                <div class="p-2">
                    <strong class="text-emerald-400">Blok ${feature.properties.code}</strong><br>
                    <span class="text-sm">${feature.properties.name}</span><br>
                    <span class="text-xs text-slate-300">Luas: ${feature.properties.hectare} Ha</span><br>
                    <span class="text-xs text-slate-400">PIC: ${feature.properties.pic || '-'}</span>
                </div>
            `);
        },
    });

    blockLayer.addTo(map.value);
    map.value._blockLayer = blockLayer;
};

const applyFilters = () => {
    filterForm.get(route('map'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.category_id = '';
    filterForm.block_id = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.status = '';
    applyFilters();
};

const selectReport = (report) => {
    selectedReport.value = report;
    isDetailSidebarOpen.value = true;
    
    if (map.value && report.latitude && report.longitude) {
        map.value.setView([report.latitude, report.longitude], 16);
    }
};

const closeDetailSidebar = () => {
    isDetailSidebarOpen.value = false;
    selectedReport.value = null;
};

const handleReportClick = (event) => {
    const reportId = event.detail;
    const report = props.reports.find(r => r.id === reportId);
    if (report) {
        selectReport(report);
    }
};

const statusUpdateForm = useForm({
    status: '',
    admin_note: '',
});

watch(selectedReport, (newReport) => {
    if (newReport) {
        statusUpdateForm.status = newReport.status;
        statusUpdateForm.admin_note = newReport.admin_note || '';
    }
});

const saveStatus = () => {
    if (!selectedReport.value) return;
    statusUpdateForm.patch(route('reports.status', selectedReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedReport.value.status = statusUpdateForm.status;
            selectedReport.value.admin_note = statusUpdateForm.admin_note;
        },
    });
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
    window.addEventListener('map-report-click', handleReportClick);
});

onBeforeUnmount(() => {
    window.removeEventListener('map-report-click', handleReportClick);
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});

watch(() => props.reports, () => {
    loadMarkers();
}, { deep: true });

watch(() => props.blocks, () => {
    loadBlockLayers();
}, { deep: true });
</script>

<template>
    <AppLayout title="Peta Monitoring Spasial" :user="user">
        <div class="flex h-[calc(100vh-4.1rem)] w-full overflow-hidden relative">
            
            <!-- Left Filter & Search Sidebar Panel -->
            <aside 
                :class="[
                    'z-30 w-80 max-w-[85vw] flex-shrink-0 glass-panel border-r border-slate-800 flex flex-col transition-all duration-300 ease-in-out absolute lg:relative inset-y-0 left-0',
                    isFilterSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:-ml-80'
                ]"
            >
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-slate-800/80 flex items-center justify-between bg-slate-900/60">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400">🎛️</span>
                        <h2 class="text-base font-bold text-slate-100">Filter & Control</h2>
                    </div>
                    <button @click="toggleFilterSidebar" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Sidebar Body Scrollable Form -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    
                    <!-- Quick Create Report Button -->
                    <Link href="/reports/create" class="btn-primary w-full py-3 flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Buat Laporan Lapangan</span>
                    </Link>

                    <!-- Filter Form Fields -->
                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="label">Kategori Kejadian</label>
                            <select v-model="filterForm.category_id" class="input">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Wilayah / Blok Kebun</label>
                            <select v-model="filterForm.block_id" class="input">
                                <option value="">Semua Blok</option>
                                <option v-for="block in blocks" :key="block.id" :value="block.id">{{ block.code }} - {{ block.name }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Dari Tanggal</label>
                                <input type="date" v-model="filterForm.date_from" class="input text-xs" />
                            </div>
                            <div>
                                <label class="label">Sampai Tanggal</label>
                                <input type="date" v-model="filterForm.date_to" class="input text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="label">Status Laporan</label>
                            <select v-model="filterForm.status" class="input">
                                <option value="">Semua Status</option>
                                <option value="OPEN">Open (Baru)</option>
                                <option value="ON_PROGRESS">On Progress (Penanganan)</option>
                                <option value="CLOSED">Closed (Selesai)</option>
                            </select>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button @click="applyFilters" class="btn-primary flex-1 text-xs py-2">
                                Terapkan
                            </button>
                            <button @click="clearFilters" class="btn-secondary flex-1 text-xs py-2">
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Export Section -->
                    <div class="pt-4 border-t border-slate-800 space-y-2">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Export Data Spatial</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <a :href="route('reports.export.csv')" target="_blank" class="btn btn-secondary text-xs py-2 flex items-center justify-center gap-1.5">
                                📥 CSV
                            </a>
                            <a :href="route('reports.export.geojson')" target="_blank" class="btn btn-secondary text-xs py-2 flex items-center justify-center gap-1.5">
                                🗺️ GeoJSON
                            </a>
                        </div>
                    </div>

                    <!-- Legend Section -->
                    <div class="pt-4 border-t border-slate-800">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2.5">Legenda Kategori</h3>
                        <div class="space-y-2">
                            <div v-for="cat in categories" :key="cat.id" class="flex items-center justify-between p-2 rounded-xl bg-slate-900/60 border border-slate-800/80">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-3.5 h-3.5 rounded-full shadow-sm" :style="{ backgroundColor: cat.color_code }"></span>
                                    <span class="text-xs font-semibold text-slate-200">{{ cat.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Floating Toggle Button (Top-Left of Map) -->
            <button 
                @click="toggleFilterSidebar" 
                class="absolute top-4 left-4 z-20 glass-panel px-3.5 py-2.5 rounded-xl border border-slate-700/80 text-xs font-bold text-slate-100 shadow-xl hover:border-emerald-500/50 hover:bg-slate-800/90 transition-all flex items-center gap-2"
                :title="isFilterSidebarOpen ? 'Sembunyikan Panel Filter' : 'Tampilkan Panel Filter'"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>{{ isFilterSidebarOpen ? 'Sembunyikan Filter' : 'Filter & Legenda' }}</span>
            </button>

            <!-- Floating Create Report Button (Top-Right of Map) -->
            <Link 
                href="/reports/create"
                class="absolute top-4 right-16 z-20 btn-primary py-2 px-3.5 text-xs shadow-xl flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Buat Laporan</span>
            </Link>

            <!-- Main Map Canvas -->
            <div ref="mapContainer" class="flex-1 h-full w-full z-10" id="map"></div>

            <!-- Right Detail Sidebar Drawer -->
            <aside 
                v-if="selectedReport"
                :class="[
                    'z-40 w-96 max-w-[90vw] glass-panel border-l border-slate-800 flex flex-col transition-all duration-300 absolute inset-y-0 right-0 shadow-2xl',
                    isDetailSidebarOpen ? 'translate-x-0' : 'translate-x-full'
                ]"
            >
                <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
                    <h2 class="text-base font-bold text-slate-100 flex items-center gap-2">
                        <span>📌 Detail Laporan</span>
                    </h2>
                    <button @click="closeDetailSidebar" class="p-1 rounded-lg text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-100">{{ selectedReport.title }}</h3>
                        <div class="mt-2">
                            <span :class="[
                                selectedReport.status === 'OPEN' ? 'badge-open' :
                                selectedReport.status === 'ON_PROGRESS' ? 'badge-progress' :
                                'badge-closed'
                            ]">
                                {{ selectedReport.status === 'OPEN' ? 'Open (Baru)' : selectedReport.status === 'ON_PROGRESS' ? 'On Progress' : 'Closed (Selesai)' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs glass-card p-3 border border-slate-800/80">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Kategori:</span>
                            <span class="font-bold text-slate-200 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: getCategoryColor(selectedReport.category_id) }"></span>
                                {{ selectedReport.category?.name || '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Blok Kebun:</span>
                            <span class="font-bold text-slate-200">{{ selectedReport.block_code || selectedReport.block?.code || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Koordinat:</span>
                            <span class="font-mono text-slate-300">{{ selectedReport.latitude }}, {{ selectedReport.longitude }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Pelapor:</span>
                            <span class="font-semibold text-slate-200">{{ selectedReport.user?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Waktu:</span>
                            <span class="text-slate-300">{{ new Date(selectedReport.reported_at).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>

                    <div v-if="selectedReport.description" class="pt-2">
                        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Kejadian</h4>
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 text-xs text-slate-200 leading-relaxed whitespace-pre-wrap">
                            {{ selectedReport.description }}
                        </div>
                    </div>

                    <div v-if="selectedReport.photo_url" class="pt-2">
                        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Foto Bukti Lapangan</h4>
                        <img :src="selectedReport.photo_url" alt="Foto bukti" class="w-full rounded-xl max-h-56 object-cover border border-slate-800 shadow-md" />
                    </div>

                    <!-- Admin status management -->
                    <div v-if="user && user.role === 'admin'" class="pt-4 border-t border-slate-800 space-y-3">
                        <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Ubah Status Laporan (Admin)</h4>
                        <div>
                            <label class="label">Status Baru</label>
                            <select v-model="statusUpdateForm.status" class="input">
                                <option value="OPEN">Open (Baru)</option>
                                <option value="ON_PROGRESS">On Progress (Dalam Penanganan)</option>
                                <option value="CLOSED">Closed (Selesai)</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Catatan Admin</label>
                            <textarea v-model="statusUpdateForm.admin_note" placeholder="Tuliskan catatan tindak lanjut..." class="input h-20" rows="3"></textarea>
                        </div>
                        <button @click="saveStatus" :disabled="statusUpdateForm.processing" class="btn-primary w-full py-2.5">
                            {{ statusUpdateForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Status' }}
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>