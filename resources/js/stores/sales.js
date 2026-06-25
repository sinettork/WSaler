import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useSalesStore = defineStore('sales', {
    state: () => ({
        items: [],
        current: null,
        loading: false,
        error: null,
        pagination: {},
    }),
    getters: {
        completed: (state) => state.items.filter((s) => s.status === 'completed'),
        voided: (state) => state.items.filter((s) => s.status === 'voided'),
        todayTotal: (state) => {
            const today = new Date().toISOString().slice(0, 10);
            return state.items
                .filter((s) => s.status === 'completed' && (s.sold_at || '').slice(0, 10) === today)
                .reduce((sum, s) => sum + Number(s.total || 0), 0);
        },
    },
    actions: {
        async fetch(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get('/sales', { params });
                this.items = res.data.data || [];
                this.pagination = {
                    current_page: res.data.meta?.current_page || 1,
                    last_page: res.data.meta?.last_page || 1,
                    total: res.data.meta?.total || 0,
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
                const res = await api.get(`/sales/${id}`);
                this.current = res.data.data || res.data;
                return this.current;
            } catch (e) {
                this.error = e;
                throw e;
            } finally {
                this.loading = false;
            }
        },
        async create(payload) {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.post('/sales', payload);
                const created = res.data.data || res.data;
                // prepend to list so it shows immediately if user navigates back
                if (created) this.items.unshift(created);
                return created;
            } catch (e) {
                this.error = e;
                throw e;
            } finally {
                this.loading = false;
            }
        },
        async void(id, reason = '') {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.post(`/sales/${id}/void`, { reason });
                const voided = res.data.data || res.data;
                // reflect new status in cached list and current
                if (voided) {
                    const idx = this.items.findIndex((s) => s.id === voided.id);
                    if (idx !== -1) this.items[idx] = voided;
                    if (this.current?.id === voided.id) this.current = voided;
                }
                return voided;
            } catch (e) {
                this.error = e;
                throw e;
            } finally {
                this.loading = false;
            }
        },
    },
});
