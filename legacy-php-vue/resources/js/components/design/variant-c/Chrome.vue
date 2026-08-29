<template>
    <div class="vc flex h-[640px] border border-stone-300 overflow-hidden">
        <!-- Sidebar — warm dark brown -->
        <aside class="w-64 shrink-0 flex flex-col" :style="{ background: 'var(--color-vc-sidebar-bg)' }">
            <!-- Brand block -->
            <div class="px-4 py-4 border-b" :style="{ borderColor: 'var(--color-vc-sidebar-border)' }">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-base shadow-md" :style="{ background: 'var(--color-vc-sidebar-accent)' }">
                        W
                    </div>
                    <div>
                        <div class="text-base font-bold text-white tracking-tight">WSaler</div>
                        <div class="text-[10px]" :style="{ color: 'var(--color-vc-sidebar-muted)' }">Wholesale Operations</div>
                    </div>
                </div>
                <!-- Warehouse selector -->
                <button class="mt-3 w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-md text-xs text-left" :style="{ background: 'var(--color-vc-sidebar-active)' }">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-3.5 h-3.5 shrink-0" :style="{ color: 'var(--color-vc-sidebar-accent)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="text-white truncate font-medium">Phnom Penh · Main</span>
                    </div>
                    <svg class="w-3 h-3 shrink-0" :style="{ color: 'var(--color-vc-sidebar-muted)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                </button>
            </div>

            <nav class="flex-1 px-2.5 py-3 space-y-0.5 overflow-auto">
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </div>
                <div class="vc-nav-item vc-nav-item-active">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="flex-1">Batches</span>
                    <span class="vc-pill vc-pill-critical" style="background: rgba(185,28,28,0.2); color: #fca5a5;">12</span>
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Products
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Sales
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Transfers
                </div>

                <div class="text-[10px] uppercase tracking-wider px-3 mt-4 mb-1 font-semibold" :style="{ color: 'var(--color-vc-sidebar-muted)' }">Master data</div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Categories
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Customers
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    Suppliers
                </div>
                <div class="vc-nav-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Warehouses
                </div>
            </nav>

            <div class="p-3 border-t" :style="{ borderColor: 'var(--color-vc-sidebar-border)' }">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full text-white flex items-center justify-center text-xs font-semibold" :style="{ background: 'var(--color-vc-sidebar-accent)' }">SR</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-white truncate">Sok Rithy</div>
                        <div class="text-[10px] truncate" :style="{ color: 'var(--color-vc-sidebar-muted)' }">Warehouse · PPH</div>
                    </div>
                    <svg class="w-3.5 h-3.5" :style="{ color: 'var(--color-vc-sidebar-muted)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 px-5 flex items-center gap-3 border-b border-stone-200 bg-white">
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-stone-500">Inventory</span>
                    <svg class="w-3.5 h-3.5 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-stone-900 font-semibold">Batches</span>
                </div>
                <div class="flex-1 max-w-md mx-4">
                    <div class="flex items-center gap-2 px-3.5 h-10 border border-stone-200 bg-stone-50 text-sm text-stone-500 rounded-lg focus-within:border-orange-400 focus-within:bg-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Search products, batches, suppliers…</span>
                    </div>
                </div>
                <button class="w-10 h-10 border border-stone-200 rounded-lg flex items-center justify-center text-stone-600 hover:bg-stone-50 relative">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-orange-500 rounded-full ring-2 ring-white"></span>
                </button>
                <button class="vc-btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Receive batch
                </button>
            </header>

            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
