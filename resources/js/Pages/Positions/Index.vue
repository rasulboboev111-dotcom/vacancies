<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { Briefcase, Plus, Search } from '@lucide/vue';
import { refDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PositionCard from '@/Pages/Positions/PositionCard.vue';
import PositionDeleteDialog from '@/Pages/Positions/PositionDeleteDialog.vue';
import PositionEmployeesDialog from '@/Pages/Positions/PositionEmployeesDialog.vue';
import PositionFormDialog from '@/Pages/Positions/PositionFormDialog.vue';

const props = defineProps({
    positions: { type: Array, required: true },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.roles.includes('Admin'));

const search = ref('');
// Debounce the local filter so it doesn't recompute on every keystroke (@vueuse/core).
const debouncedSearch = refDebounced(search, 300);
const dialog = ref(false);
const deleteDialog = ref(false);
const employeesDialog = ref(false);
const editingPosition = ref(null);
const positionToDelete = ref(null);
const selectedPosition = ref(null);

const filteredPositions = computed(() => {
    if (!debouncedSearch.value)
        return props.positions;
    const q = debouncedSearch.value.toLowerCase();
    return props.positions.filter(p => p.name?.toLowerCase().includes(q));
});

// Client-side pagination: the dataset is small enough to ship in full, so we
// keep the instant search over all positions and only page the display (9 per
// page). Server-side paging would scope the search to the current page.
const PAGE_SIZE = 9;
const currentPage = ref(1);

const pageCount = computed(() => Math.ceil(filteredPositions.value.length / PAGE_SIZE) || 1);
const pagedPositions = computed(() =>
    filteredPositions.value.slice((currentPage.value - 1) * PAGE_SIZE, currentPage.value * PAGE_SIZE),
);

// Reset to the first page when the filter changes, and clamp the page if the
// list shrinks (e.g. after a delete) so the grid never lands on an empty page.
watch(debouncedSearch, () => {
    currentPage.value = 1;
});
watch(pageCount, (count) => {
    if (currentPage.value > count)
        currentPage.value = count;
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

function openEmployeesDialog(position) {
    selectedPosition.value = position;
    employeesDialog.value = true;
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
        <v-row class="mb-6 align-end">
            <v-col cols="12" md="6">
                <label class="filter-label">Ҷустуҷӯ</label>
                <v-text-field
                    v-model="search"
                    :prepend-inner-icon="Search"
                    placeholder="Аз рӯи номи вазифа..."
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
            <v-col v-for="position in pagedPositions" :key="position.id" cols="12" sm="6" md="4">
                <PositionCard
                    :position="position"
                    :is-admin="isAdmin"
                    @view="openEmployeesDialog"
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

        <div v-if="pageCount > 1" class="d-flex justify-center mt-6">
            <v-pagination
                v-model="currentPage"
                :length="pageCount"
                :total-visible="5"
                density="comfortable"
                rounded="lg"
                active-color="indigo"
            />
        </div>

        <PositionFormDialog v-model="dialog" :position="editingPosition" />
        <PositionDeleteDialog v-model="deleteDialog" :position="positionToDelete" />
        <PositionEmployeesDialog v-model="employeesDialog" :position="selectedPosition" />
    </AuthenticatedLayout>
</template>
