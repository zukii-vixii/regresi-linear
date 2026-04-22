<script setup>
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { auth } from '../stores/auth.js';

const router = useRouter();
const name = ref(''); const email = ref(''); const phone = ref(''); const password = ref(''); const password_confirmation = ref('');
const loading = ref(false); const errors = ref({}); const errMsg = ref('');

async function submit() {
  loading.value = true; errors.value = {}; errMsg.value = '';
  try {
    await auth.register(name.value, email.value, password.value, password_confirmation.value, phone.value || null);
    router.push('/');
  } catch (e) {
    errors.value = e?.response?.data?.errors || {};
    errMsg.value = e?.response?.data?.message || 'Pendaftaran gagal.';
  } finally { loading.value = false; }
}
</script>

<template>
  <div class="auth-shell">
    <aside class="auth-left">
      <div class="relative z-10 flex items-center gap-2 font-display font-extrabold text-xl">
        <span class="w-10 h-10 rounded-xl grid place-items-center bg-white/15 backdrop-blur">∫</span>
        Regresi<span class="opacity-70">Linear</span>
      </div>
      <div class="relative z-10">
        <h2 class="font-display text-3xl font-extrabold leading-tight">Daftar akun baru.</h2>
        <p class="text-sm opacity-90 mt-3">Mulai bangun model regresi Anda dalam hitungan menit.</p>
      </div>
      <div class="relative z-10 text-xs opacity-70">© {{ new Date().getFullYear() }}</div>
    </aside>

    <section class="auth-right">
      <div class="auth-card">
        <h1 class="page-title mb-1">Buat Akun</h1>
        <p class="page-sub mb-5">Akun pertama otomatis menjadi administrator.</p>

        <form @submit.prevent="submit" class="space-y-3">
          <div><label class="label">Nama lengkap</label><input v-model="name" required class="input" />
            <p v-if="errors.name" class="text-rose-600 text-xs mt-1">{{ errors.name[0] }}</p>
          </div>
          <div><label class="label">Email</label><input v-model="email" type="email" required class="input" />
            <p v-if="errors.email" class="text-rose-600 text-xs mt-1">{{ errors.email[0] }}</p>
          </div>
          <div><label class="label">No. HP <span class="text-ink-400 font-normal">(opsional)</span></label>
            <input v-model="phone" type="tel" inputmode="tel" class="input" placeholder="0812-3456-7890" />
            <p v-if="errors.phone" class="text-rose-600 text-xs mt-1">{{ errors.phone[0] }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Password</label><input v-model="password" type="password" required class="input" /></div>
            <div><label class="label">Ulangi password</label><input v-model="password_confirmation" type="password" required class="input" /></div>
          </div>
          <p v-if="errors.password" class="text-rose-600 text-xs">{{ errors.password[0] }}</p>
          <div v-if="errMsg && !errors.email" class="alert-error">{{ errMsg }}</div>
          <button class="btn-primary w-full justify-center" :disabled="loading">{{ loading ? 'Memproses…' : 'Daftar' }}</button>
        </form>

        <div class="text-center text-sm text-ink-500 mt-5">
          Sudah punya akun? <RouterLink to="/login" class="text-sky2-600 font-semibold">Masuk</RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
