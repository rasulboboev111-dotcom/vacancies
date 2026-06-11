<script setup>
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    position: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const form = useForm({});

// Вазифу, всё ещё назначенную сотрудникам, нельзя удалить.
const hasEmployees = computed(() => (props.position?.employees_count ?? 0) > 0);

function confirmDelete() {
    if (!props.position) {
        return;
    }
    form.delete(route('positions.destroy', props.position.id), {
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <v-dialog v-model="open" max-width="460px">
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <!-- Красная/жёлтая шапка в зависимости от проверок безопасности -->
            <div
                :style="{
                    background: hasEmployees ? '#d97706' : '#dc2626',
                    padding: '20px 24px',
                }"
            >
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-3">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ hasEmployees ? 'Огоҳӣ' : 'Тасдиқ' }}
                        </div>
                        <div style="color: white; font-size: 1.05rem; font-weight: 800;">
                            {{ hasEmployees ? 'Нест кардан манъ аст' : 'Нест кардани вазифа' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                <template v-if="hasEmployees">
                    Шумо наметавонед вазифаи <strong class="text-amber-darken-3">{{ position?.name }}</strong>-ро нест кунед.
                    <div class="mt-4 pa-3 rounded-lg d-flex align-start" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2);">
                        <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #d97706; flex-shrink: 0; margin-top: 2px;" />
                        <span class="text-amber-darken-4 font-weight-bold text-body-2 leading-snug">
                            Ин вазифа ба кормандон таъин шудааст, ба шумораи: <strong>{{ position?.employees_count }} нафар.</strong>
                            <br><span class="text-grey-darken-2 font-weight-medium">Аввал онҳоро дар бахши «Кормандон» ба вазифаҳои дигар гузаронед, баъд ин вазифаро нест кунед.</span>
                        </span>
                    </div>
                </template>
                <template v-else>
                    Шумо мутмаин ҳастед, ки мехоҳед вазифаи <strong>{{ position?.name }}</strong>-ро бебозгашт нест кунед?
                    <div class="mt-3 pa-3 rounded-lg d-flex align-center" style="background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.15);">
                        <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
                        <span class="text-error font-weight-bold text-body-2">Ин амал номи вазифаро аз пойгоҳи додаҳо пурра нест мекунад!</span>
                    </div>
                </template>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                <v-btn
                    variant="tonal"
                    color="grey"
                    rounded="lg"
                    size="large"
                    class="px-6 font-weight-bold"
                    @click="open = false"
                >
                    {{ hasEmployees ? 'Фаҳмо' : 'Бекор кардан' }}
                </v-btn>
                <v-btn
                    v-if="!hasEmployees"
                    color="error"
                    variant="flat"
                    rounded="lg"
                    size="large"
                    class="px-6 font-weight-bold"
                    :loading="form.processing"
                    :disabled="form.processing"
                    style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                    @click="confirmDelete"
                >
                    <template #prepend>
                        <Trash2 style="width: 18px; height: 18px;" />
                    </template>
                    Нест кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.leading-snug {
    line-height: 1.35;
}
</style>
