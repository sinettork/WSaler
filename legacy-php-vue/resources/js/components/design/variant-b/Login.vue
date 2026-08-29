<template>
    <div class="vb min-h-[640px] flex bg-zinc-50">
        <!-- Centered card on clean background -->
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-[400px]">
                <!-- Brand mark -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-lg mb-4 shadow-lg shadow-zinc-950/20"
                         :style="{ background: 'var(--color-vb-sidebar-accent)', color: '#09090b' }">
                        W
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950">Welcome back</h1>
                    <p class="text-sm text-zinc-500 mt-1.5">Sign in to your WSaler workspace</p>
                </div>

                <!-- Card -->
                <div class="vb-card">
                    <form class="p-6 space-y-4" @submit.prevent>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1.5">Email</label>
                            <input
                                type="email"
                                value="sok.rithy@wsaler.kh"
                                readonly
                                class="w-full h-10 px-3 text-sm border border-zinc-200 bg-zinc-50 text-zinc-900 vb-mono rounded-md focus:outline-none focus:border-zinc-950 focus:ring-2 focus:ring-zinc-950/10"
                            />
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-medium text-zinc-700">Password</label>
                                <a href="#" class="text-xs font-semibold text-zinc-950 hover:text-sky-700">Forgot?</a>
                            </div>
                            <input
                                type="password"
                                value="••••••••••"
                                readonly
                                class="w-full h-10 px-3 text-sm border border-zinc-200 bg-zinc-50 text-zinc-900 vb-mono rounded-md focus:outline-none focus:border-zinc-950 focus:ring-2 focus:ring-zinc-950/10"
                            />
                        </div>
                        <label class="flex items-center gap-2 text-xs text-zinc-700">
                            <input type="checkbox" checked class="rounded border-zinc-300 text-zinc-950 focus:ring-zinc-950">
                            Keep me signed in
                        </label>
                        <button class="vb-btn-primary w-full justify-center">
                            Sign in to workspace
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>

                    <div class="px-6 py-3 border-t border-zinc-100 bg-zinc-50 text-[11px] text-zinc-500 flex items-center justify-between">
                        <span>New to WSaler?</span>
                        <a href="#" class="font-semibold text-zinc-950 hover:text-sky-700">Create account →</a>
                    </div>
                </div>

                <!-- Footer hint -->
                <div class="mt-6 flex items-center justify-center gap-2 text-[11px] text-zinc-400">
                    <span class="vb-dot vb-dot-fresh"></span>
                    <span>All systems normal</span>
                    <span class="mx-2">·</span>
                    <span class="vb-mono">12ms</span>
                </div>
            </div>
        </div>
    </div>
</template>
