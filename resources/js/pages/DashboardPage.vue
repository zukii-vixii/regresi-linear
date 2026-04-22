<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { RouterLink } from 'vue-router';

const stats = ref({ datasets: 0, data_rows: 0, predictions: 0, users: 0, recent_predictions: [], datasets_summary: [] });
const loading = ref(true);

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/stats');
    stats.value = data;
  } finally { loading.value = false; }
}
onMounted(load);
</script>

<template>
  <section class="hero mb-6">
    <div class="relative z-10 grid md:grid-cols-3 gap-6 items-center">
      <div class="md:col-span-2">
        <div class="hero-eyebrow">Aplikasi Metode Regresi Linear Sederhana</div>
        <h1>Prediksi Y dari satu variabel X — visual, transparan, &amp; mudah.</h1>
        <p>Hitung intercept (a) &amp; slope (b) dari rumus klasik <span class="font-mono opacity-100">b = (n·ΣXY − ΣX·ΣY) / (n·ΣX² − (ΣX)²)</span>, lihat scatter + trend, dan prediksi nilai Y untuk X baru.</p>
        <div class="hero-actions">
          <RouterLink to="/analisis" class="primary">⚡ Mulai Analisis</RouterLink>
          <RouterLink to="/datasets">▦ Kelola Dataset</RouterLink>
          <RouterLink to="/data">☰ Input Data</RouterLink>
          <RouterLink to="/prediksi">◎ Prediksi</RouterLink>
        </div>
      </div>
      <div class="hidden md:block">
        <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
          <div class="text-5xl font-display font-extrabold leading-none">Ŷ = a + bX</div>
          <div class="text-xs uppercase tracking-[0.2em] opacity-80 mt-2">Persamaan Garis Regresi</div>
        </div>
      </div>
    </div>
  </section>

  <div class="kpi-grid mb-6">
    <div class="kpi"><div class="icon">▦</div><div><div class="label">Dataset</div><div class="value">{{ stats.datasets }}</div></div></div>
    <div class="kpi"><div class="icon">☰</div><div><div class="label">Data Master</div><div class="value">{{ stats.data_rows }}</div></div></div>
    <div class="kpi"><div class="icon">◎</div><div><div class="label">Prediksi</div><div class="value">{{ stats.predictions }}</div></div></div>
    <div class="kpi"><div class="icon">◈</div><div><div class="label">Pengguna</div><div class="value">{{ stats.users }}</div></div></div>
  </div>

  <div class="grid lg:grid-cols-2 gap-5">
    <section class="surface p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="section-title">Dataset Terbaru</h3>
        <RouterLink to="/datasets" class="text-xs font-semibold text-sky2-600 hover:underline">Lihat semua →</RouterLink>
      </div>
      <div v-if="loading" class="text-sm text-ink-500">Memuat…</div>
      <ul v-else-if="stats.datasets_summary.length" class="space-y-2">
        <li v-for="d in stats.datasets_summary" :key="d.id"
            class="flex items-center gap-3 p-3 rounded-xl border border-ink-100 hover:bg-sky2-50/40 transition">
          <div class="w-10 h-10 rounded-xl text-white font-bold grid place-items-center"
               style="background:linear-gradient(135deg,#1f5cf5,#6b21a8)">{{ d.id }}</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-ink-900 truncate">{{ d.name }}</div>
            <div class="text-xs text-ink-500 truncate">{{ d.case_label || '—' }}</div>
          </div>
          <span class="chip-plum">{{ d.rows_count }} baris</span>
        </li>
      </ul>
      <div v-else class="text-sm text-ink-500">Belum ada dataset. Mulai dari menu <RouterLink to="/datasets" class="text-sky2-600 font-semibold">Dataset</RouterLink>.</div>
    </section>

    <section class="surface p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="section-title">Prediksi Terbaru</h3>
        <RouterLink to="/prediksi" class="text-xs font-semibold text-sky2-600 hover:underline">Buka prediksi →</RouterLink>
      </div>
      <div v-if="loading" class="text-sm text-ink-500">Memuat…</div>
      <ul v-else-if="stats.recent_predictions.length" class="space-y-2">
        <li v-for="p in stats.recent_predictions" :key="p.id" class="p-3 rounded-xl border border-ink-100">
          <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-ink-900">{{ p.dataset?.name || '—' }}</span>
            <span class="chip-sky">Ŷ = {{ Number(p.y_predicted).toLocaleString('id-ID') }}</span>
          </div>
          <div class="text-xs text-ink-500 mt-1">X = {{ p.x_input }} · oleh {{ p.user?.name || '—' }} · {{ new Date(p.created_at).toLocaleString('id-ID') }}</div>
        </li>
      </ul>
      <div v-else class="text-sm text-ink-500">Belum ada prediksi. Mulai dari menu <RouterLink to="/prediksi" class="text-sky2-600 font-semibold">Prediksi</RouterLink>.</div>
    </section>
  </div>
</template>
