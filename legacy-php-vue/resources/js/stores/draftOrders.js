import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useDraftOrdersStore = defineStore('draftOrders', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    error: null,
  }),
  getters: {
    activeDrafts: (state) => state.items,
  },
  actions: {
    async fetch() {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/draft-orders');
        this.items = res.data?.data || [];
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
        const res = await api.get(`/draft-orders/${id}`);
        this.current = res.data?.data || null;
        return this.current;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async save(payload) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.post('/draft-orders', payload);
        const draft = res.data?.data;
        // Replace existing or push new
        const idx = this.items.findIndex((d) => d.id === draft.id);
        if (idx >= 0) {
          this.items[idx] = draft;
        } else {
          this.items.unshift(draft);
        }
        return draft;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async update(id, payload) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.put(`/draft-orders/${id}`, payload);
        const draft = res.data?.data;
        const idx = this.items.findIndex((d) => d.id === draft.id);
        if (idx >= 0) {
          this.items[idx] = draft;
        }
        return draft;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async remove(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/draft-orders/${id}`);
        this.items = this.items.filter((d) => d.id !== id);
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});
