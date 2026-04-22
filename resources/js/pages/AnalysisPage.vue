<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Scatter } from 'vue-chartjs';
import { Chart as ChartJS, LinearScale, PointElement, LineElement, Tooltip, Legend, Title, LineController, ScatterController } from 'chart.js';
ChartJS.register(LinearScale, PointElement, LineElement, Tooltip, Legend, Title, LineController, ScatterController);

const datasets = ref([]);
const datasetId = ref(null);
const dataset = computed(() => datasets.value.find(d => d.id === datasetId.value));
const result = ref(null);
const loading = ref(false);
const errMsg = ref('');
const openStep = ref(0);

async function loadDatasets() {
  const { data } = await axios.get('/datasets');
  datasets.value = data.data || [];
  if (!datasetId.value && datasets.value.length) datasetId.value = datasets.value[0].id;
}
async function compute() {
  if (!datasetId.value) return;
  loading.value = true; errMsg.value = ''; result.value = null;
  try {
    const { data } = await axios.post('/regression/compute', { dataset_id: datasetId.value });
    if (data.ok === false) errMsg.value = data.message || 'Gagal menghitung.';
    else result.value = data;
  } catch (e) { errMsg.value = e?.response?.data?.message || 'Gagal menghitung.'; }
  finally { loading.value = false; }
}
watch(datasetId, () => { result.value = null; errMsg.value = ''; });

const chartData = computed(() => {
  if (!result.value) return null;
  const points = result.value.x.map((x, i) => ({ x, y: result.value.y[i] }));
  return {
    datasets: [
      { label: 'Data observasi', data: points, backgroundColor: '#1f5cf5', borderColor: '#1f5cf5', pointRadius: 5, pointHoverRadius: 7 },
      { label: 'Garis trend Ŷ = a + bX', data: result.value.trend, type: 'line', borderColor: '#a855f7', backgroundColor: 'rgba(168,85,247,0.10)', borderWidth: 2.5, pointRadius: 0, fill: false, tension: 0 },
    ],
  };
});
const chartOptions = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { color: '#475569' } } },
  scales: {
    x: { title: { display: true, text: dataset.value ? `${dataset.value.x_label}${dataset.value.x_unit ? ' ('+dataset.value.x_unit+')' : ''}` : 'X', color: '#475569' }, grid: { color: 'rgba(99,102,141,0.10)' } },
    y: { title: { display: true, text: dataset.value ? `${dataset.value.y_label}${dataset.value.y_unit ? ' ('+dataset.value.y_unit+')' : ''}` : 'Y', color: '#475569' }, grid: { color: 'rgba(99,102,141,0.10)' } },
  },
}));

function fmt(v, d=4) { if (v === null || v === undefined || Number.isNaN(v)) return '—'; const n = Number(v); return Math.abs(n) >= 1000 ? n.toLocaleString('id-ID', { maximumFractionDigits: d }) : n.toFixed(d).replace(/\.?0+$/, ''); }

onMounted(loadDatasets);
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Analisis Regresi</h1>
      <p class="page-sub">Hitung persamaan garis terbaik <span class="font-mono">Ŷ = a + bX</span> dengan metode kuadrat terkecil.</p>
    </div>
  </div>

  <section class="surface p-4 mb-5">
    <div class="grid md:grid-cols-[1fr_auto] gap-3 items-end">
      <div>
        <label class="label">Pilih Dataset</label>
        <select v-model="datasetId" class="input">
          <option v-for="d in datasets" :key="d.id" :value="d.id">{{ d.id }} · {{ d.name }}</option>
        </select>
      </div>
      <button class="btn-primary md:w-auto justify-center" :disabled="!datasetId || loading" @click="compute">
        {{ loading ? 'Menghitung…' : '⚡ Hitung Sekarang' }}
      </button>
    </div>
    <div v-if="dataset" class="mt-3 flex flex-wrap gap-2">
      <span class="chip-sky">X = {{ dataset.x_label }}</span>
      <span class="chip-plum">Y = {{ dataset.y_label }}</span>
      <span v-if="dataset.case_label" class="chip">{{ dataset.case_label }}</span>
    </div>
  </section>

  <div v-if="errMsg" class="alert-error mb-5">{{ errMsg }}</div>

  <template v-if="result">
    <div class="equation mb-5">
      <div class="label">Persamaan Garis Regresi</div>
      <div class="formula">Ŷ = {{ fmt(result.intercept) }} + {{ fmt(result.slope) }} · X</div>
      <div class="text-xs opacity-80 mt-2">{{ dataset?.y_label }} = {{ fmt(result.intercept) }} + {{ fmt(result.slope) }} · {{ dataset?.x_label }}</div>
    </div>

    <div class="kpi-grid mb-5">
      <div class="kpi"><div class="icon">R²</div><div><div class="label">R-Squared</div><div class="value">{{ fmt(result.r_squared) }}</div></div></div>
      <div class="kpi"><div class="icon">r</div><div><div class="label">Korelasi (r)</div><div class="value">{{ fmt(result.r) }}</div></div></div>
      <div class="kpi"><div class="icon">σ</div><div><div class="label">RMSE</div><div class="value">{{ fmt(result.rmse) }}</div></div></div>
      <div class="kpi"><div class="icon">|ε|</div><div><div class="label">MAE</div><div class="value">{{ fmt(result.mae) }}</div></div></div>
    </div>

    <div class="grid lg:grid-cols-5 gap-5 mb-5">
      <div class="lg:col-span-3 chart-wrap">
        <h3 class="section-title mb-3">Scatter Plot &amp; Garis Trend</h3>
        <div style="height: 380px"><Scatter v-if="chartData" :data="chartData" :options="chartOptions" /></div>
      </div>
      <div class="lg:col-span-2 surface p-5">
        <h3 class="section-title mb-3">Ringkasan Statistik</h3>
        <dl class="text-sm space-y-2">
          <div class="flex justify-between"><dt class="text-ink-500">n (jumlah data)</dt><dd class="font-mono font-semibold">{{ result.n }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">ΣX</dt><dd class="font-mono">{{ fmt(result.sum_x) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">ΣY</dt><dd class="font-mono">{{ fmt(result.sum_y) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">ΣXY</dt><dd class="font-mono">{{ fmt(result.sum_xy) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">ΣX²</dt><dd class="font-mono">{{ fmt(result.sum_x2) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">ΣY²</dt><dd class="font-mono">{{ fmt(result.sum_y2) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">X̄</dt><dd class="font-mono">{{ fmt(result.mean_x) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">Ȳ</dt><dd class="font-mono">{{ fmt(result.mean_y) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">SST</dt><dd class="font-mono">{{ fmt(result.sst) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">SSR</dt><dd class="font-mono">{{ fmt(result.ssr) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">SSE</dt><dd class="font-mono">{{ fmt(result.sse) }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-500">MSE</dt><dd class="font-mono">{{ fmt(result.mse) }}</dd></div>
        </dl>
      </div>
    </div>

    <h3 class="section-title mb-3">Langkah-langkah Perhitungan</h3>
    <div class="space-y-2 mb-5">
      <div v-for="(step, i) in result.steps" :key="i" class="step">
        <div class="step-head" @click="openStep = openStep === i ? -1 : i">
          <span class="flex items-center gap-3 font-semibold text-ink-900">
            <span class="step-num">{{ i + 1 }}</span>{{ step.title }}
          </span>
          <span class="text-ink-400">{{ openStep === i ? '−' : '+' }}</span>
        </div>
        <div v-if="openStep === i" class="step-body">{{ step.body }}</div>
      </div>
    </div>

    <h3 class="section-title mb-3">Tabel Perhitungan Lengkap</h3>
    <section class="surface overflow-auto mb-5">
      <table class="data-table">
        <thead><tr><th>i</th><th>Label</th><th>X</th><th>Y</th><th>X·Y</th><th>X²</th><th>Y²</th><th>Ŷ</th><th>ε = Y − Ŷ</th></tr></thead>
        <tbody>
          <tr v-for="(d, i) in result.detail" :key="i">
            <td class="text-ink-500">{{ d.i }}</td>
            <td>{{ d.label || '—' }}</td>
            <td class="font-mono">{{ fmt(d.x) }}</td>
            <td class="font-mono">{{ fmt(d.y) }}</td>
            <td class="font-mono">{{ fmt(d.xy) }}</td>
            <td class="font-mono">{{ fmt(d.x2) }}</td>
            <td class="font-mono">{{ fmt(d.y2) }}</td>
            <td class="font-mono text-sky2-700">{{ fmt(result.y_hat[i]) }}</td>
            <td class="font-mono" :class="result.residual[i] >= 0 ? 'text-emerald-700' : 'text-rose-700'">{{ fmt(result.residual[i]) }}</td>
          </tr>
          <tr class="bg-sky2-50 font-bold">
            <td colspan="2" class="text-right">Σ</td>
            <td class="font-mono">{{ fmt(result.sum_x) }}</td>
            <td class="font-mono">{{ fmt(result.sum_y) }}</td>
            <td class="font-mono">{{ fmt(result.sum_xy) }}</td>
            <td class="font-mono">{{ fmt(result.sum_x2) }}</td>
            <td class="font-mono">{{ fmt(result.sum_y2) }}</td>
            <td colspan="2"></td>
          </tr>
        </tbody>
      </table>
    </section>
  </template>
</template>
