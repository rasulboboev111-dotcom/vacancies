<script setup>
import { computed, useSlots } from 'vue';

// Boxless read-only field: grey label on top, dark value below. No border,
// no per-field icon — framing lives at the section level. Empty values are
// muted (pale dash) so a sea of unfilled fields recedes instead of competing
// with real data.
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: null },
    mono: { type: Boolean, default: false },
});

const slots = useSlots();

const isEmpty = computed(() => {
    if (slots.default) {
        return false;
    }
    const v = props.value;
    return v === null || v === undefined || v === '' || v === '-';
});
</script>

<template>
    <div class="data-field">
        <span class="data-field__label">{{ label }}</span>
        <span
            class="data-field__value"
            :class="{ 'data-field__value--empty': isEmpty, 'data-field__value--mono': mono }"
        >
            <slot>{{ isEmpty ? '—' : value }}</slot>
        </span>
    </div>
</template>

<style scoped>
.data-field {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.data-field__label {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #9ca3af;
    line-height: 1.3;
}
.data-field__value {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1f2937;
    line-height: 1.4;
    overflow-wrap: anywhere;
}
.data-field__value--empty {
    color: #cbd5e1;
    font-weight: 400;
}
.data-field__value--mono {
    font-family: 'JetBrains Mono', ui-monospace, 'SFMono-Regular', Menlo, monospace;
}
</style>
