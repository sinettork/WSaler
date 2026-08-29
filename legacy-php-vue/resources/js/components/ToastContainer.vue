<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
    <transition-group
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-x-4"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-4"
    >
      <div
        v-for="toast in toastStore.active"
        :key="toast.id"
        class="pointer-events-auto rounded-lg shadow-lg ring-1 overflow-hidden flex items-start gap-3 px-4 py-3"
        :class="toastClasses(toast.type)"
        role="alert"
      >
        <div class="shrink-0 mt-0.5">
          <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <svg v-else-if="toast.type === 'danger' || toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
          <svg v-else-if="toast.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3l9 16H3L12 3z"/></svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1 text-sm font-medium">
          {{ toast.message }}
        </div>
        <button
          type="button"
          @click="toastStore.remove(toast.id)"
          class="shrink-0 -mr-1 -mt-1 p-1 rounded hover:bg-black/5 transition-colors"
          aria-label="Dismiss"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '@/stores/toast';
const toastStore = useToastStore();

function toastClasses(type) {
  switch (type) {
    case 'success': return 'bg-emerald-50 text-emerald-900 ring-emerald-200';
    case 'danger':
    case 'error':   return 'bg-rose-50 text-rose-900 ring-rose-200';
    case 'warning': return 'bg-amber-50 text-amber-900 ring-amber-200';
    case 'info':    return 'bg-sky-50 text-sky-900 ring-sky-200';
    default:        return 'bg-white text-slate-900 ring-slate-200';
  }
}
</script>
