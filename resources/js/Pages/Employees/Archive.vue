<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    Archive, 
    Search, 
    FilterX, 
    Eye, 
    FolderOpen,
    User,
    Calendar,
    Phone,
    MapPin,
    Briefcase,
    Building2,
    Award,
    FileText,
    Key
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
    filters: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const branchId = ref(props.filters.branch_id || null);

const viewDialog = ref(false);
const selectedEmployee = ref(null);

watch([branchId], () => {
    applyFilters();
});

function applyFilters() {
    router.get(route('employees.archive'), {
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
    }, {
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

function changePage(page) {
    router.get(route('employees.archive'), {
        page: page,
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
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
    <Head title="Архив сотрудников" />

    <AuthenticatedLayout>
        <template #header>
                <Archive style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Архив сотрудников (Уволенные / Пенсионеры)</span>
        </template>

        <!-- Filters section -->
        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-center">
                <!-- Search bar -->
                <v-col cols="12" sm="5" md="4">
                    <v-text-field
                        v-model="search"
                        label="Поиск по ФИО, должности или ИНН"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        class="bg-surface"
                        @keyup.enter="applyFilters"
                    >
                        <template v-slot:prepend-inner>
                            <Search style="width: 16px; height: 16px; margin-right: 4px;" class="text-grey" />
                        </template>
                    </v-text-field>
                </v-col>

                <!-- Branch Filter -->
                <v-col cols="12" sm="4" md="3">
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
                        class="bg-surface"
                        :disabled="$page.props.auth.user.roles.includes('Branch Manager')"
                    ></v-select>
                </v-col>

                <v-spacer></v-spacer>

                <!-- Action buttons -->
                <v-col cols="12" sm="3" class="d-flex justify-end">
                    <v-btn
                        variant="tonal"
                        color="secondary"
                        rounded="lg"
                        class="px-4 transition-hover-btn w-100 font-weight-bold"
                        @click="resetFilters"
                    >
                        <template v-slot:prepend>
                            <FilterX style="width: 16px; height: 16px; margin-right: 4px;" />
                        </template>
                        Сбросить
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <!-- Archive Table -->
        <v-card elevation="0" class="rounded-xl border overflow-hidden bg-surface-glass">
            <v-table class="w-100 table-modern">
                <thead>
                    <tr class="bg-indigo-lighten-5">
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">ФИО</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Должность</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Филиал</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Категория</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Дата приема</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Дата ухода</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo text-center">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="employee in employees.data" :key="employee.id" class="employee-row">
                        <td class="pa-4 font-weight-bold text-indigo-darken-3">{{ employee.full_name }}</td>
                        <td class="pa-4 text-grey-darken-3 font-weight-medium">{{ employee.position }}</td>
                        <td class="pa-4">
                            <v-chip size="small" color="indigo" variant="flat" class="font-weight-bold">
                                {{ employee.branch.name }}
                            </v-chip>
                        </td>
                        <td class="pa-4"><v-chip size="small" color="secondary" variant="outlined">{{ employee.category }}</v-chip></td>
                        <td class="pa-4 text-body-2 font-weight-medium">{{ formatDate(employee.hire_date) }}</td>
                        <td class="pa-4 text-body-2 font-weight-bold text-error">{{ formatDate(employee.dismissal_date) }}</td>
                        <td class="pa-4 text-center">
                            <v-btn
                                icon
                                variant="text"
                                color="indigo"
                                size="small"
                                class="hover-scale-btn"
                                @click="openViewDialog(employee)"
                            >
                                <Eye style="width: 16px; height: 16px;" />
                            </v-btn>
                        </td>
                    </tr>
                    <tr v-if="employees.data.length === 0">
                        <td colspan="7" class="text-center py-10 text-grey text-h6 font-weight-medium bg-surface">
                            <FolderOpen style="width: 40px; height: 40px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                            Архивные сотрудники не найдены.
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <!-- Pagination Wrapper -->
            <v-divider></v-divider>
            <div class="d-flex justify-space-between align-center pa-4 bg-surface">
                <div class="text-caption text-grey font-weight-bold">
                    Показано {{ employees.from || 0 }} - {{ employees.to || 0 }} из {{ employees.total || 0 }} архивных сотрудников
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
        <v-dialog v-model="viewDialog" max-width="800px">
            <v-card v-if="selectedEmployee" class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header (Secondary/Error theme for Archive) -->
                <div style="background: linear-gradient(135deg, #475569 0%, #1e293b 100%); padding: 20px 28px;">
                    <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                            <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                                <FileText style="width: 22px; height: 22px; color: white;" />
                            </v-avatar>
                            <div class="ml-4">
                                <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Детали архивного дела</div>
                                <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ selectedEmployee.full_name }}</div>
                            </div>
                        </div>
                        <v-chip color="error" variant="flat" class="font-weight-bold" size="small">В АРХИВЕ</v-chip>
                    </div>
                </div>
                
                <v-card-text class="px-0 py-0 overflow-y-auto" style="max-height: 70vh;">
                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <User style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Основные и личные данные
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" class="pb-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">ФИО</span>
                            <span class="text-body-1 font-weight-black text-indigo-darken-4">{{ selectedEmployee.full_name }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Пол</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.gender || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Дата рождения / Возраст</span>
                            <span class="text-body-1 font-weight-bold">{{ formatDate(selectedEmployee.birth_date) }} ({{ selectedEmployee.age ? selectedEmployee.age + ' лет' : '-' }})</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Национальность</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.nationality || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Телефон</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.phone_number || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Адрес проживания</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.address || '-' }}</span>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Сведения об увольнении / выходе на пенсию
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Должность перед уходом</span>
                            <span class="text-body-1 font-weight-bold text-indigo-darken-3">{{ selectedEmployee.position }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Подразделение</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.structure }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Бывший филиал</span>
                            <span class="text-body-1 font-weight-bold text-primary">{{ selectedEmployee.branch.name }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Дата приема на работу</span>
                            <span class="text-body-1 font-weight-bold text-success">{{ formatDate(selectedEmployee.hire_date) }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Дата ухода (увольнения/пенсии)</span>
                            <span class="text-body-1 font-weight-bold text-error font-weight-black">{{ formatDate(selectedEmployee.dismissal_date) }}</span>
                        </v-col>

                        <v-col cols="12" class="py-2" v-if="selectedEmployee.total_experience">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Общий стаж работы</span>
                            <span class="text-body-1 font-weight-bold text-teal">{{ selectedEmployee.total_experience }}</span>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <Key style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> ИНН и документы
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">ИНН / РМА</span>
                            <span class="text-body-1 font-weight-bold font-mono">{{ selectedEmployee.inn || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">СИН (Код)</span>
                            <span class="text-body-1 font-weight-bold font-mono">{{ selectedEmployee.sin || '-' }}</span>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-card-actions class="px-0 pt-6">
                    <v-spacer></v-spacer>
                    <v-btn color="indigo" variant="flat" class="bg-indigo px-5" rounded="lg" @click="viewDialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
                <div class="glass-shine"></div>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
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
    background-color: rgba(239, 68, 68, 0.05) !important;
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
