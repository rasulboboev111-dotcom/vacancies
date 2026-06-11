<script setup>
import { Briefcase, FileText, User } from '@lucide/vue';
import DataField from '@/Components/DataField.vue';
import { formatDate } from '@/lib/date';

defineProps({
    employee: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="850px">
        <v-card v-if="employee" class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 20px 28px;">
                <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <User style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                Маълумот дар бораи корманд
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 700;">
                                {{ employee.full_name }}
                            </div>
                        </div>
                    </div>
                    <v-chip color="white" variant="flat" class="font-weight-medium" size="small" style="color: #4338ca;">
                        {{ employee.employment_type_label }}
                    </v-chip>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <User style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти асосӣ ва шахсӣ
                    </div>
                    <v-row dense>
                        <v-col cols="12" class="py-2">
                            <DataField label="Ному насаб" :value="employee.full_name" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Ҷинс" :value="employee.gender_label" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField
                                label="Санаи таваллуд / Синну сол"
                                :value="employee.birth_date ? `${formatDate(employee.birth_date)}${employee.age ? ` (${employee.age} сол)` : ''}` : null"
                            />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Миллат" :value="employee.nationality" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Телефон" :value="employee.phone_number" />
                        </v-col>
                        <v-col cols="12" sm="8" class="py-2">
                            <DataField label="Почтаи электронӣ" :value="employee.email" />
                        </v-col>
                        <v-col cols="12" sm="8" class="py-2">
                            <DataField label="Суроғаи истиқомат" :value="employee.address" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Зодгоҳ" :value="employee.birth_place" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Маълумот" :value="employee.education" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Ихтисос" :value="employee.specialty" />
                        </v-col>
                    </v-row>
                </v-card>

                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Briefcase style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Фаъолияти меҳнатӣ
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Вазифа" :value="employee.position?.name" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Шуъба" :value="employee.department?.name" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Филиал" :value="employee.branch?.name || 'Филиали ҳазфшуда'" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Категория" :value="employee.category" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Роҳбар" :value="employee.manager?.full_name || 'Нест'" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Собиқаи корӣ" :value="formatDate(employee.employment_start_date)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Санаи қабул" :value="formatDate(employee.hire_date)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Санаи озодшавӣ" :value="formatDate(employee.dismissal_date)" />
                        </v-col>
                        <v-col v-if="employee.dismissal_date" cols="12" class="py-2">
                            <DataField label="Сабаби озодшавӣ" :value="employee.dismissal_reason" />
                        </v-col>
                    </v-row>
                </v-card>

                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти шиноснома ва рамзҳо
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Рақами шиноснома" :value="employee.passport_number" mono />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Мӯҳлати эътибор (Аз)" :value="formatDate(employee.passport_start_date)" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="Мӯҳлати эътибор (То)" :value="formatDate(employee.passport_end_date)" />
                        </v-col>
                        <v-col cols="12" sm="4" class="py-2">
                            <DataField label="ИНН / РМА" :value="employee.inn" mono />
                        </v-col>
                        <v-col cols="12" sm="8" class="py-2">
                            <DataField label="СИН (Рамз)" :value="employee.sin" mono />
                        </v-col>
                        <v-col cols="12" class="py-2">
                            <DataField label="Аз ҷониби додашуда" :value="employee.passport_issued_by" />
                        </v-col>
                    </v-row>
                </v-card>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4 bg-white d-flex justify-end">
                <v-btn color="indigo" variant="flat" size="large" class="bg-indigo px-6 font-weight-medium text-white" rounded="lg" @click="open = false">
                    Пӯшидан
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
