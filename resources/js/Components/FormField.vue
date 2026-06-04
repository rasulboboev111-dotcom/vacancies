<script setup>
// Static field label that always sits above the input — no floating/placeholder
// labels. Keeps every label visible while typing and gives errors a stable
// anchor. Required fields get a red asterisk so the user knows before submit.
defineProps({
    label: { type: String, required: true },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <div class="form-field">
        <label class="form-field__label">
            {{ label }}<span v-if="required" class="form-field__req" aria-hidden="true">*</span>
        </label>
        <slot />
    </div>
</template>

<style scoped>
.form-field__label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    line-height: 1.3;
}
.form-field__req {
    margin-left: 2px;
    color: #dc2626;
    font-weight: 600;
}
</style>
