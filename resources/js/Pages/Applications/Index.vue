<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList, Plus, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog.vue';
import ApplicationFormDialog from '@/Pages/Applications/ApplicationFormDialog.vue';
import ApplicationsTable from '@/Pages/Applications/ApplicationsTable.vue';

const props = defineProps({
    applications: { type: Object, required: true },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
});

const { isAdmin, canCreateInBranch, canManageInBranch } = usePermissions();

const canCreate = computed(() => canCreateInBranch('create applications'));

const search = ref(props.filters.search || '');
const sourceFilter = ref(props.filters.source || null);
const branchId = ref(props.filters.branch_id ? Number(props.filters.branch_id) : null);

watch([sourceFilter, branchId], () => {
    applyFilters();
});

watchDebounced(search, applyFilters, { debounce: 400 });

function filterQuery() {
    return {
        filter: {
            search: search.value || undefined,
            source: sourceFilter.value || undefined,
            branch_id: branchId.value || undefined,
        },
    };
}

function applyFilters() {
    router.get(route('applications.index'), filterQuery(), {
        preserveState: true,
        replace: true,
        only: ['applications', 'filters'],
    });
}

function changePage(p) {
    router.get(route('applications.index'), { page: p, ...filterQuery() }, {
        preserveState: true,
        only: ['applications', 'filters'],
    });
}

// Dialog controllers
const formDialog = ref(false);
const deleteDialog = ref(false);

const editingApplication = ref(null);
const applicationToDelete = ref(null);

function openCreateDialog() {
    editingApplication.value = null;
    formDialog.value = true;
}

function openEditDialog(application) {
    editingApplication.value = application;
    formDialog.value = true;
}

function openDeleteDialog(application) {
    applicationToDelete.value = application;
    deleteDialog.value = true;
}

const canManageApp = application => canManageInBranch('delete applications', application.branch_id);
</script>

<template>
    <Head title="Аризаҳо" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <ClipboardList class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Идоракунии аризаҳо</span>
            </div>
        </template>

        <!-- Filters section -->
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-end">
                <!-- Search bar -->
                <v-col cols="12" sm="12" md="3">
                    <label class="filter-label">Ҷустуҷӯ</label>
                    <v-text-field
                        v-model="search"
                        placeholder="Ном, телефон, email..."
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        class="premium-field"
                    >
                        <template #prepend-inner>
                            <Search style="width: 18px; height: 18px; opacity: 0.5;" />
                        </template>
                    </v-text-field>
                </v-col>

                <!-- Source filter -->
                <v-col cols="12" sm="4" md="2">
                    <label class="filter-label">Манбаъ</label>
                    <v-select
                        v-model="sourceFilter"
                        :items="[{ title: 'Ҳамаи манбаъҳо', value: null }, ...sources.map(s => ({ title: s, value: s }))]"
                        item-title="title"
                        item-value="value"
                        placeholder="Ҳамаи манбаъҳо"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                    />
                </v-col>

                <!-- Branch filter (admins only) -->
                <v-col v-if="isAdmin" cols="12" sm="4" md="2">
                    <label class="filter-label">Филиал</label>
                    <v-select
                        v-model="branchId"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        placeholder="Ҳамаи филиалҳо"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                    />
                </v-col>

                <!-- Action buttons -->
                <v-col cols="12" sm="12" :md="isAdmin ? 5 : 7" class="d-flex align-center justify-md-end justify-center gap-2">
                    <v-btn
                        v-if="canCreate"
                        variant="flat"
                        rounded="lg"
                        class="px-5 transition-hover-btn font-weight-bold text-white"
                        style="background: #009cf1 !important; color: #ffffff !important; box-shadow: 0 4px 14px -4px rgba(0, 156, 241, 0.45) !important;"
                        @click="openCreateDialog"
                    >
                        <template #prepend>
                            <Plus style="width: 18px; height: 18px; color: #ffffff;" />
                        </template>
                        Илова кардан
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Applications Table -->
        <ApplicationsTable
            :applications="applications"
            :can-manage="canManageApp"
            @edit="openEditDialog"
            @delete="openDeleteDialog"
            @change-page="changePage"
        />

        <!-- Form Dialog (create / edit) -->
        <ApplicationFormDialog
            v-model="formDialog"
            :application="editingApplication"
            :branches="branches"
            :sources="sources"
            :is-admin="isAdmin"
        />

        <!-- Delete Dialog -->
        <ConfirmDeleteDialog
            v-model="deleteDialog"
            title="Нест кардани ариза"
            :delete-url="applicationToDelete ? route('applications.destroy', applicationToDelete.id) : null"
        >
            Шумо мехоҳед аризаи
            <strong>{{ applicationToDelete?.name }}</strong>-ро нест кунед?
            Ин амал баргардонида намешавад.
        </ConfirmDeleteDialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
