import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const api = useApi();

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
    receipts: [],
    refunds: [],
    transfers: [],
    adjustments: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchOverview() {
      this.loading = true;
      this.error = null;
      try {
        const [receipts, refunds, transfers, adjustments] = await Promise.all([
          api.get('/purchase-receipts').catch(() => ({ data: { data: [] } })),
          api.get('/refunds').catch(() => ({ data: { data: [] } })),
          api.get('/stock-transfers').catch(() => ({ data: { data: [] } })),
          api.get('/stock-adjustments').catch(() => ({ data: { data: [] } })),
        ]);
        this.receipts = receipts.data?.data?.data || receipts.data?.data || [];
        this.refunds = refunds.data?.data?.data || refunds.data?.data || [];
        this.transfers = transfers.data?.data?.data || transfers.data?.data || [];
        this.adjustments = adjustments.data?.data?.data || adjustments.data?.data || [];
      } catch (e) {
        this.error = e;
      } finally {
        this.loading = false;
      }
    },
  },
});
