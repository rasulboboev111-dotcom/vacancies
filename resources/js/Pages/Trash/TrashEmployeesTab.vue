<script setup>
import { RotateCcw, Search, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/lib/date';

const props = defineProps({
    employees: { type: Array, required: true },
    canManage: { type: Function, required: true },
});

defineEmits(['restore', 'force']);

const search = ref('');

const filtered = computed(() => {
    if (!search.value)
        return props.employees;
    const q = search.value.toLowerCase();
    return props.employees.filter(e =>
        e.full_name?.toLowerCase().includes(q)
        || e.inn?.toLowerCase().includes(q)
        || e.sin?.toLowerCase().includes(q)
        || e.position?.name?.toLowerCase().includes(q)
        || e.branch?.name?.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
        <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4 mb-3 mb-sm-0">
            Кормандони несткардашуда
        </div>
        <v-text-field
            v-model="search"
            :prepend-inner-icon="Search"
            label="Ҷустуҷӯи зуд аз рӯи ном, ИНН, рамз..."
            variant="outlined"
            density="comfortable"
            rounded="lg"
            hide-details
            clearable
            color="rose"
            style="max-width: 380px; width: 100%;"
        />
    </div>

    <v-table class="table-modern border rounded-xl overflow-hidden">
        <thead>
            <tr class="bg-slate-50">
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Ному насаби корманд
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Филиал
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Вазифа
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    ИНН
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    СИН (Рамз)
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Несткардашуда
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 180px;">
                    Амалҳо
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="employee in filtered" :key="employee.id" class="trash-row">
                <td class="pa-4 font-weight-bold text-slate-800">
                    {{ employee.full_name }}
                </td>
                <td class="pa-4 text-body-2">
                    {{ employee.branch?.name || '-' }}
                </td>
                <td class="pa-4 text-body-2 font-weight-medium text-slate-700">
                    {{ employee.position?.name || '-' }}
                </td>
                <td class="pa-4 text-body-2 font-mono font-weight-medium">
                    {{ employee.inn || '-' }}
                </td>
                <td class="pa-4 text-body-2 font-mono font-weight-medium text-teal-darken-3">
                    {{ employee.sin || '-' }}
                </td>
                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                    {{ formatDateTime(employee.deleted_at) }}
                </td>
                <td class="pa-4 text-center">
                    <div class="d-flex justify-center g-2">
                        <v-btn
                            v-if="canManage(employee)"
                            color="success"
                            variant="tonal"
                            size="small"
                            rounded="lg"
                            class="mr-2 hover-scale-btn font-weight-bold"
                            @click="$emit('restore', employee)"
                        >
                            <template #prepend>
                                <RotateCcw style="width: 14px; height: 14px;" />
                            </template>
                            Барқарор кардан
                        </v-btn>
                        <v-btn
                            v-if="canManage(employee)"
                            color="error"
                            variant="tonal"
                            size="small"
                            rounded="lg"
                            class="hover-scale-btn font-weight-bold"
                            @click="$emit('force', employee)"
                        >
                            <template #prepend>
                                <Trash2 style="width: 14px; height: 14px;" />
                            </template>
                            Нест кардан
                        </v-btn>
                        <span v-else class="text-caption text-grey">Танҳо тамошо</span>
                    </div>
                </td>
            </tr>
            <tr v-if="filtered.length === 0">
                <td colspan="7" class="text-center py-12">
                    <div class="d-flex flex-column align-center justify-center text-grey">
                        <Users style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                        <div class="font-weight-medium">
                            {{ search ? 'Мутобиқат дар сабад ёфт нашуд' : 'Сабади кормандон холӣ аст' }}
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </v-table>
</template>
