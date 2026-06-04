<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search, Users } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmployeeDeleteDialog from '@/Pages/Employees/EmployeeDeleteDialog.vue';
import EmployeeFormDialog from '@/Pages/Employees/EmployeeFormDialog.vue';
import EmployeeRotationDialog from '@/Pages/Employees/EmployeeRotationDialog.vue';
import EmployeesTable from '@/Pages/Employees/EmployeesTable.vue';
import EmployeeViewDialog from '@/Pages/Employees/EmployeeViewDialog.vue';

const props = defineProps({
    employees: { type: Object, required: true },
    branches: { type: Array, required: true },
    types: { type: Array, required: true },
    departments: { type: Array, default: () => [] },
    // Deferred (Inertia group "form") — absent on first paint, streamed in
    // after render, so they default to empty until they arrive. (managers are
    // fetched on demand inside the form via a searchable endpoint.)
    categories: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    educations: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    birthPlaces: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});

const { user: authUser, isAdmin, canManageInBranch, canCreateInBranch } = usePermissions();

const canManageEmployee = employee => canManageInBranch('edit employees', employee.branch_id);
const canCreateEmployees = computed(() => canCreateInBranch('create employees'));

const search = ref(props.filters.search || '');
const branchId = ref(props.filters.branch_id ? Number(props.filters.branch_id) : null);
const departmentId = ref(props.filters.department_id ? Number(props.filters.department_id) : null);
const typeId = ref(props.filters.type_id || null);

// Departments shown in the toolbar filter — narrowed to the selected branch.
const filterDepartments = computed(() =>
    branchId.value
        ? props.departments.filter(d => Number(d.branch_id) === Number(branchId.value))
        : props.departments,
);

// Drop the chosen department if it no longer belongs to the selected branch.
watch(branchId, () => {
    if (
        departmentId.value
        && !filterDepartments.value.some(d => Number(d.id) === Number(departmentId.value))
    ) {
        departmentId.value = null;
    }
});

watch([branchId, departmentId, typeId], () => {
    applyFilters();
});

// Auto-apply the text search while typing, debounced so we don't fire a
// request on every keystroke (@vueuse/core).
watchDebounced(search, applyFilters, { debounce: 400 });

function filterQuery() {
    return {
        filter: {
            search: search.value || undefined,
            branch_id: branchId.value || undefined,
            department_id: departmentId.value || undefined,
            type_id: typeId.value || undefined,
        },
    };
}

function applyFilters() {
    router.get(route('employees.index'), filterQuery(), {
        preserveState: true,
        replace: true,
        // Re-fetch only the list (+ filters echo); the toolbar/form reference
        // data is unchanged, so keep it out of every filter request.
        only: ['employees', 'filters'],
    });
}

function changePage(p) {
    router.get(route('employees.index'), { page: p, ...filterQuery() }, {
        preserveState: true,
        only: ['employees', 'filters'],
    });
}

// Dialog controllers
const createEditDialog = ref(false);
const viewDialog = ref(false);
const deleteDialog = ref(false);
const rotationDialog = ref(false);

const editingEmployee = ref(null);
const viewEmployee = ref(null);
const employeeToDelete = ref(null);
const rotationEmployee = ref(null);

function openCreateDialog() {
    editingEmployee.value = null;
    createEditDialog.value = true;
}

function openEditDialog(employee) {
    editingEmployee.value = employee;
    createEditDialog.value = true;
}

function openViewDialog(employee) {
    viewEmployee.value = employee;
    viewDialog.value = true;
}

function openDeleteDialog(employee) {
    employeeToDelete.value = employee;
    deleteDialog.value = true;
}

function openRotationDialog(employee) {
    rotationEmployee.value = employee;
    rotationDialog.value = true;
}
</script>

<template>
    <Head title="Кормандон" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Users class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Кормандон</span>
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
                        placeholder="Ном ё вазифа..."
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

                <!-- Branch Filter (admins only — branch users are scoped to their own branch) -->
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

                <!-- Department (Шуъба) Filter -->
                <v-col cols="12" sm="4" md="2">
                    <label class="filter-label">Шуъба</label>
                    <v-autocomplete
                        v-model="departmentId"
                        :items="filterDepartments"
                        item-title="name"
                        item-value="id"
                        placeholder="Ҳамаи шуъбаҳо"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        :no-data-text="branchId ? 'Шуъбаҳо ёфт нашуданд' : 'Шуъба ёфт нашуд'"
                        class="premium-field"
                    />
                </v-col>

                <!-- Employment Type Filter -->
                <v-col cols="12" sm="4" md="2">
                    <label class="filter-label">Намуди шуғл</label>
                    <v-select
                        v-model="typeId"
                        :items="types"
                        item-title="name"
                        item-value="id"
                        placeholder="Ҳама"
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
                <v-col cols="12" sm="12" md="3" class="d-flex align-center justify-md-end justify-center gap-2">
                    <v-btn
                        v-if="canCreateEmployees"
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

        <!-- Employee Table -->
        <EmployeesTable
            :employees="employees"
            :can-manage="canManageEmployee"
            @view="openViewDialog"
            @rotate="openRotationDialog"
            @edit="openEditDialog"
            @delete="openDeleteDialog"
            @change-page="changePage"
        />

        <!-- Dialogs -->
        <EmployeeViewDialog v-model="viewDialog" :employee="viewEmployee" />

        <EmployeeFormDialog
            v-model="createEditDialog"
            :employee="editingEmployee"
            :branches="branches"
            :categories="categories"
            :types="types"
            :positions="positions"
            :departments="departments"
            :nationalities="nationalities"
            :educations="educations"
            :specialties="specialties"
            :birth-places="birthPlaces"
            :is-admin="isAdmin"
            :user-branch-id="authUser?.branch_id ?? null"
        />

        <EmployeeDeleteDialog v-model="deleteDialog" :employee="employeeToDelete" />

        <EmployeeRotationDialog
            v-model="rotationDialog"
            :employee="rotationEmployee"
            :branches="branches"
            :positions="positions"
            :departments="departments"
            :is-admin="isAdmin"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
