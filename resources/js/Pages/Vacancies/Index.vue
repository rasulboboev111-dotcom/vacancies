<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    DoorOpen,
    Plus,
    Pencil,
    Trash2,
    AlertTriangle,
    Lock,
    Unlock,
} from '@lucide/vue';

const props = defineProps({
    vacancies: { type: Array, required: true },
    branches: { type: Array, required: true },
    departments: { type: Array, required: true },
    positions: { type: Array, required: true },
    structures: { type: Array, required: true },
    employmentTypes: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const isAdmin = computed(() => authUser.value?.roles?.includes('Admin') ?? false);

const selectedBranchId = ref(props.filters.branch_id != null ? Number(props.filters.branch_id) : null);
const selectedStatus = ref(props.filters.status ?? null);

const statusOptions = [
    { value: null, title: 'Ҳамаи ҳолатҳо' },
    { value: 'open', title: 'Вакансияҳои кушода' },
    { value: 'closed', title: 'Вакансияҳои баста' },
];

const branchOptions = computed(() =>
    props.branches.map((branch) => ({
        id: Number(branch.id),
        title: branch.code ? `${branch.name} (${branch.code})` : branch.name,
    })),
);

const canCreate = computed(() => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    return user.permissions?.includes('create vacancies') && user.branch_id != null;
});

const canManage = (vacancy) => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    if (!user.permissions?.includes('edit vacancies')) return false;
    return Number(vacancy.branch_id) === Number(user.branch_id);
};

const canDelete = (vacancy) => {
    const user = authUser.value;
    if (!user) return false;
    if (isAdmin.value) return true;
    if (!user.permissions?.includes('delete vacancies')) return false;
    return Number(vacancy.branch_id) === Number(user.branch_id);
};

function reload() {
    router.get(route('vacancies.index'), {
        branch_id: isAdmin.value ? selectedBranchId.value : undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(selectedBranchId, reload);
watch(selectedStatus, reload);

const dialog = ref(false);
const deleteDialog = ref(false);
const editing = ref(null);
const vacancyToDelete = ref(null);

const form = useForm({
    branch_id: null,
    department_id: null,
    position_id: null,
    structure_id: null,
    title: '',
    employment_type: null,
    requirements: '',
    schedule: '',
    salary: '',
    description: '',
    opened_at: null,
    status: 'open',
});

const formBranchId = computed(() => (isAdmin.value ? form.branch_id : authUser.value?.branch_id));

const departmentOptions = computed(() =>
    props.departments.filter((d) => Number(d.branch_id) === Number(formBranchId.value)),
);

function openCreateDialog() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.branch_id = isAdmin.value ? selectedBranchId.value : authUser.value?.branch_id;
    form.status = 'open';
    dialog.value = true;
}

function openEditDialog(vacancy) {
    editing.value = vacancy;
    form.clearErrors();
    form.branch_id = vacancy.branch_id;
    form.department_id = vacancy.department_id;
    form.position_id = vacancy.position_id;
    form.structure_id = vacancy.structure_id;
    form.title = vacancy.title;
    form.employment_type = vacancy.employment_type;
    form.requirements = vacancy.requirements || '';
    form.schedule = vacancy.schedule || '';
    form.salary = vacancy.salary || '';
    form.description = vacancy.description || '';
    form.opened_at = vacancy.opened_at;
    form.status = vacancy.status;
    dialog.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('vacancies.update', editing.value.id), {
            onSuccess: () => { dialog.value = false; form.reset(); },
        });
        return;
    }
    form.post(route('vacancies.store'), {
        onSuccess: () => { dialog.value = false; form.reset(); },
    });
}

function toggleStatus(vacancy) {
    router.put(route('vacancies.update', vacancy.id), {
        branch_id: vacancy.branch_id,
        department_id: vacancy.department_id,
        position_id: vacancy.position_id,
        structure_id: vacancy.structure_id,
        title: vacancy.title,
        employment_type: vacancy.employment_type,
        requirements: vacancy.requirements,
        schedule: vacancy.schedule,
        salary: vacancy.salary,
        description: vacancy.description,
        opened_at: vacancy.opened_at,
        status: vacancy.status === 'open' ? 'closed' : 'open',
    }, { preserveScroll: true });
}

function openDeleteDialog(vacancy) {
    vacancyToDelete.value = vacancy;
    deleteDialog.value = true;
}

function confirmDelete() {
    if (!vacancyToDelete.value) return;
    router.delete(route('vacancies.destroy', vacancyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteDialog.value = false; vacancyToDelete.value = null; },
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

        <v-row class="mb-6 align-center">
            <v-col v-if="isAdmin" cols="12" md="4">
                <v-select
                    v-model="selectedBranchId"
                    :items="[{ id: null, title: 'Ҳамаи филиалҳо' }, ...branchOptions]"
                    item-title="title"
                    item-value="id"
                    label="Филиал"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                />
            </v-col>
            <v-col cols="12" md="3">
                <v-select
                    v-model="selectedStatus"
                    :items="statusOptions"
                    item-title="title"
                    item-value="value"
                    label="Ҳолат"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                />
            </v-col>
            <v-col cols="12" :md="isAdmin ? 5 : 9" class="d-flex justify-end">
                <v-btn
                    v-if="canCreate"
                    color="indigo"
                    rounded="lg"
                    elevation="2"
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                    @click="openCreateDialog()"
                >
                    <template v-slot:prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                    </template>
                    Илова кардани вакансия
                </v-btn>
            </v-col>
        </v-row>

        <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
            <v-table v-if="vacancies.length > 0" hover>
                <thead>
                    <tr>
                        <th class="font-weight-bold text-grey-darken-3">Вазифа / Ном</th>
                        <th class="font-weight-bold text-grey-darken-3">Сохтор / Шуъба</th>
                        <th v-if="isAdmin" class="font-weight-bold text-grey-darken-3">Филиал</th>
                        <th class="font-weight-bold text-grey-darken-3">Ҷадвали корӣ</th>
                        <th class="font-weight-bold text-grey-darken-3">Маош</th>
                        <th class="font-weight-bold text-grey-darken-3">Ҳолат</th>
                        <th class="font-weight-bold text-grey-darken-3 text-right">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="vacancy in vacancies" :key="vacancy.id">
                        <td class="py-3">
                            <div class="font-weight-bold text-grey-darken-4">{{ vacancy.title }}</div>
                            <div v-if="vacancy.position" class="text-caption text-grey">{{ vacancy.position.name }}</div>
                            <v-chip v-if="vacancy.employment_type" size="x-small" color="indigo" variant="tonal" class="mt-1 font-weight-bold">
                                {{ vacancy.employment_type }}
                            </v-chip>
                        </td>
                        <td>
                            <div class="text-body-2 text-grey-darken-3">{{ vacancy.structure?.name || '—' }}</div>
                            <div class="text-caption text-grey">{{ vacancy.department?.name || '' }}</div>
                        </td>
                        <td v-if="isAdmin">
                            <span class="text-body-2">{{ vacancy.branch?.name || '—' }}</span>
                        </td>
                        <td class="text-body-2 text-grey-darken-2">{{ vacancy.schedule || '—' }}</td>
                        <td class="text-body-2 text-grey-darken-2">{{ vacancy.salary || '—' }}</td>
                        <td>
                            <v-chip
                                size="small"
                                :color="vacancy.status === 'open' ? 'amber-darken-2' : 'grey'"
                                variant="tonal"
                                class="font-weight-bold text-uppercase"
                            >
                                {{ vacancy.status === 'open' ? 'Кушода' : 'Баста' }}
                            </v-chip>
                        </td>
                        <td class="text-right">
                            <v-btn
                                v-if="canManage(vacancy)"
                                icon
                                variant="text"
                                size="small"
                                :title="vacancy.status === 'open' ? 'Бастани вакансия' : 'Кушодани вакансия'"
                                @click="toggleStatus(vacancy)"
                            >
                                <Lock v-if="vacancy.status === 'open'" style="width: 18px; height: 18px;" class="text-grey-darken-1" />
                                <Unlock v-else style="width: 18px; height: 18px;" class="text-success" />
                            </v-btn>
                            <v-btn
                                v-if="canManage(vacancy)"
                                icon
                                variant="text"
                                size="small"
                                title="Таҳрир"
                                @click="openEditDialog(vacancy)"
                            >
                                <Pencil style="width: 18px; height: 18px;" class="text-indigo" />
                            </v-btn>
                            <v-btn
                                v-if="canDelete(vacancy)"
                                icon
                                variant="text"
                                size="small"
                                title="Нест кардан"
                                @click="openDeleteDialog(vacancy)"
                            >
                                <Trash2 style="width: 18px; height: 18px;" class="text-error" />
                            </v-btn>
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <div v-else class="text-center py-12">
                <DoorOpen style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                <div class="text-h6 text-grey font-weight-medium">Вакансияҳо ёфт нашуданд.</div>
            </div>
        </v-card>

        <!-- Create / Edit dialog -->
        <v-dialog v-model="dialog" max-width="680px" persistent scrollable>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #0f2d88; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15);">
                            <DoorOpen style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                {{ editing ? 'Таҳрир' : 'Вакансияи нав' }}
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ editing ? form.title || 'Вакансия' : 'Илова кардани вакансия' }}
                            </div>
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submit">
                        <v-select
                            v-if="isAdmin"
                            v-model="form.branch_id"
                            :items="branchOptions"
                            item-title="title"
                            item-value="id"
                            label="Филиал"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            :error-messages="form.errors.branch_id"
                            class="mb-4"

                        />

                        <v-text-field
                            v-model="form.title"
                            label="Вазифа / Номи вакансия"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.title"
                            class="mb-4"
                        />

                        <v-row dense>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.position_id"
                                    :items="positions"
                                    item-title="name"
                                    item-value="id"
                                    label="Вазифа (маълумотнома)"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    :error-messages="form.errors.position_id"
                                    class="mb-4"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.structure_id"
                                    :items="structures"
                                    item-title="name"
                                    item-value="id"
                                    label="Сохтор"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    :error-messages="form.errors.structure_id"
                                    class="mb-4"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.department_id"
                                    :items="departmentOptions"
                                    item-title="name"
                                    item-value="id"
                                    label="Шуъба"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    :error-messages="form.errors.department_id"
                                    class="mb-4"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.employment_type"
                                    :items="employmentTypes"
                                    item-title="label"
                                    item-value="value"
                                    label="Намуди шуғл"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    :error-messages="form.errors.employment_type"
                                    class="mb-4"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.schedule"
                                    label="Ҷадвали корӣ"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.schedule"
                                    class="mb-4"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.salary"
                                    label="Маош"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.salary"
                                    class="mb-4"
                                />
                            </v-col>
                        </v-row>

                        <v-textarea
                            v-model="form.requirements"
                            label="Талабот ба номзад"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="3"
                            auto-grow
                            :error-messages="form.errors.requirements"
                            class="mb-4"
                        />

                        <v-textarea
                            v-model="form.description"
                            label="Тавсиф / Иловагӣ"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="2"
                            auto-grow
                            :error-messages="form.errors.description"
                            class="mb-4"
                        />

                        <v-row dense>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.opened_at"
                                    label="Санаи кушодашавӣ"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    :error-messages="form.errors.opened_at"
                                />
                            </v-col>
                            <v-col v-if="editing" cols="12" md="6">
                                <v-select
                                    v-model="form.status"
                                    :items="[{ value: 'open', title: 'Кушода' }, { value: 'closed', title: 'Баста' }]"
                                    item-title="title"
                                    item-value="value"
                                    label="Ҳолат"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                />
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn variant="tonal" color="grey" rounded="lg" size="large" :disabled="form.processing" class="px-6 font-weight-bold" @click="dialog = false">
                        Бекор кардан
                    </v-btn>
                    <v-btn color="indigo" variant="flat" rounded="lg" size="large" :loading="form.processing" class="px-6 font-weight-bold text-white" @click="submit">
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete dialog -->
        <v-dialog v-model="deleteDialog" max-width="460px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <div style="background: #dc2626; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15);">
                            <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Тасдиқ</div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">Нест кардани вакансия</div>
                        </div>
                    </div>
                </div>
                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    Шумо мутмаин ҳастед, ки мехоҳед вакансияи <strong class="text-red-darken-2">{{ vacancyToDelete?.title }}</strong>-ро нест кунед?
                </v-card-text>
                <v-divider />
                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn variant="tonal" color="grey" rounded="lg" size="large" class="px-6 font-weight-bold" @click="deleteDialog = false">
                        Бекор кардан
                    </v-btn>
                    <v-btn color="error" variant="flat" rounded="lg" size="large" class="px-6 font-weight-bold" @click="confirmDelete">
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
.transition-hover-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
</style>
