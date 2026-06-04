<script setup>
import { ArrowLeftRight, Eye, Pencil, Trash2, UserX } from '@lucide/vue';
import { formatDate } from '@/lib/date';

defineProps({
    employees: { type: Object, required: true },
    canManage: { type: Function, required: true },
});

defineEmits(['view', 'rotate', 'edit', 'delete', 'change-page']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border overflow-hidden bg-surface-glass">
        <v-table class="w-100 table-modern">
            <thead>
                <tr class="bg-indigo-lighten-5">
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Ному насаб
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Вазифа
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Филиал
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Категория
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Намуд
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Телефон
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Санаи таваллуд
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Санаи ба кор қабул аз
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="employee in employees.data" :key="employee.id" class="employee-row">
                    <td class="pa-3 font-weight-bold text-indigo-darken-3">
                        {{ employee.full_name }}
                    </td>
                    <td class="pa-3 text-grey-darken-3 font-weight-medium">
                        <div class="text-truncate col-position" :title="employee.position?.name || '-'">
                            {{ employee.position?.name || '-' }}
                        </div>
                    </td>
                    <td class="pa-3">
                        <v-chip size="small" color="indigo" variant="flat" class="font-weight-bold col-branch" :title="employee.branch?.name">
                            {{ employee.branch?.name }}
                        </v-chip>
                    </td>
                    <td class="pa-3">
                        <v-chip size="small" color="secondary" variant="outlined">
                            {{ employee.category || '-' }}
                        </v-chip>
                    </td>
                    <td class="pa-3">
                        <v-chip size="small" color="teal" variant="tonal" class="font-weight-bold">
                            {{ employee.employment_type_label || '-' }}
                        </v-chip>
                    </td>
                    <td class="pa-3 text-body-2 font-weight-medium">
                        {{ employee.phone_number || '-' }}
                    </td>
                    <td class="pa-3 text-body-2 font-weight-bold text-indigo">
                        {{ formatDate(employee.birth_date) }}
                    </td>
                    <td class="pa-3 text-body-2 font-weight-medium">
                        {{ formatDate(employee.employment_start_date) }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-btn variant="text" color="indigo" size="small" class="mr-1 hover-scale-btn" @click="$emit('view', employee)">
                            <Eye style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            color="indigo"
                            size="small"
                            class="mr-1 hover-scale-btn"
                            title="Ротатсия"
                            @click="$emit('rotate', employee)"
                        >
                            <ArrowLeftRight style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            color="primary"
                            size="small"
                            class="mr-1 hover-scale-btn"
                            @click="$emit('edit', employee)"
                        >
                            <Pencil style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            color="error"
                            size="small"
                            class="hover-scale-btn"
                            @click="$emit('delete', employee)"
                        >
                            <Trash2 style="width: 16px; height: 16px;" />
                        </v-btn>
                    </td>
                </tr>
                <tr v-if="employees.data.length === 0">
                    <td colspan="9" class="text-center py-10 text-grey text-h6 font-weight-medium bg-surface">
                        <UserX class="h-10 w-10 text-grey-lighten-1 mx-auto mb-2 opacity-50" /><br>
                        Кормандон ёфт нашуданд.
                    </td>
                </tr>
            </tbody>
        </v-table>

        <!-- Pagination Wrapper -->
        <v-divider />
        <div class="d-flex justify-space-between align-center pa-3 bg-surface">
            <div class="text-caption text-grey font-weight-bold">
                Нишон дода шуд {{ employees.from || 0 }} - {{ employees.to || 0 }} аз {{ employees.total || 0 }} корманд
            </div>
            <v-pagination
                v-if="employees.last_page > 1"
                :model-value="employees.current_page"
                :length="employees.last_page"
                :total-visible="5"
                density="comfortable"
                rounded="lg"
                active-color="indigo"
                @update:model-value="(p) => $emit('change-page', p)"
            />
        </div>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}

/* Cap the text-heavy columns so long job titles / branch names don't stretch
   the table; the full value stays available via the cell's title tooltip.
   Ному насаб (full name) is left uncapped so it always shows in full. */
.col-position {
    max-width: 280px;
}
.col-branch {
    max-width: 170px;
}
.col-branch :deep(.v-chip__content) {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
