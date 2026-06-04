<script setup>
import { FileCode } from '@lucide/vue';
import { getEventColor, getEventText, getSubjectText } from '@/Pages/ActivityLogs/activityEvents';

defineProps({
    log: { type: Object, required: true },
});

function hasChanges(properties) {
    return properties && (properties.attributes || properties.old);
}

// Show a placeholder for null/empty field values in the diff table.
function displayValue(value) {
    return value !== null && value !== '' ? value : 'холӣ';
}
</script>

<template>
    <v-timeline-item :dot-color="getEventColor(log.event)" size="small" class="mb-6">
        <div class="d-flex justify-space-between align-center mb-1">
            <div>
                <v-chip :color="getEventColor(log.event)" size="x-small" class="mr-2 font-weight-black text-uppercase px-2" variant="flat">
                    {{ getEventText(log.event) }}
                </v-chip>
                <span class="font-weight-black text-subtitle-2 text-indigo-darken-3">{{ log.causer_name }}</span>
                <span class="text-caption text-grey ml-3">{{ log.created_at }}</span>
            </div>
            <v-chip size="x-small" color="secondary" variant="outlined" class="font-weight-medium">
                {{ getSubjectText(log.subject_type) }}
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
                    Нишон додани тафсилоти тағйирот
                </v-expansion-panel-title>
                <v-expansion-panel-text class="pa-0">
                    <v-table density="compact" class="border-0 table-diff">
                        <thead>
                            <tr class="bg-indigo-lighten-5">
                                <th class="font-weight-black text-caption text-left pa-2 text-indigo text-uppercase">
                                    Майдон
                                </th>
                                <th v-if="log.properties.old" class="font-weight-black text-caption text-left pa-2 text-error text-uppercase">
                                    Буд
                                </th>
                                <th class="font-weight-black text-caption text-left pa-2 text-success text-uppercase">
                                    Шуд
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, key) in log.properties.attributes" :key="key">
                                <td class="font-weight-bold text-caption text-grey-darken-2 pa-2 font-mono">
                                    {{ key }}
                                </td>
                                <td v-if="log.properties.old" class="text-caption text-error bg-red-lighten-5 pa-2 font-weight-bold">
                                    {{ displayValue(log.properties.old[key]) }}
                                </td>
                                <td class="text-caption text-success bg-green-lighten-5 pa-2 font-weight-bold">
                                    {{ displayValue(val) }}
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>
    </v-timeline-item>
</template>

<style scoped>
.max-width-diff {
    max-width: 100%;
}
.table-diff {
    border-radius: 8px;
    overflow: hidden;
}
</style>
