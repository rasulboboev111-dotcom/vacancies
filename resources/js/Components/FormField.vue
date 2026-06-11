<script setup>
// Статичная подпись поля, всегда над вводом — без плавающих подписей и
// плейсхолдеров. Подпись остаётся видимой при наборе и даёт ошибкам стабильную
// привязку. Обязательные поля получают красную звёздочку, чтобы пользователь
// видел это до отправки.
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
