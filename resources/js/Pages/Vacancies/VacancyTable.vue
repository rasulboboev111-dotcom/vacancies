<script setup>
import { DoorOpen, Eye, Lock, Pencil, Trash2, Unlock } from '@lucide/vue';

defineProps({
    vacancies: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
    canManage: { type: Function, required: true },
    canDelete: { type: Function, required: true },
});

defineEmits(['view', 'edit', 'delete', 'toggle']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
        <v-table v-if="vacancies.length > 0" hover>
            <thead>
                <tr>
                    <th class="font-weight-bold text-grey-darken-3">
                        Вазифа / Ном
                    </th>
                    <th class="font-weight-bold text-grey-darken-3">
                        Шуъба
                    </th>
                    <th v-if="isAdmin" class="font-weight-bold text-grey-darken-3">
                        Филиал
                    </th>
                    <th class="font-weight-bold text-grey-darken-3">
                        Ҷадвали корӣ
                    </th>
                    <th class="font-weight-bold text-grey-darken-3">
                        Маош
                    </th>
                    <th class="font-weight-bold text-grey-darken-3">
                        Ҳолат
                    </th>
                    <th class="font-weight-bold text-grey-darken-3 text-right">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="vacancy in vacancies" :key="vacancy.id">
                    <td class="py-3">
                        <div class="font-weight-bold text-grey-darken-4">
                            {{ vacancy.title }}
                        </div>
                        <div v-if="vacancy.position" class="text-caption text-grey">
                            {{ vacancy.position.name }}
                        </div>
                        <v-chip v-if="vacancy.employment_type" size="x-small" color="indigo" variant="tonal" class="mt-1 font-weight-bold">
                            {{ vacancy.employment_type }}
                        </v-chip>
                    </td>
                    <td>
                        <div class="text-body-2 text-grey-darken-3">
                            {{ vacancy.department?.name || '—' }}
                        </div>
                    </td>
                    <td v-if="isAdmin">
                        <span class="text-body-2">{{ vacancy.branch?.name || '—' }}</span>
                    </td>
                    <td class="text-body-2 text-grey-darken-2">
                        {{ vacancy.schedule || '—' }}
                    </td>
                    <td class="text-body-2 text-grey-darken-2">
                        {{ vacancy.salary || '—' }}
                    </td>
                    <td>
                        <v-chip
                            size="small"
                            :color="vacancy.status === 'open' ? 'amber-darken-2' : 'grey'"
                            variant="tonal"
                            class="font-weight-bold text-uppercase"
                        >
                            {{ vacancy.status === 'open' ? 'Кушода' : 'Баста' }}
                        </v-chip>
                    </td>
                    <td class="text-right">
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            title="Дидан"
                            @click="$emit('view', vacancy)"
                        >
                            <Eye style="width: 18px; height: 18px;" class="text-grey-darken-2" />
                        </v-btn>
                        <v-btn
                            v-if="canManage(vacancy)"
                            icon
                            variant="text"
                            size="small"
                            :title="vacancy.status === 'open' ? 'Бастани вакансия' : 'Кушодани вакансия'"
                            @click="$emit('toggle', vacancy)"
                        >
                            <Lock v-if="vacancy.status === 'open'" style="width: 18px; height: 18px;" class="text-grey-darken-1" />
                            <Unlock v-else style="width: 18px; height: 18px;" class="text-success" />
                        </v-btn>
                        <v-btn
                            v-if="canManage(vacancy)"
                            icon
                            variant="text"
                            size="small"
                            title="Таҳрир"
                            @click="$emit('edit', vacancy)"
                        >
                            <Pencil style="width: 18px; height: 18px;" class="text-indigo" />
                        </v-btn>
                        <v-btn
                            v-if="canDelete(vacancy)"
                            icon
                            variant="text"
                            size="small"
                            title="Нест кардан"
                            @click="$emit('delete', vacancy)"
                        >
                            <Trash2 style="width: 18px; height: 18px;" class="text-error" />
                        </v-btn>
                    </td>
                </tr>
            </tbody>
        </v-table>

        <div v-else class="text-center py-12">
            <DoorOpen style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
            <div class="text-h6 text-grey font-weight-medium">
                Вакансияҳо ёфт нашуданд.
            </div>
        </div>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
