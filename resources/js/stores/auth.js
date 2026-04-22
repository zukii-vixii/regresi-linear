import { reactive, computed } from 'vue';
import axios from 'axios';

const TOKEN_KEY = 'rl_token';

const state = reactive({
  user: null,
  token: localStorage.getItem(TOKEN_KEY) || null,
  loaded: false,
});

function setToken(t) {
  state.token = t;
  if (t) {
    localStorage.setItem(TOKEN_KEY, t);
    axios.defaults.headers.common['Authorization'] = `Bearer ${t}`;
  } else {
    localStorage.removeItem(TOKEN_KEY);
    delete axios.defaults.headers.common['Authorization'];
  }
}

if (state.token) setToken(state.token);

async function refreshMe() {
  if (!state.token) { state.loaded = true; return; }
  try {
    const { data } = await axios.get('/auth/me');
    state.user = data;
  } catch {
    setToken(null);
    state.user = null;
  } finally { state.loaded = true; }
}

async function login(email, password) {
  const { data } = await axios.post('/auth/login', { email, password });
  setToken(data.token);
  state.user = data.user;
}

async function register(name, email, password, password_confirmation, phone = null) {
  const { data } = await axios.post('/auth/register', { name, email, phone, password, password_confirmation });
  setToken(data.token);
  state.user = data.user;
}

async function logout() {
  try { await axios.post('/auth/logout'); } catch {}
  setToken(null);
  state.user = null;
}

export const auth = {
  state,
  isAuthed: computed(() => !!state.token),
  isAdmin:  computed(() => state.user?.role === 'admin'),
  login, register, logout, refreshMe,
};
