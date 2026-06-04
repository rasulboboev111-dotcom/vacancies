<script setup>
import { Briefcase, DoorOpen, FileText } from '@lucide/vue';
import DataField from '@/Components/DataField.vue';
import { formatDate } from '@/lib/date';

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
                                Маълумот дар бораи вакансия
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 700;">
                                {{ vacancy.title }}
                            </div>
                        </div>
                    </div>
                    <v-chip color="white" variant="flat" class="font-weight-medium" size="small" :style="{ color: vacancy.status === 'open' ? '#b45309' : '#475569' }">
                        {{ vacancy.status === 'open' ? 'Кушода' : 'Баста' }}
                    </v-chip>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Briefcase style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти асосӣ
                    </div>
                    <v-row dense>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Вазифа" :value="vacancy.position?.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Шумораи кормандони зарур" :value="vacancy.openings" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Шуъба" :value="vacancy.department?.name" />
                        </v-col>
                        <v-col v-if="isAdmin" cols="12" sm="6" class="py-2">
                            <DataField label="Филиал" :value="vacancy.branch?.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Намуди шуғл" :value="vacancy.employment_type" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Ҷадвали корӣ" :value="vacancy.schedule" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Маош" :value="vacancy.salary" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Санаи кушодашавӣ" :value="formatDate(vacancy.opened_at)" />
                        </v-col>
                        <v-col v-if="vacancy.closed_at" cols="12" sm="6" class="py-2">
                            <DataField label="Санаи пӯшидашавӣ" :value="formatDate(vacancy.closed_at)" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Эҷодкунанда" :value="vacancy.creator?.name" />
                        </v-col>
                    </v-row>
                </v-card>

                <v-card v-if="vacancy.requirements || vacancy.description" elevation="0" class="rounded-xl border pa-5 bg-white">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Талабот ва тавсиф
                    </div>
                    <v-row dense>
                        <v-col v-if="vacancy.requirements" cols="12" class="py-2">
                            <DataField label="Талабот ба номзад">
                                <span style="white-space: pre-line;">{{ vacancy.requirements }}</span>
                            </DataField>
                        </v-col>
                        <v-col v-if="vacancy.description" cols="12" class="py-2">
                            <DataField label="Тавсиф">
                                <span style="white-space: pre-line;">{{ vacancy.description }}</span>
                            </DataField>
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
