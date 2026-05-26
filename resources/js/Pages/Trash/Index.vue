<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Trash2, 
    RotateCcw, 
    Building2, 
    Users, 
    User, 
    AlertTriangle,
    Mail,
    Calendar,
    Briefcase,
    FileText,
    Fingerprint
} from '@lucide/vue';

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    branches: {
        type: Array,
        required: true,
    },
    users: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const userRoles = computed(() => currentUser.value?.roles || []);

// Role checks
const isAdminOrSuperAdmin = computed(() => {
    return userRoles.value.includes('Super Admin') || userRoles.value.includes('Admin');
});

const isViewer = computed(() => {
    return userRoles.value.includes('Viewer');
});

// Active tab
const tab = ref('employees');

// Search filters for each tab
const searchEmployees = ref('');
const searchBranches = ref('');
const searchUsers = ref('');

// Filtered lists
const filteredEmployees = computed(() => {
    if (!searchEmployees.value) return props.employees;
    const q = searchEmployees.value.toLowerCase();
    return props.employees.filter(e => 
        e.full_name?.toLowerCase().includes(q) || 
        e.inn?.toLowerCase().includes(q) || 
        e.sin?.toLowerCase().includes(q) || 
        e.position?.name?.toLowerCase().includes(q) || 
        e.branch?.name?.toLowerCase().includes(q)
    );
});

const filteredBranches = computed(() => {
    if (!searchBranches.value) return props.branches;
    const q = searchBranches.value.toLowerCase();
    return props.branches.filter(b => 
        b.name?.toLowerCase().includes(q)
    );
});

const filteredUsers = computed(() => {
    if (!searchUsers.value) return props.users;
    const q = searchUsers.value.toLowerCase();
    return props.users.filter(u => 
        u.name?.toLowerCase().includes(q) || 
        u.email?.toLowerCase().includes(q) || 
        u.branch?.name?.toLowerCase().includes(q)
    );
});

// Action & Confirmation dialog state
const confirmDialog = ref(false);
const dialogAction = ref(''); // 'restore' | 'forceDelete'
const dialogType = ref('');   // 'employee' | 'branch' | 'user'
const selectedItem = ref(null);

const dialogTitle = computed(() => {
    if (dialogAction.value === 'restore') {
        return 'Подтверждение восстановления';
    } else {
        return 'Окончательное удаление';
    }
});

const dialogMessage = computed(() => {
    if (!selectedItem.value) return '';
    
    const name = dialogType.value === 'employee' 
        ? selectedItem.value.full_name 
        : (dialogType.value === 'branch' ? selectedItem.value.name : selectedItem.value.name);

    const typeLabel = dialogType.value === 'employee' 
        ? 'сотрудника' 
        : (dialogType.value === 'branch' ? 'филиал' : 'пользователя');

    if (dialogAction.value === 'restore') {
        return `Вы действительно хотите восстановить ${typeLabel} "${name}"? Запись будет полностью возвращена в активную базу данных.`;
    } else {
        let warning = `ВНИМАНИЕ! Вы собираетесь окончательно удалить ${typeLabel} "${name}" из базы данных.`;
        if (dialogType.value === 'branch') {
            warning += '\n\nЭто действие удалит филиал навсегда. Убедитесь, что к нему не привязаны другие записи.';
        } else if (dialogType.value === 'user') {
            warning += '\n\nУчетная запись пользователя будет удалена безвозвратно. Войти под ней будет невозможно.';
        } else {
            warning += '\n\nВсе личные сведения, ИНН, СИН и история работы данного сотрудника будут стерты навсегда.';
        }
        warning += '\n\nЭто действие абсолютно необратимо!';
        return warning;
    }
});

function openConfirm(action, type, item) {
    dialogAction.value = action;
    dialogType.value = type;
    selectedItem.value = item;
    confirmDialog.value = true;
}

function handleConfirm() {
    confirmDialog.value = false;
    const action = dialogAction.value;
    const type = dialogType.value;
    const item = selectedItem.value;

    if (!item) return;

    if (action === 'restore') {
        if (type === 'employee') {
            router.post(route('trash.employees.restore', item.id));
        } else if (type === 'branch') {
            router.post(route('trash.branches.restore', item.id));
        } else if (type === 'user') {
            router.post(route('trash.users.restore', item.id));
        }
    } else if (action === 'forceDelete') {
        if (type === 'employee') {
            router.delete(route('trash.employees.force', item.id));
        } else if (type === 'branch') {
            router.delete(route('trash.branches.force', item.id));
        } else if (type === 'user') {
            router.delete(route('trash.users.force', item.id));
        }
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (!isNaN(date.getTime())) {
        return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    return dateStr;
}
</script>

<template>
    <Head title="Корзина удаленных записей" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Trash2 style="width: 24px; height: 24px; margin-right: 12px;" class="text-rose-accent-4" />
                <span>Корзина удаленных записей</span>
            </div>
        </template>

        <!-- Header Card with Premium Gradient -->
        <v-card elevation="0" class="rounded-xl border mb-6 text-white relative overflow-hidden dashboard-header-card">
            <div class="pa-6 d-flex flex-column flex-sm-row justify-space-between align-sm-center z-index-1">
                <div>
                    <h1 class="text-h5 font-weight-black d-flex align-center mb-1">
                        <Trash2 style="width: 28px; height: 28px; margin-right: 10px;" />
                        Управление удаленными ресурсами
                    </h1>
                    <p class="text-subtitle-2 opacity-85 font-weight-medium">
                        Восстанавливайте случайно удаленные записи или стирайте их окончательно для освобождения базы данных.
                    </p>
                </div>
                <div class="mt-4 mt-sm-0">
                    <v-chip color="white" variant="flat" class="text-rose font-weight-black shadow-sm" size="large">
                        Всего записей: {{ employees.length + branches.length + users.length }}
                    </v-chip>
                </div>
            </div>
            <div class="header-card-glow"></div>
            <div class="glass-shine"></div>
        </v-card>

        <!-- Main Card Container -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <!-- Tabs Navigation -->
            <v-tabs
                v-model="tab"
                color="rose"
                align-tabs="start"
                class="border-b"
            >
                <v-tab value="employees" class="font-weight-bold text-subtitle-2 py-4">
                    <Users style="width: 18px; height: 18px; margin-right: 8px;" />
                    Сотрудники
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ employees.length }}
                    </v-chip>
                </v-tab>
                <v-tab value="branches" class="font-weight-bold text-subtitle-2 py-4">
                    <Building2 style="width: 18px; height: 18px; margin-right: 8px;" />
                    Филиалы
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ branches.length }}
                    </v-chip>
                </v-tab>
                <v-tab value="users" class="font-weight-bold text-subtitle-2 py-4">
                    <User style="width: 18px; height: 18px; margin-right: 8px;" />
                    Пользователи
                    <v-chip size="x-small" color="error" variant="flat" class="ml-2 font-weight-bold">
                        {{ users.length }}
                    </v-chip>
                </v-tab>
            </v-tabs>

            <!-- Window Sections -->
            <v-window v-model="tab" class="mt-6">
                
                <!-- Employees Tab -->
                <v-window-item value="employees">
                    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
                        <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4 mb-3 mb-sm-0">
                            Удаленные сотрудники
                        </div>
                        <v-text-field
                            v-model="searchEmployees"
                            prepend-inner-icon="mdi-magnify"
                            label="Быстрый поиск по имени, ИНН, коду..."
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                            clearable
                            color="rose"
                            style="max-width: 380px; width: 100%;"
                        ></v-text-field>
                    </div>

                    <v-table class="table-modern border rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">ФИО сотрудника</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Филиал</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Должность</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">ИНН</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">СИН (Код)</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Удален в</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 180px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="employee in filteredEmployees" :key="employee.id" class="trash-row">
                                <td class="pa-4 font-weight-bold text-slate-800">
                                    {{ employee.full_name }}
                                </td>
                                <td class="pa-4 text-body-2">{{ employee.branch?.name || '-' }}</td>
                                <td class="pa-4 text-body-2 font-weight-medium text-slate-700">
                                    {{ employee.position?.name || '-' }}
                                </td>
                                <td class="pa-4 text-body-2 font-mono font-weight-medium">{{ employee.inn || '-' }}</td>
                                <td class="pa-4 text-body-2 font-mono font-weight-medium text-teal-darken-3">{{ employee.sin || '-' }}</td>
                                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                                    {{ formatDate(employee.deleted_at) }}
                                </td>
                                <td class="pa-4 text-center">
                                    <div class="d-flex justify-center g-2">
                                        <v-btn
                                            v-if="!isViewer"
                                            color="success"
                                            variant="tonal"
                                            size="small"
                                            rounded="lg"
                                            class="mr-2 hover-scale-btn font-weight-bold"
                                            @click="openConfirm('restore', 'employee', employee)"
                                        >
                                            <template v-slot:prepend>
                                                <RotateCcw style="width: 14px; height: 14px;" />
                                            </template>
                                            Вернуть
                                        </v-btn>
                                        <v-btn
                                            v-if="!isViewer"
                                            color="error"
                                            variant="tonal"
                                            size="small"
                                            rounded="lg"
                                            class="hover-scale-btn font-weight-bold"
                                            @click="openConfirm('forceDelete', 'employee', employee)"
                                        >
                                            <template v-slot:prepend>
                                                <Trash2 style="width: 14px; height: 14px;" />
                                            </template>
                                            Стереть
                                        </v-btn>
                                        <span v-else class="text-caption text-grey">Только просмотр</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredEmployees.length === 0">
                                <td colspan="7" class="text-center py-12">
                                    <div class="d-flex flex-column align-center justify-center text-grey">
                                        <Users style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                                        <div class="font-weight-medium">
                                            {{ searchEmployees ? 'Совпадений в корзине не найдено' : 'Корзина сотрудников пуста' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-window-item>

                <!-- Branches Tab -->
                <v-window-item value="branches">
                    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
                        <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4 mb-3 mb-sm-0">
                            Удаленные филиалы
                        </div>
                        <v-text-field
                            v-model="searchBranches"
                            prepend-inner-icon="mdi-magnify"
                            label="Быстрый поиск по названию..."
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                            clearable
                            color="rose"
                            style="max-width: 380px; width: 100%;"
                        ></v-text-field>
                    </div>

                    <v-table class="table-modern border rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Название филиала</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Удален в</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 180px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="branch in filteredBranches" :key="branch.id" class="trash-row">
                                <td class="pa-4 font-weight-bold text-slate-800">
                                    {{ branch.name }}
                                </td>
                                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                                    {{ formatDate(branch.deleted_at) }}
                                </td>
                                <td class="pa-4 text-center">
                                    <div class="d-flex justify-center g-2">
                                        <template v-if="isAdminOrSuperAdmin">
                                            <v-btn
                                                color="success"
                                                variant="tonal"
                                                size="small"
                                                rounded="lg"
                                                class="mr-2 hover-scale-btn font-weight-bold"
                                                @click="openConfirm('restore', 'branch', branch)"
                                            >
                                                <template v-slot:prepend>
                                                    <RotateCcw style="width: 14px; height: 14px;" />
                                                </template>
                                                Вернуть
                                            </v-btn>
                                            <v-btn
                                                color="error"
                                                variant="tonal"
                                                size="small"
                                                rounded="lg"
                                                class="hover-scale-btn font-weight-bold"
                                                @click="openConfirm('forceDelete', 'branch', branch)"
                                            >
                                                <template v-slot:prepend>
                                                    <Trash2 style="width: 14px; height: 14px;" />
                                                </template>
                                                Стереть
                                            </v-btn>
                                        </template>
                                        <span v-else class="text-caption text-grey font-weight-medium">
                                            Доступно только Администраторам
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredBranches.length === 0">
                                <td colspan="3" class="text-center py-12">
                                    <div class="d-flex flex-column align-center justify-center text-grey">
                                        <Building2 style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                                        <div class="font-weight-medium">
                                            {{ searchBranches ? 'Совпадений в корзине не найдено' : 'Корзина филиалов пуста' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-window-item>

                <!-- Users Tab -->
                <v-window-item value="users">
                    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
                        <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4 mb-3 mb-sm-0">
                            Удаленные пользователи
                        </div>
                        <v-text-field
                            v-model="searchUsers"
                            prepend-inner-icon="mdi-magnify"
                            label="Быстрый поиск по имени или email..."
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                            clearable
                            color="rose"
                            style="max-width: 380px; width: 100%;"
                        ></v-text-field>
                    </div>

                    <v-table class="table-modern border rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">ФИО / Логин</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">E-mail</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Филиал</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">Удален в</th>
                                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 180px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="usr in filteredUsers" :key="usr.id" class="trash-row">
                                <td class="pa-4 font-weight-bold text-slate-800">
                                    {{ usr.name }}
                                </td>
                                <td class="pa-4 text-body-2 font-weight-medium">
                                    <div class="d-flex align-center">
                                        <Mail style="width: 14px; height: 14px; margin-right: 6px;" class="text-grey" />
                                        {{ usr.email }}
                                    </div>
                                </td>
                                <td class="pa-4 text-body-2">{{ usr.branch?.name || 'Все филиалы / Глобальный' }}</td>
                                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                                    {{ formatDate(usr.deleted_at) }}
                                </td>
                                <td class="pa-4 text-center">
                                    <div class="d-flex justify-center g-2">
                                        <v-btn
                                            v-if="!isViewer"
                                            color="success"
                                            variant="tonal"
                                            size="small"
                                            rounded="lg"
                                            class="mr-2 hover-scale-btn font-weight-bold"
                                            @click="openConfirm('restore', 'user', usr)"
                                        >
                                            <template v-slot:prepend>
                                                <RotateCcw style="width: 14px; height: 14px;" />
                                            </template>
                                            Вернуть
                                        </v-btn>
                                        
                                        <!-- Only Admin or Super Admin can force delete users -->
                                        <v-btn
                                            v-if="isAdminOrSuperAdmin && currentUser.id !== usr.id"
                                            color="error"
                                            variant="tonal"
                                            size="small"
                                            rounded="lg"
                                            class="hover-scale-btn font-weight-bold"
                                            @click="openConfirm('forceDelete', 'user', usr)"
                                        >
                                            <template v-slot:prepend>
                                                <Trash2 style="width: 14px; height: 14px;" />
                                            </template>
                                            Стереть
                                        </v-btn>
                                        <span v-else-if="currentUser.id === usr.id" class="text-caption text-grey">Текущий сеанс</span>
                                        <span v-else-if="isViewer" class="text-caption text-grey">Только просмотр</span>
                                        <span v-else class="text-caption text-grey font-weight-medium">Только для Админа</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="text-center py-12">
                                    <div class="d-flex flex-column align-center justify-center text-grey">
                                        <User style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                                        <div class="font-weight-medium">
                                            {{ searchUsers ? 'Совпадений в корзине не найдено' : 'Корзина пользователей пуста' }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-window-item>
            </v-window>
        </v-card>

        <!-- Dynamic Action Confirmation Dialog -->
        <v-dialog v-model="confirmDialog" max-width="550px" scrollable>
            <v-card class="rounded-2xl border overflow-hidden">
                <!-- Header with Action Color -->
                <div 
                    class="pa-6 text-white d-flex align-center justify-space-between" 
                    :style="{ 
                        background: dialogAction === 'restore' 
                            ? 'linear-gradient(135deg, #10b981 0%, #047857 100%)' 
                            : 'linear-gradient(135deg, #f43f5e 0%, #be123c 100%)' 
                    }"
                >
                    <div class="d-flex align-center">
                        <v-avatar color="white" size="40" class="mr-4 shadow-sm">
                            <RotateCcw v-if="dialogAction === 'restore'" style="width: 20px; height: 20px; color: #10b981;" />
                            <AlertTriangle v-else style="width: 20px; height: 20px; color: #f43f5e;" />
                        </v-avatar>
                        <div>
                            <div class="text-h6 font-weight-black">{{ dialogTitle }}</div>
                        </div>
                    </div>
                </div>

                <v-divider></v-divider>

                <!-- Message Body -->
                <v-card-text class="pa-6 bg-slate-50 text-body-1 font-weight-medium text-slate-700 whitespace-pre-line">
                    {{ dialogMessage }}
                </v-card-text>

                <!-- Actions -->
                <v-card-actions class="px-6 py-4 bg-slate-100 d-flex justify-end border-t">
                    <v-btn 
                        variant="outlined" 
                        color="grey" 
                        rounded="lg" 
                        class="px-5 font-weight-bold" 
                        @click="confirmDialog = false"
                    >
                        Отмена
                    </v-btn>
                    <v-btn 
                        :color="dialogAction === 'restore' ? 'success' : 'error'" 
                        variant="flat" 
                        rounded="lg" 
                        class="px-5 font-weight-bold ml-3" 
                        @click="handleConfirm"
                    >
                        {{ dialogAction === 'restore' ? 'Восстановить' : 'Удалить безвозвратно' }}
                    </v-btn>
                </v-card-actions>
                <div class="glass-shine"></div>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.75) !important;
    backdrop-filter: blur(12px);
}
.dashboard-header-card {
    background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%) !important;
    box-shadow: 0 10px 25px -5px rgba(244, 63, 94, 0.3) !important;
}
.header-card-glow {
    position: absolute;
    top: -20%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.table-modern {
    border-radius: 12px;
}
.trash-row {
    transition: all 0.2s ease-in-out;
}
.trash-row:hover {
    background-color: rgba(244, 63, 94, 0.04) !important;
}
.hover-scale-btn {
    transition: all 0.2s ease;
}
.hover-scale-btn:hover {
    transform: scale(1.05);
}
.font-mono {
    font-family: monospace, Courier, monospace;
}
.whitespace-pre-line {
    white-space: pre-line;
}
.glass-shine {
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.06) 50%, rgba(255,255,255,0) 100%);
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
}
</style>
