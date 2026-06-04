<script setup>
import { Award, BookOpen, Briefcase, Calendar, Clock, FileText, Globe, IdCard, Layers, Mail, MapPin, Network, Phone, User, UserCheck, Users } from '@lucide/vue';
import InfoField from '@/Components/InfoField.vue';
import { formatDate } from '@/lib/date';

defineProps({
    employee: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="850px">
        <v-card v-if="employee" class="rounded-xl overflow-hidden" elevation="8">
            <!-- Premium Gradient Header -->
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
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ employee.full_name }}
                            </div>
                        </div>
                    </div>
                    <v-chip color="white" variant="flat" class="font-weight-bold" size="small" style="color: #4338ca;">
                        {{ employee.employment_type_label }}
                    </v-chip>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <!-- Section 1: Personal Data -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <User style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти асосӣ ва шахсӣ
                    </div>
                    <v-row>
                        <v-col cols="12" class="py-2">
                            <InfoField :icon="User" label="Ному насаб">
                                <span class="text-body-1 font-weight-black text-indigo-darken-4">{{ employee.full_name }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Users" label="Ҷинс">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.gender_label || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Calendar" label="Санаи таваллуд / Синну сол">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(employee.birth_date) }} ({{ employee.age ? `${employee.age} сол` : '-' }})</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Globe" label="Миллат">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.nationality || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Phone" label="Телефон">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.phone_number || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <InfoField :icon="Mail" label="Почтаи электронӣ">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.email || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <InfoField :icon="MapPin" label="Суроғаи истиқомат">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.address || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="MapPin" label="Зодгоҳ">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.birth_place || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="BookOpen" label="Маълумот">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.education || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Award" label="Ихтисос">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.specialty || '-' }}</span>
                            </InfoField>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Section 2: Professional Data -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Briefcase style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Фаъолияти меҳнатӣ
                    </div>
                    <v-row>
                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Briefcase" label="Вазифа">
                                <span class="text-body-2 font-weight-black text-indigo-darken-3">{{ employee.position?.name || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Network" label="Шуъба">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.department?.name || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="MapPin" label="Филиал">
                                <span class="text-body-2 font-weight-bold text-indigo-accent-3 font-weight-black">{{ employee.branch?.name || 'Филиали ҳазфшуда' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Layers" label="Категория">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.category || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="UserCheck" label="Роҳбар">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.manager?.full_name || 'Нест' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Clock" label="Собиқаи корӣ" icon-bg="bg-teal-lighten-5" icon-color="text-teal">
                                <span class="text-body-2 font-weight-black text-teal-darken-3 font-weight-bold">{{ formatDate(employee.employment_start_date) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Calendar" label="Санаи қабул">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(employee.hire_date) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Calendar" label="Санаи озодшавӣ" icon-bg="bg-red-lighten-5" icon-color="text-error">
                                <span class="text-body-2 font-weight-bold text-error font-weight-black">{{ formatDate(employee.dismissal_date) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col v-if="employee.dismissal_date" cols="12" class="py-2">
                            <InfoField :icon="FileText" label="Сабаби озодшавӣ" icon-bg="bg-red-lighten-5" icon-color="text-error">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.dismissal_reason || '-' }}</span>
                            </InfoField>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Section 3: Passport & Identifiers -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти шиноснома ва рамзҳо
                    </div>
                    <v-row>
                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="IdCard" label="Рақами шиноснома">
                                <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ employee.passport_number || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Calendar" label="Мӯҳлати эътибор (Аз)">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(employee.passport_start_date) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="Calendar" label="Мӯҳлати эътибор (То)">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(employee.passport_end_date) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <InfoField :icon="FileText" label="ИНН / РМА">
                                <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ employee.inn || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <InfoField :icon="FileText" label="СИН (Рамз)">
                                <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ employee.sin || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" class="py-2">
                            <InfoField :icon="UserCheck" label="Аз ҷониби додашуда">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ employee.passport_issued_by || '-' }}</span>
                            </InfoField>
                        </v-col>
                    </v-row>
                </v-card>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4 bg-white d-flex justify-end">
                <v-btn color="indigo" variant="flat" size="large" class="bg-indigo px-6 font-weight-bold text-white" rounded="lg" style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);" @click="open = false">
                    Пӯшидан
                </v-btn>
            </v-card-actions>
            <div class="glass-shine" />
        </v-card>
    </v-dialog>
</template>
