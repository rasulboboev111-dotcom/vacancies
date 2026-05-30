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
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        const date = new Date(dateStr);
        if (!isNaN(date.getTime())) {
            return date.toLocaleDateString('ru-RU');
        }
    }
    const date = new Date(dateStr);
    if (!isNaN(date.getTime()) && dateStr.toString().includes('-')) {
        return date.toLocaleDateString('ru-RU');
    }
    return dateStr;
}
</script>

<template>
    <Head title="Бойгонии кормандон" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Archive style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Бойгонии кормандони озодшуда</span>
            </div>
        </template>

        <!-- Main Card -->
        <v-card elevation="0" class="rounded-xl border pa-6 bg-surface-glass mb-6">
            <!-- Search & Filters -->
            <v-row class="mb-6 align-center">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="search"
                        prepend-inner-icon="mdi-magnify"
                        label="Ҷустуҷӯ аз рӯи ному насаб, ИНН ё вазифа..."
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        color="indigo"
                        class="search-field"
                    ></v-text-field>
                </v-col>
                <v-col cols="12" md="4" v-if="$page.props.auth.user.permissions.includes('view employees')">
                    <v-select
                        v-model="branchId"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        label="Филтр аз рӯи филиали пешина"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        hide-details
                        clearable
                        color="indigo"
                    ></v-select>
                </v-col>
                <v-col cols="12" md="2" class="d-flex justify-end">
                    <v-btn
                        variant="tonal"
                        color="error"
                        rounded="lg"
                        block
                        class="filter-reset-btn"
                        @click="resetFilters"
                    >
                        Бекор кардан
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Table -->
            <v-table class="table-modern border rounded-xl overflow-hidden">
                <thead>
                    <tr>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Ному насаби корманд</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Филиали пешина</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Вазифа</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Воҳид</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Категория</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Санаи қабул</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo">Санаи рафтан</th>
                        <th class="font-weight-black text-subtitle-2 pa-4 text-indigo text-center">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="employee in employees.data" :key="employee.id" class="employee-row">
                        <td class="pa-4 font-weight-bold text-indigo-darken-4">{{ employee.full_name }}</td>
                        <td class="pa-4 text-body-2">{{ employee.branch?.name || '-' }}</td>
                        <td class="pa-4 text-body-2 font-weight-medium text-slate-800">{{ employee.position?.name || '-' }}</td>
                        <td class="pa-4 text-body-2 text-slate-700">{{ employee.structure?.name || '-' }}</td>
                        <td class="pa-4"><v-chip size="small" color="secondary" variant="outlined">{{ employee.category?.name || '-' }}</v-chip></td>
                        <td class="pa-4 text-body-2 font-weight-medium">{{ formatDate(employee.hire_date) }}</td>
                        <td class="pa-4 text-body-2 font-weight-bold text-error">{{ formatDate(employee.dismissal_date) }}</td>
                        <td class="pa-4 text-center">
                            <v-btn
                                color="indigo"
                                icon
                                variant="text"
                                size="small"
                                @click="openViewDialog(employee)"
                                class="hover-scale-btn"
                            >
                                <Eye style="width: 18px; height: 18px;" />
                            </v-btn>
                        </td>
                    </tr>
                    <tr v-if="employees.data.length === 0">
                        <td colspan="8" class="text-center py-8 text-grey font-weight-medium">
                            Дар бойгонӣ кормандон ёфт нашуданд.
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <!-- Pagination -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center pa-2">
                <div class="text-caption text-grey font-weight-bold">
                    Нишон дода шуд {{ employees.from || 0 }} - {{ employees.to || 0 }} аз {{ employees.total || 0 }} корманди бойгонӣ
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

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" max-width="800px" scrollable>
            <v-card v-if="selectedEmployee" class="rounded-2xl border" style="overflow: hidden;">
                <!-- Header -->
                <div class="pa-6 text-white d-flex align-center justify-space-between" style="background: #009cf1">
                    <div class="d-flex align-center">
                        <v-avatar color="white" size="48" class="mr-4 shadow-sm">
                            <User style="width: 24px; height: 24px; color: #009cf1;" />
                        </v-avatar>
                        <div>
                            <div class="text-h6 font-weight-black">{{ selectedEmployee.full_name }}</div>
                            <div class="text-caption text-white opacity-85 mt-0.5 font-weight-bold text-uppercase">ИНН: {{ selectedEmployee.inn || '-' }}</div>
                        </div>
                    </div>
                    <v-chip color="error" variant="flat" class="font-weight-black text-uppercase shadow-sm">Озодшуда</v-chip>
                </div>

                <v-divider></v-divider>

                <!-- Body content -->
                <v-card-text class="pa-6 bg-slate-50" style="max-height: 60vh;">
                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <IdCard style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Маълумоти шахсӣ
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Ҷинс</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.gender_label || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи таваллуд</span>
                            <span class="text-body-1 font-weight-bold">{{ formatDate(selectedEmployee.birth_date) }} ({{ selectedEmployee.age ? selectedEmployee.age + ' сол' : '-' }})</span>
                        </v-col>

                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Миллат</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.nationality || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Рақами телефон</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.phone_number || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Зодгоҳ</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.birth_place || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Маълумот</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.education || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Ихтисос</span>
                            <span class="text-body-1 font-weight-bold text-indigo">{{ selectedEmployee.specialty || '-' }}</span>
                        </v-col>

                        <v-col cols="12" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Суроғаи истиқомат</span>
                            <span class="text-body-1 font-weight-bold">{{ selectedEmployee.address || '-' }}</span>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4"></v-divider>

                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <Briefcase style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> Маълумот дар бораи озодшавӣ
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи ба кор қабул</span>
                            <span class="text-body-1 font-weight-bold text-success">{{ formatDate(selectedEmployee.hire_date) }}</span>
                        </v-col>

                        <v-col cols="12" sm="6" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи рафтан (озодшавӣ/нафақа)</span>
                            <span class="text-body-1 font-weight-bold text-error font-weight-black">{{ formatDate(selectedEmployee.dismissal_date) }}</span>
                        </v-col>

                        <v-col cols="12" class="py-2" v-if="selectedEmployee.employment_start_date">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">Санаи ба кор қабул аз</span>
                            <span class="text-body-1 font-weight-bold text-teal">{{ formatDate(selectedEmployee.employment_start_date) }}</span>
                        </v-col>
                    </v-row>

                    <div class="text-h6 text-indigo font-weight-bold mb-3 d-flex align-center">
                        <Key style="width: 20px; height: 20px; margin-right: 8px;" class="text-indigo" /> ИНН ва ҳуҷҷатҳо
                    </div>
                    <v-row class="mb-4">
                        <v-col cols="12" sm="4" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">ИНН / РМА</span>
                            <span class="text-body-1 font-weight-bold font-mono">{{ selectedEmployee.inn || '-' }}</span>
                        </v-col>

                        <v-col cols="12" sm="8" class="py-2">
                            <span class="text-caption text-grey d-block font-weight-bold text-uppercase">СИН (Рамз)</span>
                            <span class="text-body-1 font-weight-bold font-mono">{{ selectedEmployee.sin || '-' }}</span>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-card-actions class="px-0 pt-6">
                    <v-spacer></v-spacer>
                    <v-btn color="indigo" variant="flat" class="bg-indigo px-5" rounded="lg" @click="viewDialog = false">
                        Пӯшидан
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
    background: transparent;
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
}
</style>
