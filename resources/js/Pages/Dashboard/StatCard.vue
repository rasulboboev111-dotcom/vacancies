<script setup>
// Чистая KPI-плитка: белая карточка, один спокойный лазурный акцент на иконке,
// крупное тёмное число. Без сплошных заливок цветом — цвет здесь брендовый
// акцент, а не сигнал статуса, поэтому все четыре KPI читаются как одна семья.
defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: 0 },
    icon: { type: [Object, Function], required: true },
});
</script>

<template>
    <v-card elevation="0" class="rounded-xl border pa-5 bg-white transition-hover h-100">
        <div class="d-flex align-center justify-space-between">
            <div style="min-width: 0;">
                <div class="stat-label">
                    {{ label }}
                </div>
                <div class="stat-value">
                    {{ value }}
                </div>
            </div>
            <span class="stat-icon">
                <component :is="icon" style="width: 24px; height: 24px;" />
            </span>
        </div>
    </v-card>
</template>

<style scoped>
.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #6b7280;
    line-height: 1.3;
}
.stat-value {
    margin-top: 8px;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.1;
    color: #0f172a;
}
.stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(0, 156, 241, 0.12);
    color: #009cf1;
    flex-shrink: 0;
}
</style>
