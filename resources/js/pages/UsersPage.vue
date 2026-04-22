<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { auth } from '../stores/auth.js';

const list = ref([]);
const loading = ref(false);
const showForm = ref(false);
const editing = ref(null);
const form = reactive({ id: null, name: '', email: '', phone: '', role: 'user', password: '', password_confirmation: '' });
const errors = ref({});

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/users');
    list.value = data.data || data;
  } finally { loading.value = false; }
}
function reset() { Object.assign(form, { id: null, name: '', email: '', phone: '', role: 'user', password: '', password_confirmation: '' }); errors.value = {}; }
function openCreate() { editing.value = null; reset(); showForm.value = true; }
function openEdit(u) {
  editing.value = u.id; errors.value = {};
  Object.assign(form, { id: u.id, name: u.name, email: u.email, phone: u.phone || '', role: u.role, password: '', password_confirmation: '' });
  showForm.value = true;
}
async function save() {
  errors.value = {};
  try {
    const payload = { name: form.name, email: form.email, phone: form.phone || null, role: form.role };
    if (form.password) { payload.password = form.password; payload.password_confirmation = form.password_confirmation; }
    if (editing.value) await axios.put(`/users/${editing.value}`, payload);
    else                await axios.post('/users', payload);
    showForm.value = false; await load();
  } catch (e) { errors.value = e?.response?.data?.errors || { _: [e?.response?.data?.message || 'Gagal'] }; }
}
async function destroy(u) {
  if (u.id === auth.state.user?.id) { alert('Tidak bisa menghapus akun sendiri.'); return; }
  if (!confirm(`Hapus pengguna "${u.name}"?`)) return;
  await axios.delete(`/users/${u.id}`);
  await load();
}
onMounted(load);
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Manajemen Pengguna</h1>
      <p class="page-sub">Hanya admin yang dapat mengelola pengguna.</p>
    </div>
    <button class="btn-primary" @click="openCreate">+ Tambah Pengguna</button>
  </div>

  <section class="surface overflow-hidden">
    <table class="data-table">
      <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Role</th><th>Dibuat</th><th class="text-right">Aksi</th></tr></thead>
      <tbody>
        <tr v-if="!list.length"><td colspan="7" class="text-center py-8 text-ink-500">{{ loading ? 'Memuat…' : 'Belum ada pengguna.' }}</td></tr>
        <tr v-for="u in list" :key="u.id">
          <td class="text-ink-500">{{ u.id }}</td>
          <td class="font-semibold text-ink-900">{{ u.name }}</td>
          <td>{{ u.email }}</td>
          <td class="text-ink-600">{{ u.phone || '—' }}</td>
          <td>
            <span :class="u.role === 'admin' ? 'chip-plum' : 'chip-sky'">{{ u.role }}</span>
          </td>
          <td class="text-xs text-ink-500">{{ new Date(u.created_at).toLocaleDateString('id-ID') }}</td>
          <td class="text-right whitespace-nowrap">
            <button class="btn-ghost btn-sm" @click="openEdit(u)">Edit</button>
            <button class="btn-danger btn-sm" @click="destroy(u)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>

  <div v-if="showForm" class="modal-bg" @click.self="showForm = false">
    <div class="modal-card">
      <div class="modal-head">
        <h3 class="font-display text-lg font-bold">{{ editing ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>
        <button class="text-ink-500" @click="showForm = false">✕</button>
      </div>
      <form @submit.prevent="save" class="modal-body space-y-3">
        <div><label class="label">Nama</label><input v-model="form.name" required class="input" />
          <p v-if="errors.name" class="text-rose-600 text-xs mt-1">{{ errors.name[0] }}</p></div>
        <div><label class="label">Email</label><input v-model="form.email" type="email" required class="input" />
          <p v-if="errors.email" class="text-rose-600 text-xs mt-1">{{ errors.email[0] }}</p></div>
        <div><label class="label">No. HP <span class="text-ink-400 font-normal">(opsional)</span></label>
          <input v-model="form.phone" type="tel" inputmode="tel" class="input" placeholder="0812-3456-7890" />
          <p v-if="errors.phone" class="text-rose-600 text-xs mt-1">{{ errors.phone[0] }}</p></div>
        <div><label class="label">Role</label>
          <select v-model="form.role" class="input"><option value="user">user</option><option value="admin">admin</option></select></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">{{ editing ? 'Password baru (opsional)' : 'Password' }}</label>
            <input v-model="form.password" type="password" :required="!editing" class="input" /></div>
          <div><label class="label">Ulangi password</label>
            <input v-model="form.password_confirmation" type="password" :required="!editing || form.password" class="input" /></div>
        </div>
        <p v-if="errors.password" class="text-rose-600 text-xs">{{ errors.password[0] }}</p>
        <p v-if="errors._" class="text-rose-600 text-sm">{{ errors._[0] }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          <button class="btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</template>
