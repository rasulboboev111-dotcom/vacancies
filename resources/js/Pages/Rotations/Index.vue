<script setup>
import { Head, router } from '@inertiajs/vue3';
import { GitFork } from '@lucide/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RotationTimelineItem from '@/Pages/Rotations/RotationTimelineItem.vue';

defineProps({
    rotations: { type: Object, required: true },
});

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
                <RotationTimelineItem
                    v-for="rotation in rotations.data"
                    :key="rotation.id"
                    :rotation="rotation"
                />

                <v-timeline-item v-if="rotations.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey font-weight-medium py-4 text-center">
                        <GitFork style="width: 40px; height: 40px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                        Ҳанӯз ягон ҷобаҷогузорӣ дар система ба қайд гирифта нашудааст.
                    </div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4" />
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
                />
            </div>
        </v-card>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.rotation-timeline {
    padding-right: 8px;
}
</style>
