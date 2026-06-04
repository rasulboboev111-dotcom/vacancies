<script setup>
import { Briefcase, Calendar, Clock, DoorOpen, FileText, Layers, MapPin, Network, UserCheck, Users, Wallet } from '@lucide/vue';
import InfoField from '@/Components/InfoField.vue';
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
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ vacancy.title }}
                            </div>
                        </div>
                    </div>
                    <v-chip color="white" variant="flat" class="font-weight-bold" size="small" :style="{ color: vacancy.status === 'open' ? '#b45309' : '#475569' }">
                        {{ vacancy.status === 'open' ? 'Кушода' : 'Баста' }}
                    </v-chip>
                </div>
            </div>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <Briefcase style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти асосӣ
                    </div>
                    <v-row>
                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Briefcase" label="Вазифа">
                                <span class="text-body-2 font-weight-black text-indigo-darken-3">{{ vacancy.position?.name || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Users" label="Шумораи кормандони зарур">
                                <span class="text-body-2 font-weight-black text-slate-800">{{ vacancy.openings }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Network" label="Шуъба">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ vacancy.department?.name || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col v-if="isAdmin" cols="12" sm="6" class="py-2">
                            <InfoField :icon="MapPin" label="Филиал">
                                <span class="text-body-2 font-weight-bold text-indigo-accent-3 font-weight-black">{{ vacancy.branch?.name || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Layers" label="Намуди шуғл">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ vacancy.employment_type || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Clock" label="Ҷадвали корӣ">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ vacancy.schedule || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Wallet" label="Маош">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ vacancy.salary || '-' }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="Calendar" label="Санаи кушодашавӣ">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(vacancy.opened_at) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col v-if="vacancy.closed_at" cols="12" sm="6" class="py-2">
                            <InfoField :icon="Calendar" label="Санаи пӯшидашавӣ" icon-bg="bg-red-lighten-5" icon-color="text-error">
                                <span class="text-body-2 font-weight-bold text-error font-weight-black">{{ formatDate(vacancy.closed_at) }}</span>
                            </InfoField>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <InfoField :icon="UserCheck" label="Эҷодкунанда">
                                <span class="text-body-2 font-weight-bold text-slate-800">{{ vacancy.creator?.name || '-' }}</span>
                            </InfoField>
                        </v-col>
                    </v-row>
                </v-card>

                <v-card v-if="vacancy.requirements || vacancy.description" elevation="0" class="rounded-xl border pa-5 bg-white">
                    <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Талабот ва тавсиф
                    </div>
                    <v-row>
                        <v-col v-if="vacancy.requirements" cols="12" class="py-2">
                            <InfoField :icon="FileText" label="Талабот ба номзад">
                                <span class="text-body-2 text-slate-800" style="white-space: pre-line;">{{ vacancy.requirements }}</span>
                            </InfoField>
                        </v-col>

                        <v-col v-if="vacancy.description" cols="12" class="py-2">
                            <InfoField :icon="FileText" label="Тавсиф">
                                <span class="text-body-2 text-slate-800" style="white-space: pre-line;">{{ vacancy.description }}</span>
                            </InfoField>
                        </v-col>
                    </v-row>
                </v-card>
            </v-card-text>

            <v-divider />

            <v-card-actions class="px-6 py-4 bg-white d-flex justify-end">
                <v-btn color="indigo" variant="flat" size="large" class="bg-indigo px-6 font-weight-bold text-white" rounded="lg" @click="open = false">
                    Пӯшидан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
