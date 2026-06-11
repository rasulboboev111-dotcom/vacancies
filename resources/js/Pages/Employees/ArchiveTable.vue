<script setup>
import { RotateCcw } from '@lucide/vue';
import { formatDate } from '@/lib/date';

defineProps({
    employees: { type: Array, required: true },
    canRestore: { type: Boolean, default: false },
});

defineEmits(['view', 'restore']);
</script>

<template>
    <v-table class="table-modern border rounded-xl overflow-hidden">
        <thead>
            <tr>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Ному насаби корманд
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Филиали пешина
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Вазифа
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Шуъба
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Категория
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Санаи қабул
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">
                    Санаи рафтан
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-indigo text-center">
                    Амалҳо
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="employee in employees" :key="employee.id" class="employee-row" style="cursor: pointer;" @click="$emit('view', employee)">
                <td class="pa-4 font-weight-bold text-indigo-darken-4">
                    {{ employee.full_name }}
                </td>
                <td class="pa-4 text-body-2">
                    {{ employee.branch?.name || '-' }}
                </td>
                <td class="pa-4 text-body-2 font-weight-medium text-slate-800">
                    {{ employee.position?.name || '-' }}
                </td>
                <td class="pa-4 text-body-2 text-slate-700">
                    {{ employee.department?.name || '-' }}
                </td>
                <td class="pa-4">
                    <v-chip size="small" color="secondary" variant="outlined">
                        {{ employee.category || '-' }}
                    </v-chip>
                </td>
                <td class="pa-4 text-body-2 font-weight-medium">
                    {{ formatDate(employee.hire_date) }}
                </td>
                <td class="pa-4 text-body-2 font-weight-bold text-error">
                    {{ formatDate(employee.dismissal_date) }}
                </td>
                <td class="pa-4 text-center">
                    <v-btn
                        v-if="canRestore"
                        color="success"
                        icon
                        variant="text"
                        size="small"
                        class="hover-scale-btn"
                        title="Барқарор кардан"
                        @click.stop="$emit('restore', employee)"
                    >
                        <RotateCcw style="width: 18px; height: 18px;" />
                    </v-btn>
                </td>
            </tr>
            <tr v-if="employees.length === 0">
                <td colspan="8" class="text-center py-8 text-grey font-weight-medium">
                    Дар бойгонӣ кормандон ёфт нашуданд.
                </td>
            </tr>
        </tbody>
    </v-table>
</template>
