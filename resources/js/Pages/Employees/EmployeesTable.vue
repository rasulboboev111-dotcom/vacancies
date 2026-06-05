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
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 130px;">
                        Намуд
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 140px;">
                        Телефон
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 140px;">
                        Санаи таваллуд
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 160px;">
                        Санаи ба кор қабул аз
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="employee in employees.data" :key="employee.id" class="employee-row">
                    <td class="pa-3">
                        <button type="button" class="emp-name" @click="$emit('view', employee)">
                            {{ employee.full_name }}
                        </button>
                    </td>
                    <td class="pa-3">
                        <div class="text-truncate col-position emp-position" :title="employee.position?.name || '-'">
                            {{ employee.position?.name || '-' }}
                        </div>
                    </td>
                    <td class="pa-3">
                        <div class="text-truncate col-branch emp-branch" :title="employee.branch?.name">
                            {{ employee.branch?.name || '-' }}
                        </div>
                    </td>
                    <td class="pa-3 emp-category">
                        {{ employee.category || '-' }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip size="small" color="teal" variant="tonal" class="font-weight-bold">
                            {{ employee.employment_type_label || '-' }}
                        </v-chip>
                    </td>
                    <td class="pa-3 text-body-2 emp-secondary text-center">
                        {{ employee.phone_number || '-' }}
                    </td>
                    <td class="pa-3 text-body-2 emp-secondary text-center">
                        {{ formatDate(employee.birth_date) }}
                    </td>
                    <td class="pa-3 text-body-2 emp-secondary text-center">
                        {{ formatDate(employee.employment_start_date) }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-btn variant="text" size="small" class="mr-1 hover-scale-btn act-btn act-accent" title="Дидан" @click="$emit('view', employee)">
                            <Eye style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn act-accent"
                            title="Ротатсия"
                            @click="$emit('rotate', employee)"
                        >
                            <ArrowLeftRight style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn act-accent"
                            title="Таҳрир"
                            @click="$emit('edit', employee)"
                        >
                            <Pencil style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(employee)"
                            variant="text"
                            size="small"
                            class="hover-scale-btn act-btn act-danger"
                            title="Нест кардан"
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
    max-width: 200px;
}

/* Three tiers of text contrast carry the hierarchy without colour:
   name (darkest anchor) → position (mid) → branch/category/dashes (light). */
.emp-name {
    font: inherit;
    color: #111111;
    font-weight: 600;
    padding: 0;
    border: 0;
    background: none;
    text-align: left;
    cursor: pointer;
}
/* The one resting accent that means something: the clickable name on hover. */
.emp-name:hover {
    color: #3f51b5;
    text-decoration: underline;
}
.emp-position,
.emp-secondary {
    color: #555555;
}
.emp-branch,
.emp-category {
    color: #999999;
}

/* Rows read as discrete records: zebra striping plus a hover highlight with a
   thin indigo bar on the left of the active row. */
.table-modern tbody tr.employee-row:nth-child(even) {
    background: #fafafb;
}
.table-modern tbody tr.employee-row:hover {
    background: rgba(99, 102, 241, 0.07);
    box-shadow: inset 3px 0 0 0 #5c6bc0;
}

/* Action icons stay quiet grey at rest; their meaning (accent / danger)
   surfaces only on hover. */
.act-btn {
    color: #9ca3af !important;
}
.act-accent:hover {
    color: #3f51b5 !important;
}
.act-danger:hover {
    color: #e53935 !important;
}
</style>
