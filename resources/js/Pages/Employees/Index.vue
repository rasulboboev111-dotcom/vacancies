<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    Users,
    Search,
    FilterX,
    Plus,
    Eye,
    ArrowLeftRight,
    Pencil,
    Trash2,
    UserX,
    User,
    Briefcase,
    FileText,
    UserPlus,
    IdCard,
    AlertCircle,
    Phone,
    MapPin,
    Calendar,
    Globe,
    BookOpen,
    Award,
    Network,
    Layers,
    UserCheck,
    Clock
} from '@lucide/vue';

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
    positions: {
        type: Array,
        required: true,
    },
    structures: {
        type: Array,
        required: true,
    },
    managers: {
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
const categoryId = ref(props.filters.category_id || null);
const typeId = ref(props.filters.type_id || null);

// Dialog controllers
const createEditDialog = ref(false);
const deleteDialog = ref(false);
const viewDialog = ref(false);

const editingEmployee = ref(null);
const selectedEmployee = ref(null);
const employeeToDelete = ref(null);

// Form Tabs
const activeTab = ref(0);

const form = useForm({
    branch_id: null,
    category_id: null,
    type_id: null,
    full_name: '',
    gender: '',
    position_id: null,
    structure_id: null,
    manager_id: null,
    hire_date: '',
    dismissal_date: '',
    birth_date: '',
    nationality: '',
    passport_number: '',
    passport_start_date: '',
    passport_end_date: '',
    passport_issued_by: '',
    inn: '',
    sin: '',
    address: '',
    phone_number: '',
    birth_place: '',
    education: '',
    specialty: '',
    total_experience: '',
});

// Watch filters and perform Inertia reload on change
watch([branchId, categoryId, typeId], () => {
    applyFilters();
});

function applyFilters() {
    router.get(route('employees.index'), {
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
        category_id: categoryId.value || undefined,
        type_id: typeId.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    branchId.value = null;
    categoryId.value = null;
    typeId.value = null;
    router.get(route('employees.index'));
}

function openCreateDialog() {
    editingEmployee.value = null;
    activeTab.value = 0;
    form.reset();
    if (props.branches.length > 0) {
        form.branch_id = Number(props.branches[0].id);
    }
    form.clearErrors();
    createEditDialog.value = true;
}

function openEditDialog(employee) {
    editingEmployee.value = employee;
    activeTab.value = 0;
    form.branch_id = employee.branch_id ? Number(employee.branch_id) : null;
    form.category_id = employee.category_id ? Number(employee.category_id) : null;
    form.type_id = employee.type_id ? Number(employee.type_id) : null;
    form.full_name = employee.full_name;
    form.gender = employee.gender || '';
    form.position_id = employee.position_id ? Number(employee.position_id) : null;
    form.structure_id = employee.structure_id ? Number(employee.structure_id) : null;
    form.manager_id = employee.manager_id ? Number(employee.manager_id) : null;
    form.hire_date = employee.hire_date ? employee.hire_date.substring(0, 10) : '';
    form.dismissal_date = employee.dismissal_date ? employee.dismissal_date.substring(0, 10) : '';
    form.birth_date = employee.birth_date ? employee.birth_date.substring(0, 10) : '';
    form.nationality = employee.nationality || '';
    form.passport_number = employee.passport_number || '';
    form.passport_start_date = employee.passport_start_date ? employee.passport_start_date.substring(0, 10) : '';
    form.passport_end_date = employee.passport_end_date ? employee.passport_end_date.substring(0, 10) : '';
    form.passport_issued_by = employee.passport_issued_by || '';
    form.inn = employee.inn || '';
    form.sin = employee.sin || '';
    form.address = employee.address || '';
    form.phone_number = employee.phone_number || '';
    form.birth_place = employee.birth_place || '';
    form.education = employee.education || '';
    form.specialty = employee.specialty || '';
    form.total_experience = employee.total_experience || '';
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
        category_id: categoryId.value || undefined,
        type_id: typeId.value || undefined,
    }, {
        preserveState: true,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ru-RU');
}

// Rotation variables & functions
const rotationDialog = ref(false);
const rotationForm = useForm({
    branch_id: null,
    position_id: null,
    structure_id: null,
    rotation_date: new Date().toISOString().substring(0, 10),
    reason: '',
});

function openRotationDialog(employee) {
    selectedEmployee.value = employee;
    rotationForm.branch_id = employee.branch_id ? Number(employee.branch_id) : null;
    rotationForm.position_id = employee.position_id ? Number(employee.position_id) : null;
    rotationForm.structure_id = employee.structure_id ? Number(employee.structure_id) : null;
    rotationForm.rotation_date = new Date().toISOString().substring(0, 10);
    rotationForm.reason = '';
    rotationForm.clearErrors();
    rotationDialog.value = true;
}

function submitRotation() {
    rotationForm.post(route('employees.rotate', selectedEmployee.value.id), {
        onSuccess: () => {
            rotationDialog.value = false;
            rotationForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Сотрудники" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Users class="mr-3 text-indigo-accent-2 h-6 w-6" />
                <span>Сотрудники</span>
            </div>
        </template>

        <!-- Filters section -->
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="12" md="3">
                    <v-text-field
                        v-model="search"
                        placeholder="Поиск по ФИО, должности..."
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        class="premium-field"
                        @keyup.enter="applyFilters"
                    >
                        <template v-slot:prepend-inner>
                            <Search style="width: 18px; height: 18px; opacity: 0.5;" />
                        </template>
                    </v-text-field>
                </v-col>

                <!-- Branch Filter -->
                <v-col cols="12" sm="4" md="2">
                    <v-select
                        v-model="branchId"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        label="Филиал"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                        :disabled="$page.props.auth.user.roles.includes('Branch Manager')"
                    ></v-select>
                </v-col>

                <!-- Category Filter -->
                <v-col cols="12" sm="4" md="2">
                    <v-select
                        v-model="categoryId"
                        :items="categories"
                        item-title="name"
                        item-value="id"
                        label="Категория"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                    ></v-select>
                </v-col>

                <!-- Employment Type Filter -->
                <v-col cols="12" sm="4" md="2">
                    <v-select
                        v-model="typeId"
                        :items="types"
                        item-title="name"
                        item-value="id"
                        label="Тип занятости"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        clearable
                        class="premium-field"
                    ></v-select>
                </v-col>

                <!-- Action buttons -->
                <v-col cols="12" sm="12" md="3" class="d-flex align-center justify-md-end justify-center gap-2">
                    <v-btn
                        variant="flat"
                        rounded="lg"
                        class="px-4 transition-hover-btn font-weight-bold"
                        style="background: rgba(99, 102, 241, 0.08) !important; color: #4f46e5 !important; border: 1px solid rgba(99, 102, 241, 0.15) !important;"
                        @click="resetFilters"
                    >
                        <template v-slot:prepend>
                            <FilterX style="width: 16px; height: 16px; color: #4f46e5;" />
                        </template>
                        Сбросить
                    </v-btn>
                    
                    <v-btn
                        v-if="!$page.props.auth.user.roles.includes('Viewer')"
                        variant="flat"
                        rounded="lg"
                        class="px-5 transition-hover-btn font-weight-bold text-white"
                        style="background: #4f46e5 !important; color: #ffffff !important; box-shadow: 0 4px 14px -4px rgba(79, 70, 229, 0.45) !important;"
                        @click="openCreateDialog"
                    >
                        <template v-slot:prepend>
                            <Plus style="width: 18px; height: 18px; color: #ffffff;" />
                        </template>
                        Добавить
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Employee Table -->
        <v-card elevation="0" class="rounded-xl border overflow-hidden bg-surface-glass">
            <v-table class="w-100 table-modern">
                <thead>
                    <tr class="bg-indigo-lighten-5">
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">ФИО</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Должность</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Филиал</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Категория</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Тип</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Телефон</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Возраст</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Общий стаж</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo text-center">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="employee in employees.data" :key="employee.id" class="employee-row">
                        <td class="pa-4 font-weight-bold text-indigo-darken-3">{{ employee.full_name }}</td>
                        <td class="pa-4 text-grey-darken-3 font-weight-medium">{{ employee.position?.name || '-' }}</td>
                        <td class="pa-4">
                            <v-chip size="small" color="indigo" variant="flat" class="font-weight-bold">
                                {{ employee.branch?.name }}
                            </v-chip>
                        </td>
                        <td class="pa-4"><v-chip size="small" color="secondary" variant="outlined">{{ employee.category?.name || '-' }}</v-chip></td>
                        <td class="pa-4"><v-chip size="small" color="teal" variant="tonal" class="font-weight-bold">{{ employee.employment_type?.name || '-' }}</v-chip></td>
                        <td class="pa-4 text-body-2 font-weight-medium">{{ employee.phone_number || '-' }}</td>
                        <td class="pa-4 text-body-2 font-weight-bold text-indigo">{{ employee.age ? employee.age + ' л.' : '-' }}</td>
                        <td class="pa-4 text-body-2 font-weight-medium">{{ employee.total_experience || '-' }}</td>
                        <td class="pa-4 text-center">
                            <v-btn
                                variant="text"
                                color="indigo"
                                size="small"
                                class="mr-1 hover-scale-btn"
                                @click="openViewDialog(employee)"
                            >
                                <Eye style="width: 16px; height: 16px;" />
                            </v-btn>

                            <v-btn
                                v-if="!$page.props.auth.user.roles.includes('Viewer')"
                                variant="text"
                                color="indigo"
                                size="small"
                                class="mr-1 hover-scale-btn"
                                title="Ротация"
                                @click="openRotationDialog(employee)"
                            >
                                <ArrowLeftRight style="width: 16px; height: 16px;" />
                            </v-btn>

                            <v-btn
                                v-if="!$page.props.auth.user.roles.includes('Viewer')"
                                variant="text"
                                color="primary"
                                size="small"
                                class="mr-1 hover-scale-btn"
                                @click="openEditDialog(employee)"
                            >
                                <Pencil style="width: 16px; height: 16px;" />
                            </v-btn>

                            <v-btn
                                v-if="!$page.props.auth.user.roles.includes('Viewer')"
                                variant="text"
                                color="error"
                                size="small"
                                class="hover-scale-btn"
                                @click="openDeleteDialog(employee)"
                            >
                                <Trash2 style="width: 16px; height: 16px;" />
                            </v-btn>
                        </td>
                    </tr>
                    <tr v-if="employees.data.length === 0">
                        <td colspan="9" class="text-center py-10 text-grey text-h6 font-weight-medium bg-surface">
                            <UserX class="h-10 w-10 text-grey-lighten-1 mx-auto mb-2 opacity-50" /><br>
                            Сотрудники не найдены.
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <!-- Pagination Wrapper -->
            <v-divider></v-divider>
            <div class="d-flex justify-space-between align-center pa-4 bg-surface">
                <div class="text-caption text-grey font-weight-bold">
                    Показано {{ employees.from || 0 }} - {{ employees.to || 0 }} из {{ employees.total || 0 }} сотрудников
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
                ></v-pagination>
            </div>
        </v-card>

        <!-- View Details Dialog -->
        <v-dialog v-model="viewDialog" max-width="850px">
            <v-card v-if="selectedEmployee" class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header -->
                <div style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); padding: 20px 28px;">
                    <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                            <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                                <User style="width: 22px; height: 22px; color: white;" />
                            </v-avatar>
                            <div class="ml-4">
                                <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Детали сотрудника</div>
                                <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ selectedEmployee.full_name }}</div>
                            </div>
                        </div>
                        <v-chip color="white" variant="flat" class="font-weight-bold" size="small" style="color: #4338ca;">{{ selectedEmployee.type }}</v-chip>
                    </div>
                </div>
                
                <v-card-text class="pa-6 overflow-y-auto" style="max-height: 72vh; background-color: #f8fafc;">
                    <!-- Section 1: Personal Data -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <User style="width: 16px; height: 16px;" />
                            </v-avatar>
                            Основные и личные данные
                        </div>
                        <v-row>
                            <v-col cols="12" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <User style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">ФИО</span>
                                        <span class="text-body-1 font-weight-black text-indigo-darken-4">{{ selectedEmployee.full_name }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Users style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Пол</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.gender || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Дата рождения / Возраст</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(selectedEmployee.birth_date) }} ({{ selectedEmployee.age ? selectedEmployee.age + ' лет' : '-' }})</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Globe style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Национальность</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.nationality || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Phone style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Телефон</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.phone_number || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="8" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <MapPin style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Адрес проживания</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.address || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <MapPin style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Место рождения</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.birth_place || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <BookOpen style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Образование</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.education || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Award style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Специальность</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.specialty || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card>

                    <!-- Section 2: Professional Data -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <Briefcase style="width: 16px; height: 16px;" />
                            </v-avatar>
                            Трудовая деятельность
                        </div>
                        <v-row>
                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Briefcase style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Должность</span>
                                        <span class="text-body-2 font-weight-black text-indigo-darken-3">{{ selectedEmployee.position?.name || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Network style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Подразделение</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.structure?.name || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <MapPin style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Филиал</span>
                                        <span class="text-body-2 font-weight-bold text-indigo-accent-3 font-weight-black">{{ selectedEmployee.branch?.name || 'Удаленный филиал' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Layers style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Категория</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.category?.name || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <UserCheck style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Руководитель</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.manager?.full_name || 'Нет' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-teal-lighten-5 text-teal d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Clock style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Общий стаж работы</span>
                                        <span class="text-body-2 font-weight-black text-teal-darken-3 font-weight-bold">{{ selectedEmployee.total_experience || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Дата приема</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(selectedEmployee.hire_date) }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-red-lighten-5 text-error d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Дата увольнения</span>
                                        <span class="text-body-2 font-weight-bold text-error font-weight-black">{{ formatDate(selectedEmployee.dismissal_date) }}</span>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card>

                    <!-- Section 3: Passport & Identifiers -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center text-subtitle-1 font-weight-black text-indigo-darken-4 mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <FileText style="width: 16px; height: 16px;" />
                            </v-avatar>
                            Паспортные данные и коды
                        </div>
                        <v-row>
                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <IdCard style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Номер паспорта</span>
                                        <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ selectedEmployee.passport_number || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Срок действия (С)</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(selectedEmployee.passport_start_date) }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <Calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Срок действия (По)</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ formatDate(selectedEmployee.passport_end_date) }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="4" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <FileText style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">ИНН / РМА</span>
                                        <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ selectedEmployee.inn || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="8" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <FileText style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">СИН (Код)</span>
                                        <span class="text-body-2 font-weight-bold font-mono text-slate-800">{{ selectedEmployee.sin || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>

                            <v-col cols="12" class="py-2">
                                <div class="info-field pa-3 rounded-lg border bg-slate-50 d-flex align-start h-100">
                                    <div class="mr-3 pa-2 rounded-lg bg-indigo-lighten-5 text-indigo d-flex align-center justify-center" style="width: 36px; height: 36px; min-width: 36px;">
                                        <UserCheck style="width: 16px; height: 16px;" />
                                    </div>
                                    <div>
                                        <span class="text-caption text-grey d-block font-weight-bold text-uppercase mb-0.5">Кем выдан паспорт</span>
                                        <span class="text-body-2 font-weight-bold text-slate-800">{{ selectedEmployee.passport_issued_by || '-' }}</span>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="px-6 py-4 bg-white d-flex justify-end">
                    <v-btn color="indigo" variant="flat" size="large" class="bg-indigo px-6 font-weight-bold" rounded="lg" style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);" @click="viewDialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
                <div class="glass-shine"></div>
            </v-card>
        </v-dialog>

        <!-- Create / Edit Dialog -->
        <v-dialog v-model="createEditDialog" max-width="850px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header -->
                <div style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); padding: 20px 28px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <UserPlus style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">{{ editingEmployee ? 'Редактирование' : 'Новый сотрудник' }}</div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ editingEmployee ? form.full_name || 'Редактировать сотрудника' : 'Добавить сотрудника' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Tabs for neat grouping -->
                <v-tabs v-model="activeTab" color="indigo" align-tabs="start" class="px-4 pt-2" show-arrows>
                    <v-tab :value="0">
                        <div class="d-flex align-center">
                            <User style="width: 16px; height: 16px; margin-right: 6px;" />
                            <span>Основное</span>
                        </div>
                    </v-tab>
                    <v-tab :value="1">
                        <div class="d-flex align-center">
                            <IdCard style="width: 16px; height: 16px; margin-right: 6px;" />
                            <span>Личные данные</span>
                        </div>
                    </v-tab>
                    <v-tab :value="2">
                        <div class="d-flex align-center">
                            <FileText style="width: 16px; height: 16px; margin-right: 6px;" />
                            <span>Документы и ИНН</span>
                        </div>
                    </v-tab>
                </v-tabs>

                <v-card-text class="px-6 pt-4 overflow-y-auto" style="max-height: 62vh;">
                    <v-form @submit.prevent="submit">
                        <v-window v-model="activeTab">
                            <!-- Tab 1: Основная трудовая информация -->
                            <v-window-item :value="0">
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
                                        <v-autocomplete
                                            v-model="form.position_id"
                                            :items="positions"
                                            item-title="name"
                                            item-value="id"
                                            label="Должность"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            required
                                            :error-messages="form.errors.position_id"
                                        ></v-autocomplete>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-autocomplete
                                            v-model="form.structure_id"
                                            :items="structures"
                                            item-title="name"
                                            item-value="id"
                                            label="Подразделение / Отдел (Сохтор)"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            required
                                            :error-messages="form.errors.structure_id"
                                        ></v-autocomplete>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.category_id"
                                            :items="categories"
                                            item-title="name"
                                            item-value="id"
                                            label="Категория"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            required
                                            :error-messages="form.errors.category_id"
                                        ></v-select>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.type_id"
                                            :items="types"
                                            item-title="name"
                                            item-value="id"
                                            label="Тип занятости"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            required
                                            :error-messages="form.errors.type_id"
                                        ></v-select>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-autocomplete
                                            v-model="form.manager_id"
                                            :items="managers"
                                            item-title="full_name"
                                            item-value="id"
                                            label="Непосредственный руководитель"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            clearable
                                            :error-messages="form.errors.manager_id"
                                        ></v-autocomplete>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.total_experience"
                                            label="Общий стаж работы"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.total_experience"
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
                                </v-row>
                            </v-window-item>

                            <!-- Tab 2: Личные данные -->
                            <v-window-item :value="1">
                                <v-row>
                                    <v-col cols="12" sm="4">
                                        <v-select
                                            v-model="form.gender"
                                            :items="['Мужской', 'Женский']"
                                            label="Пол"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            required
                                            :error-messages="form.errors.gender"
                                        ></v-select>
                                    </v-col>

                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.birth_date"
                                            label="Дата рождения"
                                            type="date"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.birth_date"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.nationality"
                                            label="Национальность"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.nationality"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.phone_number"
                                            label="Номер телефона"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.phone_number"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.birth_place"
                                            label="Место рождения"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.birth_place"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.education"
                                            label="Образование"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.education"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.specialty"
                                            label="Ихтисос (Специальность)"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.specialty"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="form.address"
                                            label="Адрес проживания"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.address"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-window-item>

                            <!-- Tab 3: Паспорт и Коды -->
                            <v-window-item :value="2">
                                <v-row>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.passport_number"
                                            label="Номер паспорта"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.passport_number"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.passport_start_date"
                                            label="Срок действия (С)"
                                            type="date"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.passport_start_date"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.passport_end_date"
                                            label="Срок действия (По)"
                                            type="date"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.passport_end_date"
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
                                            v-model="form.sin"
                                            label="СИН (Код)"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            :error-messages="form.errors.sin"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="form.passport_issued_by"
                                            label="Кем выдан паспорт (Мақоми шеносномадиҳанда)"
                                            variant="outlined"
                                            density="comfortable"
                                            rounded="lg"
                                            rows="2"
                                            :error-messages="form.errors.passport_issued_by"
                                        ></v-textarea>
                                    </v-col>
                                </v-row>
                            </v-window-item>
                        </v-window>
                    </v-form>
                </v-card-text>

                <v-card-actions class="px-2 pt-4">
                    <v-btn
                        v-if="activeTab > 0"
                        variant="tonal"
                        color="indigo"
                        rounded="lg"
                        @click="activeTab--"
                    >
                        Назад
                    </v-btn>
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
                        v-if="activeTab < 2"
                        color="indigo"
                        variant="flat"
                        class="bg-indigo px-5"
                        rounded="lg"
                        @click="activeTab++"
                    >
                        Далее
                    </v-btn>
                    <v-btn
                        v-else
                        color="indigo"
                        variant="flat"
                        class="bg-indigo px-5"
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
        <v-dialog v-model="deleteDialog" max-width="440px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Red Gradient Header -->
                <div style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <AlertCircle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Подтверждение</div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">Удаление сотрудника</div>
                        </div>
                    </div>
                </div>
                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    Вы уверены, что хотите удалить сотрудника <strong class="text-red-darken-2">{{ employeeToDelete?.full_name }}</strong>? Это действие необратимо.
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="deleteDialog = false"
                        :disabled="form.processing"
                        class="px-6 font-weight-bold"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        class="px-6 font-weight-bold"
                        @click="confirmDelete"
                        :loading="form.processing"
                        style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                    >
                        <template v-slot:prepend>
                            <Trash2 style="width: 18px; height: 18px;" />
                        </template>
                        Удалить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Rotation Dialog -->
        <v-dialog v-model="rotationDialog" max-width="620px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header -->
                <div style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); padding: 24px 28px;">
                    <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                            <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                                <ArrowLeftRight style="width: 22px; height: 22px; color: white;" />
                            </v-avatar>
                            <div class="ml-4">
                                <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Ротация кадров</div>
                                <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ selectedEmployee?.full_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-6 overflow-y-auto" style="max-height: 60vh;">
                    <v-form @submit.prevent="submitRotation">
                        <v-select
                            v-model="rotationForm.branch_id"
                            :items="branches"
                            item-title="name"
                            item-value="id"
                            label="Новый филиал"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="rotationForm.errors.branch_id"
                            class="mb-4"
                            :disabled="$page.props.auth.user.roles.includes('Branch Manager')"
                        ></v-select>

                        <v-autocomplete
                            v-model="rotationForm.position_id"
                            :items="positions"
                            item-title="name"
                            item-value="id"
                            label="Новая должность"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="rotationForm.errors.position_id"
                            class="mb-4"
                        ></v-autocomplete>

                        <v-autocomplete
                            v-model="rotationForm.structure_id"
                            :items="structures"
                            item-title="name"
                            item-value="id"
                            label="Новое подразделение / отдел (Сохтор)"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="rotationForm.errors.structure_id"
                            class="mb-4"
                        ></v-autocomplete>

                        <v-text-field
                            v-model="rotationForm.rotation_date"
                            label="Дата ротации"
                            type="date"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="rotationForm.errors.rotation_date"
                            class="mb-4"
                        ></v-text-field>

                        <v-textarea
                            v-model="rotationForm.reason"
                            label="Причина / Основание ротации"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="3"
                            :error-messages="rotationForm.errors.reason"
                        ></v-textarea>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="rotationDialog = false"
                        :disabled="rotationForm.processing"
                        class="px-6 font-weight-bold"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        @click="submitRotation"
                        :loading="rotationForm.processing"
                        class="px-6 font-weight-bold"
                        style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    >
                        <template v-slot:prepend>
                            <ArrowLeftRight style="width: 18px; height: 18px;" />
                        </template>
                        Выполнить ротацию
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.info-field {
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    background-color: #f8fafc !important;
    transition: all 0.2s ease-in-out;
}
.info-field:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
}
.v-theme--dark .info-field {
    border-color: rgba(51, 65, 85, 0.8) !important;
    background-color: #1e293b !important;
}
.v-theme--dark .info-field:hover {
    border-color: #475569 !important;
}
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.table-modern {
    border-radius: 12px;
}
.employee-row {
    transition: all 0.2s ease-in-out;
}
.employee-row:hover {
    background-color: rgba(79, 70, 229, 0.05) !important;
}
.transition-hover-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
.hover-scale-btn {
    transition: all 0.2s ease;
}
.hover-scale-btn:hover {
    transform: scale(1.15);
}
.font-mono {
    font-family: monospace, Courier, monospace;
}
.border-glow {
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}
.glass-shine {
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0) 100%);
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
}
</style>
