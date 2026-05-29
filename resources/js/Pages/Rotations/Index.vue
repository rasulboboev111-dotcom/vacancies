<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
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

function changePage(page) {
    router.get(route('rotations.index'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="Таърихи ҷобаҷогузорӣ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <GitFork style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Таърихи ҷобаҷогузорӣ ва ивазкунии вазифаҳо</span>
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
                                {{ rotation.employee ? rotation.employee.full_name : 'Корманд несткардашуда' }}
                            </span>
                            <span class="text-caption text-grey ml-3 d-inline-flex align-center">
                                <Clock style="width: 12px; height: 12px; margin-right: 4px;" class="text-grey" />
                                {{ formatDate(rotation.rotation_date) }}
                            </span>
                        </div>
                        <v-chip size="x-small" color="indigo" variant="outlined" class="font-weight-bold">
                            Санаи ҷобаҷогузорӣ: {{ formatDate(rotation.rotation_date) }}
                        </v-chip>
                    </div>

                    <!-- Rotation details visual board -->
                    <v-row class="mt-2 pl-1 mb-2">
                        <v-col cols="12" md="5" class="py-1">
                            <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-error">
                                <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Таъиноти кӯҳна</span>
                                <div class="font-weight-bold text-body-2 text-indigo-darken-2 mt-1">
                                    {{ rotation.old_position?.name || '-' }}
                                </div>
                                <div class="text-caption text-grey-darken-3 font-weight-medium">
                                    {{ rotation.old_structure?.name || '-' }}
                                </div>
                                <v-chip size="x-small" color="error" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                                    {{ rotation.old_branch ? rotation.old_branch.name : 'Филиали несткардашуда' }}
                                </v-chip>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="2" class="d-flex justify-center align-center py-2">
                            <v-avatar color="indigo-lighten-5" size="40" class="border">
                                <ArrowRight style="width: 16px; height: 16px;" class="text-indigo hidden-sm-and-down" />
                                <ArrowDown style="width: 16px; height: 16px;" class="text-indigo hidden-md-and-up" />
                            </v-avatar>
                        </v-col>

                        <v-col cols="12" md="5" class="py-1">
                            <v-card elevation="0" class="pa-3 rounded-lg border bg-surface-darken text-center border-l-4 border-success">
                                <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Таъиноти нав</span>
                                <div class="font-weight-bold text-body-2 text-indigo-darken-4 mt-1">
                                    {{ rotation.new_position?.name || '-' }}
                                </div>
                                <div class="text-caption text-grey-darken-3 font-weight-medium">
                                    {{ rotation.new_structure?.name || '-' }}
                                </div>
                                <v-chip size="x-small" color="success" variant="tonal" class="mt-2 font-weight-bold text-uppercase">
                                    {{ rotation.new_branch ? rotation.new_branch.name : 'Филиали несткардашуда' }}
                                </v-chip>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Reason box -->
                    <div v-if="rotation.reason" class="text-body-2 text-grey-darken-3 font-weight-medium bg-indigo-lighten-5 pa-3 rounded-lg border pl-4 border-l-4 border-indigo mt-3">
                        <span class="d-flex align-center text-caption text-indigo font-weight-bold text-uppercase mb-1">
                            <Info style="width: 16px; height: 16px; margin-right: 4px;" class="text-indigo" />
                            Асос / Сабаби ҷобаҷогузорӣ:
                        </span>
                        {{ rotation.reason }}
                    </div>
                </v-timeline-item>
                
                <v-timeline-item v-if="rotations.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey font-weight-medium py-4 text-center">
                        <GitFork style="width: 40px; height: 40px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                        Ҳанӯз ягон ҷобаҷогузорӣ дар система ба қайд гирифта нашудааст.
                    </div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Нишон дода шуд {{ rotations.from || 0 }} - {{ rotations.to || 0 }} аз {{ rotations.total || 0 }} сабти ҷобаҷогузорӣ
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
