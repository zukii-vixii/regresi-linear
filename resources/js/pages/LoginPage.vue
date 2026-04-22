<script setup>
import { ref } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { auth } from '../stores/auth.js';

const router = useRouter();
const route = useRoute();
const email = ref('admin@local.test');
const password = ref('admin123');
const loading = ref(false);
const errMsg = ref('');

async function submit() {
  loading.value = true; errMsg.value = '';
  try {
    await auth.login(email.value, password.value);
    router.push(route.query.next || '/');
  } catch (e) {
    errMsg.value = e?.response?.data?.message
      || e?.response?.data?.errors?.email?.[0]
      || 'Login gagal.';
  } finally { loading.value = false; }
}
</script>

<template>
  <div class="auth-shell">
    <aside class="auth-left">
      <div class="relative z-10">
        <div class="flex items-center gap-2 font-display font-extrabold text-xl">
          <span class="w-10 h-10 rounded-xl grid place-items-center bg-white/15 backdrop-blur">∫</span>
          Regresi<span class="opacity-70">Linear</span>
        </div>
      </div>
      <div class="relative z-10 max-w-md">
        <div class="text-xs uppercase tracking-[0.2em] opacity-80 mb-3">Aplikasi Skripsi · Tugas · Belajar</div>
        <h2 class="font-display text-3xl font-extrabold leading-tight mb-3">Bangun model prediksi dengan satu variabel bebas.</h2>
        <p class="text-sm opacity-90">Hitung intercept &amp; slope, lihat scatter plot dengan garis trend, dan prediksi nilai Y untuk X baru — semuanya tanpa harus coding.</p>

        <div class="mt-8 grid grid-cols-3 gap-3 text-sm">
          <div class="bg-white/10 backdrop-blur rounded-xl px-3 py-3">
            <div class="opacity-70 text-xs">Persamaan</div>
            <div class="font-mono font-bold mt-1">Ŷ = a + bX</div>
          </div>
          <div class="bg-white/10 backdrop-blur rounded-xl px-3 py-3">
            <div class="opacity-70 text-xs">Visual</div>
            <div class="font-bold mt-1">Scatter + Trend</div>
          </div>
          <div class="bg-white/10 backdrop-blur rounded-xl px-3 py-3">
            <div class="opacity-70 text-xs">Stack</div>
            <div class="font-bold mt-1">Laravel · Vue</div>
          </div>
        </div>
      </div>
      <div class="relative z-10 text-xs opacity-70">© {{ new Date().getFullYear() }} · MIT</div>
    </aside>

    <section class="auth-right">
      <div class="auth-card">
        <h1 class="page-title mb-1">Masuk</h1>
        <p class="page-sub mb-5">Gunakan akun yang sudah terdaftar.</p>

        <form @submit.prevent="submit" class="space-y-3">
          <div class="field">
            <label class="label">Email</label>
            <input v-model="email" type="email" required autofocus class="input" />
          </div>
          <div class="field">
            <label class="label">Password</label>
            <input v-model="password" type="password" required class="input" />
          </div>

          <div v-if="errMsg" class="alert-error">{{ errMsg }}</div>

          <button class="btn-primary w-full justify-center" :disabled="loading">
            {{ loading ? 'Memproses…' : 'Masuk' }}
          </button>

          <div class="text-right">
            <RouterLink to="/lupa-password" class="text-xs text-sky2-600 hover:underline">Lupa password?</RouterLink>
          </div>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-ink-400">
          <span class="flex-1 h-px bg-ink-200"></span>atau<span class="flex-1 h-px bg-ink-200"></span>
        </div>

        <RouterLink to="/register" class="btn-secondary w-full justify-center">Buat akun baru</RouterLink>

        <div class="alert-info mt-5 text-xs">
          <strong>Demo:</strong> admin@local.test / admin123 &nbsp;·&nbsp; user@local.test / user1234
        </div>
      </div>
    </section>
  </div>
</template>
