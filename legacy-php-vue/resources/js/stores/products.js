import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useProductsStore = defineStore('products', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
    pagination: {},
  }),
  getters: {
    active: (state) => state.items.filter((p) => p.status === 'active'),
  },
  actions: {
    async fetch(params = {}, options = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/products', { params, silent: options.silent === true });
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
    async lookup(search) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/products/lookup', { params: { search } });
        return res.data.data || [];
      } catch (e) {
        this.error = e;
        return [];
      } finally {
        this.loading = false;
      }
    },
    async get(id) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get(`/products/${id}`);
        this.current = res.data.data || res.data;
        return this.current;
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
    async create(formData) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post('/products', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        await this.fetch();
        return res.data;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async update(id, formData) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post(`/products/${id}?_method=PUT`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
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
        await api.delete(`/products/${id}`);
        await this.fetch();
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async addVariation(productId, data) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post(`/products/${productId}/variations`, data);
        return res.data;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async updateVariation(productId, varId, data) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.put(`/products/${productId}/variations/${varId}`, data);
        return res.data;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async deleteVariation(productId, varId) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/products/${productId}/variations/${varId}`);
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    buildFormData(payload) {
      const fd = new FormData();
      for (const key in payload) {
        if (payload[key] === undefined || payload[key] === null) continue;
        if (key === 'image' && payload[key] instanceof File) {
          fd.append('image', payload[key]);
          continue;
        }
        if (key === 'variations' && Array.isArray(payload[key])) {
          payload[key].forEach((v, i) => {
            Object.keys(v).forEach((vk) => {
              if (v[vk] !== undefined && v[vk] !== null) {
                fd.append(`variations[${i}][${vk}]`, v[vk]);
              }
            });
          });
          continue;
        }
        fd.append(key, payload[key]);
      }
      return fd;
    },
  },
});
