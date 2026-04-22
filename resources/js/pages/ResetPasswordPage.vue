<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();

const email = ref('');
const token = ref('');
const password = ref('');
const password_confirmation = ref('');
const loading = ref(false);
const errMsg = ref('');
const okMsg = ref('');

onMounted(() => {
  email.value = String(route.query.email || '');
  token.value = String(route.query.token || '');
});

async function submit() {
  loading.value = true; errMsg.value = ''; okMsg.value = '';
  try {
    const { data } = await axios.post('/auth/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });
    okMsg.value = data.message || 'Password berhasil direset.';
    setTimeout(() => router.push({ name: 'login' }), 1500);
  } catch (e) {
    errMsg.value = e?.response?.data?.message
      || e?.response?.data?.errors?.token?.[0]
      || e?.response?.data?.errors?.password?.[0]
      || e?.response?.data?.errors?.email?.[0]
      || 'Gagal mereset password.';
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
        <div class="text-xs uppercase tracking-[0.2em] opacity-80 mb-3">Reset Password</div>
        <h2 class="font-display text-3xl font-extrabold leading-tight mb-3">Buat password baru yang kuat.</h2>
        <p class="text-sm opacity-90">Gunakan minimal 6 karakter. Setelah berhasil, Anda akan diarahkan ke halaman login.</p>
      </div>
      <div class="relative z-10 text-xs opacity-70">© {{ new Date().getFullYear() }} · MIT</div>
    </aside>

    <section class="auth-right">
      <div class="auth-card">
        <h1 class="page-title mb-1">Reset Password</h1>
        <p class="page-sub mb-5">Masukkan password baru untuk akun Anda.</p>

        <form @submit.prevent="submit" class="space-y-3">
          <div class="field">
            <label class="label">Email</label>
            <input v-model="email" type="email" required class="input" />
          </div>
          <div class="field">
            <label class="label">Token Reset</label>
            <input v-model="token" type="text" required class="input font-mono text-xs" />
            <p class="text-[11px] text-ink-500 mt-1">Token diisi otomatis dari tautan reset.</p>
          </div>
          <div class="field">
            <label class="label">Password Baru</label>
            <input v-model="password" type="password" required minlength="6" class="input" />
          </div>
          <div class="field">
            <label class="label">Konfirmasi Password</label>
            <input v-model="password_confirmation" type="password" required minlength="6" class="input" />
          </div>

          <div v-if="errMsg" class="alert-error">{{ errMsg }}</div>
          <div v-if="okMsg" class="alert-success">{{ okMsg }}</div>

          <button class="btn-primary w-full justify-center" :disabled="loading">
            {{ loading ? 'Memproses…' : 'Reset Password' }}
          </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-ink-400">
          <span class="flex-1 h-px bg-ink-200"></span>atau<span class="flex-1 h-px bg-ink-200"></span>
        </div>

        <RouterLink to="/login" class="btn-secondary w-full justify-center">← Kembali ke Login</RouterLink>
      </div>
    </section>
  </div>
</template>
