<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    iconBg: { type: String, default: 'bg-indigo-lighten-5' },
    iconColor: { type: String, default: 'text-indigo' },
});
</script>

<template>
    <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
        <div :class="`mr-3 pa-2 rounded-lg ${iconBg} ${iconColor} d-flex align-center justify-center`" style="width: 36px; height: 36px; min-width: 36px;">
            <component :is="icon" style="width: 16px; height: 16px;" />
        </div>
        <div style="min-width: 0; flex: 1; overflow-wrap: anywhere;">
            <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">{{ label }}</span>
            <slot />
        </div>
    </div>
</template>

<style scoped>
.info-field {
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    background-color: #f8fafc !important;
    transition: all 0.2s ease-in-out;
}
.info-field:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
}
.v-theme--dark .info-field {
    border-color: rgba(51, 65, 85, 0.8) !important;
    background-color: #1e293b !important;
}
.v-theme--dark .info-field:hover {
    border-color: #475569 !important;
}
</style>
