<script setup>
import { AlertTriangle } from '@lucide/vue';
import { computed } from 'vue';
import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog.vue';

const props = defineProps({
    department: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const hasChildren = computed(() => (props.department?.children_count ?? 0) > 0);
const deleteUrl = computed(() => (props.department ? route('departments.destroy', props.department.id) : null));
</script>

<template>
    <ConfirmDeleteDialog
        v-model="open"
        title="Нест кардани шуъба"
        :delete-url="deleteUrl"
        :confirm-disabled="hasChildren"
        preserve-scroll
    >
        Шумо мутмаин ҳастед, ки мехоҳед шуъбаи <strong class="text-red-darken-2">{{ department?.name }}</strong>-ро нест кунед?
        <div
            v-if="hasChildren"
            class="mt-3 pa-3 rounded-lg d-flex align-center"
            style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);"
        >
            <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
            <span class="text-error font-weight-bold text-body-2">Аввал зершуъбаҳоро нест кунед ё кӯчонед.</span>
        </div>
    </ConfirmDeleteDialog>
</template>
