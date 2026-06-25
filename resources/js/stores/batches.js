import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useBatchesStore = defineStore('batches', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
    pagination: {},
    expiring: [],
    expired: [],
  }),
  getters: {
    active: (state) => state.items.filter((b) => b.status === 'active'),
  },
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/batches', { params });
        this.items = res.data.data || [];
        this.pagination = {
          current_page: res.data.meta?.current_page || 1,
          last_page: res.data.meta?.last_page || 1,
          total: res.data.meta?.total || 0,
          links: res.data.meta?.links || res.data.links || [],
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
        const res = await api.get(`/batches/${id}`);
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
        const res = await api.post('/batches', data);
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
        const res = await api.put(`/batches/${id}`, data);
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
        await api.delete(`/batches/${id}`);
        await this.fetch();
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async fetchExpiring(days = 30) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/batches/expiring', { params: { days } });
        this.expiring = res.data.data || [];
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
    async fetchExpired() {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/batches/expired');
        this.expired = res.data.data || [];
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
  },
});
