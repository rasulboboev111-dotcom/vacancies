<script setup>
import { Briefcase, MoreVertical, Pencil, Trash2, Users } from '@lucide/vue';

defineProps({
    position: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

defineEmits(['view', 'edit', 'delete']);
</script>

<template>
    <v-card
        elevation="0"
        :ripple="false"
        class="rounded-xl border pa-5 h-100 d-flex flex-column position-relative overflow-hidden position-card"
        @click="$emit('view', position)"
    >
        <div class="d-flex justify-space-between align-start mb-3">
            <div class="d-flex align-center ga-3 position-head" style="min-width: 0;">
                <span class="position-icon">
                    <Briefcase style="width: 20px; height: 20px;" />
                </span>
                <h3 class="position-name mb-0">
                    {{ position.name }}
                </h3>
            </div>

            <v-menu v-if="isAdmin">
                <template #activator="{ props: menuProps }">
                    <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps" @click.stop>
                        <MoreVertical style="width: 16px; height: 16px;" />
                    </v-btn>
                </template>
                <v-list density="comfortable" rounded="xl" class="border py-1">
                    <v-list-item title="Таҳрир" class="font-weight-bold" @click="$emit('edit', position)">
                        <template #prepend>
                            <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                        </template>
                    </v-list-item>
                    <v-list-item title="Нест кардан" class="text-error font-weight-bold" @click="$emit('delete', position)">
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                        </template>
                    </v-list-item>
                </v-list>
            </v-menu>
        </div>

        <div class="flex-grow-1" />

        <v-divider class="my-3" />

        <div class="d-flex justify-space-between align-center">
            <span class="position-meta text-uppercase">Ҳайат</span>
            <v-chip
                :color="position.employees_count > 0 ? 'teal' : 'grey'"
                variant="tonal"
                class="font-weight-medium px-3"
                size="medium"
            >
                <Users
                    style="width: 16px; height: 16px; margin-right: 4px;"
                    :class="position.employees_count > 0 ? 'text-teal' : 'text-grey'"
                />
                {{ position.employees_count }} нафар.
            </v-chip>
        </div>
    </v-card>
</template>

<style scoped>
.position-card {
    cursor: pointer;
}
/* Кликабельна, но визуально статична — без подъёма при наведении и оверлея ссылки/наведения Vuetify. */
.position-card :deep(.v-card__overlay) {
    display: none;
}
.position-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(37, 99, 235, 0.18);
    color: #1d4ed8;
    flex-shrink: 0;
}
.position-head {
    min-height: 52px;
}
.position-name {
    color: #141414;
    font-size: 1.0625rem;
    font-weight: 700;
    line-height: 1.3;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.position-meta {
    color: #9ca3af;
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.04em;
}
</style>
