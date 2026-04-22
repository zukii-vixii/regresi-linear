<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const list = ref([]);
const loading = ref(false);
const showForm = ref(false);
const editing = ref(null);
const form = reactive({
  id: null, name: '', case_label: '', description: '',
  x_label: 'X', x_unit: '', y_label: 'Y', y_unit: '',
});
const errors = ref({});

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/datasets');
    list.value = data.data || [];
  } finally { loading.value = false; }
}
function reset() {
  Object.assign(form, {
    id: null, name: '', case_label: '', description: '',
    x_label: 'X', x_unit: '', y_label: 'Y', y_unit: '',
  });
  errors.value = {};
}
function openCreate() { editing.value = null; reset(); showForm.value = true; }
function openEdit(row) {
  editing.value = row.id; errors.value = {};
  Object.assign(form, {
    id: row.id, name: row.name, case_label: row.case_label || '',
    description: row.description || '',
    x_label: row.x_label, x_unit: row.x_unit || '',
    y_label: row.y_label, y_unit: row.y_unit || '',
  });
  showForm.value = true;
}
async function save() {
  errors.value = {};
  try {
    const payload = {
      name: form.name, case_label: form.case_label || null, description: form.description || null,
      x_label: form.x_label, x_unit: form.x_unit || null,
      y_label: form.y_label, y_unit: form.y_unit || null,
    };
    if (editing.value) await axios.put(`/datasets/${editing.value}`, payload);
    else                await axios.post('/datasets', payload);
    showForm.value = false;
    await load();
  } catch (e) { errors.value = e?.response?.data?.errors || { _: [e?.response?.data?.message || 'Gagal'] }; }
}
async function destroy(row) {
  if (!confirm(`Hapus dataset "${row.name}" beserta semua baris datanya?`)) return;
  await axios.delete(`/datasets/${row.id}`);
  await load();
}
onMounted(load);
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">Dataset</h1>
      <p class="page-sub">Setiap dataset = satu studi kasus dengan satu variabel bebas (X) dan satu variabel terikat (Y).</p>
    </div>
    <button class="btn-primary" @click="openCreate">+ Tambah Dataset</button>
  </div>

  <section class="surface overflow-hidden">
    <table class="data-table">
      <thead><tr><th>#</th><th>Nama</th><th>Kasus</th><th>X</th><th>Y</th><th>Baris</th><th class="text-right">Aksi</th></tr></thead>
      <tbody>
        <tr v-if="!list.length"><td colspan="7" class="text-center py-8 text-ink-500">Belum ada dataset.</td></tr>
        <tr v-for="d in list" :key="d.id">
          <td class="text-ink-500">{{ d.id }}</td>
          <td>
            <div class="font-semibold text-ink-900">{{ d.name }}</div>
            <div v-if="d.description" class="text-xs text-ink-500 truncate max-w-[260px]">{{ d.description }}</div>
          </td>
          <td class="text-sm text-ink-600">{{ d.case_label || '—' }}</td>
          <td><span class="chip-sky">{{ d.x_label }}{{ d.x_unit ? ' · '+d.x_unit : '' }}</span></td>
          <td><span class="chip-plum">{{ d.y_label }}{{ d.y_unit ? ' · '+d.y_unit : '' }}</span></td>
          <td><span class="chip">{{ d.rows_count }}</span></td>
          <td class="text-right whitespace-nowrap">
            <button class="btn-ghost btn-sm" @click="openEdit(d)">Edit</button>
            <button class="btn-danger btn-sm" @click="destroy(d)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>
  </section>

  <div v-if="showForm" class="modal-bg" @click.self="showForm = false">
    <div class="modal-card max-w-2xl">
      <div class="modal-head">
        <h3 class="font-display text-lg font-bold">{{ editing ? 'Edit Dataset' : 'Tambah Dataset Baru' }}</h3>
        <button class="text-ink-500 hover:text-ink-900" @click="showForm = false">✕</button>
      </div>
      <form @submit.prevent="save" class="modal-body space-y-3">
        <div class="grid md:grid-cols-2 gap-3">
          <div><label class="label">Nama Dataset</label><input v-model="form.name" required class="input" placeholder="cth: Pengaruh Biaya Iklan" />
            <p v-if="errors.name" class="text-rose-600 text-xs mt-1">{{ errors.name[0] }}</p>
          </div>
          <div><label class="label">Label Kasus</label><input v-model="form.case_label" class="input" placeholder="cth: Studi UMKM" /></div>
        </div>
        <div><label class="label">Deskripsi</label><textarea v-model="form.description" rows="2" class="input" placeholder="Tujuan analisis…"></textarea></div>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="panel p-4 bg-sky2-50/50 border-sky2-100">
            <div class="text-xs font-bold uppercase tracking-wider text-sky2-700 mb-2">Variabel Bebas (X)</div>
            <label class="label">Nama X</label><input v-model="form.x_label" required class="input" placeholder="cth: Biaya Iklan" />
            <label class="label mt-2">Satuan X</label><input v-model="form.x_unit" class="input" placeholder="cth: juta Rp" />
          </div>
          <div class="panel p-4 bg-plum-50/50 border-plum-100">
            <div class="text-xs font-bold uppercase tracking-wider text-plum-700 mb-2">Variabel Terikat (Y)</div>
            <label class="label">Nama Y</label><input v-model="form.y_label" required class="input" placeholder="cth: Penjualan" />
            <label class="label mt-2">Satuan Y</label><input v-model="form.y_unit" class="input" placeholder="cth: juta Rp" />
          </div>
        </div>

        <p v-if="errors._" class="text-rose-600 text-sm">{{ errors._[0] }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          <button class="btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</template>
