import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useUnitsStore = defineStore('units', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
  }),
  getters: {
    baseUnits: (state) => {
      const bases = state.items.filter((u) => u.base === true);
      if (state.items.length > 0 && bases.length === 0 && typeof console !== 'undefined') {
        console.warn('[units] No base units found in', state.items.length, 'loaded units. Mark at least one unit as base.');
      }
      return bases;
    },
    byCode: (state) => (code) => state.items.find((u) => u.short_code === code),
  },
  actions: {
    async fetch() {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/units');
        this.items = res.data.data || [];
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
        const res = await api.post('/units', data);
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
        const res = await api.put(`/units/${id}`, data);
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
        await api.delete(`/units/${id}`);
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
