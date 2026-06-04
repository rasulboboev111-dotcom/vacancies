<script setup>
import { Network, RotateCcw, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/lib/date';

const props = defineProps({
    departments: { type: Array, required: true },
    canManage: { type: Function, required: true },
});

defineEmits(['restore', 'force']);

const search = ref('');

const filtered = computed(() => {
    if (!search.value) {
        return props.departments;
    }
    const q = search.value.toLowerCase();
    return props.departments.filter(
        d => d.name?.toLowerCase().includes(q) || d.branch?.name?.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
        <div class="text-subtitle-1 font-weight-bold text-grey-darken-4 mb-3 mb-sm-0">
            Шуъбаҳои несткардашуда
        </div>
        <div class="trash-search">
            <span class="trash-search__label">Ҷустуҷӯи зуд аз рӯи ном</span>
            <v-text-field
                v-model="search"
                :prepend-inner-icon="Search"
                placeholder="Номи шуъба ё филиал..."
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
                clearable
                color="rose"
            />
        </div>
    </div>

    <v-table class="table-modern border rounded-xl overflow-hidden">
        <thead>
            <tr class="bg-slate-50">
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Номи шуъба
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Филиал
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Несткардашуда
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 120px;">
                    Амалҳо
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="department in filtered" :key="department.id" class="trash-row">
                <td class="pa-4 font-weight-bold text-slate-800">
                    {{ department.name }}
                </td>
                <td class="pa-4 text-body-2 font-weight-medium text-grey-darken-2">
                    {{ department.branch?.name || '—' }}
                </td>
                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                    {{ formatDateTime(department.deleted_at) }}
                </td>
                <td class="pa-4 text-center">
                    <div class="d-flex justify-center ga-2">
                        <template v-if="canManage(department)">
                            <v-btn
                                variant="text"
                                size="small"
                                icon
                                rounded="lg"
                                class="trash-action-btn trash-action-btn--restore"
                                @click="$emit('restore', department)"
                            >
                                <RotateCcw style="width: 16px; height: 16px;" />
                                <v-tooltip activator="parent" location="top">
                                    Барқарор кардан
                                </v-tooltip>
                            </v-btn>
                            <v-btn
                                variant="text"
                                size="small"
                                icon
                                rounded="lg"
                                class="trash-action-btn trash-action-btn--delete"
                                @click="$emit('force', department)"
                            >
                                <Trash2 style="width: 16px; height: 16px;" />
                                <v-tooltip activator="parent" location="top">
                                    Нест кардан
                                </v-tooltip>
                            </v-btn>
                        </template>
                        <span v-else class="text-caption text-grey font-weight-medium">
                            Дастрасӣ нест
                        </span>
                    </div>
                </td>
            </tr>
            <tr v-if="filtered.length === 0">
                <td colspan="4" class="text-center py-12">
                    <div class="d-flex flex-column align-center justify-center text-grey">
                        <Network style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                        <div class="font-weight-medium">
                            {{ search ? 'Мутобиқат дар сабад ёфт нашуд' : 'Сабади шуъбаҳо холӣ аст' }}
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </v-table>
</template>
