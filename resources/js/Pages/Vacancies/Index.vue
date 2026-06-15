<script setup>
import { Head, router } from '@inertiajs/vue3';
import { DoorOpen, Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { firstError } from '@/lib/errors';
import { VACANCY_STATUS_CLOSED, VACANCY_STATUS_OPEN } from '@/lib/vacancy';
import VacancyDeleteDialog from '@/Pages/Vacancies/VacancyDeleteDialog.vue';
import VacancyFormDialog from '@/Pages/Vacancies/VacancyFormDialog.vue';
import VacancyTable from '@/Pages/Vacancies/VacancyTable.vue';
import VacancyViewDialog from '@/Pages/Vacancies/VacancyViewDialog.vue';

const props = defineProps({
    vacancies: { type: Object, required: true },
    branches: { type: Array, required: true },
    departments: { type: Array, required: true },
    positions: { type: Array, required: true },
    formOptions: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const { user: authUser, isAdmin, canManageInBranch, canCreateInBranch } = usePermissions();

const selectedBranchId = ref(props.filters.branch_id != null ? Number(props.filters.branch_id) : null);
const selectedStatus = ref(props.filters.status ?? null);
const toggling = ref(false);
const toggleError = ref('');

const statusOptions = [
    { value: null, title: 'Ҳамаи ҳолатҳо' },
    { value: VACANCY_STATUS_OPEN, title: 'Вакансияҳои кушода' },
    { value: VACANCY_STATUS_CLOSED, title: 'Вакансияҳои баста' },
];

const branchOptions = computed(() =>
    props.branches.map(branch => ({
        id: Number(branch.id),
        title: branch.name,
    })),
);

const canCreate = computed(() => canCreateInBranch('create vacancies'));
const canManage = vacancy => canManageInBranch('edit vacancies', vacancy.branch_id);
const canDelete = vacancy => canManageInBranch('delete vacancies', vacancy.branch_id);

function reload() {
    router.get(route('vacancies.index'), {
        filter: {
            branch_id: isAdmin.value ? selectedBranchId.value || undefined : undefined,
            status: selectedStatus.value || undefined,
        },
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Переход на другую страницу с сохранением активных фильтров.
function changePage(page) {
    router.get(route('vacancies.index'), {
        page,
        filter: {
            branch_id: isAdmin.value ? selectedBranchId.value || undefined : undefined,
            status: selectedStatus.value || undefined,
        },
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Текущие фильтры списка передаются изменяющим действиям, чтобы редирект назад
// к списку сохранял тот же вид, а не перескакивал на филиал вакансии.
const filterParams = computed(() => {
    const params = {};
    if (isAdmin.value && selectedBranchId.value) {
        params.filter_branch_id = selectedBranchId.value;
    }
    if (selectedStatus.value) {
        params.filter_status = selectedStatus.value;
    }
    return params;
});

watch(selectedBranchId, reload);
watch(selectedStatus, reload);

const dialog = ref(false);
const deleteDialog = ref(false);
const deleting = ref(false);
const editing = ref(null);
const vacancyToDelete = ref(null);

const viewDialog = ref(false);
const viewVacancy = ref(null);

function openViewDialog(vacancy) {
    viewVacancy.value = vacancy;
    viewDialog.value = true;
}

function openCreateDialog() {
    editing.value = null;
    dialog.value = true;
}

function openEditDialog(vacancy) {
    editing.value = vacancy;
    dialog.value = true;
}

function toggleStatus(vacancy) {
    // Защита от быстрого двойного клика, порождающего два конкурирующих PUT
    // (open→closed, затем closed→open), которые в сумме дают no-op, но сталкиваются на лету.
    if (toggling.value) {
        return;
    }
    toggling.value = true;
    toggleError.value = '';
    // Частичное обновление: отсутствующие поля не доходят до validated(), поэтому
    // переключение не затирает остальную часть формы.
    router.put(route('vacancies.update', { id: vacancy.id, ...filterParams.value }), {
        status: vacancy.status === VACANCY_STATUS_OPEN ? VACANCY_STATUS_CLOSED : VACANCY_STATUS_OPEN,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            // Строка сохраняет реальный статус (Inertia сохраняет состояние при ошибке);
            // сообщаем пользователю о неудаче, вместо того чтобы молча провалиться.
            toggleError.value = firstError(errors, 'Ҳолати вакансия иваз нашуд. Бори дигар кӯшиш кунед.');
        },
        onFinish: () => { toggling.value = false; },
    });
}

// Страница печати — отдельное Blade-представление, стилизованное 1:1 под официальную
// docx-форму заявки; открываем в отдельной вкладке для печати / сохранения в PDF.
function printVacancy(vacancy) {
    window.open(route('vacancies.print', { id: vacancy.id }), '_blank');
}

function openDeleteDialog(vacancy) {
    vacancyToDelete.value = vacancy;
    deleteDialog.value = true;
}

function confirmDelete() {
    if (!vacancyToDelete.value)
        return;
    router.delete(route('vacancies.destroy', { id: vacancyToDelete.value.id, ...filterParams.value }), {
        preserveScroll: true,
        onStart: () => { deleting.value = true; },
        onFinish: () => { deleting.value = false; },
        onSuccess: () => {
            deleteDialog.value = false;
            vacancyToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Вакансияҳо" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <DoorOpen style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Идоракунии вакансияҳо</span>
            </div>
        </template>

        <v-alert v-if="toggleError" type="error" variant="tonal" closable class="mb-4 rounded-lg font-weight-bold" @click:close="toggleError = ''">
            {{ toggleError }}
        </v-alert>

        <v-card elevation="0" class="rounded-xl border pa-5 bg-surface-glass mb-6">
            <v-row class="align-end">
                <v-col v-if="isAdmin" cols="12" sm="6" md="4">
                    <label class="filter-label">Филиал</label>
                    <v-select
                        v-model="selectedBranchId"
                        :items="[{ id: null, title: 'Ҳамаи филиалҳо' }, ...branchOptions]"
                        item-title="title"
                        item-value="id"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        class="premium-field"
                    />
                </v-col>
                <v-col cols="12" sm="6" md="3">
                    <label class="filter-label">Ҳолат</label>
                    <v-select
                        v-model="selectedStatus"
                        :items="statusOptions"
                        item-title="title"
                        item-value="value"
                        variant="solo"
                        density="comfortable"
                        rounded="lg"
                        flat
                        hide-details
                        class="premium-field"
                    />
                </v-col>
                <v-col cols="12" :md="isAdmin ? 5 : 9" class="d-flex align-center justify-md-end justify-center gap-2">
                    <v-btn
                        v-if="canCreate"
                        variant="flat"
                        rounded="lg"
                        class="px-5 transition-hover-btn font-weight-bold text-white"
                        style="background: #009cf1 !important; color: #ffffff !important; box-shadow: 0 4px 14px -4px rgba(0, 156, 241, 0.45) !important;"
                        @click="openCreateDialog()"
                    >
                        <template #prepend>
                            <Plus style="width: 18px; height: 18px; color: #ffffff;" />
                        </template>
                        Илова кардани вакансия
                    </v-btn>
                </v-col>
            </v-row>
        </v-card>

        <VacancyTable
            :vacancies="vacancies.data"
            :is-admin="isAdmin"
            :can-manage="canManage"
            :can-delete="canDelete"
            @view="openViewDialog"
            @edit="openEditDialog"
            @delete="openDeleteDialog"
            @toggle="toggleStatus"
            @print="printVacancy"
        />

        <div v-if="vacancies.data.length > 0" class="d-flex align-center justify-space-between flex-wrap gap-3 mt-4 pt-3" style="border-top: 1px solid #e9edf3;">
            <div class="text-caption font-weight-bold" style="color: #94a3b8;">
                Нишон дода шуд {{ vacancies.from || 0 }} – {{ vacancies.to || 0 }} аз {{ vacancies.total || 0 }} вакансия
            </div>
            <v-pagination
                v-if="vacancies.last_page > 1"
                :model-value="vacancies.current_page"
                :length="vacancies.last_page"
                :total-visible="5"
                density="comfortable"
                rounded="lg"
                active-color="indigo"
                @update:model-value="changePage"
            />
        </div>

        <VacancyFormDialog
            v-model="dialog"
            :vacancy="editing"
            :is-admin="isAdmin"
            :branches="branches"
            :departments="departments"
            :positions="positions"
            :form-options="formOptions"
            :default-branch-id="selectedBranchId"
            :user-branch-id="authUser?.branch_id ?? null"
            :filter-params="filterParams"
        />

        <VacancyDeleteDialog v-model="deleteDialog" :vacancy="vacancyToDelete" :processing="deleting" @confirm="confirmDelete" />

        <VacancyViewDialog v-model="viewDialog" :vacancy="viewVacancy" :is-admin="isAdmin" />
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
</style>
