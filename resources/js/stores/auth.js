import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => {
    const stored = JSON.parse(localStorage.getItem('user') || 'null');
    return {
      user: stored,
      token: localStorage.getItem('token') || null,
      loading: false,
      // Cached permissions list — updated on login + fetchUser so the
      // frontend can decide whether to make optional API calls and
      // avoid 403s on the dashboard / sidebar.
      permissions: Array.isArray(stored?.permissions) ? stored.permissions : [],
    };
  },
  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (role) => {
      if (!state.user || !state.user.role) return false;
      if (Array.isArray(role)) return role.includes(state.user.role);
      return state.user.role === role;
    },
    // True if the user has any of the listed permissions. Super-admin-like
    // roles get all permissions, so we treat the admin role as a wildcard.
    hasPermission: (state) => (permission) => {
      if (!state.user) return false;
      if (state.user.role === 'admin') return true;
      const list = Array.isArray(permission) ? permission : [permission];
      return list.some((p) => state.permissions.includes(p));
    },
  },
  actions: {
    async login(credentials) {
      this.loading = true;
      try {
        const res = await axios.post('/api/auth/login', credentials);
        this.user = res.data.user;
        this.token = res.data.token;
        this.permissions = Array.isArray(res.data.user?.permissions)
          ? res.data.user.permissions
          : [];
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
        this.permissions = Array.isArray(res.data.user?.permissions)
          ? res.data.user.permissions
          : [];
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
      this.permissions = [];
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
        this.permissions = Array.isArray(res.data.permissions) ? res.data.permissions : [];
        localStorage.setItem('user', JSON.stringify(res.data));
      } catch (e) {
        this.user = null;
        this.token = null;
        this.permissions = [];
        localStorage.removeItem('user');
        localStorage.removeItem('token');
      }
    },
  },
});
