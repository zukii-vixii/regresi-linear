<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { auth } from '../stores/auth.js';

const form = reactive({ name: '', email: '', current_password: '', password: '', password_confirmation: '' });
const errors = ref({});
const ok = ref(false);
const errMsg = ref('');
const loading = ref(false);

function fill() {
  form.name = auth.state.user?.name || '';
  form.email = auth.state.user?.email || '';
}
async function save() {
  errors.value = {}; ok.value = false; errMsg.value = ''; loading.value = true;
  try {
    const payload = { name: form.name, email: form.email };
    if (form.password) {
      payload.password = form.password;
      payload.password_confirmation = form.password_confirmation;
      payload.current_password = form.current_password;
    }
    await axios.put('/auth/profile', payload);
    await auth.refreshMe();
    fill();
    form.password = ''; form.password_confirmation = ''; form.current_password = '';
    ok.value = true;
  } catch (e) {
    errors.value = e?.response?.data?.errors || {};
    errMsg.value = e?.response?.data?.message || 'Gagal menyimpan.';
  } finally { loading.value = false; }
}
onMounted(fill);
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Profil Saya</h1>
      <p class="page-sub">Perbarui data akun &amp; password Anda.</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <aside class="surface p-6 text-center">
      <div class="w-24 h-24 rounded-2xl mx-auto grid place-items-center text-white text-4xl font-extrabold"
           style="background:linear-gradient(135deg,#1f5cf5,#6b21a8)">
        {{ (auth.state.user?.name || '?').slice(0, 1).toUpperCase() }}
      </div>
      <div class="font-display text-xl font-bold mt-3">{{ auth.state.user?.name }}</div>
      <div class="text-sm text-ink-500">{{ auth.state.user?.email }}</div>
      <div class="mt-3"><span :class="auth.state.user?.role === 'admin' ? 'chip-plum' : 'chip-sky'">{{ auth.state.user?.role }}</span></div>
    </aside>

    <section class="surface p-6 lg:col-span-2">
      <form @submit.prevent="save" class="space-y-3">
        <div class="grid md:grid-cols-2 gap-3">
          <div><label class="label">Nama</label><input v-model="form.name" required class="input" />
            <p v-if="errors.name" class="text-rose-600 text-xs mt-1">{{ errors.name[0] }}</p></div>
          <div><label class="label">Email</label><input v-model="form.email" type="email" required class="input" />
            <p v-if="errors.email" class="text-rose-600 text-xs mt-1">{{ errors.email[0] }}</p></div>
        </div>

        <div class="border-t border-ink-100 pt-4 mt-4">
          <h3 class="section-title mb-2">Ganti Password</h3>
          <p class="text-xs text-ink-500 mb-3">Biarkan kosong jika tidak ingin mengganti.</p>
          <div class="space-y-3">
            <div><label class="label">Password Lama</label><input v-model="form.current_password" type="password" class="input" />
              <p v-if="errors.current_password" class="text-rose-600 text-xs mt-1">{{ errors.current_password[0] }}</p></div>
            <div class="grid md:grid-cols-2 gap-3">
              <div><label class="label">Password Baru</label><input v-model="form.password" type="password" class="input" /></div>
              <div><label class="label">Ulangi Password Baru</label><input v-model="form.password_confirmation" type="password" class="input" /></div>
            </div>
            <p v-if="errors.password" class="text-rose-600 text-xs">{{ errors.password[0] }}</p>
          </div>
        </div>

        <div v-if="ok" class="alert-info">Profil berhasil diperbarui.</div>
        <div v-if="errMsg && !Object.keys(errors).length" class="alert-error">{{ errMsg }}</div>

        <div class="flex justify-end pt-2">
          <button class="btn-primary" :disabled="loading">{{ loading ? 'Menyimpan…' : 'Simpan Perubahan' }}</button>
        </div>
      </form>
    </section>
  </div>
</template>
