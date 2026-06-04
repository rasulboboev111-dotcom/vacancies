<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { Briefcase, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PositionCard from '@/Pages/Positions/PositionCard.vue';
import PositionDeleteDialog from '@/Pages/Positions/PositionDeleteDialog.vue';
import PositionFormDialog from '@/Pages/Positions/PositionFormDialog.vue';

const props = defineProps({
    positions: { type: Array, required: true },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.roles.includes('Admin'));

const search = ref('');
const dialog = ref(false);
const deleteDialog = ref(false);
const editingPosition = ref(null);
const positionToDelete = ref(null);

const filteredPositions = computed(() => {
    if (!search.value)
        return props.positions;
    const q = search.value.toLowerCase();
    return props.positions.filter(p => p.name?.toLowerCase().includes(q));
});

function openCreateDialog() {
    editingPosition.value = null;
    dialog.value = true;
}

function openEditDialog(position) {
    editingPosition.value = position;
    dialog.value = true;
}

function openDeleteDialog(position) {
    positionToDelete.value = position;
    deleteDialog.value = true;
}
</script>

<template>
    <Head title="Вазифаҳо" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Briefcase style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Идоракунии вазифаҳо</span>
            </div>
        </template>

        <!-- Search and Action Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" md="6">
                <v-text-field
                    v-model="search"
                    prepend-inner-icon="mdi-magnify"
                    label="Ҷустуҷӯ аз рӯи номи вазифа..."
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                    clearable
                    color="indigo"
                    class="search-field"
                />
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-md-end">
                <v-btn
                    v-if="isAdmin"
                    color="indigo"
                    rounded="lg"
                    elevation="2"
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                    @click="openCreateDialog"
                >
                    <template #prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                    </template>
                    Илова кардани вазифа
                </v-btn>
            </v-col>
        </v-row>

        <!-- Positions Grid -->
        <v-row>
            <v-col v-for="position in filteredPositions" :key="position.id" cols="12" sm="6" md="4">
                <PositionCard
                    :position="position"
                    :is-admin="isAdmin"
                    @edit="openEditDialog"
                    @delete="openDeleteDialog"
                />
            </v-col>

            <v-col v-if="filteredPositions.length === 0" cols="12" class="text-center py-12">
                <Briefcase style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                <div class="text-h6 text-grey font-weight-medium">
                    {{ search ? 'Вазифа бо чунин ном ёфт нашуд' : 'Вазифаҳо ёфт нашуд.' }}
                </div>
            </v-col>
        </v-row>

        <PositionFormDialog v-model="dialog" :position="editingPosition" />
        <PositionDeleteDialog v-model="deleteDialog" :position="positionToDelete" />
    </AuthenticatedLayout>
</template>
