<script setup>
import { MapPin, MoreVertical, Pencil, Trash2, Users } from '@lucide/vue';

defineProps({
    branch: { type: Object, required: true },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column transition-hover position-relative overflow-hidden">
        <div class="d-flex justify-space-between align-start mb-3">
            <div>
                <v-chip color="indigo" variant="flat" size="small" class="font-weight-black text-uppercase tracking-wider mb-2">
                    {{ branch.code }}
                </v-chip>
                <h3 class="text-h6 font-weight-black text-indigo-darken-3">
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

        <p class="text-body-2 text-grey-darken-2 mb-4 flex-grow-1 font-weight-medium d-flex align-center">
            <MapPin style="width: 16px; height: 16px; margin-right: 8px;" class="text-indigo" />
            {{ branch.address || 'Суроға нишон дода нашудааст' }}
        </p>

        <v-divider class="my-3" />

        <div class="d-flex justify-space-between align-center">
            <span class="text-subtitle-2 text-grey font-weight-bold text-uppercase">Ҳайат</span>
            <v-chip color="teal" variant="tonal" class="font-weight-black px-3" size="medium">
                <Users style="width: 16px; height: 16px; margin-right: 4px;" class="text-teal" />
                {{ branch.employees_count }} нафар.
            </v-chip>
        </div>
    </v-card>
</template>
