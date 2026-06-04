<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Archive, RotateCcw } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { firstError } from '@/lib/errors';
import ArchivedEmployeeDialog from '@/Pages/Employees/ArchivedEmployeeDialog.vue';
import ArchiveTable from '@/Pages/Employees/ArchiveTable.vue';

const props = defineProps({
    employees: { type: Object, required: true },
    branches: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const { isAdmin, hasPermission } = usePermissions();

// Reinstating an archived employee is an update — gate the button on the same
// permission the policy requires (admins receive it via their role). Branch
// users are scoped to their own branch, so the branch filter is for admins only.
const canRestore = computed(() => hasPermission('edit employees'));

const search = ref(props.filters.search || '');
const branchId = ref(props.filters.branch_id || null);

const viewDialog = ref(false);
const selectedEmployee = ref(null);

const restoreDialog = ref(false);
const employeeToRestore = ref(null);
const restoring = ref(false);
const restoreError = ref('');

watch([branchId], () => {
    applyFilters();
});

function filterQuery() {
    return {
        filter: {
            search: search.value || undefined,
            branch_id: branchId.value || undefined,
        },
    };
}

function applyFilters() {
    router.get(route('employees.archive'), filterQuery(), {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    branchId.value = null;
    router.get(route('employees.archive'));
}

function openViewDialog(employee) {
    selectedEmployee.value = employee;
    viewDialog.value = true;
}

function openRestoreDialog(employee) {
    employeeToRestore.value = employee;
    restoreError.value = '';
    restoreDialog.value = true;
}

function confirmRestore() {
    if (!employeeToRestore.value) {
        return;
    }
    restoreError.value = '';
    router.post(route('employees.restore', employeeToRestore.value.id), {}, {
        preserveScroll: true,
        onStart: () => (restoring.value = true),
        onFinish: () => (restoring.value = false),
        onSuccess: () => {
            restoreDialog.value = false;
            employeeToRestore.value = null;
        },
        onError: (errors) => {
            // Keep the dialog open and tell the user why the restore failed.
            restoreError.value = firstError(errors, 'Барқарорсозӣ иҷро нашуд. Бори дигар кӯшиш кунед.');
        },
    });
}

function changePage(page) {
    router.get(route('employees.archive'), { page, ...filterQuery() }, {
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Бойгонии кормандон" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Archive style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Бойгонии кормандони озодшуда</span>
            </div>
        </template>

        <!-- Main Card -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <!-- Search & Filters -->
            <v-row class="mb-6 align-center">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="search"
                        prepend-inner-icon="mdi-magnify"
                        label="Ҷустуҷӯ аз рӯи ному насаб, ИНН ё вазифа..."
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        color="indigo"
                        class="search-field"
                    />
                </v-col>
                <v-col v-if="isAdmin" cols="12" md="4">
                    <v-select
                        v-model="branchId"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        label="Филтр аз рӯи филиали пешина"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        color="indigo"
                    />
                </v-col>
                <v-col cols="12" md="2" class="d-flex justify-end">
                    <v-btn
                        variant="tonal"
                        color="error"
                        rounded="lg"
                        block
                        class="filter-reset-btn"
                        @click="resetFilters"
                    >
                        Бекор кардан
                    </v-btn>
                </v-col>
            </v-row>

            <ArchiveTable :employees="employees.data" :can-restore="canRestore" @view="openViewDialog" @restore="openRestoreDialog" />

            <!-- Pagination -->
            <v-divider class="my-4" />
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Нишон дода шуд {{ employees.from || 0 }} - {{ employees.to || 0 }} аз {{ employees.total || 0 }} корманди бойгонӣ
                </div>
                <v-pagination
                    v-if="employees.last_page > 1"
                    :model-value="employees.current_page"
                    :length="employees.last_page"
                    :total-visible="5"
                    density="comfortable"
                    rounded="lg"
                    active-color="indigo"
                    @update:model-value="changePage"
                />
            </div>
        </v-card>

        <ArchivedEmployeeDialog v-model="viewDialog" :employee="selectedEmployee" />

        <!-- Restore confirmation -->
        <v-dialog v-model="restoreDialog" max-width="440px">
            <v-card class="rounded-xl pa-2" elevation="8">
                <v-card-title class="d-flex align-center font-weight-bold text-h6 text-indigo-darken-3">
                    <RotateCcw style="width: 22px; height: 22px; margin-right: 10px;" class="text-success" />
                    Барқарор кардани корманд
                </v-card-title>
                <v-card-text class="text-body-1 text-grey-darken-2">
                    Корманд <span class="font-weight-bold text-indigo-darken-3">{{ employeeToRestore?.full_name }}</span>
                    аз бойгонӣ ба рӯйхати кормандони фаъол баргардонида мешавад (санаи озодшавӣ нест карда мешавад). Идома медиҳед?
                    <v-alert v-if="restoreError" type="error" variant="tonal" density="compact" class="mt-3 rounded-lg">
                        {{ restoreError }}
                    </v-alert>
                </v-card-text>
                <v-card-actions class="px-4 pb-4">
                    <v-spacer />
                    <v-btn variant="flat" rounded="lg" class="px-5 font-weight-bold" style="background-color: #fdecec; color: #dc2626;" :disabled="restoring" @click="restoreDialog = false">
                        Бекор кардан
                    </v-btn>
                    <v-btn variant="flat" rounded="lg" class="px-5 font-weight-bold" style="background-color: #16a34a; color: #ffffff;" :loading="restoring" :disabled="restoring" @click="confirmRestore">
                        Барқарор кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
