<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: 0 },
    icon: { type: [Object, Function], required: true },
    gradient: { type: String, default: 'bg-gradient-indigo' },
});
</script>

<template>
    <v-card elevation="0" :class="`rounded-xl pa-5 ${gradient} border-glow relative overflow-hidden transition-hover`">
        <div class="d-flex justify-between align-center">
            <div>
                <span class="text-subtitle-2 text-white-50 font-weight-medium text-uppercase tracking-wider">{{ label }}</span>
                <h2 class="text-h3 font-weight-black mt-2 text-white">
                    {{ value }}
                </h2>
            </div>
            <v-avatar rounded="xl" size="64" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                <component :is="icon" style="width: 32px; height: 32px; color: white;" />
            </v-avatar>
        </div>
        <div class="glass-shine" />
    </v-card>
</template>

<style scoped>
.bg-gradient-indigo {
    background: #009cf1 !important;
}
.bg-gradient-emerald {
    background: #059669 !important;
}
.bg-gradient-amber {
    background: #d97706 !important;
}
.bg-gradient-slate {
    background: #475569 !important;
}
.border-glow {
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}
</style>
