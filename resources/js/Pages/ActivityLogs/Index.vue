<script setup>
import { Head, router } from '@inertiajs/vue3';
import { FilterX, History, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActivityLogItem from '@/Pages/ActivityLogs/ActivityLogItem.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || null);

watch(eventFilter, applyFilters);

// Auto-apply the text search while typing, debounced (@vueuse/core).
watchDebounced(search, applyFilters, { debounce: 400 });

function applyFilters() {
    router.get(route('activity-logs.index'), {
        filter: {
            search: search.value || undefined,
            event: eventFilter.value || undefined,
        },
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    // A fresh (non-preserveState) visit remounts the page with empty filters,
    // which re-initialises the search/event refs — so we must NOT clear them
    // here, or the watchers would fire a second, redundant request.
    router.get(route('activity-logs.index'));
}

function changePage(page) {
    router.get(route('activity-logs.index'), {
        page,
        filter: {
            search: search.value || undefined,
            event: eventFilter.value || undefined,
        },
    }, {
        preserveState: true,
    });
}

// Tajik-labelled options for the event filter dropdown.
const eventFilterOptions = [
    { value: 'created', title: 'Эҷодшуда' },
    { value: 'updated', title: 'Навсозӣшуда' },
    { value: 'deleted', title: 'Несткардашуда' },
];
</script>

<template>
    <Head title="Сабти амалҳо" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <History style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Сабти амалҳо (Аудит)</span>
            </div>
        </template>

        <!-- Filters section -->
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="12" md="5">
                    <v-text-field
                        v-model="search"
                        placeholder="Ҷустуҷӯ аз рӯи тавсифи амал..."
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        class="premium-field"
                    >
                        <template #prepend-inner>
                            <Search style="width: 18px; height: 18px; opacity: 0.5;" />
                        </template>
                    </v-text-field>
                </v-col>

                <!-- Event Filter -->
                <v-col cols="12" sm="8" md="4">
                    <v-select
                        v-model="eventFilter"
                        :items="eventFilterOptions"
                        item-title="title"
                        item-value="value"
                        label="Намуди амал"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                    />
                </v-col>

                <!-- Reset button -->
                <v-col cols="12" sm="4" md="3" class="d-flex align-center justify-md-end justify-center">
                    <v-btn
                        variant="flat"
                        rounded="lg"
                        class="transition-hover-btn font-weight-bold w-100"
                        style="background: rgba(0, 156, 241, 0.08) !important; color: #009cf1 !important; border: 1px solid rgba(0, 156, 241, 0.15) !important;"
                        @click="resetFilters"
                    >
                        <template #prepend>
                            <FilterX style="width: 16px; height: 16px; color: #009cf1;" />
                        </template>
                        Тоза кардан
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Timeline list -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <v-timeline density="compact" align="start" class="activity-timeline">
                <ActivityLogItem v-for="log in logs.data" :key="log.id" :log="log" />

                <v-timeline-item v-if="logs.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey font-weight-medium py-4">
                        Сабти амалҳо вуҷуд надорад.
                    </div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4" />
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Нишон дода шуд {{ logs.from || 0 }} - {{ logs.to || 0 }} аз {{ logs.total || 0 }} сабт
                </div>
                <v-pagination
                    v-if="logs.last_page > 1"
                    :model-value="logs.current_page"
                    :length="logs.last_page"
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
.activity-timeline {
    padding-right: 8px;
}
</style>
