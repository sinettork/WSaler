<template>
    <div class="vc min-h-[640px] flex items-center justify-center p-6 relative overflow-hidden" style="background: linear-gradient(135deg, #fdfaf5 0%, #f5ebd9 100%);">
        <!-- Decorative warm shapes -->
        <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full opacity-30" style="background: radial-gradient(circle, #fb923c 0%, transparent 70%);"></div>
        <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full opacity-20" style="background: radial-gradient(circle, #c2410c 0%, transparent 70%);"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand mark — bigger, more personality -->
            <div class="flex flex-col items-center mb-7">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl mb-4" style="background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); box-shadow: 0 10px 25px -5px rgba(194, 65, 12, 0.4);">
                    W
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950">Welcome to WSaler</h1>
                <p class="text-sm text-stone-600 mt-1.5">Wholesale operations, made simpler.</p>
            </div>

            <!-- Card -->
            <div class="vc-card">
                <form class="p-7 space-y-4" @submit.prevent>
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1.5">Email</label>
                        <input
                            type="email"
                            value="sok.rithy@wsaler.kh"
                            readonly
                            class="w-full h-11 px-4 text-sm border border-stone-200 bg-stone-50 text-stone-900 vc-mono rounded-lg focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                        />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-sm font-medium text-stone-700">Password</label>
                            <a href="#" class="text-xs font-semibold text-orange-700 hover:text-orange-800">Forgot?</a>
                        </div>
                        <input
                            type="password"
                            value="••••••••••"
                            readonly
                            class="w-full h-11 px-4 text-sm border border-stone-200 bg-stone-50 text-stone-900 vc-mono rounded-lg focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-stone-700">
                        <input type="checkbox" checked class="rounded border-stone-300 text-orange-600 focus:ring-orange-500">
                        Stay signed in
                    </label>
                    <button class="vc-btn-primary w-full justify-center h-11 text-sm">
                        Sign in
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <!-- Language picker -->
                <div class="px-7 py-3 border-t border-stone-100 bg-stone-50 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs text-stone-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        <button class="font-semibold text-stone-900">English</button>
                        <span class="text-stone-300">·</span>
                        <button class="hover:text-stone-900">ភាសាខ្មែរ</button>
                    </div>
                    <a href="#" class="text-xs font-semibold text-orange-700 hover:text-orange-800">Create account →</a>
                </div>
            </div>

            <!-- Trust strip -->
            <div class="mt-6 flex items-center justify-center gap-4 text-[11px] text-stone-500">
                <span class="flex items-center gap-1.5"><span class="vc-dot vc-dot-fresh"></span>Online</span>
                <span>·</span>
                <span>Trusted by 200+ wholesalers in Cambodia</span>
            </div>
        </div>
    </div>
</template>
