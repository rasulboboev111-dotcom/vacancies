<script setup>
import { Download, FileX, Pencil, Trash2 } from '@lucide/vue';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
    applications: { type: Object, required: true },
    canManage: { type: Function, required: true },
});

defineEmits(['view', 'edit', 'delete', 'change-page']);

const { canManageInBranch } = usePermissions();

function canEdit(app) {
    return canManageInBranch('edit applications', app.branch_id);
}

function canDelete(app) {
    return props.canManage(app);
}

function hasSurvey(survey) {
    return survey && typeof survey === 'object' && Object.keys(survey).length > 0;
}
</script>

<template>
    <v-card elevation="0" class="rounded-xl border overflow-hidden bg-surface-glass">
        <v-table class="w-100 table-modern">
            <thead>
                <tr class="bg-indigo-lighten-5">
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Ному насаб
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Телефон
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Email
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Вакансия
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo">
                        Филиал
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Манбаъ
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Анкета
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Резюме
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center" style="min-width: 140px;">
                        Сана
                    </th>
                    <th class="font-weight-black text-subtitle-2 pa-3 text-indigo text-center">
                        Амалҳо
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="app in applications.data" :key="app.id" class="app-row" @click="$emit('view', app)">
                    <td class="pa-3">
                        <span class="app-name">{{ app.name }}</span>
                    </td>
                    <td class="pa-3 text-body-2 app-secondary">
                        {{ app.phone || '—' }}
                    </td>
                    <td class="pa-3 text-body-2 app-secondary">
                        {{ app.email || '—' }}
                    </td>
                    <td class="pa-3">
                        <div class="text-truncate col-vacancy app-secondary" :title="app.vacancy?.title || app.vacancy_title || '—'">
                            {{ app.vacancy?.title || app.vacancy_title || '—' }}
                        </div>
                    </td>
                    <td class="pa-3">
                        <div class="text-truncate col-branch app-dim" :title="app.branch?.name || '—'">
                            {{ app.branch?.name || '—' }}
                        </div>
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip v-if="app.source" size="small" color="indigo" variant="tonal" class="font-weight-bold">
                            {{ app.source }}
                        </v-chip>
                        <span v-else class="app-dim">—</span>
                    </td>
                    <td class="pa-3 text-center">
                        <v-chip v-if="hasSurvey(app.survey)" size="small" color="success" variant="tonal" class="font-weight-bold">
                            Ҳаст
                        </v-chip>
                        <span v-else class="app-dim">—</span>
                    </td>
                    <td class="pa-3 text-center">
                        <a
                            v-if="app.has_resume && app.resume_download_url"
                            :href="app.resume_download_url"
                            target="_blank"
                            class="resume-download-btn hover-scale-btn act-btn act-accent"
                            title="Зеркашии резюме"
                            @click.stop
                        >
                            <Download style="width: 16px; height: 16px;" />
                        </a>
                        <span v-else class="app-dim">—</span>
                    </td>
                    <td class="pa-3 text-body-2 app-secondary text-center">
                        {{ app.created_at }}
                    </td>
                    <td class="pa-3 text-center">
                        <v-btn
                            v-if="canEdit(app)"
                            variant="text"
                            size="small"
                            class="mr-1 hover-scale-btn act-btn act-accent"
                            title="Таҳрир"
                            @click.stop="$emit('edit', app)"
                        >
                            <Pencil style="width: 16px; height: 16px;" />
                        </v-btn>

                        <v-btn
                            v-if="canDelete(app)"
                            variant="text"
                            size="small"
                            class="hover-scale-btn act-btn act-danger"
                            title="Нест кардан"
                            @click.stop="$emit('delete', app)"
                        >
                            <Trash2 style="width: 16px; height: 16px;" />
                        </v-btn>
                    </td>
                </tr>
                <tr v-if="applications.data.length === 0">
                    <td colspan="10" class="text-center py-10 text-grey text-h6 font-weight-medium bg-surface">
                        <FileX class="h-10 w-10 text-grey-lighten-1 mx-auto mb-2 opacity-50" /><br>
                        Аризаҳо ёфт нашуданд.
                    </td>
                </tr>
            </tbody>
        </v-table>

        <!-- Pagination -->
        <v-divider />
        <div class="d-flex justify-space-between align-center pa-3 bg-surface">
            <div class="text-caption text-grey font-weight-bold">
                Нишон дода шуд {{ applications.from || 0 }} - {{ applications.to || 0 }} аз {{ applications.total || 0 }} ариза
            </div>
            <v-pagination
                v-if="applications.last_page > 1"
                :model-value="applications.current_page"
                :length="applications.last_page"
                :total-visible="5"
                density="comfortable"
                rounded="lg"
                active-color="indigo"
                @update:model-value="(p) => $emit('change-page', p)"
            />
        </div>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}

.col-vacancy {
    max-width: 220px;
}
.col-branch {
    max-width: 160px;
}

.app-name {
    font-weight: 600;
    color: #111111;
}
.app-secondary {
    color: #555555;
}
.app-dim {
    color: #999999;
}

.table-modern tbody tr.app-row {
    cursor: pointer;
}
.table-modern tbody tr.app-row:nth-child(even) {
    background: #fafafb;
}
.table-modern tbody tr.app-row:hover {
    background: rgba(99, 102, 241, 0.07);
    box-shadow: inset 3px 0 0 0 #5c6bc0;
}

.act-btn {
    color: #9ca3af !important;
}
.act-accent:hover {
    color: #3f51b5 !important;
}
.act-danger:hover {
    color: #e53935 !important;
}

/* Resume download link styled as an icon button */
.resume-download-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    transition: color 0.15s ease, transform 0.15s ease;
    text-decoration: none;
}
.resume-download-btn:hover {
    color: #3f51b5 !important;
    transform: scale(1.1);
}
</style>
