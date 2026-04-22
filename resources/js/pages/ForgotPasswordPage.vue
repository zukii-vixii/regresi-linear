<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';

const email = ref('');
const loading = ref(false);
const errMsg = ref('');
const okMsg = ref('');
const resetUrl = ref('');
const copied = ref(false);

async function submit() {
  loading.value = true; errMsg.value = ''; okMsg.value = ''; resetUrl.value = ''; copied.value = false;
  try {
    const { data } = await axios.post('/auth/forgot-password', { email: email.value });
    okMsg.value = data.message || 'Permintaan diproses.';
    if (data.reset_url) resetUrl.value = data.reset_url;
  } catch (e) {
    errMsg.value = e?.response?.data?.message
      || e?.response?.data?.errors?.email?.[0]
      || 'Gagal memproses permintaan.';
  } finally { loading.value = false; }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(resetUrl.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1800);
  } catch {}
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
        <div class="text-xs uppercase tracking-[0.2em] opacity-80 mb-3">Pemulihan Akun</div>
        <h2 class="font-display text-3xl font-extrabold leading-tight mb-3">Lupa password? Tenang, kami bantu pulihkan.</h2>
        <p class="text-sm opacity-90">Masukkan email yang terdaftar. Sistem akan membuat tautan reset password yang berlaku 60 menit.</p>
      </div>
      <div class="relative z-10 text-xs opacity-70">© {{ new Date().getFullYear() }} · MIT</div>
    </aside>

    <section class="auth-right">
      <div class="auth-card">
        <h1 class="page-title mb-1">Lupa Password</h1>
        <p class="page-sub mb-5">Kami akan membuat tautan reset password.</p>

        <form @submit.prevent="submit" class="space-y-3">
          <div class="field">
            <label class="label">Email</label>
            <input v-model="email" type="email" required autofocus class="input" placeholder="email@anda.com" />
          </div>

          <div v-if="errMsg" class="alert-error">{{ errMsg }}</div>
          <div v-if="okMsg && !resetUrl" class="alert-info">{{ okMsg }}</div>

          <button class="btn-primary w-full justify-center" :disabled="loading">
            {{ loading ? 'Memproses…' : 'Kirim Permintaan Reset' }}
          </button>
        </form>

        <div v-if="resetUrl" class="mt-5 panel p-4 bg-emerald-50/60 border-emerald-200">
          <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-2">Tautan Reset Password</div>
          <p class="text-xs text-ink-600 mb-2">{{ okMsg }} Salin tautan di bawah lalu buka untuk mengganti password.</p>
          <div class="flex items-center gap-2">
            <input :value="resetUrl" readonly class="input text-xs font-mono" />
            <button type="button" class="btn-secondary btn-sm" @click="copyLink">
              {{ copied ? '✓ Disalin' : 'Salin' }}
            </button>
          </div>
          <a :href="resetUrl" class="btn-primary btn-sm mt-3 inline-flex">Buka Halaman Reset →</a>
        </div>

        <div class="my-5 flex items-center gap-3 text-xs text-ink-400">
          <span class="flex-1 h-px bg-ink-200"></span>atau<span class="flex-1 h-px bg-ink-200"></span>
        </div>

        <div class="flex items-center justify-between text-sm">
          <RouterLink to="/login" class="text-sky2-600 hover:underline">← Kembali ke Login</RouterLink>
          <RouterLink to="/register" class="text-sky2-600 hover:underline">Buat akun baru</RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
