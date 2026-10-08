<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useForm, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { deleteDraft, getDrafts, saveDraft } from "@/Utils/offlineDb.js";

const props = defineProps({
  user: Object,
  categories: Array,
  blocks: Array,
});

const gpsLoading = ref(false);
const gpsError = ref("");
const isOnline = ref(navigator.onLine);
const pendingDrafts = ref(0);
const syncMessage = ref("");
const syncing = ref(false);
const submitting = ref(false);
let watchId = null;

const form = useForm({
  category_id: "",
  block_id: "",
  block_code: "",
  title: "",
  description: "",
  latitude: "",
  longitude: "",
  photo: null,
  checklist_answers: {},
});

// GPS location
const getLocation = () => {
  if (!navigator.geolocation) {
    gpsError.value = "Browser tidak mendukung GPS.";
    return;
  }
  gpsLoading.value = true;
  gpsError.value = "";
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.latitude = pos.coords.latitude.toFixed(8);
      form.longitude = pos.coords.longitude.toFixed(8);
      gpsLoading.value = false;
    },
    (err) => {
      gpsError.value = "Gagal mendapatkan lokasi: " + err.message;
      gpsLoading.value = false;
    },
    { enableHighAccuracy: true, timeout: 10000 },
  );
};

// Watch GPS continuously
const startGpsWatch = () => {
  if (!navigator.geolocation) return;
  watchId = navigator.geolocation.watchPosition(
    (pos) => {
      form.latitude = pos.coords.latitude.toFixed(8);
      form.longitude = pos.coords.longitude.toFixed(8);
    },
    () => {},
    { enableHighAccuracy: true },
  );
};

const refreshDraftCount = async () => {
  pendingDrafts.value = (await getDrafts()).filter(
    (draft) => draft.owner_id === props.user.id,
  ).length;
};

const draftPayload = () => ({
  client_uuid: crypto.randomUUID(),
  owner_id: props.user.id,
  category_id: form.category_id,
  block_id: form.block_id || null,
  block_code: form.block_code || null,
  title: form.title,
  description: form.description || null,
  latitude: form.latitude,
  longitude: form.longitude,
  photo: form.photo,
  checklist_answers: form.checklist_answers,
});

const syncDrafts = async () => {
  if (!navigator.onLine || syncing.value) return;

  syncing.value = true;
  syncMessage.value = "";
  let synced = 0;

  for (const draft of (await getDrafts()).filter(
    (item) => item.owner_id === props.user.id,
  )) {
    const payload = new FormData();
    for (const [key, value] of Object.entries(draft)) {
      if (
        ["id", "owner_id", "created_at", "checklist_answers"].includes(key) ||
        value === null ||
        value === ""
      )
        continue;
      payload.append(key, value);
    }
    for (const [key, value] of Object.entries(draft.checklist_answers || {})) {
      payload.append(`checklist_answers[${key}]`, value);
    }

    try {
      await window.axios.post(route("reports.store"), payload);
      await deleteDraft(draft.id);
      synced++;
    } catch (error) {
      if (!error.response) break;
      syncMessage.value = `Draft gagal disinkronkan: ${error.response.data?.message || "data tidak valid"}`;
      break;
    }
  }

  await refreshDraftCount();
  if (synced)
    syncMessage.value = `${synced} laporan offline berhasil disinkronkan.`;
  syncing.value = false;
};

const handleOnline = async () => {
  isOnline.value = true;
  await syncDrafts();
};

const handleOffline = () => {
  isOnline.value = false;
};

onMounted(async () => {
  getLocation();
  startGpsWatch();
  await refreshDraftCount();
  window.addEventListener("online", handleOnline);
  window.addEventListener("offline", handleOffline);
  await syncDrafts();
});

onBeforeUnmount(() => {
  if (watchId !== null) navigator.geolocation.clearWatch(watchId);
  window.removeEventListener("online", handleOnline);
  window.removeEventListener("offline", handleOffline);
});

// Photo handler
const onPhotoChange = (e) => {
  form.photo = e.target.files[0] ?? null;
};

// Submit
const saveOffline = async () => {
  await saveDraft(draftPayload());
  await refreshDraftCount();
  syncMessage.value =
    "Laporan disimpan di perangkat dan akan dikirim saat online.";
  form.reset();
};

const submit = async () => {
  form.clearErrors();
  if (!navigator.onLine) {
    await saveOffline();
    return;
  }

  submitting.value = true;
  const draft = draftPayload();
  const payload = new FormData();
  for (const [key, value] of Object.entries(draft)) {
    if (
      ["owner_id", "checklist_answers"].includes(key) ||
      value === null ||
      value === ""
    )
      continue;
    payload.append(key, value);
  }
  for (const [key, value] of Object.entries(draft.checklist_answers || {})) {
    payload.append(`checklist_answers[${key}]`, value);
  }

  try {
    await window.axios.post(route("reports.store"), payload);
    window.location.href = route("map");
  } catch (error) {
    if (!error.response) {
      await saveDraft(draft);
      await refreshDraftCount();
      syncMessage.value = "Koneksi terputus. Laporan disimpan di perangkat.";
      form.reset();
    } else if (error.response.status === 422) {
      form.setError(error.response.data.errors || {});
    } else {
      syncMessage.value =
        error.response.data?.message || "Laporan gagal dikirim.";
    }
  } finally {
    submitting.value = false;
  }
};

const selectedCategory = ref(null);
const updateCategory = () => {
  selectedCategory.value =
    props.categories?.find((c) => c.id == form.category_id) ?? null;
  form.checklist_answers = {};
};
</script>

<template>
  <AppLayout title="Buat Laporan" :user="user">
    <Head><title>Buat Laporan Lapangan - Kebun Tebu</title></Head>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 space-y-6">
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3 text-sm"
        :class="
          isOnline
            ? 'border-emerald-700/60 bg-emerald-950/40 text-emerald-300'
            : 'border-amber-700/60 bg-amber-950/40 text-amber-300'
        "
      >
        <span>{{
          isOnline ? "Online" : "Offline — laporan akan disimpan di perangkat"
        }}</span>
        <button
          v-if="pendingDrafts"
          type="button"
          class="font-semibold underline disabled:opacity-50"
          :disabled="!isOnline || syncing"
          @click="syncDrafts"
        >
          {{
            syncing ? "Menyinkronkan..." : `Sinkronkan ${pendingDrafts} draft`
          }}
        </button>
      </div>
      <p v-if="syncMessage" class="text-sm text-sky-300">{{ syncMessage }}</p>

      <!-- Page Header -->
      <div
        class="flex items-center justify-between glass-panel px-6 py-4 rounded-2xl border border-slate-800"
      >
        <div>
          <h1 class="text-xl font-bold text-slate-100">
            📝 Buat Laporan Lapangan
          </h1>
          <p class="text-xs text-slate-400 mt-0.5">
            Laporan akan langsung muncul di peta GIS monitoring
          </p>
        </div>
        <Link
          :href="route('reports.index')"
          class="text-slate-400 hover:text-slate-200 transition-colors"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </Link>
      </div>

      <form
        @submit.prevent="submit"
        class="space-y-5"
        enctype="multipart/form-data"
      >
        <!-- Kategori -->
        <div
          class="glass-panel p-5 rounded-2xl border border-slate-800 space-y-4"
        >
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">
            📁 Informasi Laporan
          </h2>

          <div>
            <label class="label"
              >Kategori Kejadian <span class="text-rose-400">*</span></label
            >
            <select
              v-model="form.category_id"
              @change="updateCategory"
              class="input"
              required
            >
              <option value="">— Pilih Kategori —</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
            <p
              v-if="form.errors.category_id"
              class="mt-1 text-xs text-rose-400"
            >
              {{ form.errors.category_id }}
            </p>
          </div>

          <div>
            <label class="label"
              >Judul Kejadian <span class="text-rose-400">*</span></label
            >
            <input
              type="text"
              v-model="form.title"
              class="input"
              placeholder="Contoh: Hama wereng di blok B3"
              required
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-rose-400">
              {{ form.errors.title }}
            </p>
          </div>

          <div>
            <label class="label">Deskripsi / Kronologi</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="input"
              placeholder="Tuliskan kronologi kejadian secara ringkas..."
            ></textarea>
          </div>

          <!-- Block -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Blok Kebun</label>
              <select v-model="form.block_id" class="input">
                <option value="">— Pilih Blok —</option>
                <option v-for="blk in blocks" :key="blk.id" :value="blk.id">
                  {{ blk.code }} {{ blk.name ? "- " + blk.name : "" }}
                </option>
              </select>
            </div>
            <div>
              <label class="label">Kode Blok Manual</label>
              <input
                type="text"
                v-model="form.block_code"
                class="input"
                placeholder="Contoh: BLK-A12"
              />
            </div>
          </div>
        </div>

        <!-- GPS Lokasi -->
        <div
          class="glass-panel p-5 rounded-2xl border border-slate-800 space-y-3"
        >
          <div class="flex items-center justify-between">
            <h2
              class="text-sm font-bold text-slate-300 uppercase tracking-wider"
            >
              📍 Lokasi GPS
            </h2>
            <button
              type="button"
              @click="getLocation"
              :disabled="gpsLoading"
              class="btn btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5"
            >
              <svg
                v-if="gpsLoading"
                class="w-3.5 h-3.5 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
              <svg
                v-else
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              {{ gpsLoading ? "Mencari..." : "Perbarui GPS" }}
            </button>
          </div>

          <p v-if="gpsError" class="text-xs text-rose-400">⚠️ {{ gpsError }}</p>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label"
                >Latitude <span class="text-rose-400">*</span></label
              >
              <input
                type="number"
                step="any"
                v-model="form.latitude"
                class="input font-mono text-xs"
                placeholder="-6.12345678"
                required
              />
              <p v-if="form.errors.latitude" class="mt-1 text-xs text-rose-400">
                {{ form.errors.latitude }}
              </p>
            </div>
            <div>
              <label class="label"
                >Longitude <span class="text-rose-400">*</span></label
              >
              <input
                type="number"
                step="any"
                v-model="form.longitude"
                class="input font-mono text-xs"
                placeholder="106.12345678"
                required
              />
              <p
                v-if="form.errors.longitude"
                class="mt-1 text-xs text-rose-400"
              >
                {{ form.errors.longitude }}
              </p>
            </div>
          </div>

          <div
            v-if="form.latitude && form.longitude"
            class="text-xs text-emerald-400 flex items-center gap-1.5"
          >
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            Koordinat terdeteksi: {{ form.latitude }}, {{ form.longitude }}
          </div>
        </div>

        <!-- Foto Bukti -->
        <div
          class="glass-panel p-5 rounded-2xl border border-slate-800 space-y-3"
        >
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">
            📸 Foto Bukti
          </h2>
          <input
            type="file"
            accept="image/jpeg,image/png,image/jpg,image/webp"
            @change="onPhotoChange"
            class="block w-full text-sm text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border file:border-slate-700 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 cursor-pointer"
          />
          <p class="text-xs text-slate-500">Maksimum 5MB · JPEG, PNG, WebP</p>
          <p v-if="form.errors.photo" class="text-xs text-rose-400">
            {{ form.errors.photo }}
          </p>
        </div>

        <!-- Submit -->
        <div class="flex gap-3 pt-2">
          <button
            type="submit"
            :disabled="submitting"
            class="btn-primary flex-1 py-3 text-base font-bold flex items-center justify-center gap-2"
          >
            <svg
              v-if="submitting"
              class="w-4 h-4 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
            <svg
              v-else
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
              />
            </svg>
            {{ submitting ? "Mengirim Laporan..." : "Kirim Laporan" }}
          </button>
          <Link
            :href="route('reports.index')"
            class="btn-secondary py-3 px-5 font-semibold"
            >Batal</Link
          >
        </div>
      </form>
    </div>
  </AppLayout>
</template>
