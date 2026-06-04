<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Building2, Trash2, User, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { firstError } from '@/lib/errors';
import TrashBranchesTab from '@/Pages/Trash/TrashBranchesTab.vue';
import TrashConfirmDialog from '@/Pages/Trash/TrashConfirmDialog.vue';
import TrashEmployeesTab from '@/Pages/Trash/TrashEmployeesTab.vue';
import TrashUsersTab from '@/Pages/Trash/TrashUsersTab.vue';

defineProps({
    employees: { type: Array, required: true },
    branches: { type: Array, required: true },
    users: { type: Array, required: true },
});

const { user: currentUser, isAdmin, hasPermission, canManageInBranch } = usePermissions();
const canManageEmployees = computed(() => hasPermission('delete employees'));
const canManageTrashedEmployee = employee => canManageInBranch('delete employees', employee.branch_id);

const tab = ref('employees');

// Action & Confirmation dialog state
const confirmDialog = ref(false);
const dialogAction = ref(''); // 'restore' | 'forceDelete'
const dialogType = ref(''); // 'employee' | 'branch' | 'user'
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

    if (!item || processing.value)
        return;

    const routes = {
        restore: {
            employee: 'trash.employees.restore',
            branch: 'trash.branches.restore',
            user: 'trash.users.restore',
        },
        forceDelete: {
            employee: 'trash.employees.force',
            branch: 'trash.branches.force',
            user: 'trash.users.force',
        },
    };

    const routeName = routes[action]?.[type];
    if (!routeName)
        return;

    // Keep the dialog open (and the button busy) until the request resolves, so
    // a slow connection can't be double-submitted and errors stay visible.
    processing.value = true;
    actionError.value = '';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            confirmDialog.value = false;
            selectedItem.value = null;
        },
        onError: (errors) => {
            // Surface the failure instead of silently doing nothing; the dialog
            // stays open so the user can read it and retry.
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
    <Head title="Сабади сабтҳои несткардашуда" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Trash2 style="width: 24px; height: 24px; margin-right: 12px;" class="text-rose-accent-4" />
                <span>Сабади сабтҳои несткардашуда</span>
            </div>
        </template>

        <v-alert v-if="actionError" type="error" variant="tonal" closable class="mb-4 rounded-lg font-weight-bold" @click:close="actionError = ''">
            {{ actionError }}
        </v-alert>

        <!-- Header Card with Premium Gradient -->
        <v-card elevation="0" class="rounded-xl border mb-6 text-white relative overflow-hidden dashboard-header-card">
            <div class="pa-6 d-flex flex-column flex-sm-row justify-space-between align-sm-center z-index-1">
                <div>
                    <h1 class="text-h5 font-weight-black d-flex align-center mb-1">
                        <Trash2 style="width: 28px; height: 28px; margin-right: 10px;" />
                        Идоракунии захираҳои несткардашуда
                    </h1>
                    <p class="text-subtitle-2 opacity-85 font-weight-medium">
                        Сабтҳои тасодуфан несткардашударо барқарор кунед ё барои озод кардани пойгоҳи додаҳо онҳоро бебозгашт нест кунед.
                    </p>
                </div>
                <div class="mt-4 mt-sm-0">
                    <v-chip color="white" variant="flat" class="text-rose font-weight-black shadow-sm" size="large">
                        Ҳамаи сабтҳо: {{ employees.length + branches.length + users.length }}
                    </v-chip>
                </div>
            </div>
            <div class="header-card-glow" />
            <div class="glass-shine" />
        </v-card>

        <!-- Main Card Container -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <!-- Tabs Navigation -->
            <v-tabs v-model="tab" color="rose" align-tabs="start" class="border-b">
                <v-tab value="employees" class="font-weight-bold text-subtitle-2 py-4">
                    <Users style="width: 18px; height: 18px; margin-right: 8px;" />
                    Кормандон
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ employees.length }}
                    </v-chip>
                </v-tab>
                <v-tab value="branches" class="font-weight-bold text-subtitle-2 py-4">
                    <Building2 style="width: 18px; height: 18px; margin-right: 8px;" />
                    Филиалҳо
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ branches.length }}
                    </v-chip>
                </v-tab>
                <v-tab value="users" class="font-weight-bold text-subtitle-2 py-4">
                    <User style="width: 18px; height: 18px; margin-right: 8px;" />
                    Корбарон
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ users.length }}
                    </v-chip>
                </v-tab>
            </v-tabs>

            <!-- Window Sections -->
            <v-window v-model="tab" class="mt-6">
                <v-window-item value="employees">
                    <TrashEmployeesTab
                        :employees="employees"
                        :can-manage="canManageTrashedEmployee"
                        @restore="(e) => openConfirm('restore', 'employee', e)"
                        @force="(e) => openConfirm('forceDelete', 'employee', e)"
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

        <!-- Dynamic Action Confirmation Dialog -->
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
.dashboard-header-card {
    background: #e11d48 !important;
    box-shadow: 0 10px 25px -5px rgba(244, 63, 94, 0.3) !important;
}
.header-card-glow {
    position: absolute;
    top: -20%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: transparent;
    border-radius: 50%;
    pointer-events: none;
}
</style>
