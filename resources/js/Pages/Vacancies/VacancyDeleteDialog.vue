<script setup>
import { AlertTriangle, Trash2 } from '@lucide/vue';

defineProps({
    vacancy: { type: Object, default: null },
    // Driven by the parent's request lifecycle so it resets on error too.
    processing: { type: Boolean, default: false },
});

defineEmits(['confirm']);

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="460px">
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #dc2626; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15);">
                        <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-3">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            Тасдиқ
                        </div>
                        <div style="color: white; font-size: 1.05rem; font-weight: 800;">
                            Нест кардани вакансия
                        </div>
                    </div>
                </div>
            </div>
            <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                Шумо мутмаин ҳастед, ки мехоҳед вакансияи <strong class="text-red-darken-2">{{ vacancy?.position?.name }}</strong>-ро нест кунед?
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                <v-btn variant="tonal" color="grey" rounded="lg" size="large" class="px-6 font-weight-bold" :disabled="processing" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn color="error" variant="flat" rounded="lg" size="large" class="px-6 font-weight-bold" :loading="processing" :disabled="processing" @click="$emit('confirm')">
                    <template #prepend>
                        <Trash2 style="width: 18px; height: 18px;" />
                    </template>
                    Нест кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
