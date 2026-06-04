<script setup>
// Clean KPI tile: white card, one calm azure accent on the icon, a large dark
// number. No full-colour fills — colour here is a brand accent, not a status
// signal, so all four KPIs read as one consistent family.
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
