<script setup>
import { Network, Users, Workflow } from '@lucide/vue';

defineProps({
    mode: { type: String, default: 'dept' }, // 'dept' | 'branch'
    title: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    employees: { type: Array, default: () => [] },
    manager: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="520" scrollable>
        <v-card rounded="xl">
            <v-card-title class="d-flex align-start justify-space-between pa-4" style="gap: 8px;">
                <div class="d-flex align-start" style="gap: 8px; min-width: 0;">
                    <Network v-if="mode === 'branch'" style="width: 18px; height: 18px; flex: none; margin-top: 4px;" />
                    <Workflow v-else style="width: 18px; height: 18px; flex: none; margin-top: 4px;" />
                    <span class="text-h6 font-weight-bold dialog-title">{{ title }}</span>
                </div>
                <v-btn icon variant="text" density="comfortable" class="flex-none" @click="open = false">
                    <span style="font-size: 1.25rem; line-height: 1;">&times;</span>
                </v-btn>
            </v-card-title>
            <v-divider />

            <v-card-text class="pa-0">
                <div v-if="loading" class="text-center pa-8">
                    <v-progress-circular indeterminate color="indigo" />
                </div>
                <template v-else>
                    <!-- Manager (Роҳбар) — separate highlighted row on top -->
                    <v-list v-if="manager" density="comfortable" class="py-0">
                        <v-list-item class="manager-row">
                            <template #prepend>
                                <v-avatar color="indigo" size="36">
                                    <Users style="width: 16px; height: 16px; color: #fff;" />
                                </v-avatar>
                            </template>
                            <v-list-item-title class="font-weight-bold d-flex align-center flex-wrap" style="gap: 8px;">
                                {{ manager.full_name }}
                                <v-chip size="x-small" color="indigo" variant="flat" class="font-weight-bold">
                                    Роҳбар
                                </v-chip>
                            </v-list-item-title>
                            <v-list-item-subtitle>{{ manager.phone_number || 'Телефон нест' }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                    <v-divider v-if="manager && employees.length" />

                    <div v-if="!manager && employees.length === 0" class="text-center pa-8 text-grey">
                        {{ mode === 'branch' ? 'Дар ин филиал корманди берун аз шуъба нест.' : 'Дар ин шуъба корманд нест.' }}
                    </div>
                    <v-list v-else-if="employees.length" lines="two" density="comfortable">
                        <v-list-item v-for="emp in employees" :key="emp.id">
                            <template #prepend>
                                <v-avatar color="indigo-lighten-5" size="36">
                                    <Users style="width: 16px; height: 16px;" />
                                </v-avatar>
                            </template>
                            <v-list-item-title class="font-weight-bold d-flex align-center flex-wrap" style="gap: 8px;">
                                {{ emp.full_name }}
                                <v-chip v-if="emp.is_manager" size="x-small" color="indigo" variant="tonal" class="font-weight-bold">
                                    Роҳбар
                                </v-chip>
                            </v-list-item-title>
                            <v-list-item-subtitle>{{ emp.phone_number || 'Телефон нест' }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </template>
            </v-card-text>

            <template v-if="!loading && (employees.length || manager)">
                <v-divider />
                <v-card-actions class="px-4 py-2">
                    <span class="text-caption text-grey-darken-1">Ҳамагӣ: {{ employees.length + (manager ? 1 : 0) }}</span>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.manager-row {
    background: rgba(0, 156, 241, 0.06);
    border-left: 3px solid #009cf1;
}
.dialog-title {
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.3;
}
</style>
