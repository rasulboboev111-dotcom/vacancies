<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center relative overflow-hidden bg-slate-50 text-slate-900 font-sans"
        style="background: #f8fafc;"
    >
        <!-- Decorative Ambient Light -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-sky-500/5 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-md px-6 py-12">
            <!-- Premium Light Container Card -->
            <div 
                class="w-full bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-3xl p-8 shadow-xl relative overflow-hidden"
                style="box-shadow: 0 20px 50px -12px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9);"
            >
                <!-- Top Glow Line with Tojiktelecom Primary Brand Gradient -->
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-transparent via-[#009cf1] to-transparent"></div>
                
                <!-- Logo & Brand -->
                <div class="flex flex-col items-center mb-8">
                    <Link href="/" class="flex flex-col items-center group">
                        <div class="px-6 py-4 rounded-2xl bg-white border border-slate-100 flex items-center justify-center shadow-md transform group-hover:scale-105 transition-transform duration-300">
                            <img src="https://tojiktelecom.tj/logonew.svg" style="height: 44px; width: auto; object-fit: contain;" alt="Tojiktelecom" />
                        </div>
                        <span class="mt-5 text-xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700">TOJIKTELECOM</span>
                        <span class="text-xs text-sky-600 font-bold uppercase tracking-widest mt-1 opacity-90">HR-Портал</span>
                    </Link>
                </div>

                <slot />
            </div>

            <!-- Footer Meta -->
            <div class="mt-8 text-center text-xs text-slate-400 font-semibold">
                &copy; 2026 ҶСП "Тоҷиктелеком". Ҳамаи ҳуқуқҳо ҳифз шудаанд.
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Premium light form element styling for auth pages */
:deep(label) {
    color: #0f2d88 !important; /* Tojiktelecom Dark Navy */
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

:deep(input[type="email"]),
:deep(input[type="password"]),
:deep(input[type="text"]) {
    background-color: #f8fafc !important; /* Very soft slate */
    border: 1px solid #e2e8f0 !important; /* Soft gray border */
    border-radius: 12px !important;
    color: #0f172a !important; /* Dark Slate text */
    padding: 0.75rem 1rem !important;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
    line-height: 1.25rem !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02) !important;
}

:deep(input[type="email"]:focus),
:deep(input[type="password"]:focus),
:deep(input[type="text"]:focus) {
    border-color: #009cf1 !important; /* Tojiktelecom Azure focus */
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(0, 156, 241, 0.12), 0 2px 4px rgba(0, 0, 0, 0.02) !important;
}

:deep(span.text-slate-300) {
    color: #334155 !important; /* Dark Slate for Remember me label */
    font-weight: 600 !important;
}

:deep(a.text-slate-400) {
    color: #64748b !important; /* Cool gray link */
    font-weight: 600 !important;
    transition: color 0.15s ease !important;
}

:deep(a.text-slate-400:hover) {
    color: #009cf1 !important; /* Brand azure hover */
    text-decoration: underline !important;
}

/* Override default primary buttons on auth forms to match light brand style */
:deep(button[type="submit"]) {
    background: #0f2d88 !important;
    box-shadow: 0 8px 20px -6px rgba(0, 156, 241, 0.4) !important;
    border-radius: 12px !important;
    text-transform: none !important;
    font-weight: 700 !important;
    letter-spacing: normal !important;
    transition: all 0.25s ease !important;
}
:deep(button[type="submit"]:hover) {
    transform: translateY(-1px) !important;
    box-shadow: 0 10px 24px -5px rgba(0, 156, 241, 0.5) !important;
}
</style>
