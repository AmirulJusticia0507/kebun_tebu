<script setup>
import { nextTick, onBeforeUnmount, ref } from "vue";
import { useForm, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import L from "leaflet";

defineProps({
  user: Object,
  blocks: Array,
});

const showModal = ref(false);
const editingBlock = ref(null);
const boundaryMapContainer = ref(null);
const boundaryPoints = ref([]);
let boundaryMap = null;
let boundaryLayer = null;

const form = useForm({
  code: "",
  name: "",
  hectare: "",
  pic_user_id: "",
  is_active: true,
  polygon: null,
});

const syncPolygon = () => {
  if (boundaryLayer && boundaryMap) boundaryMap.removeLayer(boundaryLayer);
  boundaryLayer = null;

  if (boundaryPoints.value.length >= 3) {
    boundaryLayer = L.polygon(boundaryPoints.value, {
      color: "#10b981",
      weight: 3,
      fillColor: "#10b981",
      fillOpacity: 0.25,
    }).addTo(boundaryMap);

    const coordinates = boundaryPoints.value.map(({ lat, lng }) => [lng, lat]);
    coordinates.push([...coordinates[0]]);
    form.polygon = { type: "Polygon", coordinates: [coordinates] };
  } else {
    if (boundaryPoints.value.length) {
      boundaryLayer = L.polyline(boundaryPoints.value, {
        color: "#10b981",
        weight: 3,
        dashArray: "6 6",
      }).addTo(boundaryMap);
    }
    form.polygon = null;
  }
};

const initBoundaryMap = async () => {
  await nextTick();
  if (!boundaryMapContainer.value) return;

  boundaryMap?.remove();
  boundaryMap = L.map(boundaryMapContainer.value, {
    center: [-7.7956, 110.3695],
    zoom: 15,
  });

  L.tileLayer(
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
    { attribution: "Tiles &copy; Esri", maxZoom: 19 },
  ).addTo(boundaryMap);

  boundaryMap.on("click", ({ latlng }) => {
    boundaryPoints.value.push(latlng);
    syncPolygon();
  });

  syncPolygon();
  if (boundaryPoints.value.length >= 3) {
    boundaryMap.fitBounds(L.latLngBounds(boundaryPoints.value), {
      padding: [24, 24],
    });
  }
};

const resetBoundary = () => {
  boundaryPoints.value = [];
  syncPolygon();
};

const undoBoundaryPoint = () => {
  boundaryPoints.value.pop();
  boundaryPoints.value = [...boundaryPoints.value];
  syncPolygon();
};

const closeModal = () => {
  showModal.value = false;
  boundaryMap?.remove();
  boundaryMap = null;
  boundaryLayer = null;
};

const openCreate = () => {
  editingBlock.value = null;
  form.reset();
  form.is_active = true;
  boundaryPoints.value = [];
  showModal.value = true;
  initBoundaryMap();
};

const openEdit = (block) => {
  editingBlock.value = block;
  form.code = block.code;
  form.name = block.name;
  form.hectare = block.hectare || "";
  form.pic_user_id = block.pic_user_id || "";
  form.is_active = block.is_active;
  form.polygon = block.polygon || null;
  boundaryPoints.value = (block.polygon?.coordinates?.[0] || [])
    .slice(0, -1)
    .map(([lng, lat]) => L.latLng(lat, lng));
  showModal.value = true;
  initBoundaryMap();
};

const submit = () => {
  if (editingBlock.value) {
    form.put(route("admin.blocks.update", editingBlock.value.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("admin.blocks.store"), {
      onSuccess: () => {
        closeModal();
        form.reset();
      },
    });
  }
};

const toggleActive = (block) => {
  router.delete(route("admin.blocks.destroy", block.id));
};

onBeforeUnmount(() => boundaryMap?.remove());
</script>

<template>
  <AppLayout title="Blok Kebun" :user="user">
    <Head><title>Manajemen Blok Kebun - Kebun Tebu</title></Head>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Blok / Wilayah Kebun</h1>
          <p class="text-gray-600 mt-1">Kelola data blok perkebunan</p>
        </div>
        <button @click="openCreate" class="btn-primary">+ Tambah Blok</button>
      </div>

      <div class="card overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Kode
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Nama Blok
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Luas (Ha)
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                PIC
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Laporan
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Status
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="block in blocks"
              :key="block.id"
              class="hover:bg-gray-50"
            >
              <td class="px-4 py-3 font-mono font-medium text-gray-900">
                {{ block.code }}
              </td>
              <td class="px-4 py-3 text-gray-900">{{ block.name }}</td>
              <td class="px-4 py-3 text-gray-600">
                {{ block.hectare ? `${block.hectare} Ha` : "-" }}
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ block.pic?.name || "-" }}
              </td>
              <td class="px-4 py-3 text-gray-600">{{ block.reports_count }}</td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    block.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-500',
                  ]"
                >
                  {{ block.is_active ? "Aktif" : "Nonaktif" }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button
                    @click="openEdit(block)"
                    class="text-sm text-blue-600 hover:underline"
                  >
                    Edit
                  </button>
                  <button
                    @click="toggleActive(block)"
                    class="text-sm text-yellow-600 hover:underline"
                  >
                    {{ block.is_active ? "Nonaktifkan" : "Aktifkan" }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="blocks.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                Belum ada blok
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    >
      <div class="card max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">
            {{ editingBlock ? "Edit Blok" : "Tambah Blok Baru" }}
          </h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            ✕
          </button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <div>
            <label class="label">Kode Blok</label
            ><input
              type="text"
              v-model="form.code"
              class="input font-mono"
              placeholder="BLOK-A12"
              required
            />
          </div>
          <div>
            <label class="label">Nama Blok</label
            ><input type="text" v-model="form.name" class="input" required />
          </div>
          <div>
            <label class="label">Luas (Hektar)</label
            ><input
              type="number"
              step="0.01"
              v-model="form.hectare"
              class="input"
            />
          </div>
          <div>
            <div class="mb-2 flex items-center justify-between gap-3">
              <div>
                <label class="label">Batas Area Kebun</label>
                <p class="text-xs text-gray-500">
                  Klik minimal 3 titik sudut pada peta untuk membentuk area.
                </p>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="text-xs font-semibold text-amber-600 hover:underline disabled:opacity-40"
                  :disabled="!boundaryPoints.length"
                  @click="undoBoundaryPoint"
                >
                  Urungkan
                </button>
                <button
                  type="button"
                  class="text-xs font-semibold text-rose-600 hover:underline disabled:opacity-40"
                  :disabled="!boundaryPoints.length"
                  @click="resetBoundary"
                >
                  Reset
                </button>
              </div>
            </div>
            <div
              ref="boundaryMapContainer"
              class="h-64 w-full overflow-hidden rounded-xl border border-gray-300"
            ></div>
            <p
              class="mt-2 text-xs"
              :class="
                boundaryPoints.length >= 3
                  ? 'text-emerald-600'
                  : 'text-gray-500'
              "
            >
              {{ boundaryPoints.length }} titik dipilih
              <span v-if="boundaryPoints.length >= 3"
                >— area siap disimpan</span
              >
            </p>
          </div>
          <div class="flex gap-2 pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="btn-primary flex-1"
            >
              Simpan
            </button>
            <button
              type="button"
              @click="closeModal"
              class="btn-secondary flex-1"
            >
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
