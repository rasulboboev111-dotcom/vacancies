<script setup>
import { computed } from 'vue';
import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog.vue';

const props = defineProps({
    employee: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const deleteUrl = computed(() => (props.employee ? route('employees.destroy', props.employee.id) : null));
</script>

<template>
    <ConfirmDeleteDialog v-model="open" title="Нест кардани корманд" :delete-url="deleteUrl" preserve-state preserve-scroll>
        Шумо мутмаин ҳастед, ки мехоҳед корманди <strong class="text-red-darken-2">{{ employee?.full_name }}</strong>-ро нест кунед? Ин амал бебозгашт аст.
    </ConfirmDeleteDialog>
</template>
