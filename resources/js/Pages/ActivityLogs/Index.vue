<script setup>
import { Head, router } from '@inertiajs/vue3';
import { FilterX, History, Search, Trash2, TriangleAlert } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActivityLogItem from '@/Pages/ActivityLogs/ActivityLogItem.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || null);

watch(eventFilter, applyFilters);

// Применяем текстовый поиск во время ввода с задержкой (@vueuse/core).
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
    // Свежий (без preserveState) переход перемонтирует страницу с пустыми фильтрами,
    // что заново инициализирует ref'ы search/event — поэтому здесь их очищать НЕ нужно,
    // иначе наблюдатели отправили бы второй, лишний запрос.
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

// Варианты для выпадающего фильтра событий с подписями на таджикском. Ведущий
// вариант null ("Ҳама") — значение по умолчанию, так что поле всегда показывает
// выбранное значение, а не плавающий placeholder.
const eventFilterOptions = [
    { value: null, title: 'Ҳама' },
    { value: 'created', title: 'Эҷодшуда' },
    { value: 'updated', title: 'Навсозӣшуда' },
    { value: 'deleted', title: 'Несткардашуда' },
];

const clearDialog = ref(false);
const clearing = ref(false);

function clearLogs() {
    clearing.value = true;
    router.delete(route('activity-logs.clear'), {
        preserveScroll: true,
        onFinish: () => {
            clearing.value = false;
            clearDialog.value = false;
        },
    });
}
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

        <v-card elevation="0" class="rounded-xl border pa-4 bg-surface-glass mb-6 app-form">
            <v-row class="align-end" density="comfortable">
                <v-col cols="12" md="5">
                    <label class="filter-label">Ҷустуҷӯ</label>
                    <v-text-field
                        v-model="search"
                        placeholder="Аз рӯи тавсифи амал..."
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                    >
                        <template #prepend-inner>
                            <Search style="width: 18px; height: 18px; opacity: 0.5;" />
                        </template>
                    </v-text-field>
                </v-col>

                <v-col cols="12" sm="6" md="3">
                    <label class="filter-label">Намуди амал</label>
                    <v-select
                        v-model="eventFilter"
                        :items="eventFilterOptions"
                        item-title="title"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                    />
                </v-col>

                <v-col cols="auto" class="d-flex align-end">
                    <v-btn
                        variant="text"
                        icon
                        rounded="lg"
                        size="large"
                        class="text-grey-darken-2"
                        @click="resetFilters"
                    >
                        <FilterX style="width: 20px; height: 20px;" />
                        <v-tooltip activator="parent" location="top">
                            Поксозии филтр
                        </v-tooltip>
                    </v-btn>
                </v-col>

                <v-col v-if="isAdmin" cols="6" sm="3" md="2">
                    <v-btn
                        variant="tonal"
                        color="error"
                        rounded="lg"
                        size="large"
                        class="w-100 font-weight-medium"
                        :disabled="logs.total === 0"
                        @click="clearDialog = true"
                    >
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px;" />
                        </template>
                        Тоза кардан
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass">
            <div class="d-flex align-center justify-space-between mb-4">
                <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4">
                    Таърихи амалҳо
                </div>
                <span class="text-caption text-grey font-weight-bold">
                    Ҳамагӣ: {{ logs.total || 0 }}
                </span>
            </div>

            <div v-if="logs.data.length > 0" class="d-flex flex-column ga-3">
                <ActivityLogItem v-for="log in logs.data" :key="log.id" :log="log" />
            </div>

            <div v-else class="text-center py-12">
                <History style="width: 44px; height: 44px; opacity: 0.4; margin: 0 auto 8px;" class="text-grey" />
                <div class="text-body-1 text-grey font-weight-medium">
                    Сабти амалҳо вуҷуд надорад.
                </div>
            </div>

            <template v-if="logs.data.length > 0">
                <v-divider class="my-4" />
                <div class="d-flex justify-space-between align-center px-1">
                    <div class="text-caption text-grey font-weight-bold">
                        Нишон дода шуд {{ logs.from || 0 }} – {{ logs.to || 0 }} аз {{ logs.total || 0 }} сабт
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
            </template>
        </v-card>

        <v-dialog v-model="clearDialog" max-width="440px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div class="pa-6 text-center">
                    <v-avatar color="red-lighten-5" size="56" class="mb-3">
                        <TriangleAlert style="width: 28px; height: 28px;" class="text-error" />
                    </v-avatar>
                    <h3 class="text-h6 font-weight-bold text-grey-darken-4 mb-2">
                        Тоза кардани таърихи амалҳо?
                    </h3>
                    <p class="text-body-2 text-grey-darken-1">
                        Ҳамаи <b>{{ logs.total || 0 }}</b> сабти аудит нест карда мешавад. Ин амал бебозгашт аст.
                    </p>
                </div>
                <v-divider />
                <v-card-actions class="pa-4">
                    <v-btn variant="text" rounded="lg" :disabled="clearing" @click="clearDialog = false">
                        Бекор кардан
                    </v-btn>
                    <v-spacer />
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        class="px-5 font-weight-medium"
                        :loading="clearing"
                        @click="clearLogs"
                    >
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px;" />
                        </template>
                        Бале, тоза кун
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
