<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Tentang Metode</h1>
      <p class="page-sub">Regresi linear sederhana — fondasi statistika &amp; machine learning.</p>
    </div>
    <span class="nav-pill">Versi 1.1.0</span>
  </div>

  <section class="hero mb-6">
    <div class="hero-eyebrow">Simple Linear Regression</div>
    <h1>Hubungan satu variabel bebas (X) dengan satu variabel terikat (Y).</h1>
    <p>Tujuannya: menemukan garis lurus terbaik <span class="font-mono">Ŷ = a + bX</span> yang meminimalkan jumlah kuadrat kesalahan (Sum of Squared Errors).</p>
  </section>

  <div class="grid lg:grid-cols-2 gap-5 mb-6">
    <article class="surface p-6">
      <h3 class="section-title mb-3">📐 Rumus Inti</h3>
      <div class="space-y-3 text-sm">
        <div class="panel p-4 bg-sky2-50/50 border-sky2-100">
          <div class="text-xs font-bold uppercase tracking-wider text-sky2-700 mb-1">Slope (b) — kemiringan garis</div>
          <div class="font-mono text-base">b = (n · ΣXY − ΣX · ΣY) / (n · ΣX² − (ΣX)²)</div>
        </div>
        <div class="panel p-4 bg-plum-50/50 border-plum-100">
          <div class="text-xs font-bold uppercase tracking-wider text-plum-700 mb-1">Intercept (a) — titik potong sumbu Y</div>
          <div class="font-mono text-base">a = (ΣY − b · ΣX) / n &nbsp;atau&nbsp; a = Ȳ − b · X̄</div>
        </div>
        <div class="panel p-4 bg-amber-50/50 border-amber-100">
          <div class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-1">Koefisien Determinasi (R²)</div>
          <div class="font-mono text-base">R² = 1 − (SSE / SST) &nbsp;dengan&nbsp; SST = Σ(Y − Ȳ)²</div>
        </div>
      </div>
    </article>

    <article class="surface p-6">
      <h3 class="section-title mb-3">📊 Cara Membaca Hasil</h3>
      <ul class="space-y-3 text-sm">
        <li class="flex gap-3"><span class="step-num">1</span><div><strong>Slope (b)</strong> — setiap kenaikan 1 unit X, Y berubah sebesar b.</div></li>
        <li class="flex gap-3"><span class="step-num">2</span><div><strong>Intercept (a)</strong> — perkiraan Y ketika X = 0 (interpretasinya bergantung konteks).</div></li>
        <li class="flex gap-3"><span class="step-num">3</span><div><strong>R²</strong> — proporsi variasi Y yang dijelaskan oleh X. Mendekati 1 = model sangat baik.</div></li>
        <li class="flex gap-3"><span class="step-num">4</span><div><strong>Korelasi (r)</strong> — kekuatan &amp; arah hubungan: −1 (negatif sempurna) sampai +1 (positif sempurna).</div></li>
        <li class="flex gap-3"><span class="step-num">5</span><div><strong>RMSE / MAE</strong> — rata-rata besar kesalahan prediksi (semakin kecil semakin baik).</div></li>
      </ul>
    </article>
  </div>

  <section class="surface p-6 mb-6">
    <h3 class="section-title mb-3">💡 Contoh Penggunaan</h3>
    <div class="grid md:grid-cols-3 gap-3 text-sm">
      <div class="panel p-4"><div class="chip-sky mb-2">Pemasaran</div>Hubungan biaya iklan terhadap penjualan bulanan.</div>
      <div class="panel p-4"><div class="chip-plum mb-2">Pendidikan</div>Pengaruh jam belajar terhadap nilai ujian mahasiswa.</div>
      <div class="panel p-4"><div class="chip-amber mb-2">Operasional</div>Estimasi waktu produksi berdasarkan jumlah unit.</div>
      <div class="panel p-4"><div class="chip-emerald mb-2">Kesehatan</div>Hubungan jam tidur dengan tingkat fokus.</div>
      <div class="panel p-4"><div class="chip-rose mb-2">Energi</div>Konsumsi listrik berdasarkan suhu udara.</div>
      <div class="panel p-4"><div class="chip mb-2">Real Estate</div>Harga rumah berdasarkan luas tanah.</div>
    </div>
  </section>

  <section class="surface p-6">
    <h3 class="section-title mb-3">⚠️ Asumsi &amp; Batasan</h3>
    <ul class="text-sm text-ink-700 list-disc pl-5 space-y-1">
      <li>Hubungan antara X dan Y diasumsikan <strong>linear</strong>.</li>
      <li>Variabel X tidak boleh memiliki <strong>varian nol</strong> (semua nilai sama).</li>
      <li>Diperlukan <strong>minimal 2 baris data</strong> untuk membentuk garis (lebih banyak = lebih akurat).</li>
      <li>Outlier dapat sangat mempengaruhi slope &amp; intercept — periksa scatter plot terlebih dahulu.</li>
      <li>Regresi tidak menyatakan <em>kausalitas</em>, hanya hubungan statistik.</li>
    </ul>
    <div class="mt-5 flex flex-wrap gap-2">
      <RouterLink to="/analisis" class="btn-primary">⚡ Coba Hitung Sekarang</RouterLink>
      <RouterLink to="/datasets" class="btn-secondary">▦ Lihat Dataset</RouterLink>
    </div>
  </section>

  <section class="surface p-6 mt-6">
    <h3 class="section-title mb-3">🆕 Apa yang baru di v1.1.0</h3>
    <ul class="text-sm text-ink-700 list-disc pl-5 space-y-1">
      <li>Fitur <strong>Lupa Password</strong>: minta tautan reset langsung dari halaman login.</li>
      <li>Halaman <strong>Reset Password</strong> dengan token berlaku 60 menit &amp; otomatis mencabut sesi lama.</li>
      <li>Penanda versi aplikasi pada footer dan halaman Tentang.</li>
    </ul>
  </section>
</template>
