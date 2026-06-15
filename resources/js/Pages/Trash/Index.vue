<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Building2, Network, Trash2, User, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { firstError } from '@/lib/errors';
import TrashBranchesTab from '@/Pages/Trash/TrashBranchesTab.vue';
import TrashConfirmDialog from '@/Pages/Trash/TrashConfirmDialog.vue';
import TrashDepartmentsTab from '@/Pages/Trash/TrashDepartmentsTab.vue';
import TrashEmployeesTab from '@/Pages/Trash/TrashEmployeesTab.vue';
import TrashUsersTab from '@/Pages/Trash/TrashUsersTab.vue';

const props = defineProps({
    employees: { type: Array, required: true },
    branches: { type: Array, required: true },
    users: { type: Array, required: true },
    departments: { type: Array, default: () => [] },
});

const { user: currentUser, isAdmin, hasPermission, canManageInBranch } = usePermissions();
const canManageEmployees = computed(() => hasPermission('delete employees'));
const canManageTrashedEmployee = employee => canManageInBranch('delete employees', employee.branch_id);
const canManageTrashedDepartment = department => canManageInBranch('delete departments', department.branch_id);

const tab = ref('employees');

// Плитки-сводки кликабельны, каждая переключает на свою вкладку.
const tiles = computed(() => [
    { key: 'employees', label: 'Кормандон', icon: Users, count: props.employees.length },
    { key: 'departments', label: 'Шуъбаҳо', icon: Network, count: props.departments.length },
    { key: 'branches', label: 'Филиалҳо', icon: Building2, count: props.branches.length },
    { key: 'users', label: 'Корбарон', icon: User, count: props.users.length },
]);

const totalCount = computed(() =>
    props.employees.length + props.departments.length + props.branches.length + props.users.length,
);

// Состояние диалога действия и подтверждения
const confirmDialog = ref(false);
const dialogAction = ref(''); // 'restore' | 'forceDelete'
const dialogType = ref(''); // 'employee' | 'department' | 'branch' | 'user'
const selectedItem = ref(null);
const processing = ref(false);
const actionError = ref('');

function openConfirm(action, type, item) {
    dialogAction.value = action;
    dialogType.value = type;
    selectedItem.value = item;
    confirmDialog.value = true;
}

function handleConfirm() {
    const action = dialogAction.value;
    const type = dialogType.value;
    const item = selectedItem.value;

    if (!item || processing.value) {
        return;
    }

    const routes = {
        restore: {
            employee: 'trash.employees.restore',
            department: 'trash.departments.restore',
            branch: 'trash.branches.restore',
            user: 'trash.users.restore',
        },
        forceDelete: {
            employee: 'trash.employees.force',
            department: 'trash.departments.force',
            branch: 'trash.branches.force',
            user: 'trash.users.force',
        },
    };

    const routeName = routes[action]?.[type];
    if (!routeName) {
        return;
    }

    // Держим диалог открытым (а кнопку занятой), пока запрос не завершится, чтобы
    // на медленном соединении нельзя было отправить повторно и ошибки оставались видны.
    processing.value = true;
    actionError.value = '';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            confirmDialog.value = false;
            selectedItem.value = null;
        },
        onError: (errors) => {
            actionError.value = firstError(errors, 'Амалиёт иҷро нашуд. Бори дигар кӯшиш кунед.');
        },
        onFinish: () => {
            processing.value = false;
        },
    };

    if (action === 'restore') {
        router.post(route(routeName, item.id), {}, options);
    }
    else {
        router.delete(route(routeName, item.id), options);
    }
}
</script>

<template>
    <Head title="Сабад" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center text-error">
                <Trash2 style="width: 24px; height: 24px; margin-right: 12px;" />
                <span>Сабад (Сабтҳои несткардашуда)</span>
            </div>
        </template>

        <v-alert v-if="actionError" type="error" variant="tonal" closable class="mb-4 rounded-lg font-weight-bold" @click:close="actionError = ''">
            {{ actionError }}
        </v-alert>

        <v-row class="mb-6" density="comfortable">
            <v-col v-for="t in tiles" :key="t.key" cols="6" md="3">
                <button
                    type="button"
                    class="trash-tile"
                    :class="{ 'trash-tile--active': tab === t.key }"
                    @click="tab = t.key"
                >
                    <span class="trash-tile__icon">
                        <component :is="t.icon" style="width: 22px; height: 22px;" />
                    </span>
                    <span class="trash-tile__text">
                        <span class="trash-tile__count">{{ t.count }}</span>
                        <span class="trash-tile__label">{{ t.label }}</span>
                    </span>
                </button>
            </v-col>
        </v-row>

        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass">
            <div class="d-flex align-center justify-space-between mb-2">
                <div class="text-subtitle-1 font-weight-bold text-grey-darken-4">
                    Идоракунии сабад
                </div>
                <span class="text-caption text-grey font-weight-bold">
                    Ҳамагӣ дар сабад: {{ totalCount }}
                </span>
            </div>
            <v-divider class="mb-4" />

            <v-tabs v-model="tab" color="error" align-tabs="start" show-arrows class="mb-2">
                <v-tab v-for="t in tiles" :key="t.key" :value="t.key" class="font-weight-bold text-subtitle-2 text-none">
                    <component :is="t.icon" style="width: 16px; height: 16px; margin-right: 6px;" />
                    {{ t.label }}
                </v-tab>
            </v-tabs>

            <v-window v-model="tab" class="mt-4">
                <v-window-item value="employees">
                    <TrashEmployeesTab
                        :employees="employees"
                        :can-manage="canManageTrashedEmployee"
                        @restore="(e) => openConfirm('restore', 'employee', e)"
                        @force="(e) => openConfirm('forceDelete', 'employee', e)"
                    />
                </v-window-item>

                <v-window-item value="departments">
                    <TrashDepartmentsTab
                        :departments="departments"
                        :can-manage="canManageTrashedDepartment"
                        @restore="(d) => openConfirm('restore', 'department', d)"
                        @force="(d) => openConfirm('forceDelete', 'department', d)"
                    />
                </v-window-item>

                <v-window-item value="branches">
                    <TrashBranchesTab
                        :branches="branches"
                        :is-admin="isAdmin"
                        @restore="(b) => openConfirm('restore', 'branch', b)"
                        @force="(b) => openConfirm('forceDelete', 'branch', b)"
                    />
                </v-window-item>

                <v-window-item value="users">
                    <TrashUsersTab
                        :users="users"
                        :is-admin="isAdmin"
                        :can-manage="canManageEmployees"
                        :current-user-id="currentUser.id"
                        @restore="(u) => openConfirm('restore', 'user', u)"
                        @force="(u) => openConfirm('forceDelete', 'user', u)"
                    />
                </v-window-item>
            </v-window>
        </v-card>

        <TrashConfirmDialog
            v-model="confirmDialog"
            :action="dialogAction"
            :type="dialogType"
            :item="selectedItem"
            :processing="processing"
            @confirm="handleConfirm"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.75) !important;
    backdrop-filter: blur(12px);
}

.trash-tile {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    border: 1px solid #eef1f5;
    border-radius: 14px;
    background: #ffffff;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}
.trash-tile:hover {
    box-shadow: 0 8px 22px -12px rgba(15, 23, 42, 0.2);
    transform: translateY(-2px);
}
.trash-tile--active {
    border-color: rgba(244, 63, 94, 0.5);
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.1);
}
.trash-tile__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    flex-shrink: 0;
    background: rgba(244, 63, 94, 0.12);
    color: #e11d48;
}
.trash-tile__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.trash-tile__count {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
    color: #0f172a;
}
.trash-tile__label {
    margin-top: 4px;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #94a3b8;
}
</style>
