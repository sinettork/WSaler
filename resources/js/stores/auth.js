import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (role) => {
      if (!state.user || !state.user.role) return false;
      if (Array.isArray(role)) return role.includes(state.user.role);
      return state.user.role === role;
    },
  },
  actions: {
    async login(credentials) {
      this.loading = true;
      try {
        const res = await axios.post('/api/auth/login', credentials);
        this.user = res.data.user;
        this.token = res.data.token;
        localStorage.setItem('user', JSON.stringify(res.data.user));
        localStorage.setItem('token', res.data.token);
        return res;
      } finally {
        this.loading = false;
      }
    },
    async register(payload) {
      this.loading = true;
      try {
        const res = await axios.post('/api/auth/register', payload);
        this.user = res.data.user;
        this.token = res.data.token;
        localStorage.setItem('user', JSON.stringify(res.data.user));
        localStorage.setItem('token', res.data.token);
        return res;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      try {
        await axios.post('/api/auth/logout', {}, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
      } catch (e) {
        // ignore
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
    },
    async fetchUser() {
      if (!this.token) return;
      try {
        const res = await axios.get('/api/auth/me', {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.user = res.data;
        localStorage.setItem('user', JSON.stringify(res.data));
      } catch (e) {
        this.user = null;
        this.token = null;
        localStorage.removeItem('user');
        localStorage.removeItem('token');
      }
    },
  },
});
