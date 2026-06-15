<script setup>
import { Briefcase, IdCard, Key, User } from '@lucide/vue';
import { formatDate } from '@/lib/date';

defineProps({
    employee: { type: Object, default: null },
});

const open = defineModel({ type: Boolean, default: false });
</script>

<template>
    <v-dialog v-model="open" max-width="800px" scrollable>
        <v-card v-if="employee" class="rounded-2xl border" style="overflow: hidden;">
            <div class="pa-6 text-white d-flex align-center justify-space-between" style="background: #009cf1">
                <div class="d-flex align-center">
                    <v-avatar color="white" size="48" class="mr-4 shadow-sm">
                        <User style="width: 24px; height: 24px; color: #009cf1;" />
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-black">
                            {{ employee.full_name }}
                        </div>
                        <div class="text-caption text-white opacity-85 mt-0.5 font-weight-bold text-uppercase">
                            ИНН: {{ employee.inn || '-' }}
                        </div>
                    </div>
                </div>
                <v-chip color="error" variant="flat" class="font-weight-black text-uppercase shadow-sm">
                    Озодшуда
                </v-chip>
            </div>

            <v-divider />

            <v-card-text class="pa-6 bg-slate-50" style="max-height: 60vh;">
                <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                    <IdCard style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Маълумоти шахсӣ
                </div>
                <v-row class="mb-4">
                    <v-col cols="12" sm="4" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Ҷинс</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.gender_label || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="4" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи таваллуд</span>
                        <span class="text-body-1 font-weight-bold">{{ formatDate(employee.birth_date) }} ({{ employee.age ? `${employee.age} сол` : '-' }})</span>
                    </v-col>

                    <v-col cols="12" sm="4" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Миллат</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.nationality || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Рақами телефон</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.phone_number || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Зодгоҳ</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.birth_place || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Маълумот</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.education || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Ихтисос</span>
                        <span class="text-body-1 font-weight-bold text-indigo">{{ employee.specialty || '-' }}</span>
                    </v-col>

                    <v-col cols="12" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Суроғаи истиқомат</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.address || '-' }}</span>
                    </v-col>
                </v-row>

                <v-divider class="my-4" />

                <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                    <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Маълумот дар бораи озодшавӣ
                </div>
                <v-row class="mb-4">
                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи ба кор қабул</span>
                        <span class="text-body-1 font-weight-bold text-success">{{ formatDate(employee.hire_date) }}</span>
                    </v-col>

                    <v-col cols="12" sm="6" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи рафтан (озодшавӣ/нафақа)</span>
                        <span class="text-body-1 font-weight-bold text-error font-weight-black">{{ formatDate(employee.dismissal_date) }}</span>
                    </v-col>

                    <v-col cols="12" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Сабаби озодшавӣ</span>
                        <span class="text-body-1 font-weight-bold">{{ employee.dismissal_reason || '-' }}</span>
                    </v-col>

                    <v-col v-if="employee.employment_start_date" cols="12" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи ба кор қабул аз</span>
                        <span class="text-body-1 font-weight-bold text-teal">{{ formatDate(employee.employment_start_date) }}</span>
                    </v-col>
                </v-row>

                <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                    <Key style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> ИНН ва ҳуҷҷатҳо
                </div>
                <v-row class="mb-4">
                    <v-col cols="12" sm="4" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">ИНН / РМА</span>
                        <span class="text-body-1 font-weight-bold font-mono">{{ employee.inn || '-' }}</span>
                    </v-col>

                    <v-col cols="12" sm="8" class="py-2">
                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase">СИН (Рамз)</span>
                        <span class="text-body-1 font-weight-bold font-mono">{{ employee.sin || '-' }}</span>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-card-actions class="px-0 pt-6">
                <v-spacer />
                <v-btn variant="flat" class="px-5 font-weight-bold" rounded="lg" style="background-color: #009cf1; color: #ffffff;" @click="open = false">
                    Пӯшидан
                </v-btn>
            </v-card-actions>
            <div class="glass-shine" />
        </v-card>
    </v-dialog>
</template>
