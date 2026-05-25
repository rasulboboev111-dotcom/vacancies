<script setup>
import { Link } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
</script>

<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center relative overflow-hidden bg-slate-900 text-slate-100 font-sans"
        style="background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 45%), radial-gradient(circle at 90% 80%, rgba(239, 68, 68, 0.1) 0%, transparent 40%), #0f172a;"
    >
        <!-- Decorative Ambient Light -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-md px-6">
            <!-- Glassmorphism Container Card -->
            <div 
                class="w-full bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden"
                style="box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05);"
            >
                <!-- Top Glow Line -->
                <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-indigo-500 to-transparent"></div>
                
                <!-- Logo & Brand -->
                <div class="flex flex-col items-center mb-8">
                    <Link href="/" class="flex flex-col items-center group">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 transform group-hover:scale-105 transition-transform duration-300">
                            <ShieldCheck style="width: 32px; height: 32px; color: white;" />
                        </div>
                        <span class="mt-4 text-xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-slate-400">VACANCY FLOW</span>
                        <span class="text-xs text-indigo-400 font-bold uppercase tracking-widest mt-1 opacity-80">Portal Authentication</span>
                    </Link>
                </div>

                <slot />
            </div>

            <!-- Footer Meta -->
            <div class="mt-8 text-center text-xs text-slate-500 font-medium">
                &copy; 2026 Vacancy Flow. Все права защищены.
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Keyframes for soft pulse animation */
@keyframes pulse-slow {
    0%, 100% { opacity: 0.8; }
    50% { opacity: 1; transform: scale(1.02); }
}
</style>
