<script setup>
import { Building2, MapPin, MoreVertical, Pencil, Trash2, Users } from '@lucide/vue';

defineProps({
    branch: { type: Object, required: true },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column transition-hover position-relative overflow-hidden">
        <div class="d-flex justify-space-between align-start mb-3">
            <div class="d-flex align-center ga-3 branch-head" style="min-width: 0;">
                <span class="branch-icon">
                    <Building2 style="width: 20px; height: 20px;" />
                </span>
                <h3 class="branch-name mb-0">
                    {{ branch.name }}
                </h3>
            </div>

            <v-menu>
                <template #activator="{ props: menuProps }">
                    <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                        <MoreVertical style="width: 16px; height: 16px;" />
                    </v-btn>
                </template>
                <v-list density="comfortable" rounded="xl" class="border py-1">
                    <v-list-item title="Таҳрир" class="font-weight-bold" @click="$emit('edit', branch)">
                        <template #prepend>
                            <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                        </template>
                    </v-list-item>
                    <v-list-item title="Нест кардан" class="text-error font-weight-bold" @click="$emit('delete', branch)">
                        <template #prepend>
                            <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                        </template>
                    </v-list-item>
                </v-list>
            </v-menu>
        </div>

        <p class="branch-address mb-4 flex-grow-1 d-flex align-center">
            <MapPin style="width: 15px; height: 15px; margin-right: 8px; flex: none;" />
            {{ branch.address || 'Суроға нишон дода нашудааст' }}
        </p>

        <v-divider class="my-3" />

        <div class="d-flex justify-space-between align-center">
            <span class="branch-meta text-uppercase">Ҳайат</span>
            <v-chip
                :color="branch.employees_count > 0 ? 'teal' : 'grey'"
                variant="tonal"
                class="font-weight-medium px-3"
                size="medium"
            >
                <Users
                    style="width: 16px; height: 16px; margin-right: 4px;"
                    :class="branch.employees_count > 0 ? 'text-teal' : 'text-grey'"
                />
                {{ branch.employees_count }} нафар.
            </v-chip>
        </div>
    </v-card>
</template>

<style scoped>
.branch-icon {
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
.branch-head {
    min-height: 52px;
}
.branch-name {
    color: #141414;
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
}
.branch-address {
    color: #6b7280;
    font-size: 0.8125rem;
    font-weight: 400;
    line-height: 1.4;
}
.branch-meta {
    color: #9ca3af;
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.04em;
}
</style>
