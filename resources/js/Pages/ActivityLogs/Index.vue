<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    History, 
    Search, 
    FilterX, 
    FileCode
} from '@lucide/vue';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || null);

watch([eventFilter], () => {
    applyFilters();
});

function applyFilters() {
    router.get(route('activity-logs.index'), {
        search: search.value || undefined,
        event: eventFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    eventFilter.value = null;
    router.get(route('activity-logs.index'));
}

function changePage(page) {
    router.get(route('activity-logs.index'), {
        page: page,
        search: search.value || undefined,
        event: eventFilter.value || undefined,
    }, {
        preserveState: true,
    });
}

function getEventColor(event) {
    switch (event) {
        case 'created': return 'success';
        case 'updated': return 'indigo';
        case 'deleted': return 'error';
        default: return 'grey';
    }
}

function getEventText(event) {
    switch (event) {
        case 'created': return 'Создание';
        case 'updated': return 'Обновление';
        case 'deleted': return 'Удаление';
        default: return event;
    }
}

function hasChanges(properties) {
    return properties && (properties.attributes || properties.old);
}
</script>

<template>
    <Head title="Логи действий" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <History style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Лог действий (Аудит)</span>
            </div>
        </template>

        <!-- Filters section -->
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="5">
                    <v-text-field
                        v-model="search"
                        label="Поиск по описанию действия"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        class="bg-surface"
                        @keyup.enter="applyFilters"
                    >
                        <template v-slot:prepend-inner>
                            <Search style="width: 16px; height: 16px; margin-right: 4px;" class="text-grey-darken-1" />
                        </template>
                    </v-text-field>
                </v-col>

                <!-- Event Filter -->
                <v-col cols="12" sm="4">
                    <v-select
                        v-model="eventFilter"
                        :items="['created', 'updated', 'deleted']"
                        label="Тип действия"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        class="bg-surface"
                    >
                        <template v-slot:item="{ props, item }">
                            <v-list-item v-bind="props" :title="getEventText(item.raw)"></v-list-item>
                        </template>
                        <template v-slot:selection="{ item }">
                            <span>{{ getEventText(item.raw) }}</span>
                        </template>
                    </v-select>
                </v-col>

                <!-- Reset button -->
                <v-col cols="12" sm="3" class="d-flex justify-end">
                    <v-btn
                        variant="tonal"
                        color="secondary"
                        rounded="lg"
                        block
                        class="transition-hover-btn font-weight-bold"
                        @click="resetFilters"
                    >
                        <template v-slot:prepend>
                            <FilterX style="width: 16px; height: 16px; margin-right: 4px;" />
                        </template>
                        Сбросить фильтры
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Timeline list -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <v-timeline density="compact" align="start" class="activity-timeline">
                <v-timeline-item
                    v-for="log in logs.data"
                    :key="log.id"
                    :dot-color="getEventColor(log.event)"
                    size="small"
                    class="mb-6"
                >
                    <div class="d-flex justify-space-between align-center mb-1">
                        <div>
                            <v-chip :color="getEventColor(log.event)" size="x-small" class="mr-2 font-weight-black text-uppercase px-2" variant="flat">
                                {{ getEventText(log.event) }}
                            </v-chip>
                            <span class="font-weight-black text-subtitle-2 text-indigo-darken-3">{{ log.causer_name }}</span>
                            <span class="text-caption text-grey ml-3">{{ log.created_at }}</span>
                        </div>
                        <v-chip size="x-small" color="secondary" variant="outlined" class="font-weight-medium">
                            {{ log.subject_type }}
                        </v-chip>
                    </div>
                    
                    <div class="text-body-1 font-weight-bold text-grey-darken-3 mb-3 pl-1">
                        {{ log.description }}
                    </div>

                    <!-- Changes Diff details -->
                    <v-expansion-panels v-if="hasChanges(log.properties)" class="elevation-0 border rounded-lg overflow-hidden max-width-diff bg-surface">
                        <v-expansion-panel elevation="0">
                            <v-expansion-panel-title class="py-2 px-4 text-caption font-weight-black text-grey-darken-1 d-flex align-center">
                                <FileCode style="width: 16px; height: 16px; margin-right: 8px;" class="text-indigo" />
                                Показать детали изменений
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="pa-0">
                                <v-table density="compact" class="border-0 table-diff">
                                    <thead>
                                        <tr class="bg-indigo-lighten-5">
                                            <th class="font-weight-black text-caption text-left pa-2 text-indigo text-uppercase">Поле</th>
                                            <th v-if="log.properties.old" class="font-weight-black text-caption text-left pa-2 text-error text-uppercase">Было</th>
                                            <th class="font-weight-black text-caption text-left pa-2 text-success text-uppercase">Стало</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(val, key) in log.properties.attributes" :key="key">
                                            <td class="font-weight-bold text-caption text-grey-darken-2 pa-2 font-mono">{{ key }}</td>
                                            <td v-if="log.properties.old" class="text-caption text-error bg-red-lighten-5 pa-2 font-weight-bold">
                                                {{ log.properties.old[key] !== null && log.properties.old[key] !== '' ? log.properties.old[key] : 'пусто' }}
                                            </td>
                                            <td class="text-caption text-success bg-green-lighten-5 pa-2 font-weight-bold">
                                                {{ val !== null && val !== '' ? val : 'пусто' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-timeline-item>
                
                <v-timeline-item v-if="logs.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey font-weight-medium py-4">Логи действий отсутствуют.</div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Показано {{ logs.from || 0 }} - {{ logs.to || 0 }} из {{ logs.total || 0 }} логов
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
.transition-hover-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
.max-width-diff {
    max-width: 100%;
}
.table-diff {
    border-radius: 8px;
    overflow: hidden;
}
.font-mono {
    font-family: monospace, Courier, monospace;
}
.activity-timeline {
    padding-right: 8px;
}
</style>
