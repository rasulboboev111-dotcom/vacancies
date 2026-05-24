<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    GitFork, 
    Clock, 
    ArrowRight, 
    ArrowDown, 
    Info 
} from '@lucide/vue';

const props = defineProps({
    rotations: {
        type: Object,
        required: true,
    },
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ru-RU');
}
</script>

<template>
    <Head title="История ротаций" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <GitFork class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>История ротаций и должностных перемещений</span>
            </div>
        </template>

        <!-- Timeline list -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <v-timeline density="compact" align="start" class="rotation-timeline">
                <v-timeline-item
                    v-for="rotation in rotations.data"
                    :key="rotation.id"
                    dot-color="indigo"
                    size="small"
                    class="mb-6"
                >
                    <div class="d-flex justify-space-between align-center mb-1">
                        <div>
                            <span class="font-weight-black text-subtitle-1 text-indigo-darken-4">
                                {{ rotation.employee ? rotation.employee.full_name : 'Сотрудник удален' }}
                            </span>
                            <span class="text-caption text-grey ml-3 d-inline-flex align-center">
                                <Clock class="h-3 w-3 mr-1 text-grey" />
                                {{ formatDate(rotation.rotation_date) }}
                            </span>
                        </div>
                        <v-chip size="x-small" color="indigo" variant="outlined" class="font-weight-bold">
                            Дата ротации: {{ formatDate(rotation.rotation_date) }}
                        </v-chip>
                    </div>

                    <!-- Rotation details visual board -->
                    <v-row class="mt-2 pl-1 mb-2">
                        <v-col cols="12" md="5" class="py-1">
                            <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-error">
                                <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Прежнее назначение</span>
                                <div class="font-weight-bold text-body-2 text-indigo-darken-2 mt-1">
                                    {{ rotation.old_position }}
                                </div>
                                <div class="text-caption text-grey-darken-3 font-weight-medium">
                                    {{ rotation.old_structure }}
                                </div>
                                <v-chip size="x-small" color="error" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                                    {{ rotation.old_branch ? rotation.old_branch.name : 'Удаленный филиал' }}
                                </v-chip>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="2" class="d-flex justify-center align-center py-2">
                            <v-avatar color="indigo-lighten-5" size="40" class="border">
                                <ArrowRight class="h-4 w-4 text-indigo hidden-sm-and-down" />
                                <ArrowDown class="h-4 w-4 text-indigo hidden-md-and-up" />
                            </v-avatar>
                        </v-col>

                        <v-col cols="12" md="5" class="py-1">
                            <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-success">
                                <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Новое назначение</span>
                                <div class="font-weight-bold text-body-2 text-indigo-darken-4 mt-1">
                                    {{ rotation.new_position }}
                                </div>
                                <div class="text-caption text-grey-darken-3 font-weight-medium">
                                    {{ rotation.new_structure }}
                                </div>
                                <v-chip size="x-small" color="success" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                                    {{ rotation.new_branch ? rotation.new_branch.name : 'Удаленный филиал' }}
                                </v-chip>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Reason box -->
                    <div v-if="rotation.reason" class="text-body-2 text-grey-darken-3 font-weight-medium bg-indigo-lighten-5 pa-3 rounded-lg border pl-4 border-l-4 border-indigo mt-3">
                        <span class="d-flex align-center text-caption text-indigo font-weight-bold text-uppercase mb-1">
                            <Info class="h-4 w-4 mr-1 text-indigo" />
                            Основание / Причина ротации:
                        </span>
                        {{ rotation.reason }}
                    </div>
                </v-timeline-item>
                
                <v-timeline-item v-if="rotations.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey font-weight-medium py-4 text-center">
                        <GitFork class="h-10 w-10 text-grey mx-auto mb-2 opacity-50" />
                        Ротаций в системе пока не зарегистрировано.
                    </div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Показано {{ rotations.from || 0 }} - {{ rotations.to || 0 }} из {{ rotations.total || 0 }} записей ротаций
                </div>
                <v-pagination
                    v-if="rotations.last_page > 1"
                    :model-value="rotations.current_page"
                    :length="rotations.last_page"
                    :total-visible="5"
                    density="comfortable"
                    rounded="lg"
                    active-color="indigo"
                    @update:model-value="changePage"
                ></v-pagination>
            </div>
        </v-card>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.bg-surface-darken {
    background-color: rgba(248, 250, 252, 0.9) !important;
}
.rotation-timeline {
    padding-right: 8px;
}
.border-l-4 {
    border-left-width: 4px !important;
}
</style>
