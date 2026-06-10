<script setup>
import { computed } from 'vue';

// Docx-style checkbox row for the «Заявка на подбор персонала» choice groups
// (Образование, Тип занятости, Приоритет…). Single choice by default, but
// clicking the checked option clears it — every group is optional on the
// paper form too. `multiple` switches to an array model (Знание языков).
const props = defineProps({
    options: { type: Array, required: true }, // [{ value, label }]
    modelValue: { type: [String, Array], default: null },
    multiple: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed(() => (props.multiple ? (props.modelValue ?? []) : props.modelValue));

function isOn(value) {
    return props.multiple ? selected.value.includes(value) : selected.value === value;
}

function toggle(value) {
    if (props.multiple) {
        emit('update:modelValue', isOn(value) ? selected.value.filter(v => v !== value) : [...selected.value, value]);
        return;
    }
    emit('update:modelValue', selected.value === value ? null : value);
}
</script>

<template>
    <div class="choice-group">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="choice"
            :class="{ 'choice--on': isOn(option.value) }"
            :aria-pressed="isOn(option.value)"
            @click="toggle(option.value)"
        >
            <span class="choice__box" aria-hidden="true">
                <svg v-if="isOn(option.value)" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6.5L4.8 9.2L10 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            {{ option.label }}
        </button>
    </div>
</template>

<style scoped>
.choice-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 18px;
}

.choice {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 2px;
    border: 0;
    background: none;
    font: inherit;
    font-size: 0.875rem;
    color: #334155;
    cursor: pointer;
    transition: color 0.15s ease;
}

.choice:hover {
    color: #0f3d5c;
}

.choice__box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    flex: 0 0 16px;
    border: 1.5px solid #94a8b8;
    border-radius: 3px;
    background: #ffffff;
    color: #ffffff;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.choice__box svg {
    width: 11px;
    height: 11px;
}

.choice--on {
    color: #0f3d5c;
    font-weight: 600;
}

.choice--on .choice__box {
    background: #0f3d5c;
    border-color: #0f3d5c;
}
</style>
