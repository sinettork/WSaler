import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useUsersStore = defineStore('users', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
    pagination: { page: 1, lastPage: 1, perPage: 15, total: 0 },
  }),
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/users', { params });
        this.items = res.data.data || [];
        this.pagination = {
          page: res.data.meta?.current_page || res.data.current_page || 1,
          lastPage: res.data.meta?.last_page || res.data.last_page || 1,
          perPage: res.data.meta?.per_page || res.data.per_page || 15,
          total: res.data.meta?.total || res.data.total || 0,
        };
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
    async get(id) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get(`/users/${id}`);
        this.current = res.data.data || res.data;
        return this.current;
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
    async create(data) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post('/users', data);
        await this.fetch();
        return res.data;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async update(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.put(`/users/${id}`, data);
        await this.fetch();
        return res.data;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async delete(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/users/${id}`);
        await this.fetch();
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});
