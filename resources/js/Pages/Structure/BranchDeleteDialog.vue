<script setup>
import { AlertTriangle } from '@lucide/vue';
import { computed } from 'vue';
import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog.vue';

const props = defineProps({
    branch: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const deleteUrl = computed(() => (props.branch ? route('branches.destroy', props.branch.id) : null));
</script>

<template>
    <ConfirmDeleteDialog v-model="open" title="Нест кардани филиал" :delete-url="deleteUrl" preserve-scroll>
        Шумо мутмаин ҳастед, ки мехоҳед филиали <strong class="text-red-darken-2">{{ branch?.name }}</strong>-ро нест кунед?
        <div class="mt-3 pa-3 rounded-lg d-flex align-center" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
            <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
            <span class="text-error font-weight-bold text-body-2">Ҳамаи кормандони алоқаманд низ бебозгашт нест карда мешаванд!</span>
        </div>
    </ConfirmDeleteDialog>
</template>
