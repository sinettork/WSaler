<template>
    <div class="flex h-screen overflow-hidden bg-slate-50">
        <!-- Mobile sidebar overlay -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="mobileOpen"
                    class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm md:hidden"
                    @click="mobileOpen = false"
                ></div>
            </Transition>
        </Teleport>

        <!-- Sidebar (mobile drawer + desktop fixed) -->
        <div
            :class="[
                'fixed inset-y-0 left-0 z-50 md:relative md:translate-x-0 transition-transform duration-200 ease-out',
                mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
            ]"
        >
            <AppSidebar />
        </div>

        <div class="flex-1 flex flex-col overflow-hidden w-full">
            <AppNavbar @toggle-sidebar="mobileOpen = !mobileOpen" :sidebar-open="mobileOpen" />
            <main id="main-content" class="flex-1 overflow-auto" tabindex="-1">
                <div class="px-4 sm:px-6 py-6 max-w-7xl mx-auto w-full">
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import AppSidebar from './AppSidebar.vue';
import AppNavbar from './AppNavbar.vue';

const mobileOpen = ref(false);

function onResize() {
    if (window.innerWidth >= 768) {
        mobileOpen.value = false;
    }
}

onMounted(() => window.addEventListener('resize', onResize));
onBeforeUnmount(() => window.removeEventListener('resize', onResize));
</script>
