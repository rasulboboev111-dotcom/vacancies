<script setup>
import { Briefcase, MoreVertical, Pencil, Trash2, Users } from '@lucide/vue';

defineProps({
    position: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column bg-surface-glass transition-hover position-relative overflow-hidden">
        <div class="d-flex justify-space-between align-start mb-3">
            <div class="d-flex align-center">
                <v-avatar color="indigo-lighten-5" class="mr-3" size="40">
                    <Briefcase style="width: 20px; height: 20px;" class="text-indigo" />
                </v-avatar>
                <div>
                    <h3 class="text-h6 font-weight-black text-indigo-darken-3">
                        {{ position.name }}
                    </h3>
                </div>
            </div>

            <!-- Actions menu for Admin -->
            <v-menu v-if="isAdmin">
                <template #activator="{ props: menuProps }">
                    <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                        <MoreVertical style="width: 16px; height: 16px;" />
                    </v-btn>
                </template>
                <v-list density="comfortable" rounded="xl" class="border py-1">
                    <v-list-item title="Таҳрир" class="font-weight-bold" @click="$emit('edit', position)">
                        <template #prepend>
                            <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                        </template>
                    </v-list-item>
                    <v-list-item title="Нест кардан" class="text-error font-weight-bold" @click="$emit('delete', position)">
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                        </template>
                    </v-list-item>
                </v-list>
            </v-menu>
        </div>

        <v-divider class="my-3" />

        <div class="d-flex justify-space-between align-center flex-grow-1 align-end">
            <span class="text-subtitle-2 text-grey font-weight-bold text-uppercase">Ҳайат</span>
            <v-chip :color="position.employees_count > 0 ? 'teal' : 'grey'" variant="tonal" class="font-weight-black px-3" size="medium">
                <template #prepend>
                    <Users style="width: 16px; height: 16px; margin-right: 4px;" :class="position.employees_count > 0 ? 'text-teal' : 'text-grey'" />
                </template>
                {{ position.employees_count }} нафар
            </v-chip>
        </div>
        <div class="glass-shine" />
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.75) !important;
    backdrop-filter: blur(12px);
}
</style>
