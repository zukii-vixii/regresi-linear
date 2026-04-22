<script setup>
import { ref, computed } from 'vue';
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router';
import { auth } from './stores/auth.js';

const router = useRouter();
const route = useRoute();
const mobileOpen = ref(false);

const isGuest = computed(() => route.meta.guest);
const initial = computed(() => (auth.state.user?.name || '?').slice(0, 1).toUpperCase());

async function logout() {
  await auth.logout();
  router.push({ name: 'login' });
}

const menus = [
  { to: '/',         label: 'Dashboard',   icon: '◉' },
  { to: '/datasets', label: 'Dataset',     icon: '▦' },
  { to: '/data',     label: 'Data Master', icon: '☰' },
  { to: '/analisis', label: 'Analisis',    icon: '∑' },
  { to: '/prediksi', label: 'Prediksi',    icon: '◎' },
];
</script>

<template>
  <RouterView v-if="isGuest" />

  <template v-else>
    <header class="topbar">
      <div class="nav-inner">
        <RouterLink to="/" class="nav-brand">
          <span class="mark">∫</span>
          <span class="hidden sm:inline">Regresi<span class="text-sky2-600">Linear</span></span>
        </RouterLink>

        <nav class="nav-links">
          <RouterLink v-for="m in menus" :key="m.to" :to="m.to" class="nav-link">
            <span class="text-sky2-500 mr-1">{{ m.icon }}</span>{{ m.label }}
          </RouterLink>
          <RouterLink v-if="auth.isAdmin.value" to="/pengguna" class="nav-link">
            <span class="text-plum-500 mr-1">◈</span>Pengguna
          </RouterLink>
        </nav>

        <div class="nav-cta">
          <span v-if="auth.isAdmin.value" class="nav-pill hidden md:inline-flex">ADMIN</span>
          <div class="hidden md:flex items-center gap-2">
            <RouterLink to="/profil" class="flex items-center gap-2 px-2 py-1 rounded-xl hover:bg-ink-100 transition">
              <div class="w-8 h-8 rounded-full grid place-items-center text-white font-bold text-sm"
                   style="background:linear-gradient(135deg,#1f5cf5,#6b21a8)">{{ initial }}</div>
              <div class="text-left">
                <div class="text-xs font-semibold text-ink-900 leading-none">{{ auth.state.user?.name }}</div>
                <div class="text-[10px] text-ink-500 leading-none mt-0.5">{{ auth.state.user?.email }}</div>
              </div>
            </RouterLink>
            <button class="btn-ghost btn-sm" @click="logout" title="Keluar">⏻</button>
          </div>
          <button class="nav-mobile-toggle" @click="mobileOpen = !mobileOpen">☰</button>
        </div>
      </div>

      <div v-if="mobileOpen" class="nav-mobile" @click="mobileOpen = false">
        <RouterLink v-for="m in menus" :key="m.to" :to="m.to">{{ m.icon }} &nbsp; {{ m.label }}</RouterLink>
        <RouterLink v-if="auth.isAdmin.value" to="/pengguna">◈ &nbsp; Pengguna</RouterLink>
        <RouterLink to="/profil">◐ &nbsp; Profil ({{ auth.state.user?.name }})</RouterLink>
        <RouterLink to="/tentang">ℹ &nbsp; Tentang</RouterLink>
        <a @click.prevent="logout" href="#">⏻ &nbsp; Keluar</a>
      </div>
    </header>

    <main class="shell">
      <RouterView />
    </main>

    <footer class="max-w-7xl mx-auto px-4 lg:px-6 py-6 text-center text-xs text-ink-400">
      © {{ new Date().getFullYear() }} Regresi Linear
      <span class="nav-pill ml-1" style="font-size:10px;padding:1px 6px;">v1.3.0</span>
      · dibangun dengan Laravel + Vue 3 ·
      <RouterLink to="/tentang" class="text-sky2-600 hover:underline">Tentang Metode</RouterLink>
    </footer>
  </template>
</template>
