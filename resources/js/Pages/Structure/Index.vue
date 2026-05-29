<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DepartmentTreeNode from '@/Pages/Structure/DepartmentTreeNode.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { VueFlow, Position, useVueFlow } from '@vue-flow/core';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import {
    Building2,
    Network,
    Users,
    DoorOpen,
    Workflow,
    Plus,
    Pencil,
    Trash2,
    MoreVertical,
    MapPin,
    AlertTriangle,
} from '@lucide/vue';

const props = defineProps({
    structure: { type: Array, required: true },
    branches: { type: Array, default: () => [] },
    departmentsFlat: { type: Array, default: () => [] },
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const isAdmin = computed(() => authUser.value?.roles?.includes('Admin') ?? false);

/* ------------------------------------------------------------------ *
 * Org chart (read-only)
 * ------------------------------------------------------------------ */
const X_GAP = 260;
const Y_GAP = 170;

function buildGraph(structure) {
    const nodes = [];
    const edges = [];
    let leafIndex = 0;

    const root = {
        uid: 'root',
        kind: 'root',
        label: 'Ташкилот',
        subtitle: `${structure.length} ${structure.length === 1 ? 'филиал' : 'филиал'}`,
        children: structure.map((branch) => ({
            uid: `b-${branch.id}`,
            kind: 'branch',
            label: branch.name,
            code: branch.code,
            employees: branch.employees_count,
            vacancies: branch.open_vacancies,
            children: (branch.departments || []).map(mapDepartment),
        })),
    };

    function mapDepartment(dept) {
        return {
            uid: `d-${dept.id}`,
            kind: 'dept',
            label: dept.name,
            code: dept.code,
            vacancies: dept.open_vacancies,
            children: (dept.children || []).map(mapDepartment),
        };
    }

    function layout(node, depth, parentUid) {
        let x;
        if (!node.children || node.children.length === 0) {
            x = leafIndex * X_GAP;
            leafIndex += 1;
        } else {
            const childXs = node.children.map((child) => layout(child, depth + 1, node.uid));
            x = (childXs[0] + childXs[childXs.length - 1]) / 2;
        }

        nodes.push({
            id: node.uid,
            type: 'org',
            position: { x, y: depth * Y_GAP },
            sourcePosition: Position.Bottom,
            targetPosition: Position.Top,
            data: {
                label: node.label,
                code: node.code,
                kind: node.kind,
                employees: node.employees,
                vacancies: node.vacancies,
                subtitle: node.subtitle,
            },
        });

        if (parentUid) {
            edges.push({
                id: `${parentUid}->${node.uid}`,
                source: parentUid,
                target: node.uid,
                type: 'smoothstep',
                animated: node.vacancies > 0,
                style: { stroke: '#94a3b8', strokeWidth: 1.5 },
            });
        }

        return x;
    }

    if (structure.length > 0) {
        layout(root, 0, null);
    }

    return { nodes, edges };
}

const graph = computed(() => buildGraph(props.structure));
const nodes = ref(graph.value.nodes);
const edges = ref(graph.value.edges);

// Keep the chart in sync after Inertia reloads (e.g. after CRUD actions).
watch(graph, (next) => {
    nodes.value = next.nodes;
    edges.value = next.edges;
});

const { fitView } = useVueFlow();

function onPaneReady() {
    fitView({ padding: 0.2 });
}

/* ------------------------------------------------------------------ *
 * Permissions
 * ------------------------------------------------------------------ */
const canCreateDepartments = computed(() => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    return user.permissions?.includes('create departments') && user.branch_id != null;
});

const canManageDepartment = (department) => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    if (!user.permissions?.includes('edit departments')) return false;
    return Number(department.branch_id) === Number(user.branch_id);
};

const canDeleteDepartment = (department) => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    if (!user.permissions?.includes('delete departments')) return false;
    return Number(department.branch_id) === Number(user.branch_id);
};

/* ------------------------------------------------------------------ *
 * Management panel — shared branch selector
 * ------------------------------------------------------------------ */
const tab = ref('departments');

const branchOptions = computed(() =>
    props.branches.map((branch) => ({
        id: Number(branch.id),
        title: branch.code ? `${branch.name} (${branch.code})` : branch.name,
    })),
);

const selectedBranchId = ref(props.branches[0] ? Number(props.branches[0].id) : null);

// After reloads keep the current selection if it still exists, else fall back.
watch(
    () => props.branches,
    (list) => {
        const ids = list.map((b) => Number(b.id));
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
            (d) =>
                Number(d.branch_id) === Number(branchId) &&
                (d.parent_id ?? null) === (parentId ?? null),
        )
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }))
        .map((d) => ({
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
const departmentToDelete = ref(null);

const deptForm = useForm({
    branch_id: null,
    parent_id: null,
    name: '',
    code: '',
});

const parentOptions = computed(() => {
    const branchId = selectedBranchId.value;
    let options = props.departmentsFlat.filter(
        (department) => Number(department.branch_id) === Number(branchId),
    );

    if (editingDepartment.value) {
        const excludedIds = new Set([editingDepartment.value.id]);
        const collectDescendants = (parentId) => {
            props.departmentsFlat
                .filter((department) => department.parent_id === parentId)
                .forEach((child) => {
                    excludedIds.add(child.id);
                    collectDescendants(child.id);
                });
        };
        collectDescendants(editingDepartment.value.id);

        options = options.filter((department) => !excludedIds.has(department.id));
    }

    return options;
});

function openCreateDepartment(parentId = null) {
    editingDepartment.value = null;
    deptForm.reset();
    deptForm.clearErrors();
    deptForm.branch_id = selectedBranchId.value;
    deptForm.parent_id = parentId;
    deptDialog.value = true;
}

function openEditDepartment(department) {
    editingDepartment.value = department;
    deptForm.branch_id = department.branch_id;
    deptForm.parent_id = department.parent_id;
    deptForm.name = department.name;
    deptForm.code = department.code || '';
    deptForm.clearErrors();
    deptDialog.value = true;
}

function openDeleteDepartment(department) {
    departmentToDelete.value = department;
    deptDeleteDialog.value = true;
}

function submitDepartment() {
    deptForm.branch_id = selectedBranchId.value != null ? Number(selectedBranchId.value) : null;
    deptForm.parent_id =
        deptForm.parent_id != null && deptForm.parent_id !== '' ? Number(deptForm.parent_id) : null;

    if (editingDepartment.value) {
        deptForm.put(route('departments.update', editingDepartment.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deptDialog.value = false;
                deptForm.reset();
            },
        });
        return;
    }

    deptForm.post(route('departments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            deptDialog.value = false;
            deptForm.reset();
        },
    });
}

function confirmDeleteDepartment() {
    if (!departmentToDelete.value) return;

    deptForm.delete(route('departments.destroy', departmentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deptDeleteDialog.value = false;
            departmentToDelete.value = null;
        },
    });
}

/* ------------------------------------------------------------------ *
 * Branches management (Admin only)
 * ------------------------------------------------------------------ */
const branchDialog = ref(false);
const branchDeleteDialog = ref(false);
const editingBranch = ref(null);
const branchToDelete = ref(null);

const branchForm = useForm({
    name: '',
    code: '',
    address: '',
});

function openCreateBranch() {
    editingBranch.value = null;
    branchForm.reset();
    branchForm.clearErrors();
    branchDialog.value = true;
}

function openEditBranch(branch) {
    editingBranch.value = branch;
    branchForm.name = branch.name;
    branchForm.code = branch.code;
    branchForm.address = branch.address || '';
    branchForm.clearErrors();
    branchDialog.value = true;
}

function openDeleteBranch(branch) {
    branchToDelete.value = branch;
    branchDeleteDialog.value = true;
}

function submitBranch() {
    if (editingBranch.value) {
        branchForm.put(route('branches.update', editingBranch.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                branchDialog.value = false;
                branchForm.reset();
            },
        });
        return;
    }

    branchForm.post(route('branches.store'), {
        preserveScroll: true,
        onSuccess: () => {
            branchDialog.value = false;
            branchForm.reset();
        },
    });
}

function confirmDeleteBranch() {
    if (!branchToDelete.value) return;

    branchForm.delete(route('branches.destroy', branchToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            branchDeleteDialog.value = false;
            branchToDelete.value = null;
        },
    });
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
        <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden mb-6" style="height: 460px;">
            <div v-if="nodes.length === 0" class="text-center py-12 h-100 d-flex flex-column justify-center align-center">
                <Network style="width: 48px; height: 48px; margin-bottom: 8px; opacity: 0.5;" class="text-grey" />
                <div class="text-h6 text-grey font-weight-medium">Барои намоиши сохтор маълумот нест.</div>
            </div>

            <VueFlow
                v-else
                :nodes="nodes"
                :edges="edges"
                :nodes-connectable="false"
                :nodes-draggable="true"
                :elements-selectable="false"
                fit-view-on-init
                :min-zoom="0.2"
                :max-zoom="1.5"
                class="structure-flow"
                @pane-ready="onPaneReady"
            >
                <template #node-org="{ data }">
                    <div class="org-node" :class="`org-node--${data.kind}`">
                        <div class="org-node__icon">
                            <Building2 v-if="data.kind === 'root'" style="width: 18px; height: 18px;" />
                            <Network v-else-if="data.kind === 'branch'" style="width: 18px; height: 18px;" />
                            <Workflow v-else style="width: 18px; height: 18px;" />
                        </div>
                        <div class="org-node__body">
                            <div class="org-node__title">{{ data.label }}</div>
                            <div v-if="data.code" class="org-node__code">{{ data.code }}</div>
                            <div v-if="data.subtitle" class="org-node__sub">{{ data.subtitle }}</div>
                            <div v-if="data.kind !== 'root'" class="org-node__stats">
                                <span v-if="data.kind === 'branch'" class="org-node__stat" title="Кормандон">
                                    <Users style="width: 13px; height: 13px;" /> {{ data.employees ?? 0 }}
                                </span>
                                <span v-if="data.vacancies > 0" class="org-node__stat org-node__stat--vac" title="Вакансияҳои кушода">
                                    <DoorOpen style="width: 13px; height: 13px;" /> {{ data.vacancies }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </VueFlow>
        </v-card>

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
                                    <template v-slot:prepend>
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
                                    <template v-slot:prepend>
                                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                                    </template>
                                    Илова кардани филиал
                                </v-btn>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col v-for="branch in branches" :key="branch.id" cols="12" sm="6" md="4">
                                <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column transition-hover position-relative overflow-hidden">
                                    <div class="d-flex justify-space-between align-start mb-3">
                                        <div>
                                            <v-chip color="indigo" variant="flat" size="small" class="font-weight-black text-uppercase tracking-wider mb-2">
                                                {{ branch.code }}
                                            </v-chip>
                                            <h3 class="text-h6 font-weight-black text-indigo-darken-3">{{ branch.name }}</h3>
                                        </div>

                                        <v-menu>
                                            <template v-slot:activator="{ props: menuProps }">
                                                <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                                                    <MoreVertical style="width: 16px; height: 16px;" />
                                                </v-btn>
                                            </template>
                                            <v-list density="comfortable" rounded="xl" class="border py-1">
                                                <v-list-item
                                                    title="Таҳрир"
                                                    class="font-weight-bold"
                                                    @click="openEditBranch(branch)"
                                                >
                                                    <template v-slot:prepend>
                                                        <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                                    </template>
                                                </v-list-item>
                                                <v-list-item
                                                    title="Нест кардан"
                                                    class="text-error font-weight-bold"
                                                    @click="openDeleteBranch(branch)"
                                                >
                                                    <template v-slot:prepend>
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

                                    <v-divider class="my-3"></v-divider>

                                    <div class="d-flex justify-space-between align-center">
                                        <span class="text-subtitle-2 text-grey font-weight-bold text-uppercase">Ҳайат</span>
                                        <v-chip color="teal" variant="tonal" class="font-weight-black px-3" size="medium">
                                            <Users style="width: 16px; height: 16px; margin-right: 4px;" class="text-teal" />
                                            {{ branch.employees_count }} нафар.
                                        </v-chip>
                                    </div>
                                </v-card>
                            </v-col>

                            <v-col v-if="branches.length === 0" cols="12" class="text-center py-12">
                                <Building2 style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                                <div class="text-h6 text-grey font-weight-medium">Филиалҳо ёфт нашуданд.</div>
                            </v-col>
                        </v-row>
                    </div>
                </v-window-item>
            </v-window>
        </v-card>

        <!-- Department dialog -->
        <v-dialog v-model="deptDialog" max-width="560px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #0f2d88; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <Network style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                {{ editingDepartment ? 'Таҳрир' : 'Шуъбаи нав' }}
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ editingDepartment ? deptForm.name || 'Шуъба' : 'Илова кардани шуъба' }}
                            </div>
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submitDepartment">
                        <v-select
                            v-model="deptForm.parent_id"
                            :items="parentOptions"
                            item-title="name"
                            item-value="id"
                            label="Шуъбаи болоӣ"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            clearable
                            :error-messages="deptForm.errors.parent_id"
                            class="mb-4"
                            hint="Барои шуъбаи решагӣ холӣ монед"
                            persistent-hint
                        />

                        <v-text-field
                            v-model="deptForm.name"
                            label="Номи шуъба"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="deptForm.errors.name"
                            class="mb-4"
                        />

                        <v-text-field
                            v-model="deptForm.code"
                            label="Рамзи шуъба"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            :error-messages="deptForm.errors.code"
                        />
                    </v-form>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        :disabled="deptForm.processing"
                        class="px-6 font-weight-bold"
                        @click="deptDialog = false"
                    >
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        :loading="deptForm.processing"
                        class="px-6 font-weight-bold"
                        style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                        @click="submitDepartment"
                    >
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Department delete dialog -->
        <v-dialog v-model="deptDeleteDialog" max-width="460px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #dc2626; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Тасдиқ</div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">Нест кардани шуъба</div>
                        </div>
                    </div>
                </div>
                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    Шумо мутмаин ҳастед, ки мехоҳед шуъбаи <strong class="text-red-darken-2">{{ departmentToDelete?.name }}</strong>-ро нест кунед?
                    <div
                        v-if="departmentToDelete?.children_count > 0"
                        class="mt-3 pa-3 rounded-lg d-flex align-center"
                        style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);"
                    >
                        <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
                        <span class="text-error font-weight-bold text-body-2">Аввал зершуъбаҳоро нест кунед ё кӯчонед.</span>
                    </div>
                </v-card-text>
                <v-divider />
                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        :disabled="deptForm.processing"
                        class="px-6 font-weight-bold"
                        @click="deptDeleteDialog = false"
                    >
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        class="px-6 font-weight-bold"
                        :loading="deptForm.processing"
                        :disabled="departmentToDelete?.children_count > 0"
                        style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                        @click="confirmDeleteDepartment"
                    >
                        <template v-slot:prepend>
                            <Trash2 style="width: 18px; height: 18px;" />
                        </template>
                        Нест кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Branch dialog -->
        <v-dialog v-model="branchDialog" max-width="520px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #0f2d88; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <Building2 style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">{{ editingBranch ? 'Таҳрир' : 'Филиали нав' }}</div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ editingBranch ? branchForm.name || 'Филиал' : 'Илова кардани филиал' }}</div>
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submitBranch">
                        <v-text-field
                            v-model="branchForm.name"
                            label="Номи филиал"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="branchForm.errors.name"
                            class="mb-4"
                        ></v-text-field>

                        <v-text-field
                            v-model="branchForm.code"
                            label="Рамзи филиал (масалан, DSH)"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="branchForm.errors.code"
                            class="mb-4"
                        ></v-text-field>

                        <v-text-field
                            v-model="branchForm.address"
                            label="Суроға"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            :error-messages="branchForm.errors.address"
                        ></v-text-field>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="branchDialog = false"
                        :disabled="branchForm.processing"
                        class="px-6 font-weight-bold"
                    >
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        @click="submitBranch"
                        :loading="branchForm.processing"
                        class="px-6 font-weight-bold"
                        style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    >
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Branch delete dialog -->
        <v-dialog v-model="branchDeleteDialog" max-width="460px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #dc2626; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Тасдиқ</div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">Нест кардани филиал</div>
                        </div>
                    </div>
                </div>
                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    Шумо мутмаин ҳастед, ки мехоҳед филиали <strong class="text-red-darken-2">{{ branchToDelete?.name }}</strong>-ро нест кунед?
                    <div class="mt-3 pa-3 rounded-lg d-flex align-center" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
                        <span class="text-error font-weight-bold text-body-2">Ҳамаи кормандони алоқаманд низ бебозгашт нест карда мешаванд!</span>
                    </div>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="branchDeleteDialog = false"
                        :disabled="branchForm.processing"
                        class="px-6 font-weight-bold"
                    >
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        class="px-6 font-weight-bold"
                        @click="confirmDeleteBranch"
                        :loading="branchForm.processing"
                        style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                    >
                        <template v-slot:prepend>
                            <Trash2 style="width: 18px; height: 18px;" />
                        </template>
                        Нест кардан
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
.structure-flow {
    height: 100%;
    width: 100%;
}
.transition-hover-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
.transition-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px -10px rgba(79, 70, 229, 0.15);
}
.hover-scale-btn {
    transition: all 0.2s ease;
}
.hover-scale-btn:hover {
    transform: scale(1.15);
}
.tracking-wider {
    letter-spacing: 0.05em;
}
.org-node {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 180px;
    max-width: 220px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 20px -10px rgba(15, 45, 136, 0.25);
}
.org-node--root {
    background: #0f2d88;
    border: none;
    color: #fff;
}
.org-node--branch {
    border-left: 4px solid #009cf1;
}
.org-node--dept {
    border-left: 4px solid #94a3b8;
}
.org-node__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 9px;
    flex-shrink: 0;
    background: rgba(0, 156, 241, 0.12);
    color: #0284c7;
}
.org-node--root .org-node__icon {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}
.org-node__title {
    font-weight: 800;
    font-size: 0.9rem;
    line-height: 1.2;
    color: inherit;
}
.org-node--root .org-node__title {
    color: #fff;
}
.org-node__code {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-top: 2px;
}
.org-node__sub {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 2px;
}
.org-node__stats {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}
.org-node__stat {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 7px;
    border-radius: 999px;
}
.org-node__stat--vac {
    color: #b45309;
    background: rgba(245, 158, 11, 0.14);
}
</style>
