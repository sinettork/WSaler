import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useWarehousesStore = defineStore('warehouses', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
    pagination: {},
  }),
  getters: {
    active: (state) => state.items.filter((w) => w.is_active),
    default: (state) => state.items.find((w) => w.is_default),
  },
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/warehouses', { params });
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
        const res = await api.get(`/warehouses/${id}`);
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
        const res = await api.post('/warehouses', data);
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
        const res = await api.put(`/warehouses/${id}`, data);
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
        await api.delete(`/warehouses/${id}`);
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
