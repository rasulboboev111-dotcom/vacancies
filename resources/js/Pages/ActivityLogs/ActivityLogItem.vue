<script setup>
import { ChevronDown, Clock } from '@lucide/vue';
import { ref } from 'vue';
import { getEventText, getSubjectText } from '@/Pages/ActivityLogs/activityEvents';

defineProps({
    log: { type: Object, required: true },
});

const open = ref(false);

function hasChanges(properties) {
    return properties && (properties.attributes || properties.old);
}

// Логи через трейт LogsActivity без своего текста получают description = само
// событие («created»/«updated»/«deleted»). Оно уже показано таджикским чипом
// события, поэтому такую дублирующую английскую строку не выводим — оставляем
// только осмысленные ручные описания.
function meaningfulDescription(description) {
    return description && !['created', 'updated', 'deleted'].includes(description);
}

// Показываем заглушку для null/пустых значений полей в таблице различий.
function displayValue(value) {
    return value !== null && value !== '' ? value : 'холӣ';
}

function initial(name) {
    return (name || '?').charAt(0).toUpperCase();
}
</script>

<template>
    <div class="log-row" :class="`log-row--${log.event}`">
        <div class="log-row__avatar" :class="`log-row__avatar--${log.event}`">
            {{ initial(log.causer_name) }}
        </div>

        <div class="log-row__body">
            <div class="d-flex align-center flex-wrap ga-2 mb-1">
                <span class="log-row__who">{{ log.causer_name }}</span>
                <span class="log-row__event" :class="`log-row__event--${log.event}`">
                    {{ getEventText(log.event) }}
                </span>
                <span class="log-row__subject">{{ getSubjectText(log.subject_type) }}</span>
            </div>

            <div v-if="meaningfulDescription(log.description)" class="log-row__desc">
                {{ log.description }}
            </div>

            <div class="d-flex align-center flex-wrap ga-4 mt-2">
                <span class="log-row__meta">
                    <Clock style="width: 13px; height: 13px;" /> {{ log.created_at }}
                </span>
                <button
                    v-if="hasChanges(log.properties)"
                    type="button"
                    class="log-row__toggle"
                    @click="open = !open"
                >
                    <ChevronDown style="width: 14px; height: 14px;" :class="{ 'log-row__chev--open': open }" />
                    {{ open ? 'Пинҳон кардани тағйирот' : 'Тафсилоти тағйирот' }}
                </button>
            </div>

            <v-expand-transition>
                <div v-if="open && hasChanges(log.properties)" class="log-row__diff">
                    <v-table density="compact" class="table-diff">
                        <thead>
                            <tr>
                                <th class="diff-head">
                                    Майдон
                                </th>
                                <th v-if="log.properties.old" class="diff-head text-error">
                                    Буд
                                </th>
                                <th class="diff-head text-success">
                                    Шуд
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, key) in log.properties.attributes" :key="key">
                                <td class="diff-key">
                                    {{ key }}
                                </td>
                                <td v-if="log.properties.old" class="diff-old">
                                    {{ displayValue(log.properties.old[key]) }}
                                </td>
                                <td class="diff-new">
                                    {{ displayValue(val) }}
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </div>
            </v-expand-transition>
        </div>
    </div>
</template>

<style scoped>
.log-row {
    display: flex;
    gap: 14px;
    padding: 16px 18px;
    border: 1px solid #eef1f5;
    border-left: 3px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.log-row:hover {
    box-shadow: 0 6px 18px -10px rgba(15, 23, 42, 0.18);
}
.log-row--created {
    border-left-color: #10b981;
}
.log-row--updated {
    border-left-color: #009cf1;
}
.log-row--deleted {
    border-left-color: #ef4444;
}

.log-row__avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #475569;
    background: #f1f5f9;
}
.log-row__avatar--created {
    color: #047857;
    background: rgba(16, 185, 129, 0.13);
}
.log-row__avatar--updated {
    color: #0284c7;
    background: rgba(0, 156, 241, 0.13);
}
.log-row__avatar--deleted {
    color: #b91c1c;
    background: rgba(239, 68, 68, 0.13);
}

.log-row__body {
    min-width: 0;
    flex: 1;
}
.log-row__who {
    font-weight: 700;
    font-size: 0.9rem;
    color: #0f172a;
}
.log-row__event {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 2px 8px;
    border-radius: 999px;
}
.log-row__event--created {
    color: #047857;
    background: rgba(16, 185, 129, 0.13);
}
.log-row__event--updated {
    color: #0284c7;
    background: rgba(0, 156, 241, 0.13);
}
.log-row__event--deleted {
    color: #b91c1c;
    background: rgba(239, 68, 68, 0.13);
}
.log-row__subject {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 1px 8px;
    border-radius: 999px;
}
.log-row__desc {
    font-size: 0.875rem;
    color: #334155;
    line-height: 1.45;
    overflow-wrap: anywhere;
}
.log-row__meta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
}
.log-row__toggle {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #009cf1;
    cursor: pointer;
    background: none;
    border: 0;
    padding: 0;
}
.log-row__toggle:hover {
    text-decoration: underline;
}
.log-row__chev--open {
    transform: rotate(180deg);
    transition: transform 0.2s ease;
}

.log-row__diff {
    margin-top: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.table-diff :deep(.diff-head) {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #f8fafc;
    color: #64748b;
}
.table-diff :deep(.diff-key) {
    font-weight: 600;
    font-size: 0.78rem;
    color: #475569;
}
.table-diff :deep(.diff-old) {
    font-size: 0.78rem;
    font-weight: 600;
    color: #b91c1c;
    background: rgba(239, 68, 68, 0.05);
    text-decoration: line-through;
    text-decoration-color: rgba(185, 28, 28, 0.4);
}
.table-diff :deep(.diff-new) {
    font-size: 0.78rem;
    font-weight: 600;
    color: #047857;
    background: rgba(16, 185, 129, 0.05);
}
</style>
