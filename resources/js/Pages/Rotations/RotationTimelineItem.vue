<script setup>
import { ArrowRight, Briefcase, Building2, CalendarDays, Network, Quote } from '@lucide/vue';
import { computed } from 'vue';
import { formatDate } from '@/lib/date';

const props = defineProps({
    rotation: { type: Object, required: true },
    // Позиция в списке — задаёт ступенчатую анимацию появления.
    index: { type: Number, default: 0 },
});

const employeeName = computed(() =>
    props.rotation.employee?.full_name?.trim() || 'Корманди несткардашуда',
);

// Двухбуквенная монограмма для аватара (инициалы первого и второго слова).
const initials = computed(() => {
    const parts = employeeName.value.split(/\s+/).filter(Boolean);
    const a = parts[0]?.[0] ?? '';
    const b = parts[1]?.[0] ?? '';
    return (a + b).toUpperCase() || '—';
});

// Три вещи, которые может изменить ротация. Для каждой вычисляем до/после
// и действительно ли значение изменилось, чтобы карточка подсвечивала реальные
// изменения и приглушала серым то, что осталось прежним.
const rows = computed(() => {
    const r = props.rotation;
    return [
        { key: 'position', label: 'Вазифа', icon: Briefcase, from: r.old_position?.name, to: r.new_position?.name },
        { key: 'department', label: 'Шуъба', icon: Network, from: r.old_department?.name, to: r.new_department?.name },
        { key: 'branch', label: 'Филиал', icon: Building2, from: r.old_branch?.name, to: r.new_branch?.name },
    ].map(row => ({
        ...row,
        from: row.from || '—',
        to: row.to || '—',
        changed: (row.from || '') !== (row.to || ''),
    }));
});

const changedCount = computed(() => rows.value.filter(r => r.changed).length);

const entranceDelay = computed(() => `${Math.min(props.index, 12) * 55}ms`);
</script>

<template>
    <article class="rot-card" :style="{ animationDelay: entranceDelay }">
        <div class="rot-body">
            <header class="rot-head">
                <div class="rot-emp">
                    <span class="rot-avatar">{{ initials }}</span>
                    <div class="rot-emp__meta">
                        <span class="rot-emp__name">{{ employeeName }}</span>
                        <span class="rot-emp__kicker">
                            Ҷобаҷогузории вазифа
                            <em v-if="changedCount" class="rot-emp__count">{{ changedCount }} тағйирот</em>
                        </span>
                    </div>
                </div>
                <span class="rot-date">
                    <CalendarDays class="rot-date__icon" />
                    {{ formatDate(rotation.rotation_date) }}
                </span>
            </header>

            <div class="rot-diff">
                <div v-for="row in rows" :key="row.key" class="rot-row" :class="{ 'is-changed': row.changed }">
                    <span class="rot-row__label">
                        <component :is="row.icon" class="rot-row__icon" />
                        {{ row.label }}
                    </span>
                    <div class="rot-flow">
                        <span class="rot-val rot-val--from" :title="row.from">{{ row.from }}</span>
                        <span class="rot-arrow"><ArrowRight /></span>
                        <span class="rot-val rot-val--to" :title="row.to">{{ row.to }}</span>
                        <span v-if="!row.changed" class="rot-same">бетағйир</span>
                    </div>
                </div>
            </div>

            <div v-if="rotation.reason" class="rot-reason">
                <Quote class="rot-reason__icon" />
                <p class="rot-reason__text">
                    <span class="rot-reason__label">Асос:</span>
                    {{ rotation.reason }}
                </p>
            </div>
        </div>
    </article>
</template>

<style scoped>
.rot-card {
    position: relative;
    opacity: 0;
    animation: rot-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
@keyframes rot-in {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

.rot-body {
    background: #ffffff;
    border: 1px solid #e6eaf1;
    border-radius: 18px;
    padding: 18px 20px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
}
.rot-card:hover .rot-body {
    transform: translateY(-3px);
    border-color: #d3d9e6;
    box-shadow: 0 18px 38px -22px rgba(15, 23, 42, 0.4);
}

/* ── Шапка ── */
.rot-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.rot-emp {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.rot-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    flex-shrink: 0;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #4338ca;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 1px solid #d9defb;
}
.rot-emp__meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.rot-emp__name {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.rot-emp__kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 3px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
}
.rot-emp__count {
    font-style: normal;
    padding: 1px 8px;
    border-radius: 999px;
    background: rgba(99, 102, 241, 0.12);
    color: #4f46e5;
    letter-spacing: 0.02em;
}
.rot-date {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}
.rot-date__icon {
    width: 14px;
    height: 14px;
    color: #64748b;
}

/* ── Строки различий ── */
.rot-diff {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.rot-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 12px;
    background: #fafbfc;
    border: 1px solid #eef1f6;
}
.rot-row.is-changed {
    background: linear-gradient(90deg, rgba(244, 63, 94, 0.05) 0%, rgba(16, 185, 129, 0.06) 100%);
    border-color: #e6ecf2;
}
.rot-row__label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}
.rot-row__icon {
    width: 15px;
    height: 15px;
    color: #94a3b8;
}

.rot-flow {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex-wrap: wrap;
}
.rot-val {
    /* Показываем имя полностью — длинные значения переносим, а не обрезаем. */
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 600;
}
/* Состояние покоя: обе стороны спокойного серого цвета (ничего не изменилось) */
.rot-val--from,
.rot-val--to {
    color: #64748b;
    background: #eef1f5;
}
.rot-arrow {
    display: inline-flex;
    color: #cbd5e1;
    flex-shrink: 0;
}
.rot-arrow :deep(svg) {
    width: 16px;
    height: 16px;
}
.rot-same {
    font-size: 0.66rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #aab4c4;
}

/* Изменённая строка: розовый источник → изумрудное назначение, стрелка светится индиго */
.is-changed .rot-val--from {
    color: #be123c;
    background: rgba(244, 63, 94, 0.1);
    text-decoration: line-through;
    text-decoration-color: rgba(190, 18, 60, 0.4);
}
.is-changed .rot-val--to {
    color: #047857;
    background: rgba(16, 185, 129, 0.13);
    box-shadow: inset 0 0 0 1px rgba(5, 150, 105, 0.25);
}
.is-changed .rot-arrow {
    color: #6366f1;
    animation: rot-nudge 1.6s ease-in-out infinite;
}
@keyframes rot-nudge {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(3px); }
}

/* ── Причина ── */
.rot-reason {
    display: flex;
    gap: 10px;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #f8fafc;
    border-left: 3px solid #6366f1;
}
.rot-reason__icon {
    width: 16px;
    height: 16px;
    color: #6366f1;
    flex-shrink: 0;
    margin-top: 2px;
}
.rot-reason__text {
    margin: 0;
    font-size: 0.86rem;
    line-height: 1.5;
    color: #475569;
}
.rot-reason__label {
    font-weight: 700;
    color: #334155;
    margin-right: 4px;
}

@media (max-width: 600px) {
    .rot-row {
        grid-template-columns: 1fr;
        gap: 6px;
    }
}
</style>
