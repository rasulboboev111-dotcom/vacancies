<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    employees: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const branchId = ref(props.filters.branch_id || null);
const category = ref(props.filters.category || null);
const type = ref(props.filters.type || null);

// Dialog controllers
const createEditDialog = ref(false);
const deleteDialog = ref(false);
const viewDialog = ref(false);

const editingEmployee = ref(null);
const selectedEmployee = ref(null);
const employeeToDelete = ref(null);

const form = useForm({
    branch_id: null,
    category: '',
    type: '',
    full_name: '',
    position: '',
    structure: '',
    direct_manager: '',
    hire_date: '',
    dismissal_date: '',
    passport_issued_by: '',
    inn: '',
});

// Watch filters and perform Inertia reload on change
watch([branchId, category, type], () => {
    applyFilters();
});

function applyFilters() {
    router.get(route('employees.index'), {
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
        category: category.value || undefined,
        type: type.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    branchId.value = null;
    category.value = null;
    type.value = null;
    router.get(route('employees.index'));
}

function openCreateDialog() {
    editingEmployee.value = null;
    form.reset();
    // Default to the first branch in user's list (if Branch Manager, it will be their branch)
    if (props.branches.length > 0) {
        form.branch_id = props.branches[0].id;
    }
    form.clearErrors();
    createEditDialog.value = true;
}

function openEditDialog(employee) {
    editingEmployee.value = employee;
    form.branch_id = employee.branch_id;
    form.category = employee.category;
    form.type = employee.type;
    form.full_name = employee.full_name;
    form.position = employee.position;
    form.structure = employee.structure;
    form.direct_manager = employee.direct_manager || '';
    form.hire_date = employee.hire_date ? employee.hire_date.substring(0, 10) : '';
    form.dismissal_date = employee.dismissal_date ? employee.dismissal_date.substring(0, 10) : '';
    form.passport_issued_by = employee.passport_issued_by || '';
    form.inn = employee.inn || '';
    form.clearErrors();
    createEditDialog.value = true;
}

function openViewDialog(employee) {
    selectedEmployee.value = employee;
    viewDialog.value = true;
}

function openDeleteDialog(employee) {
    employeeToDelete.value = employee;
    deleteDialog.value = true;
}

function submit() {
    if (editingEmployee.value) {
        form.put(route('employees.update', editingEmployee.value.id), {
            onSuccess: () => {
                createEditDialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('employees.store'), {
            onSuccess: () => {
                createEditDialog.value = false;
                form.reset();
            },
        });
    }
}

function confirmDelete() {
    if (employeeToDelete.value) {
        form.delete(route('employees.destroy', employeeToDelete.value.id), {
            onSuccess: () => {
                deleteDialog.value = false;
                employeeToDelete.value = null;
            },
        });
    }
}

function changePage(page) {
    router.get(route('employees.index'), {
        page: page,
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
        category: category.value || undefined,
        type: type.value || undefined,
    }, {
        preserveState: true,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ru-RU');
}
</script>

<template>
    <Head title="Сотрудники" />

    <AuthenticatedLayout>
        <template #header>
            Сотрудники
        </template>

        <!-- Filters section -->
        <v-card elevation="2" class="rounded-xl pa-5 mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="4" md="3">
                    <v-text-field
                        v-model="search"
                        label="Поиск по ФИО, должности или ИНН"
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        @keyup.enter="applyFilters"
                    ></v-text-field>
                </v-col>

                <!-- Branch Filter (disabled for Branch Managers as they only see their own branch) -->
                <v-col cols="12" sm="3" md="2">
                    <v-select
                        v-model="branchId"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        label="Филиал"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        :disabled="$page.props.auth.user.roles.includes('Branch Manager')"
                    ></v-select>
                </v-col>

                <!-- Category Filter -->
                <v-col cols="12" sm="3" md="2">
                    <v-select
                        v-model="category"
                        :items="categories"
                        label="Категория"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                    ></v-select>
                </v-col>

                <!-- Employment Type Filter -->
                <v-col cols="12" sm="2" md="2">
                    <v-select
                        v-model="type"
                        :items="types"
                        label="Тип занятости"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                    ></v-select>
                </v-col>

                <!-- Action buttons -->
                <v-col cols="12" sm="6" md="3" class="d-flex justify-end gap-2">
                    <v-btn
                        variant="tonal"
                        color="secondary"
                        rounded="lg"
                        class="mr-2"
                        @click="resetFilters"
                    >
                        Сбросить
                    </v-btn>
                    
                    <v-btn
                        v-if="!$page.props.auth.user.roles.includes('Viewer')"
                        color="primary"
                        prepend-icon="mdi-plus"
                        rounded="lg"
                        elevation="2"
                        @click="openCreateDialog"
                    >
                        Добавить
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Employee Table -->
        <v-card elevation="2" class="rounded-xl overflow-hidden">
            <v-table class="w-100">
                <thead>
                    <tr class="bg-grey-lighten-4">
                        <th class="font-weight-bold text-subtitle-2 pa-4">ФИО</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4">Должность</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4">Филиал</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4">Категория</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4">Тип</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4">Дата приема</th>
                        <th class="font-weight-bold text-subtitle-2 pa-4 text-center">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="employee in employees.data" :key="employee.id">
                        <td class="pa-4 font-weight-medium">{{ employee.full_name }}</td>
                        <td class="pa-4">{{ employee.position }}</td>
                        <td class="pa-4">
                            <v-chip size="small" color="primary" variant="tonal" class="font-weight-medium">
                                {{ employee.branch.name }}
                            </v-chip>
                        </td>
                        <td class="pa-4">{{ employee.category }}</td>
                        <td class="pa-4">{{ employee.type }}</td>
                        <td class="pa-4">{{ formatDate(employee.hire_date) }}</td>
                        <td class="pa-4 text-center">
                            <v-btn
                                icon="mdi-eye"
                                variant="text"
                                color="info"
                                size="small"
                                class="mr-1"
                                @click="openViewDialog(employee)"
                            ></v-btn>

                            <v-btn
                                v-if="!$page.props.auth.user.roles.includes('Viewer')"
                                icon="mdi-pencil"
                                variant="text"
                                color="primary"
                                size="small"
                                class="mr-1"
                                @click="openEditDialog(employee)"
                            ></v-btn>

                            <v-btn
                                v-if="!$page.props.auth.user.roles.includes('Viewer')"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                size="small"
                                @click="openDeleteDialog(employee)"
                            ></v-btn>
                        </td>
                    </tr>
                    <tr v-if="employees.data.length === 0">
                        <td colspan="7" class="text-center py-8 text-grey">
                            Сотрудники не найдены.
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <!-- Pagination Wrapper -->
            <v-divider></v-divider>
            <div class="d-flex justify-space-between align-center pa-4 bg-white">
                <div class="text-caption text-grey">
                    Показано {{ employees.from || 0 }} - {{ employees.to || 0 }} из {{ employees.total || 0 }} сотрудников
                </div>
                <v-pagination
                    v-if="employees.last_page > 1"
                    :model-value="employees.current_page"
                    :length="employees.last_page"
                    :total-visible="5"
                    density="comfortable"
                    rounded="lg"
                    @update:model-value="changePage"
                ></v-pagination>
            </div>
        </v-card>

        <!-- View Details Dialog -->
        <v-dialog v-model="viewDialog" max-width="600px">
            <v-card v-if="selectedEmployee" class="rounded-xl pa-5">
                <v-card-title class="font-weight-bold text-h5 px-0 pt-0 pb-4 d-flex justify-space-between align-center">
                    Детали сотрудника
                    <v-chip color="primary" variant="flat" size="small">{{ selectedEmployee.type }}</v-chip>
                </v-card-title>
                <v-divider class="mb-4"></v-divider>
                
                <v-card-text class="px-0 py-0">
                    <v-row>
                        <v-col cols="12" class="pb-2">
                            <span class="text-caption text-grey d-block">ФИО</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.full_name }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Должность</span>
                            <span class="text-body-1 font-weight-medium">{{ selectedEmployee.position }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Подразделение</span>
                            <span class="text-body-1 font-weight-medium">{{ selectedEmployee.structure }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Филиал</span>
                            <span class="text-body-1 font-weight-medium text-primary">{{ selectedEmployee.branch.name }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Категория</span>
                            <span class="text-body-1 font-weight-medium">{{ selectedEmployee.category }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Руководитель</span>
                            <span class="text-body-1 font-weight-medium">{{ selectedEmployee.direct_manager || 'Нет' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">ИНН</span>
                            <span class="text-body-1 font-weight-medium font-mono">{{ selectedEmployee.inn || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Дата приема</span>
                            <span class="text-body-1 font-weight-medium">{{ formatDate(selectedEmployee.hire_date) }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block">Дата увольнения</span>
                            <span class="text-body-1 font-weight-medium text-error">{{ formatDate(selectedEmployee.dismissal_date) }}</span>
                        </v-col>

                        <v-col cols="12" class="pt-2">
                            <span class="text-caption text-grey d-block">Кем выдан паспорт</span>
                            <span class="text-body-1 font-weight-medium text-wrap">{{ selectedEmployee.passport_issued_by || '-' }}</span>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-card-actions class="px-0 pt-6">
                    <v-spacer></v-spacer>
                    <v-btn color="primary" variant="flat" rounded="lg" @click="viewDialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Create / Edit Dialog -->
        <v-dialog v-model="createEditDialog" max-width="700px" persistent>
            <v-card class="rounded-xl pa-5">
                <v-card-title class="font-weight-bold text-h6 px-2">
                    {{ editingEmployee ? 'Редактировать сотрудника' : 'Добавить сотрудника' }}
                </v-card-title>
                
                <v-card-text class="px-2 pt-4">
                    <v-form @submit.prevent="submit">
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.full_name"
                                    label="ФИО сотрудника"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.full_name"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="form.branch_id"
                                    :items="branches"
                                    item-title="name"
                                    item-value="id"
                                    label="Филиал"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.branch_id"
                                    :disabled="$page.props.auth.user.roles.includes('Branch Manager')"
                                ></v-select>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.position"
                                    label="Должность"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.position"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.structure"
                                    label="Подразделение / Отдел"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.structure"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.category"
                                    label="Категория"
                                    placeholder="например, Руководство, Специалисты"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.category"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.type"
                                    label="Тип занятости"
                                    placeholder="например, Штатный, Контракт"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.type"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.direct_manager"
                                    label="Непосредственный руководитель"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.direct_manager"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.inn"
                                    label="ИНН / РМА"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.inn"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.hire_date"
                                    label="Дата приема"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    required
                                    :error-messages="form.errors.hire_date"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.dismissal_date"
                                    label="Дата увольнения"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.dismissal_date"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    v-model="form.passport_issued_by"
                                    label="Кем выдан паспорт"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    rows="2"
                                    :error-messages="form.errors.passport_issued_by"
                                ></v-textarea>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-card-actions class="px-2 pt-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        rounded="lg"
                        @click="createEditDialog = false"
                        :disabled="form.processing"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        rounded="lg"
                        @click="submit"
                        :loading="form.processing"
                    >
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400px">
            <v-card class="rounded-xl pa-4">
                <v-card-title class="font-weight-bold text-h6 px-2 text-error">
                    Подтверждение удаления
                </v-card-title>
                <v-card-text class="px-2 pt-2 text-body-1">
                    Вы уверены, что хотите удалить сотрудника <strong>{{ employeeToDelete?.full_name }}</strong>?
                </v-card-text>
                <v-card-actions class="px-2 pt-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        rounded="lg"
                        @click="deleteDialog = false"
                        :disabled="form.processing"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        @click="confirmDelete"
                        :loading="form.processing"
                    >
                        Удалить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>
