<script setup>
import { DoorOpen, Lock, Pencil, Printer, Trash2, Unlock } from '@lucide/vue';
import { salaryText, scheduleText, statusLabel } from '@/lib/vacancy';

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
                <tr v-for="vacancy in vacancies" :key="vacancy.id" class="vacancy-row" @click="$emit('view', vacancy)">
                    <td class="pa-3">
                        <div class="text-truncate vac-name" :title="vacancy.position?.name || '—'">
                            {{ vacancy.position?.name || '—' }}
                        </div>
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
                        {{ salaryText(vacancy.salary, 'сом.') || '—' }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip
                            size="small"
                            :color="vacancy.status === 'open' ? 'teal' : 'grey'"
                            variant="tonal"
                            class="font-weight-bold text-uppercase"
                        >
                            {{ statusLabel(vacancy, 'tg') }}
                        </v-chip>
                    </td>
                    <td class="pa-3 text-center">
                        <v-btn variant="text" size="small" class="mr-1 hover-scale-btn act-btn act-accent" title="Чопи заявка" @click.stop="$emit('print', vacancy)">
                            <Printer style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canManage(vacancy)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn"
                            :class="vacancy.status === 'open' ? 'act-accent' : 'act-success'"
                            :title="vacancy.status === 'open' ? 'Бастани вакансия' : 'Кушодани вакансия'"
                            @click.stop="$emit('toggle', vacancy)"
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
                            @click.stop="$emit('edit', vacancy)"
                        >
                            <Pencil style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canDelete(vacancy)"
                            variant="text"
                            size="small"
                            class="hover-scale-btn act-btn act-danger"
                            title="Нест кардан"
                            @click.stop="$emit('delete', vacancy)"
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

/* Три уровня контраста текста задают иерархию без цвета:
   заголовок (самый тёмный якорь) → отдел/график (средний) → филиал (светлый). */
/* Ограничиваем колонку имени, чтобы длинный заголовок должности обрезался в одну
   строку с многоточием; полное значение остаётся доступным через title-подсказку ячейки. */
.vac-name {
    max-width: 260px;
    color: #111111;
    font-weight: 600;
}
.vac-position,
.vac-secondary {
    color: #555555;
}
.vac-branch {
    color: #999999;
}

/* Строки читаются как отдельные записи: зебра-полосы плюс подсветка при наведении
   с тонкой indigo-полосой слева у активной строки. */
.table-modern tbody tr.vacancy-row {
    cursor: pointer;
}
.table-modern tbody tr.vacancy-row:nth-child(even) {
    background: #fafafb;
}
.table-modern tbody tr.vacancy-row:hover {
    background: rgba(99, 102, 241, 0.07);
    box-shadow: inset 3px 0 0 0 #5c6bc0;
}

/* Иконки действий остаются спокойно-серыми в покое; их смысл (акцент / успех /
   опасность) проявляется только при наведении. */
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
