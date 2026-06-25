<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="text-center max-w-md">
      <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-brand-50 mb-6">
        <span class="text-5xl font-bold text-brand-600">404</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">Page not found</h1>
      <p class="text-sm text-slate-600 mb-8">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <router-link
        to="/dashboard"
        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg bg-brand-600 text-white shadow-sm hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/>
        </svg>
        Back to dashboard
      </router-link>
    </div>
  </div>
</template>

<script setup>
// No logic needed
</script>
