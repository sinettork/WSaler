import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useCategoriesStore = defineStore('categories', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
    pagination: {},
  }),
  getters: {
    active: (state) => state.items.filter((i) => i.is_active),
    flatList: (state) => {
      const result = [];
      function flatten(list, depth = 0) {
        for (const item of list || []) {
          result.push({ ...item, depth });
          flatten(item.children, depth + 1);
        }
      }
      flatten(state.items);
      return result;
    },
  },
  actions: {
    async fetch(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/categories', { params });
        this.items = res.data.data || [];
        this.pagination = {
          current_page: res.data.current_page || 1,
          last_page: res.data.last_page || 1,
          total: res.data.total || 0,
          links: res.data.links || [],
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
        const res = await api.get(`/categories/${id}`);
        this.current = res.data;
        return res.data;
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
        const res = await api.post('/categories', data);
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
        const res = await api.put(`/categories/${id}`, data);
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
        await api.delete(`/categories/${id}`);
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
