<script setup>
import { onMounted, ref } from 'vue';

const ready = ref(false);
onMounted(() => requestAnimationFrame(() => (ready.value = true)));
</script>

<template>
    <div class="auth-shell" :class="{ 'is-ready': ready }">
        <!-- soft ambient brand glows -->
        <div class="auth-shell__glow auth-shell__glow--a" />
        <div class="auth-shell__glow auth-shell__glow--b" />

        <div class="auth-card">
            <span class="auth-card__top" />

            <!-- Logo & brand -->
            <div class="auth-brand">
                <span class="auth-brand__logo">
                    <img src="https://tojiktelecom.tj/logonew.svg" alt="Tojiktelecom">
                </span>
                <span class="auth-brand__name">TOJIKTELECOM</span>
            </div>

            <slot />
        </div>
    </div>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 2rem 1.25rem;
    font-family: 'Manrope', system-ui, sans-serif;
    background:
        radial-gradient(circle at 50% 0%, #f1f8fe 0%, transparent 55%),
        #f7f9fc;
}
.auth-shell__glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(120px);
    pointer-events: none;
    z-index: 0;
}
.auth-shell__glow--a {
    width: 460px;
    height: 460px;
    top: -160px;
    right: -120px;
    background: rgba(0, 156, 241, 0.16);
}
.auth-shell__glow--b {
    width: 420px;
    height: 420px;
    bottom: -180px;
    left: -120px;
    background: rgba(79, 70, 229, 0.12);
}

.auth-card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 26rem;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    border: 1px solid #e8edf4;
    border-radius: 24px;
    padding: 2.5rem 2.25rem;
    box-shadow:
        0 24px 60px -20px rgba(15, 27, 45, 0.18),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    overflow: hidden;
    opacity: 0;
    transform: translateY(18px) scale(0.99);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.is-ready .auth-card { opacity: 1; transform: none; }
.auth-card__top {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #009cf1 50%, transparent);
}

.auth-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 2rem;
}
.auth-brand__logo {
    display: grid;
    place-items: center;
    width: 88px;
    height: 80px;
    border-radius: 20px;
    background: #fff;
    border: 1px solid #eef2f7;
    box-shadow: 0 10px 24px -12px rgba(0, 97, 184, 0.35);
}
.auth-brand__logo img { height: 48px; width: auto; object-fit: contain; }
.auth-brand__name {
    font-weight: 800;
    letter-spacing: 0.2em;
    font-size: 0.98rem;
    color: #0f1b2d;
    padding-left: 0.2em;
}


@media (prefers-reduced-motion: reduce) {
    .auth-card { opacity: 1 !important; transform: none !important; transition: none !important; }
}
</style>
