<template>
    <div class="va min-h-[640px] flex items-stretch bg-stone-100">
        <!-- Left: operational status panel -->
        <div class="hidden lg:flex w-2/5 p-8 flex-col justify-between text-white" :style="{ background: 'var(--color-va-sidebar-bg)' }">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-amber-500 flex items-center justify-center text-stone-900 font-bold text-base rounded-sm">
                        W
                    </div>
                    <div class="text-lg font-bold tracking-tight">WSaler</div>
                </div>
                <div class="mt-1 text-[10px] uppercase tracking-[0.25em] text-stone-400">
                    Warehouse Operations
                </div>
            </div>

            <div class="space-y-5">
                <div class="border-l-2 border-amber-500 pl-4">
                    <div class="text-[10px] uppercase tracking-widest text-stone-400 mb-2">Today · {{ today }}</div>
                    <div class="grid grid-cols-2 gap-y-3 gap-x-6">
                        <div>
                            <div class="text-[11px] text-stone-400">Pending receipts</div>
                            <div class="text-2xl font-bold va-mono">7</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-stone-400">Batches expiring 7d</div>
                            <div class="text-2xl font-bold va-mono text-amber-500">12</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-stone-400">Low stock alerts</div>
                            <div class="text-2xl font-bold va-mono">3</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-stone-400">Drafts awaiting</div>
                            <div class="text-2xl font-bold va-mono">5</div>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-stone-400 leading-relaxed max-w-sm">
                    Operations dashboard for warehouse managers, purchasing,
                    and floor staff. Multi-warehouse stock, FEFO batch picking,
                    expiry tracking, and approvals in one place.
                </div>
            </div>

            <div class="text-[10px] text-stone-500 va-mono">
                v2.4.1 · {{ warehouseCount }} warehouses · {{ userCount }} active users
            </div>
        </div>

        <!-- Right: sign-in form -->
        <div class="flex-1 flex items-center justify-center p-6 bg-white border-l border-stone-200">
            <div class="w-full max-w-sm">
                <div class="mb-6">
                    <div class="text-[10px] uppercase tracking-widest text-stone-500 mb-1">Sign in</div>
                    <h1 class="text-2xl font-bold text-stone-900">Operator login</h1>
                </div>

                <form class="space-y-4" @submit.prevent>
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-600 uppercase tracking-wider mb-1">Email</label>
                        <input
                            type="email"
                            value="sok.rithy@wsaler.kh"
                            readonly
                            class="w-full h-10 px-3 text-sm border border-stone-300 bg-stone-50 text-stone-900 va-mono focus:outline-none focus:border-sky-700"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-600 uppercase tracking-wider mb-1">Password</label>
                        <input
                            type="password"
                            value="••••••••••"
                            readonly
                            class="w-full h-10 px-3 text-sm border border-stone-300 bg-stone-50 text-stone-900 va-mono focus:outline-none focus:border-sky-700"
                        />
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-stone-600">
                            <input type="checkbox" checked class="accent-sky-900">
                            Remember device
                        </label>
                        <a href="#" class="text-sky-900 font-semibold">Forgot password?</a>
                    </div>
                    <button
                        type="submit"
                        class="w-full h-10 bg-sky-900 hover:bg-sky-800 text-white text-sm font-semibold tracking-wide"
                    >
                        Sign in →
                    </button>
                </form>

                <div class="mt-6 pt-4 border-t border-stone-200 text-[11px] text-stone-500 va-mono">
                    <div class="flex items-center gap-2">
                        <span class="va-dot va-dot-fresh"></span>
                        <span>API healthy</span>
                        <span class="ml-auto">PPH-01 ↔ SVR-03 · 12ms</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
const warehouseCount = 3;
const userCount = 24;
</script>
