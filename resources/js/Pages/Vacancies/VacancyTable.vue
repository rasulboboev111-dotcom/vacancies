<script setup>
import { DoorOpen, Eye, Lock, Pencil, Printer, Trash2, Unlock } from '@lucide/vue';
import { scheduleText } from '@/lib/vacancy';

defineProps({
    vacancies: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
    canManage: { type: Function, required: true },
    canDelete: { type: Function, required: true },
});

defineEmits(['view', 'edit', 'delete', 'toggle', 'print']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border overflow-hidden bg-surface-glass">
        <v-table class="w-100 table-modern">
            <thead>
                <tr class="bg-indigo-lighten-5">
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Вазифа / Ном
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Шуъба
                    </th>
                    <th v-if="isAdmin" class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Филиал
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 140px;">
                        Намуд
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 160px;">
                        Ҷадвали корӣ
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 130px;">
                        Маош
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 130px;">
                        Ҳолат
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="vacancy in vacancies" :key="vacancy.id" class="vacancy-row">
                    <td class="pa-3">
                        <button type="button" class="vac-name" @click="$emit('view', vacancy)">
                            {{ vacancy.position?.name || '—' }}
                        </button>
                    </td>
                    <td class="pa-3 vac-position">
                        {{ vacancy.department?.name || '—' }}
                    </td>
                    <td v-if="isAdmin" class="pa-3 vac-branch">
                        {{ vacancy.branch?.name || '—' }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip
                            v-if="vacancy.employment_type_label"
                            size="small"
                            color="indigo"
                            variant="tonal"
                            class="font-weight-bold"
                        >
                            {{ vacancy.employment_type_label }}
                        </v-chip>
                        <span v-else class="vac-branch">—</span>
                    </td>
                    <td class="pa-3 vac-secondary text-center">
                        {{ scheduleText(vacancy) || '—' }}
                    </td>
                    <td class="pa-3 vac-secondary text-center">
                        {{ vacancy.salary != null ? `${vacancy.salary.toLocaleString('ru-RU')} сом.` : '—' }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip
                            size="small"
                            :color="vacancy.status === 'open' ? 'teal' : 'grey'"
                            variant="tonal"
                            class="font-weight-bold text-uppercase"
                        >
                            {{ vacancy.status === 'open' ? 'Кушода' : 'Баста' }}
                        </v-chip>
                    </td>
                    <td class="pa-3 text-center">
                        <v-btn variant="text" size="small" class="mr-1 hover-scale-btn act-btn act-accent" title="Дидан" @click="$emit('view', vacancy)">
                            <Eye style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn variant="text" size="small" class="mr-1 hover-scale-btn act-btn act-accent" title="Чопи заявка" @click="$emit('print', vacancy)">
                            <Printer style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(vacancy)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn"
                            :class="vacancy.status === 'open' ? 'act-accent' : 'act-success'"
                            :title="vacancy.status === 'open' ? 'Бастани вакансия' : 'Кушодани вакансия'"
                            @click="$emit('toggle', vacancy)"
                        >
                            <Lock v-if="vacancy.status === 'open'" style="width: 16px; height: 16px;" />
                            <Unlock v-else style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(vacancy)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn act-accent"
                            title="Таҳрир"
                            @click="$emit('edit', vacancy)"
                        >
                            <Pencil style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canDelete(vacancy)"
                            variant="text"
                            size="small"
                            class="hover-scale-btn act-btn act-danger"
                            title="Нест кардан"
                            @click="$emit('delete', vacancy)"
                        >
                            <Trash2 style="width: 16px; height: 16px;" />
                        </v-btn>
                    </td>
                </tr>
                <tr v-if="vacancies.length === 0">
                    <td :colspan="isAdmin ? 8 : 7" class="text-center py-10 text-grey text-h6 font-weight-medium bg-surface">
                        <DoorOpen class="h-10 w-10 text-grey-lighten-1 mx-auto mb-2 opacity-50" /><br>
                        Вакансияҳо ёфт нашуданд.
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}

/* Three tiers of text contrast carry the hierarchy without colour:
   title (darkest anchor) → department/schedule (mid) → branch (light). */
.vac-name {
    font: inherit;
    color: #111111;
    font-weight: 600;
    padding: 0;
    border: 0;
    background: none;
    text-align: left;
    cursor: pointer;
}
.vac-name:hover {
    color: #3f51b5;
    text-decoration: underline;
}
.vac-position,
.vac-secondary {
    color: #555555;
}
.vac-branch {
    color: #999999;
}

/* Rows read as discrete records: zebra striping plus a hover highlight with a
   thin indigo bar on the left of the active row. */
.table-modern tbody tr.vacancy-row:nth-child(even) {
    background: #fafafb;
}
.table-modern tbody tr.vacancy-row:hover {
    background: rgba(99, 102, 241, 0.07);
    box-shadow: inset 3px 0 0 0 #5c6bc0;
}

/* Action icons stay quiet grey at rest; their meaning (accent / success /
   danger) surfaces only on hover. */
.act-btn {
    color: #9ca3af !important;
}
.act-accent:hover {
    color: #3f51b5 !important;
}
.act-success:hover {
    color: #2e7d32 !important;
}
.act-danger:hover {
    color: #e53935 !important;
}
</style>
