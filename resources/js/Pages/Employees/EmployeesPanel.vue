<script setup>
import { router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { useCrudDialogs } from '@/composables/useCrudDialogs';
import { usePermissions } from '@/composables/usePermissions';
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
    // Отложенные (группа Inertia "form") — отсутствуют при первой отрисовке,
    // подгружаются после рендера, поэтому до их прихода по умолчанию пусты.
    // (руководители загружаются по требованию внутри формы через поисковый эндпоинт.)
    categories: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    educations: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    birthPlaces: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    // Маршрут, перезапрашиваемый при фильтрации/пагинации — страница «Кормандон»
    // оставляет 'employees.index', вкладка на странице «Сохтор» передаёт
    // 'structure.index', чтобы список фильтровался, не покидая структуру.
    routeName: { type: String, default: 'employees.index' },
});

const { user: authUser, isAdmin, canManageInBranch, canCreateInBranch } = usePermissions();

const canManageEmployee = employee => canManageInBranch('edit employees', employee.branch_id);
const canCreateEmployees = computed(() => canCreateInBranch('create employees'));

const search = ref(props.filters.search || '');
const branchId = ref(props.filters.branch_id ? Number(props.filters.branch_id) : null);
const departmentId = ref(props.filters.department_id ? Number(props.filters.department_id) : null);
const typeId = ref(props.filters.type_id || null);

// Шуъбы в фильтре панели инструментов — сужены до выбранного филиала.
const filterDepartments = computed(() =>
    branchId.value
        ? props.departments.filter(d => Number(d.branch_id) === Number(branchId.value))
        : props.departments,
);

// Сбрасываем выбранную шуъбу, если она больше не относится к выбранному филиалу.
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

// Текстовый поиск применяется на лету с задержкой (debounce), чтобы не слать
// запрос на каждое нажатие клавиши (@vueuse/core).
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
    router.get(route(props.routeName), filterQuery(), {
        preserveState: true,
        replace: true,
        // Перезапрашиваем только список (+ эхо фильтров); справочные данные
        // панели/формы (и оргструктура на странице «Сохтор») не меняются,
        // поэтому исключаются из каждого запроса фильтра.
        only: ['employees', 'filters'],
    });
}

function changePage(p) {
    router.get(route(props.routeName), { page: p, ...filterQuery() }, {
        preserveState: true,
        only: ['employees', 'filters'],
    });
}

// Общий CRUD-набор (форма/просмотр/удаление); ротация — локальный диалог.
const {
    formDialog: createEditDialog,
    viewDialog,
    deleteDialog,
    editing: editingEmployee,
    viewing: viewEmployee,
    toDelete: employeeToDelete,
    openCreate: openCreateDialog,
    openEdit: openEditDialog,
    openView: openViewDialog,
    openDelete: openDeleteDialog,
} = useCrudDialogs();

const rotationDialog = ref(false);
const rotationEmployee = ref(null);

function openRotationDialog(employee) {
    rotationEmployee.value = employee;
    rotationDialog.value = true;
}
</script>

<template>
    <div>
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-end">
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

                <!-- Фильтр по филиалу (только для админов — пользователи филиала ограничены своим филиалом) -->
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

        <EmployeesTable
            :employees="employees"
            :can-manage="canManageEmployee"
            @view="openViewDialog"
            @rotate="openRotationDialog"
            @edit="openEditDialog"
            @delete="openDeleteDialog"
            @change-page="changePage"
        />

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
    </div>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
