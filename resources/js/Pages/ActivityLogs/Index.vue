<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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
        case 'updated': return 'primary';
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
            Лог действий (Аудит)
        </template>

        <!-- Filters section -->
        <v-card elevation="2" class="rounded-xl pa-5 mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="5">
                    <v-text-field
                        v-model="search"
                        label="Поиск по описанию действия"
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        @keyup.enter="applyFilters"
                    ></v-text-field>
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
                        @click="resetFilters"
                    >
                        Сбросить фильтры
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Timeline list -->
        <v-card elevation="2" class="rounded-xl pa-5 mb-6">
            <v-timeline density="compact" align="start">
                <v-timeline-item
                    v-for="log in logs.data"
                    :key="log.id"
                    :dot-color="getEventColor(log.event)"
                    size="small"
                    class="mb-4"
                >
                    <div class="d-flex justify-space-between align-center mb-1">
                        <div>
                            <v-chip :color="getEventColor(log.event)" size="x-small" class="mr-2 font-weight-bold">
                                {{ getEventText(log.event) }}
                            </v-chip>
                            <span class="font-weight-bold text-subtitle-2">{{ log.causer_name }}</span>
                            <span class="text-caption text-grey ml-3">{{ log.created_at }}</span>
                        </div>
                        <span class="text-caption font-weight-medium bg-grey-lighten-4 px-2 py-1 rounded text-grey">
                            {{ log.subject_type }}
                        </span>
                    </div>
                    
                    <div class="text-body-1 font-weight-medium text-grey-darken-3 mb-2">
                        {{ log.description }}
                    </div>

                    <!-- Changes Diff details -->
                    <v-expansion-panels v-if="hasChanges(log.properties)" class="elevation-0 border rounded-lg overflow-hidden">
                        <v-expansion-panel elevation="0">
                            <v-expansion-panel-title class="py-1 px-4 text-caption font-weight-medium text-grey">
                                Показать детали изменений
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="pa-0">
                                <v-table density="compact" class="border-0">
                                    <thead>
                                        <tr class="bg-grey-lighten-5">
                                            <th class="font-weight-bold text-caption text-left pa-2">Поле</th>
                                            <th v-if="log.properties.old" class="font-weight-bold text-caption text-left pa-2">Было</th>
                                            <th class="font-weight-bold text-caption text-left pa-2">Стало</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(val, key) in log.properties.attributes" :key="key">
                                            <td class="font-weight-bold text-caption text-grey pa-2">{{ key }}</td>
                                            <td v-if="log.properties.old" class="text-caption text-error bg-red-lighten-5 pa-2">
                                                {{ log.properties.old[key] !== null ? log.properties.old[key] : 'пусто' }}
                                            </td>
                                            <td class="text-caption text-success bg-green-lighten-5 pa-2">
                                                {{ val !== null ? val : 'пусто' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-timeline-item>
                
                <v-timeline-item v-if="logs.data.length === 0" dot-color="grey" size="small">
                    <div class="text-body-1 text-grey py-4">Логи действий отсутствуют.</div>
                </v-timeline-item>
            </v-timeline>

            <!-- Pagination -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey">
                    Показано {{ logs.from || 0 }} - {{ logs.to || 0 }} из {{ logs.total || 0 }} логов
                </div>
                <v-pagination
                    v-if="logs.last_page > 1"
                    :model-value="logs.current_page"
                    :length="logs.last_page"
                    :total-visible="5"
                    density="comfortable"
                    rounded="lg"
                    @update:model-value="changePage"
                ></v-pagination>
            </div>
        </v-card>
    </AuthenticatedLayout>
</template>
