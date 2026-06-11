<script setup>
import { Briefcase, Building2, Network, Users } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps({
    position: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const loading = ref(false);
const employees = ref([]);

// Lazy-load the position's staff only when the dialog actually opens, so the
// positions grid stays light (it ships counts, not the full employee lists).
watch(open, (isOpen) => {
    if (!isOpen || !props.position) {
        return;
    }

    loading.value = true;
    employees.value = [];

    window.axios
        .get(route('positions.employees', props.position.id))
        .then((response) => {
            employees.value = response.data.employees ?? [];
        })
        .catch(() => {
            employees.value = [];
        })
        .finally(() => {
            loading.value = false;
        });
});
</script>

<template>
    <v-dialog v-model="open" max-width="520" scrollable>
        <v-card rounded="xl">
            <v-card-title class="d-flex align-start justify-space-between pa-4" style="gap: 8px;">
                <div class="d-flex align-start" style="gap: 8px; min-width: 0;">
                    <Briefcase style="width: 18px; height: 18px; flex: none; margin-top: 4px;" />
                    <span class="text-h6 font-weight-bold dialog-title">{{ position?.name }}</span>
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
                    <div v-if="employees.length === 0" class="text-center pa-8 text-grey">
                        Дар ин вазифа ҳоло корманд нест.
                    </div>
                    <v-list v-else lines="two" density="comfortable">
                        <v-list-item v-for="emp in employees" :key="emp.id">
                            <template #prepend>
                                <v-avatar color="indigo-lighten-5" size="36">
                                    <Users style="width: 16px; height: 16px;" />
                                </v-avatar>
                            </template>
                            <v-list-item-title class="font-weight-bold">
                                {{ emp.full_name }}
                            </v-list-item-title>
                            <v-list-item-subtitle class="d-flex align-center flex-wrap" style="gap: 4px 12px;">
                                <span class="d-inline-flex align-center" style="gap: 4px;">
                                    <Network style="width: 13px; height: 13px;" />
                                    {{ emp.department || '—' }}
                                </span>
                                <span class="d-inline-flex align-center" style="gap: 4px;">
                                    <Building2 style="width: 13px; height: 13px;" />
                                    {{ emp.branch || '—' }}
                                </span>
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </template>
            </v-card-text>

            <template v-if="!loading && employees.length">
                <v-divider />
                <v-card-actions class="px-4 py-2">
                    <span class="text-caption text-grey-darken-1">Ҳамагӣ: {{ employees.length }} нафар.</span>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.dialog-title {
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.3;
}
</style>
