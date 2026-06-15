<script setup>
import { ClipboardList, Download, FileText, ListChecks, User } from '@lucide/vue';
import { computed } from 'vue';
import DataField from '@/Components/DataField.vue';
import DialogHeader from '@/Components/DialogHeader.vue';

const props = defineProps({
    application: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
});

const open = defineModel({ type: Boolean, default: false });

// Анкета бота приходит произвольным объектом ключ→значение. Нескалярные значения
// приводим к строке, чтобы массивы/объекты не отрисовывались как «[object Object]».
const surveyEntries = computed(() => {
    const survey = props.application?.survey;
    if (!survey || typeof survey !== 'object') {
        return [];
    }
    return Object.entries(survey).map(([key, value]) => ({
        key,
        value: value !== null && typeof value === 'object' ? JSON.stringify(value) : value,
    }));
});

const vacancyTitle = computed(
    () => props.application?.vacancy?.title || props.application?.vacancy_title || null,
);
</script>

<template>
    <v-dialog v-model="open" max-width="680px">
        <v-card v-if="application" class="rounded-xl overflow-hidden" elevation="8">
            <DialogHeader kicker="Ариза" :title="application.name || 'Номзад'">
                <template #icon>
                    <User style="width: 22px; height: 22px; color: white;" />
                </template>
            </DialogHeader>

            <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                <!-- 1. Маълумоти номзад -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <User style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Маълумоти номзад
                    </div>
                    <v-row density="comfortable">
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Ному насаб" :value="application.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Телефон" :value="application.phone" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Email" :value="application.email" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Вакансия" :value="vacancyTitle" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Манбаъ" :value="application.source" />
                        </v-col>
                        <v-col v-if="isAdmin" cols="12" sm="6" class="py-2">
                            <DataField label="Филиал" :value="application.branch?.name" />
                        </v-col>
                        <v-col cols="12" sm="6" class="py-2">
                            <DataField label="Сана" :value="application.created_at" />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 2. Шарҳ -->
                <v-card v-if="application.summary" elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <FileText style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Шарҳ
                    </div>
                    <DataField label="Шарҳи кӯтоҳ" :value="application.summary" multiline />
                </v-card>

                <!-- 3. Анкета -->
                <v-card v-if="surveyEntries.length" elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <ListChecks style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Анкета
                    </div>
                    <v-row density="comfortable">
                        <v-col v-for="entry in surveyEntries" :key="entry.key" cols="12" class="py-2">
                            <DataField :label="entry.key" :value="entry.value" multiline />
                        </v-col>
                    </v-row>
                </v-card>

                <!-- 4. Резюме -->
                <v-card elevation="0" class="rounded-xl border pa-5 bg-white">
                    <div class="d-flex align-center section-title mb-4">
                        <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                            <ClipboardList style="width: 16px; height: 16px;" />
                        </v-avatar>
                        Резюме
                    </div>
                    <a
                        v-if="application.has_resume && application.resume_download_url"
                        :href="application.resume_download_url"
                        target="_blank"
                        class="d-inline-flex align-center text-indigo font-weight-medium resume-link"
                    >
                        <Download style="width: 16px; height: 16px;" class="mr-2" />
                        {{ application.resume_filename || 'Зеркашии резюме' }}
                    </a>
                    <span v-else class="text-grey">Резюме замима нашудааст</span>
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
.resume-link {
    text-decoration: none;
}
.resume-link:hover {
    text-decoration: underline;
}
</style>
