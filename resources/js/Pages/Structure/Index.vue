<script setup>
import { Head } from '@inertiajs/vue3';
import { Building2, Network, Plus, Workflow } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BranchCard from '@/Pages/Structure/BranchCard.vue';
import BranchDeleteDialog from '@/Pages/Structure/BranchDeleteDialog.vue';
import BranchFormDialog from '@/Pages/Structure/BranchFormDialog.vue';
import DepartmentDeleteDialog from '@/Pages/Structure/DepartmentDeleteDialog.vue';
import DepartmentFormDialog from '@/Pages/Structure/DepartmentFormDialog.vue';
import DepartmentTreeNode from '@/Pages/Structure/DepartmentTreeNode.vue';
import StructureEmployeesDialog from '@/Pages/Structure/StructureEmployeesDialog.vue';
import SvgOrgTree from '@/Pages/Structure/SvgOrgTree.vue';

const props = defineProps({
    structure: { type: Array, required: true },
    branches: { type: Array, default: () => [] },
    departmentsFlat: { type: Array, default: () => [] },
});

const { isAdmin, canManageInBranch, canCreateInBranch } = usePermissions();

/* ------------------------------------------------------------------ *
 * Node click → employees popup
 * ------------------------------------------------------------------ */
const employeesDialog = ref(false);
const employeesLoading = ref(false);
const popupMode = ref('dept'); // 'dept' | 'branch'
const popupTitle = ref('');
const popupEmployees = ref([]);
const popupManager = ref(null);

function onNodeClick(node) {
    const isBranch = node.data.kind === 'branch';

    popupMode.value = isBranch ? 'branch' : 'dept';
    popupTitle.value = node.data.label;
    popupEmployees.value = [];
    popupManager.value = null;
    employeesDialog.value = true;
    employeesLoading.value = true;

    const id = Number(String(node.id).replace(/^[bd]-/, ''));
    const routeName = isBranch ? 'structure.branch.employees' : 'structure.department.employees';

    window.axios
        .get(route(routeName, id))
        .then((response) => {
            popupEmployees.value = response.data.employees ?? [];
            popupManager.value = response.data.manager ?? null;
        })
        .catch(() => {
            popupEmployees.value = [];
            popupManager.value = null;
        })
        .finally(() => {
            employeesLoading.value = false;
        });
}

/* ------------------------------------------------------------------ *
 * Permissions
 * ------------------------------------------------------------------ */
const canCreateDepartments = computed(() => canCreateInBranch('create departments'));
const canManageDepartment = department => canManageInBranch('edit departments', department.branch_id);
const canDeleteDepartment = department => canManageInBranch('delete departments', department.branch_id);

/* ------------------------------------------------------------------ *
 * Management panel — shared branch selector
 * ------------------------------------------------------------------ */
const tab = ref('departments');

const branchOptions = computed(() =>
    props.branches.map(branch => ({
        id: Number(branch.id),
        title: branch.name,
    })),
);

const selectedBranchId = ref(props.branches[0] ? Number(props.branches[0].id) : null);

watch(
    () => props.branches,
    (list) => {
        const ids = list.map(b => Number(b.id));
        if (selectedBranchId.value == null || !ids.includes(selectedBranchId.value)) {
            selectedBranchId.value = ids[0] ?? null;
        }
    },
);

/* ------------------------------------------------------------------ *
 * Departments management
 * ------------------------------------------------------------------ */
function buildDeptTree(flat, branchId, parentId = null) {
    return flat
        .filter(
            d =>
                Number(d.branch_id) === Number(branchId)
                && (d.parent_id ?? null) === (parentId ?? null),
        )
        // Mirror the server ordering (source sort_order, then name).
        .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)
            || a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }))
        .map(d => ({
            ...d,
            children: buildDeptTree(flat, branchId, d.id),
        }));
}

const departmentTree = computed(() =>
    selectedBranchId.value ? buildDeptTree(props.departmentsFlat, selectedBranchId.value) : [],
);

const deptDialog = ref(false);
const deptDeleteDialog = ref(false);
const editingDepartment = ref(null);
const createParentId = ref(null);
const departmentToDelete = ref(null);

const parentOptions = computed(() => {
    const branchId = selectedBranchId.value;
    let options = props.departmentsFlat.filter(
        department => Number(department.branch_id) === Number(branchId),
    );

    if (editingDepartment.value) {
        const excludedIds = new Set([editingDepartment.value.id]);
        const collectDescendants = (parentId) => {
            props.departmentsFlat
                .filter(department => department.parent_id === parentId)
                .forEach((child) => {
                    excludedIds.add(child.id);
                    collectDescendants(child.id);
                });
        };
        collectDescendants(editingDepartment.value.id);

        options = options.filter(department => !excludedIds.has(department.id));
    }

    return options;
});

function openCreateDepartment(parentId = null) {
    editingDepartment.value = null;
    createParentId.value = parentId;
    deptDialog.value = true;
}

function openEditDepartment(department) {
    editingDepartment.value = department;
    deptDialog.value = true;
}

function openDeleteDepartment(department) {
    departmentToDelete.value = department;
    deptDeleteDialog.value = true;
}

/* ------------------------------------------------------------------ *
 * Branches management (Admin only)
 * ------------------------------------------------------------------ */
const branchDialog = ref(false);
const branchDeleteDialog = ref(false);
const editingBranch = ref(null);
const branchToDelete = ref(null);

function openCreateBranch() {
    editingBranch.value = null;
    branchDialog.value = true;
}

function openEditBranch(branch) {
    editingBranch.value = branch;
    branchDialog.value = true;
}

function openDeleteBranch(branch) {
    branchToDelete.value = branch;
    branchDeleteDialog.value = true;
}
</script>

<template>
    <Head title="Сохтор" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Workflow style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Сохтори ташкилӣ</span>
            </div>
        </template>

        <!-- Org chart (read-only) -->
        <SvgOrgTree :structure="structure" @node-click="onNodeClick" />

        <!-- Employees popup -->
        <StructureEmployeesDialog
            v-model="employeesDialog"
            :mode="popupMode"
            :title="popupTitle"
            :loading="employeesLoading"
            :employees="popupEmployees"
            :manager="popupManager"
        />

        <!-- Management panel -->
        <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
            <v-tabs v-model="tab" color="indigo" class="border-b px-2">
                <v-tab value="departments" class="font-weight-bold text-none">
                    <Network style="width: 18px; height: 18px; margin-right: 8px;" />
                    Шуъбаҳо
                </v-tab>
                <v-tab v-if="isAdmin" value="branches" class="font-weight-bold text-none">
                    <Building2 style="width: 18px; height: 18px; margin-right: 8px;" />
                    Филиалҳо
                </v-tab>
            </v-tabs>

            <v-window v-model="tab">
                <!-- Departments management -->
                <v-window-item value="departments">
                    <div class="pa-4">
                        <v-row class="mb-2 align-center">
                            <v-col cols="12" md="4">
                                <v-select
                                    v-if="branchOptions.length > 0"
                                    v-model="selectedBranchId"
                                    :items="branchOptions"
                                    item-title="title"
                                    item-value="id"
                                    label="Филиал"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details
                                />
                            </v-col>
                            <v-col cols="12" md="8" class="d-flex justify-end">
                                <v-btn
                                    v-if="canCreateDepartments && selectedBranchId"
                                    color="indigo"
                                    rounded="lg"
                                    elevation="2"
                                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                                    @click="openCreateDepartment()"
                                >
                                    <template #prepend>
                                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                                    </template>
                                    Илова кардани шуъба
                                </v-btn>
                            </v-col>
                        </v-row>

                        <template v-if="departmentTree.length > 0">
                            <DepartmentTreeNode
                                v-for="department in departmentTree"
                                :key="department.id"
                                :department="department"
                                :level="0"
                                :can-manage="canManageDepartment"
                                :can-delete="canDeleteDepartment"
                                :can-create="canCreateDepartments"
                                @edit="openEditDepartment"
                                @delete="openDeleteDepartment"
                                @add-child="openCreateDepartment"
                            />
                        </template>

                        <div v-else class="text-center py-12">
                            <Network style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                            <div class="text-h6 text-grey font-weight-medium">
                                {{ selectedBranchId ? 'Шуъбаҳо ёфт нашуданд.' : 'Филиалро интихоб кунед ё ба корбар филиал таъин кунед.' }}
                            </div>
                        </div>
                    </div>
                </v-window-item>

                <!-- Branches management (Admin only) -->
                <v-window-item v-if="isAdmin" value="branches">
                    <div class="pa-4">
                        <v-row class="mb-2 align-center">
                            <v-col cols="12" class="d-flex justify-end">
                                <v-btn
                                    color="indigo"
                                    rounded="lg"
                                    elevation="2"
                                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                                    @click="openCreateBranch"
                                >
                                    <template #prepend>
                                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                                    </template>
                                    Илова кардани филиал
                                </v-btn>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col v-for="branch in branches" :key="branch.id" cols="12" sm="6" md="4">
                                <BranchCard :branch="branch" @edit="openEditBranch" @delete="openDeleteBranch" />
                            </v-col>

                            <v-col v-if="branches.length === 0" cols="12" class="text-center py-12">
                                <Building2 style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                                <div class="text-h6 text-grey font-weight-medium">
                                    Филиалҳо ёфт нашуданд.
                                </div>
                            </v-col>
                        </v-row>
                    </div>
                </v-window-item>
            </v-window>
        </v-card>

        <!-- Dialogs -->
        <DepartmentFormDialog
            v-model="deptDialog"
            :department="editingDepartment"
            :parent-options="parentOptions"
            :branch-id="selectedBranchId"
            :initial-parent-id="createParentId"
        />
        <DepartmentDeleteDialog v-model="deptDeleteDialog" :department="departmentToDelete" />
        <BranchFormDialog v-model="branchDialog" :branch="editingBranch" />
        <BranchDeleteDialog v-model="branchDeleteDialog" :branch="branchToDelete" />
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
