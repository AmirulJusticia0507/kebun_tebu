<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
const sidebarOpen = ref(false);

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
            <div class="p-2 min-w-[250px]">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: ${getCategoryColor(report.category_id)}"></span>
                    <strong>${report.title}</strong>
                </div>
                <p class="text-sm text-gray-600 mb-2">${report.description || 'Tidak ada deskripsi'}</p>
                <div class="text-xs text-gray-500 space-y-1">
                    <div>Kategori: ${report.category?.name || '-'}</div>
                    <div>Blok: ${report.block_code || report.block?.code || '-'}</div>
                    <div>Pelapor: ${report.user?.name || '-'}</div>
                    <div>Waktu: ${new Date(report.reported_at).toLocaleString('id-ID')}</div>
                    <div><span class="px-2 py-0.5 rounded text-xs" style="background-color: ${statusColor}20; color: ${statusColor};">${statusLabel}</span></div>
                </div>
                <button 
                    onclick="window.dispatchEvent(new CustomEvent('map-report-click', { detail: ${report.id} }))"
                    class="mt-2 w-full btn-primary text-xs py-1"
                >
                    Lihat Detail
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
            color: '#16a34a',
            weight: 2,
            opacity: 0.8,
            fillColor: '#16a34a',
            fillOpacity: 0.1,
        }),
        onEachFeature: (feature, layer) => {
            layer.bindPopup(`
                <div class="p-2">
                    <strong>Blok ${feature.properties.code}</strong><br>
                    ${feature.properties.name}<br>
                    Luas: ${feature.properties.hectare} Ha<br>
                    PIC: ${feature.properties.pic || '-'}
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
    sidebarOpen.value = true;
    
    if (map.value && report.latitude && report.longitude) {
        map.value.setView([report.latitude, report.longitude], 16);
    }
};

const closeSidebar = () => {
    sidebarOpen.value = false;
    selectedReport.value = null;
};

const handleReportClick = (event) => {
    const reportId = event.detail;
    const report = props.reports.find(r => r.id === reportId);
    if (report) {
        selectReport(report);
    }
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
    <AppLayout :title="__('Peta Monitoring')" :user="user">
        <div class="h-[calc(100vh-4rem)] relative">
            <!-- Filter Sidebar -->
            <aside class="fixed inset-y-0 left-0 z-50 w-80 bg-white border-r border-gray-200 transform transition-transform duration-300 lg:translate-x-0 -translate-x-full lg:static lg:relative" :class="{ 'translate-x-0': sidebarOpen }">
                <div class="flex flex-col h-full">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Filter</h2>
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div>
                            <label class="label">Kategori Kejadian</label>
                            <select v-model="filterForm.category_id" class="input">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Wilayah / Blok</label>
                            <select v-model="filterForm.block_id" class="input">
                                <option value="">Semua Blok</option>
                                <option v-for="block in blocks" :key="block.id" :value="block.id">{{ block.code }} - {{ block.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.date_from" class="input" />
                        </div>

                        <div>
                            <label class="label">Sampai Tanggal</label>
                            <input type="date" v-model="filterForm.date_to" class="input" />
                        </div>

                        <div>
                            <label class="label">Status</label>
                            <select v-model="filterForm.status" class="input">
                                <option value="">Semua Status</option>
                                <option value="OPEN">Open</option>
                                <option value="ON_PROGRESS">On Progress</option>
                                <option value="CLOSED">Closed</option>
                            </select>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <button @click="applyFilters" class="btn-primary flex-1">Terapkan</button>
                            <button @click="clearFilters" class="btn-outline flex-1">Reset</button>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Legenda</h3>
                            <div class="space-y-2">
                                <div v-for="cat in categories" :key="cat.id" class="flex items-center gap-2 text-sm">
                                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color_code }"></span>
                                    <span>{{ cat.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile overlay -->
                <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="sidebarOpen = false"></div>
            </aside>

            <!-- Map Container -->
            <div ref="mapContainer" class="map-container lg:ml-0" id="map"></div>

            <!-- Report Detail Sidebar -->
            <aside v-if="selectedReport" class="fixed inset-y-0 right-0 z-50 w-96 bg-white border-l border-gray-200 transform transition-transform duration-300 translate-x-full lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen }">
                <div class="flex flex-col h-full">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Detail Laporan</h2>
                        <button @click="closeSidebar" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ selectedReport.title }}</h3>
                            <span :class="[
                                'badge',
                                selectedReport.status === 'OPEN' ? 'badge-open' :
                                selectedReport.status === 'ON_PROGRESS' ? 'badge-progress' :
                                'badge-closed'
                            ]" class="ml-2">
                                {{ selectedReport.status === 'OPEN' ? 'Open' : selectedReport.status === 'ON_PROGRESS' ? 'On Progress' : 'Closed' }}
                            </span>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kategori</span>
                                <span class="font-medium flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: getCategoryColor(selectedReport.category_id) }"></span>
                                    {{ selectedReport.category?.name }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Blok</span>
                                <span class="font-medium">{{ selectedReport.block_code || selectedReport.block?.code || '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Koordinat</span>
                                <span class="font-medium font-mono">{{ selectedReport.latitude }}, {{ selectedReport.longitude }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pelapor</span>
                                <span class="font-medium">{{ selectedReport.user?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Waktu</span>
                                <span class="font-medium">{{ new Date(selectedReport.reported_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status</span>
                                <span :class="[
                                    'badge',
                                    selectedReport.status === 'OPEN' ? 'badge-open' :
                                    selectedReport.status === 'ON_PROGRESS' ? 'badge-progress' :
                                    'badge-closed'
                                ]">
                                    {{ selectedReport.status === 'OPEN' ? 'Open' : selectedReport.status === 'ON_PROGRESS' ? 'On Progress' : 'Closed' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedReport.description" class="pt-4 border-t border-gray-100">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Deskripsi</h4>
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ selectedReport.description }}</p>
                        </div>

                        <div v-if="selectedReport.photo_url" class="pt-4 border-t border-gray-100">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Foto Bukti</h4>
                            <img :src="selectedReport.photo_url" alt="Foto bukti" class="w-full rounded-lg max-h-64 object-cover" />
                        </div>

                        <div v-if="selectedReport.checklist_answers && Object.keys(selectedReport.checklist_answers).length > 0" class="pt-4 border-t border-gray-100">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Checklist</h4>
                            <div class="space-y-1">
                                <div v-for="(value, key) in selectedReport.checklist_answers" :key="key" class="text-sm">
                                    <span class="text-gray-600">{{ key }}:</span>
                                    <span class="font-medium ml-2">{{ value }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="user.is_admin" class="pt-4 border-t border-gray-100 space-y-3">
                            <h4 class="text-sm font-medium text-gray-900">Ubah Status</h4>
                            <select v-model="selectedReport.status" class="input" @change="updateStatus">
                                <option value="OPEN">Open</option>
                                <option value="ON_PROGRESS">On Progress</option>
                                <option value="CLOSED">Closed</option>
                            </select>
                            <textarea v-model="selectedReport.admin_note" placeholder="Catatan admin (opsional)" class="input h-20" rows="3"></textarea>
                            <button @click="saveStatus" class="btn-primary w-full">Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile map controls -->
            <div class="fixed bottom-24 left-4 right-4 lg:hidden z-30 flex justify-center gap-2">
                <button @click="sidebarOpen = !sidebarOpen" class="btn-primary shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
            </div>
        </div>
    </AppLayout>
</template>