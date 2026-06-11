<script setup>
import { BadgeCheck, Briefcase, Clock, DoorOpen, FileText, Flag, ListChecks } from '@lucide/vue';
import DataField from '@/Components/DataField.vue';
import { formatDate } from '@/lib/date';
import { probationText, scheduleText } from '@/lib/vacancy';

defineProps({
    vacancy: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
});

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="760px">
        <v-card v-if="vacancy" class="rounded-xl overflow-hidden" elevation="8">
            <!-- Header -->
            <div style="background: #009cf1; padding: 20px 28px;">
                <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <DoorOpen style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                Заявка на подбор персонала
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 700;">
                                {{ vacancy.position?.name || 'Вакансия' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <!-- 1. Информация о вакансии -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Briefcase style="width: 16px; height: 16px;" />
                        </v-avatar>
                        1. Информация о вакансии
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Должность (позиция)" :value="vacancy.position?.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Структурное подразделение" :value="vacancy.department?.name" />
                        </v-col>
                        <v-col v-if="isAdmin" cols="12" sm="6" class="py-2">
                            <DataField label="Филиал" :value="vacancy.branch?.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Место деятельности / локация" :value="vacancy.location" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Количество вакансий" :value="vacancy.openings" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Непосредственный руководитель" :value="vacancy.supervisor" />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 2. Требования к кандидату -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        2. Требования к кандидату
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Образование" :value="vacancy.education_label" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Опыт работы" :value="vacancy.experience_label" />
                        </v-col>
                        <v-col cols="12" class="py-2">
                            <DataField label="Знание языков" :value="vacancy.languages?.length ? vacancy.languages.join(', ') : null" />
                        </v-col>
                        <v-col cols="12" class="py-2">
                            <DataField label="Ключевые навыки и знание программ" :value="vacancy.skills" multiline />
                        </v-col>
                        <v-col cols="12" class="py-2">
                            <DataField label="Дополнительные требования к кандидату" :value="vacancy.requirements" multiline />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 3. Должностные обязанности -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <ListChecks style="width: 16px; height: 16px;" />
                        </v-avatar>
                        3. Должностные обязанности
                    </div>
                    <v-row dense>
                        <v-col cols="12" class="py-2">
                            <DataField label="Основные обязанности" :value="vacancy.responsibilities" multiline />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 4. Условия работы -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Clock style="width: 16px; height: 16px;" />
                        </v-avatar>
                        4. Условия работы
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Тип занятости" :value="vacancy.employment_type_label" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="График работы" :value="scheduleText(vacancy)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Формат работы" :value="vacancy.work_format_label" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Предполагаемый уровень дохода" :value="vacancy.salary != null ? `${vacancy.salary.toLocaleString('ru-RU')} сомонӣ` : null" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Испытательный срок" :value="probationText(vacancy)" />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 5. Причина открытия и сроки -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Flag style="width: 16px; height: 16px;" />
                        </v-avatar>
                        5. Причина открытия позиции и сроки
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Причина открытия позиции" :value="vacancy.opening_reason_label" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Приоритет / срочность" :value="vacancy.priority_label" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Дата подачи заявки" :value="formatDate(vacancy.opened_at)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Планируемая дата закрытия (Дедлайн)" :value="formatDate(vacancy.deadline)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Инициатор заявки" :value="vacancy.creator?.name" />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 6. Согласование заявки -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <BadgeCheck style="width: 16px; height: 16px;" />
                        </v-avatar>
                        6. Согласование заявки
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Статус вакансии" :value="vacancy.status === 'open' ? 'Открыта' : 'Закрыта'" />
                        </v-col>
                        <v-col v-if="vacancy.closed_at" cols="12" sm="6" class="py-2">
                            <DataField label="Дата закрытия" :value="formatDate(vacancy.closed_at)" />
                        </v-col>
                    </v-row>
                </v-card>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4 bg-white d-flex justify-end">
                <v-btn color="indigo" variant="flat" size="large" class="bg-indigo px-6 font-weight-medium text-white" rounded="lg" @click="open = false">
                    Закрыть
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e1b4b;
}
</style>
