<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const datasets = ref([]);
const datasetId = ref(null);
const dataset = computed(() => datasets.value.find(d => d.id === datasetId.value));
const rows = ref([]);
const meta = ref({});
const loading = ref(false);

const showForm = ref(false);
const editing = ref(null);
const form = reactive({ id: null, label: '', x_value: '', y_value: '' });
const errors = ref({});

const showBulk = ref(false);
const bulkText = ref('');
const bulkErr = ref('');

async function loadDatasets() {
  const { data } = await axios.get('/datasets');
  datasets.value = data.data || [];
  if (!datasetId.value && datasets.value.length) datasetId.value = datasets.value[0].id;
}
async function loadRows() {
  if (!datasetId.value) { rows.value = []; return; }
  loading.value = true;
  try {
    const { data } = await axios.get('/data-rows', { params: { dataset_id: datasetId.value, per_page: 200 } });
    rows.value = data.data || [];
    meta.value = data.meta || {};
  } finally { loading.value = false; }
}
watch(datasetId, loadRows);

function reset() { Object.assign(form, { id: null, label: '', x_value: '', y_value: '' }); errors.value = {}; }
function openCreate() { editing.value = null; reset(); showForm.value = true; }
function openEdit(r) {
  editing.value = r.id; errors.value = {};
  Object.assign(form, { id: r.id, label: r.label || '', x_value: r.x_value, y_value: r.y_value });
  showForm.value = true;
}
async function save() {
  errors.value = {};
  try {
    const payload = {
      dataset_id: datasetId.value,
      label: form.label || null,
      x_value: parseFloat(form.x_value),
      y_value: parseFloat(form.y_value),
    };
    if (editing.value) await axios.put(`/data-rows/${editing.value}`, payload);
    else                await axios.post('/data-rows', payload);
    showForm.value = false; await loadRows();
  } catch (e) { errors.value = e?.response?.data?.errors || { _: [e?.response?.data?.message || 'Gagal'] }; }
}
async function destroy(r) {
  if (!confirm(`Hapus baris #${r.id}?`)) return;
  await axios.delete(`/data-rows/${r.id}`);
  await loadRows();
}
async function bulkImport() {
  bulkErr.value = '';
  const lines = bulkText.value.split('\n').map(l => l.trim()).filter(Boolean);
  const parsed = [];
  for (const [i, line] of lines.entries()) {
    const parts = line.split(/[,;\t]/).map(s => s.trim());
    if (parts.length < 2) { bulkErr.value = `Baris ${i+1}: minimal 2 kolom (X, Y) atau 3 (Label, X, Y).`; return; }
    let label, x, y;
    if (parts.length === 2) { label = null; x = parts[0]; y = parts[1]; }
    else                    { label = parts[0] || null; x = parts[1]; y = parts[2]; }
    const xn = parseFloat(x), yn = parseFloat(y);
    if (Number.isNaN(xn) || Number.isNaN(yn)) { bulkErr.value = `Baris ${i+1}: nilai X/Y bukan angka valid.`; return; }
    parsed.push({ label, x_value: xn, y_value: yn });
  }
  if (!parsed.length) { bulkErr.value = 'Tidak ada baris.'; return; }
  try {
    await axios.post('/data-rows/bulk', { dataset_id: datasetId.value, rows: parsed });
    showBulk.value = false; bulkText.value = ''; await loadRows();
  } catch (e) { bulkErr.value = e?.response?.data?.message || 'Gagal mengimpor.'; }
}

onMounted(loadDatasets);
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Data Master</h1>
      <p class="page-sub">Pasangan nilai (X, Y) untuk setiap dataset.</p>
    </div>
    <div class="flex gap-2">
      <button class="btn-secondary" :disabled="!datasetId" @click="showBulk = true; bulkErr=''">⤴ Import Massal</button>
      <button class="btn-primary"   :disabled="!datasetId" @click="openCreate">+ Tambah Baris</button>
    </div>
  </div>

  <section class="surface p-4 mb-5">
    <label class="label">Pilih Dataset</label>
    <select v-model="datasetId" class="input">
      <option v-for="d in datasets" :key="d.id" :value="d.id">{{ d.id }} · {{ d.name }}</option>
    </select>
    <div v-if="dataset" class="mt-3 text-sm text-ink-600 flex flex-wrap gap-2">
      <span class="chip-sky">X = {{ dataset.x_label }}{{ dataset.x_unit ? ' ('+dataset.x_unit+')' : '' }}</span>
      <span class="chip-plum">Y = {{ dataset.y_label }}{{ dataset.y_unit ? ' ('+dataset.y_unit+')' : '' }}</span>
      <span class="chip">{{ rows.length }} baris</span>
    </div>
  </section>

  <section class="surface overflow-hidden">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Label</th>
          <th>X · {{ dataset?.x_label || 'X' }}</th>
          <th>Y · {{ dataset?.y_label || 'Y' }}</th>
          <th class="text-right">Aksi</th></tr>
      </thead>
      <tbody>
        <tr v-if="!rows.length"><td colspan="5" class="text-center py-8 text-ink-500">{{ loading ? 'Memuat…' : 'Belum ada baris.' }}</td></tr>
        <tr v-for="(r, i) in rows" :key="r.id">
          <td class="text-ink-500">{{ i+1 }}</td>
          <td>{{ r.label || '—' }}</td>
          <td class="font-mono">{{ Number(r.x_value).toLocaleString('id-ID') }}</td>
          <td class="font-mono">{{ Number(r.y_value).toLocaleString('id-ID') }}</td>
          <td class="text-right whitespace-nowrap">
            <button class="btn-ghost btn-sm" @click="openEdit(r)">Edit</button>
            <button class="btn-danger btn-sm" @click="destroy(r)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>

  <!-- Modal form -->
  <div v-if="showForm" class="modal-bg" @click.self="showForm = false">
    <div class="modal-card">
      <div class="modal-head">
        <h3 class="font-display text-lg font-bold">{{ editing ? 'Edit Baris Data' : 'Tambah Baris Data' }}</h3>
        <button class="text-ink-500" @click="showForm = false">✕</button>
      </div>
      <form @submit.prevent="save" class="modal-body space-y-3">
        <div><label class="label">Label / Catatan (opsional)</label>
          <input v-model="form.label" class="input" placeholder="cth: Bulan Jan / Mahasiswa A" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">X — {{ dataset?.x_label }}</label>
            <input v-model="form.x_value" type="number" step="any" required class="input font-mono" />
          </div>
          <div><label class="label">Y — {{ dataset?.y_label }}</label>
            <input v-model="form.y_value" type="number" step="any" required class="input font-mono" />
          </div>
        </div>
        <p v-if="errors._" class="text-rose-600 text-sm">{{ errors._[0] }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          <button class="btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bulk import modal -->
  <div v-if="showBulk" class="modal-bg" @click.self="showBulk = false">
    <div class="modal-card max-w-xl">
      <div class="modal-head">
        <h3 class="font-display text-lg font-bold">Import Massal</h3>
        <button class="text-ink-500" @click="showBulk = false">✕</button>
      </div>
      <div class="modal-body space-y-3">
        <p class="text-sm text-ink-600">Tempel data dengan format <span class="chip">X, Y</span> atau <span class="chip">Label, X, Y</span> per baris (pemisah koma/tab/titik koma).</p>
        <textarea v-model="bulkText" rows="10" class="input font-mono text-xs"
          placeholder="Bln Jan, 2.0, 22&#10;Bln Feb, 2.5, 25&#10;Bln Mar, 3.0, 28"></textarea>
        <div v-if="bulkErr" class="alert-error">{{ bulkErr }}</div>
        <div class="flex justify-end gap-2">
          <button class="btn-secondary" @click="showBulk = false">Batal</button>
          <button class="btn-primary" @click="bulkImport">Import</button>
        </div>
      </div>
    </div>
  </div>
</template>
