<script setup>
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Trash2 } from '@lucide/vue';

/**
 * Общий диалог подтверждения удаления (красная шапка + слот тела + Отмена/Удалить).
 * Сам отправляет DELETE на `deleteUrl` и закрывается при успехе. `form.processing`
 * управляет состоянием загрузки/блокировки и сбрасывается автоматически при ошибке.
 */
const props = defineProps({
    title: { type: String, required: true },
    deleteUrl: { type: String, default: null },
    confirmDisabled: { type: Boolean, default: false },
    preserveScroll: { type: Boolean, default: false },
    // Сохранять состояние страницы при удалении — нужно, когда диалог открыт
    // внутри страницы с локальным состоянием (например, активная вкладка на
    // «Сохтор»), чтобы редирект back() не перемонтировал её.
    preserveState: { type: Boolean, default: false },
});

const open = defineModel({ type: Boolean, default: false });

const form = useForm({});

function confirm() {
    if (!props.deleteUrl || props.confirmDisabled) {
        return;
    }
    form.delete(props.deleteUrl, {
        preserveScroll: props.preserveScroll,
        preserveState: props.preserveState,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <v-dialog v-model="open" max-width="460px">
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #dc2626; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-3">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            Тасдиқ
                        </div>
                        <div style="color: white; font-size: 1.05rem; font-weight: 800;">
                            {{ title }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                <slot />
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                <v-btn variant="tonal" color="grey" rounded="lg" size="large" :disabled="form.processing" class="px-6 font-weight-bold" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn
                    color="error"
                    variant="flat"
                    rounded="lg"
                    size="large"
                    class="px-6 font-weight-bold"
                    :loading="form.processing"
                    :disabled="confirmDisabled"
                    style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                    @click="confirm"
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
