<script setup>
import { AlertTriangle, RotateCcw } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    action: { type: String, default: '' }, // 'restore' | 'forceDelete'
    type: { type: String, default: '' }, // 'employee' | 'branch' | 'user'
    item: { type: Object, default: null },
    processing: { type: Boolean, default: false },
});

defineEmits(['confirm']);

const open = defineModel({ type: Boolean, default: false });

const title = computed(() =>
    props.action === 'restore' ? 'Тасдиқи барқароркунӣ' : 'Несткунии бебозгашт',
);

const message = computed(() => {
    if (!props.item)
        return '';

    const name = props.type === 'employee' ? props.item.full_name : props.item.name;
    const typeLabels = { employee: 'кормандро', branch: 'филиалро', user: 'корбарро', department: 'шуъбаро' };
    const typeLabel = typeLabels[props.type] ?? 'корбарро';

    if (props.action === 'restore') {
        return `Шумо дар ҳақиқат мехоҳед ${typeLabel} "${name}" барқарор кунед? Сабт пурра ба пойгоҳи додаҳои фаъол бармегардад.`;
    }

    let warning = `ДИҚҚАТ! Шумо мехоҳед ${typeLabel} "${name}" аз пойгоҳи додаҳо бебозгашт нест кунед.`;
    if (props.type === 'branch') {
        warning += '\n\nИн амал филиалро абадан нест мекунад. Мутмаин шавед, ки ба он сабтҳои дигар вобаста нестанд.';
    }
    else if (props.type === 'department') {
        warning += '\n\nИн амал шуъбаро абадан нест мекунад. Мутмаин шавед, ки ба он зершуъбаҳо вобаста нестанд.';
    }
    else if (props.type === 'user') {
        warning += '\n\nҲисоби корбар бебозгашт нест карда мешавад. Бо он ворид шудан имконнопазир хоҳад буд.';
    }
    else {
        warning += '\n\nҲамаи маълумоти шахсӣ, ИНН, СИН ва таърихи кори ин корманд абадан нест карда мешавад.';
    }
    warning += '\n\nИн амал комилан бебозгашт аст!';
    return warning;
});
</script>

<template>
    <v-dialog v-model="open" max-width="550px" scrollable>
        <v-card class="rounded-2xl border overflow-hidden">
            <!-- Header with Action Color -->
            <div
                class="pa-6 text-white d-flex align-center justify-space-between"
                :style="{ background: action === 'restore' ? '#059669' : '#e11d48' }"
            >
                <div class="d-flex align-center">
                    <v-avatar color="white" size="40" class="mr-4 shadow-sm">
                        <RotateCcw v-if="action === 'restore'" style="width: 20px; height: 20px; color: #10b981;" />
                        <AlertTriangle v-else style="width: 20px; height: 20px; color: #f43f5e;" />
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-black">
                            {{ title }}
                        </div>
                    </div>
                </div>
            </div>

            <v-divider />

            <!-- Message Body -->
            <v-card-text class="pa-6 bg-slate-50 text-body-1 font-weight-medium text-slate-700 whitespace-pre-line">
                {{ message }}
            </v-card-text>

            <!-- Actions -->
            <v-card-actions class="px-6 py-4 bg-slate-100 d-flex justify-end border-t">
                <v-btn variant="outlined" color="grey" rounded="lg" class="px-5 font-weight-bold" :disabled="processing" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn
                    :color="action === 'restore' ? 'success' : 'error'"
                    variant="flat"
                    rounded="lg"
                    class="px-5 font-weight-bold ml-3"
                    :loading="processing"
                    :disabled="processing"
                    @click="$emit('confirm')"
                >
                    {{ action === 'restore' ? 'Барқарор кардан' : 'Бебозгашт нест кардан' }}
                </v-btn>
            </v-card-actions>
            <div class="glass-shine" />
        </v-card>
    </v-dialog>
</template>

<style scoped>
.whitespace-pre-line {
    white-space: pre-line;
}
</style>
