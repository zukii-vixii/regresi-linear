<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Scatter } from 'vue-chartjs';
import { Chart as ChartJS, LinearScale, PointElement, LineElement, Tooltip, Legend, LineController, ScatterController } from 'chart.js';
ChartJS.register(LinearScale, PointElement, LineElement, Tooltip, Legend, LineController, ScatterController);

const datasets = ref([]);
const datasetId = ref(null);
const dataset = computed(() => datasets.value.find(d => d.id === datasetId.value));
const xInput = ref('');
const result = ref(null);
const fit = ref(null);
const errMsg = ref('');
const loading = ref(false);
const history = ref([]);

async function loadDatasets() {
  const { data } = await axios.get('/datasets');
  datasets.value = data.data || [];
  if (!datasetId.value && datasets.value.length) datasetId.value = datasets.value[0].id;
}
async function loadFit() {
  if (!datasetId.value) return;
  fit.value = null;
  try {
    const { data } = await axios.post('/regression/compute', { dataset_id: datasetId.value });
    if (data.ok !== false) fit.value = data;
  } catch {}
}
async function loadHistory() {
  const { data } = await axios.get('/regression/history', { params: { dataset_id: datasetId.value, per_page: 10 } });
  history.value = data.data || [];
}
async function predict() {
  errMsg.value = ''; result.value = null;
  if (xInput.value === '' || xInput.value === null) { errMsg.value = 'Masukkan nilai X.'; return; }
  loading.value = true;
  try {
    const { data } = await axios.post('/regression/predict', { dataset_id: datasetId.value, x: parseFloat(xInput.value) });
    if (data.ok === false) errMsg.value = data.message || 'Gagal memprediksi.';
    else { result.value = data; await loadHistory(); }
  } catch (e) { errMsg.value = e?.response?.data?.message || 'Gagal memprediksi.'; }
  finally { loading.value = false; }
}

watch(datasetId, async () => {
  result.value = null; errMsg.value = '';
  await loadFit(); await loadHistory();
});

const chartData = computed(() => {
  if (!fit.value) return null;
  const points = fit.value.x.map((x, i) => ({ x, y: fit.value.y[i] }));
  const sets = [
    { label: 'Data observasi', data: points, backgroundColor: '#1f5cf5', pointRadius: 5 },
    { label: 'Garis trend', data: fit.value.trend, type: 'line', borderColor: '#a855f7', borderWidth: 2.5, pointRadius: 0, tension: 0, fill: false },
  ];
  if (result.value) {
    sets.push({ label: 'Prediksi', data: [{ x: Number(result.value.x), y: Number(result.value.y_predicted) }],
      backgroundColor: '#f59e0b', borderColor: '#f59e0b', pointRadius: 9, pointStyle: 'rectRot' });
  }
  return { datasets: sets };
});
const chartOpts = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { color: '#475569' } } },
  scales: {
    x: { title: { display: true, text: dataset.value?.x_label || 'X', color: '#475569' }, grid: { color: 'rgba(99,102,141,0.10)' } },
    y: { title: { display: true, text: dataset.value?.y_label || 'Y', color: '#475569' }, grid: { color: 'rgba(99,102,141,0.10)' } },
  },
}));

function fmt(v, d=4) { if (v === null || v === undefined || Number.isNaN(v)) return '—'; const n = Number(v); return Math.abs(n) >= 1000 ? n.toLocaleString('id-ID', { maximumFractionDigits: d }) : n.toFixed(d).replace(/\.?0+$/, ''); }

onMounted(async () => { await loadDatasets(); await loadFit(); await loadHistory(); });
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Prediksi</h1>
      <p class="page-sub">Masukkan nilai X baru untuk memprediksi nilai Ŷ berdasarkan model regresi.</p>
    </div>
  </div>

  <section class="surface p-5 mb-5">
    <div class="grid md:grid-cols-3 gap-3">
      <div>
        <label class="label">Dataset</label>
        <select v-model="datasetId" class="input">
          <option v-for="d in datasets" :key="d.id" :value="d.id">{{ d.id }} · {{ d.name }}</option>
        </select>
      </div>
      <div>
        <label class="label">Nilai X — {{ dataset?.x_label }}</label>
        <input v-model="xInput" type="number" step="any" class="input font-mono" placeholder="cth: 6.5" @keyup.enter="predict" />
      </div>
      <div class="flex items-end">
        <button class="btn-primary w-full justify-center" :disabled="!datasetId || loading" @click="predict">
          {{ loading ? 'Memproses…' : '◎ Prediksi' }}
        </button>
      </div>
    </div>
    <div v-if="fit" class="mt-3 text-sm text-ink-600 flex flex-wrap items-center gap-2">
      Model: <span class="chip-sky font-mono">Ŷ = {{ fmt(fit.intercept) }} + {{ fmt(fit.slope) }} · X</span>
      <span class="chip-plum">R² = {{ fmt(fit.r_squared) }}</span>
      <span class="chip">n = {{ fit.n }}</span>
    </div>
  </section>

  <div v-if="errMsg" class="alert-error mb-5">{{ errMsg }}</div>

  <div v-if="result" class="grid lg:grid-cols-2 gap-5 mb-5">
    <div class="equation">
      <div class="label">Hasil Prediksi</div>
      <div class="formula">Ŷ = {{ fmt(result.y_predicted) }}</div>
      <div class="text-sm opacity-90 mt-3 font-mono">{{ result.substitution }}</div>
      <div class="text-xs opacity-80 mt-2">{{ dataset?.y_label }}{{ dataset?.y_unit ? ' ('+dataset.y_unit+')' : '' }} ketika {{ dataset?.x_label }} = {{ result.x }}</div>
    </div>
    <div class="chart-wrap">
      <h3 class="section-title mb-3">Visualisasi</h3>
      <div style="height: 320px"><Scatter v-if="chartData" :data="chartData" :options="chartOpts" /></div>
    </div>
  </div>

  <div v-if="!result && fit" class="chart-wrap mb-5">
    <h3 class="section-title mb-3">Scatter &amp; Garis Trend</h3>
    <div style="height: 320px"><Scatter v-if="chartData" :data="chartData" :options="chartOpts" /></div>
  </div>

  <section class="surface overflow-hidden">
    <div class="px-4 py-3 border-b border-ink-100 flex items-center justify-between">
      <h3 class="section-title">Riwayat Prediksi</h3>
      <span class="chip">{{ history.length }} terbaru</span>
    </div>
    <table class="data-table">
      <thead><tr><th>#</th><th>Dataset</th><th>X</th><th>Ŷ</th><th>R²</th><th>Pengguna</th><th>Waktu</th></tr></thead>
      <tbody>
        <tr v-if="!history.length"><td colspan="7" class="text-center py-6 text-ink-500">Belum ada prediksi.</td></tr>
        <tr v-for="h in history" :key="h.id">
          <td class="text-ink-500">{{ h.id }}</td>
          <td>{{ h.dataset?.name || '—' }}</td>
          <td class="font-mono">{{ fmt(h.x_input) }}</td>
          <td class="font-mono font-semibold text-sky2-700">{{ fmt(h.y_predicted) }}</td>
          <td class="font-mono">{{ fmt(h.r_squared) }}</td>
          <td>{{ h.user?.name || '—' }}</td>
          <td class="text-xs text-ink-500">{{ new Date(h.created_at).toLocaleString('id-ID') }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
