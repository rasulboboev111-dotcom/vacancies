<script setup>
// Общая синяя шапка диалогов «Заявка на подбор персонала» (форма и просмотр):
// бренд-полоса #009cf1, аватар-иконка и блок kicker/title/subtitle. Иконка
// передаётся через слот, чтобы каждый диалог ставил свою.
defineProps({
    kicker: { type: String, default: '' },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="dialog-head">
        <div class="d-flex align-center">
            <v-avatar size="42" rounded="lg" class="dialog-head__icon">
                <slot name="icon" />
            </v-avatar>
            <div class="ml-4">
                <div v-if="kicker" class="dialog-head__kicker">
                    {{ kicker }}
                </div>
                <div class="dialog-head__title">
                    {{ title }}
                </div>
                <div v-if="subtitle" class="dialog-head__sub">
                    {{ subtitle }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog-head {
    background: #009cf1;
    padding: 20px 24px;
}

.dialog-head__icon {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
}

.dialog-head__kicker {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.dialog-head__title {
    color: white;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.dialog-head__sub {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.78rem;
    font-style: italic;
}
</style>
