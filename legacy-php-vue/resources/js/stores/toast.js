import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),
  getters: {
    active: (state) => state.toasts,
  },
  actions: {
    add(type, message) {
      const id = nextId++;
      this.toasts.push({ id, type, message });
      setTimeout(() => this.remove(id), 4000);
    },
    remove(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },
    success(message) {
      this.add('success', message);
    },
    error(message) {
      this.add('danger', message);
    },
    info(message) {
      this.add('info', message);
    },
    warning(message) {
      this.add('warning', message);
    },
  },
});
