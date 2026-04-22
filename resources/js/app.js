import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import App from './App.vue';
import { auth } from './stores/auth.js';
import '../css/app.css';

import LoginPage          from './pages/LoginPage.vue';
import RegisterPage       from './pages/RegisterPage.vue';
import ForgotPasswordPage from './pages/ForgotPasswordPage.vue';
import ResetPasswordPage  from './pages/ResetPasswordPage.vue';
import DashboardPage   from './pages/DashboardPage.vue';
import DatasetsPage    from './pages/DatasetsPage.vue';
import DataPage        from './pages/DataPage.vue';
import AnalysisPage    from './pages/AnalysisPage.vue';
import PredictPage     from './pages/PredictPage.vue';
import UsersPage       from './pages/UsersPage.vue';
import ProfilePage     from './pages/ProfilePage.vue';
import AboutPage       from './pages/AboutPage.vue';

axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login',           name: 'login',           component: LoginPage,          meta: { guest: true } },
    { path: '/register',        name: 'register',        component: RegisterPage,       meta: { guest: true } },
    { path: '/lupa-password',   name: 'lupa-password',   component: ForgotPasswordPage, meta: { guest: true } },
    { path: '/reset-password',  name: 'reset-password',  component: ResetPasswordPage,  meta: { guest: true } },
    { path: '/',         name: 'dashboard',component: DashboardPage,meta: { auth: true } },
    { path: '/datasets', name: 'datasets', component: DatasetsPage, meta: { auth: true } },
    { path: '/data',     name: 'data',     component: DataPage,     meta: { auth: true } },
    { path: '/analisis', name: 'analisis', component: AnalysisPage, meta: { auth: true } },
    { path: '/prediksi', name: 'prediksi', component: PredictPage,  meta: { auth: true } },
    { path: '/pengguna', name: 'pengguna', component: UsersPage,    meta: { auth: true, admin: true } },
    { path: '/profil',   name: 'profil',   component: ProfilePage,  meta: { auth: true } },
    { path: '/tentang',  name: 'tentang',  component: AboutPage,    meta: { auth: true } },
  ],
});

router.beforeEach((to) => {
  if (to.meta.auth  && !auth.isAuthed.value) return { name: 'login', query: { next: to.fullPath } };
  if (to.meta.guest &&  auth.isAuthed.value) return { name: 'dashboard' };
  if (to.meta.admin && auth.state.user?.role !== 'admin') return { name: 'dashboard' };
});

axios.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err?.response?.status === 401) {
      auth.logout();
      if (router.currentRoute.value.name !== 'login') router.push({ name: 'login' });
    }
    return Promise.reject(err);
  }
);

(async () => {
  await auth.refreshMe();
  createApp(App).use(router).mount('#app');
})();
