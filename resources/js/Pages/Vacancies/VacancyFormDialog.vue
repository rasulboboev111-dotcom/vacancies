<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { DoorOpen } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { vacancySchema } from '@/lib/schemas';

const props = defineProps({
    vacancy: { type: Object, default: null }, // null → create
    isAdmin: { type: Boolean, default: false },
    branches: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    employmentTypes: { type: Array, default: () => [] },
    defaultBranchId: { type: Number, default: null },
    userBranchId: { type: Number, default: null },
    filterParams: { type: Object, default: () => ({}) },
});

const open = defineModel({ type: Boolean, default: false });

const branchOptions = computed(() =>
    props.branches.map(branch => ({
        id: Number(branch.id),
        title: branch.name,
    })),
);

const positionNames = computed(() => props.positions.map(p => p.name));

const FIELDS = [
    'branch_id',
    'department_id',
    'position',
    'title',
    'openings',
    'employment_type',
    'requirements',
    'schedule',
    'salary',
    'description',
    'opened_at',
    'status',
];

// vee-validate owns client-side validation; Inertia owns submit + server errors.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(vacancySchema),
    initialValues: { branch_id: null, department_id: null, position: null, title: '', openings: 1, employment_type: null, requirements: '', schedule: '', salary: '', description: '', opened_at: null, status: 'open' },
});
const [branchId, branchIdAttrs] = defineField('branch_id');
const [departmentId, departmentIdAttrs] = defineField('department_id');
const [position, positionAttrs] = defineField('position');
const [title, titleAttrs] = defineField('title');
const [openings, openingsAttrs] = defineField('openings');
const [employmentType, employmentTypeAttrs] = defineField('employment_type');
const [requirements, requirementsAttrs] = defineField('requirements');
const [schedule, scheduleAttrs] = defineField('schedule');
const [salary, salaryAttrs] = defineField('salary');
const [description, descriptionAttrs] = defineField('description');
const [openedAt, openedAtAttrs] = defineField('opened_at');
const [status, statusAttrs] = defineField('status');

const inertia = useInertiaForm(Object.fromEntries(FIELDS.map(f => [f, null])));

const formBranchId = computed(() => (props.isAdmin ? branchId.value : props.userBranchId));

const departmentOptions = computed(() =>
    props.departments.filter(d => Number(d.branch_id) === Number(formBranchId.value)),
);

// Drop a stale department when the (admin-changed) branch no longer owns it,
// so a cross-branch department id never reaches the backend.
watch(formBranchId, (bid) => {
    if (
        departmentId.value
        && !props.departments.some(
            d => Number(d.id) === Number(departmentId.value) && Number(d.branch_id) === Number(bid),
        )
    ) {
        departmentId.value = null;
    }
});

watch(open, (visible) => {
    if (!visible)
        return;
    const v = props.vacancy;
    resetForm({
        values: v
            ? {
                    branch_id: v.branch_id,
                    department_id: v.department_id,
                    position: v.position?.name ?? null,
                    title: v.title,
                    openings: v.openings ?? 1,
                    employment_type: v.employment_type,
                    requirements: v.requirements ?? '',
                    schedule: v.schedule ?? '',
                    salary: v.salary ?? '',
                    description: v.description ?? '',
                    opened_at: v.opened_at,
                    status: v.status,
                }
            : {
                    branch_id: props.isAdmin ? props.defaultBranchId : props.userBranchId,
                    department_id: null,
                    position: null,
                    title: '',
                    openings: 1,
                    employment_type: null,
                    requirements: '',
                    schedule: '',
                    salary: '',
                    description: '',
                    opened_at: null,
                    status: 'open',
                },
    });
});

const submit = handleSubmit((values) => {
    Object.assign(inertia, values);

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        for (const field of FIELDS) {
            if (serverErrors[field])
                setFieldError(field, serverErrors[field]);
        }
    };

    if (props.vacancy) {
        inertia.put(route('vacancies.update', { vacancy: props.vacancy.id, ...props.filterParams }), { onSuccess, onError });
        return;
    }
    inertia.post(route('vacancies.store', props.filterParams), { onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="680px" persistent scrollable>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15);">
                        <DoorOpen style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ vacancy ? 'Таҳрир' : 'Вакансияи нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ vacancy ? title || 'Вакансия' : 'Илова кардани вакансия' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6">
                <v-form class="app-form" @submit.prevent="submit">
                    <FormField v-if="isAdmin" label="Филиал" class="mb-4">
                        <v-select
                            v-model="branchId"
                            v-bind="branchIdAttrs"
                            :items="branchOptions"
                            item-title="title"
                            item-value="id"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.branch_id"
                        />
                    </FormField>

                    <FormField label="Вазифа / Номи вакансия" class="mb-4">
                        <v-text-field
                            v-model="title"
                            v-bind="titleAttrs"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.title"
                        />
                    </FormField>

                    <FormField label="Шумораи кормандони зарур" required class="mb-4">
                        <v-text-field
                            v-model.number="openings"
                            v-bind="openingsAttrs"
                            type="number"
                            min="1"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.openings"
                        />
                    </FormField>

                    <v-row dense>
                        <v-col cols="12" md="6">
                            <FormField label="Вазифа (интихоб ё вориди нав)" class="mb-4">
                                <v-combobox
                                    v-model="position"
                                    v-bind="positionAttrs"
                                    :items="positionNames"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    hide-details="auto"
                                    :error-messages="errors.position"
                                />
                            </FormField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <FormField label="Шуъба" class="mb-4">
                                <v-select
                                    v-model="departmentId"
                                    v-bind="departmentIdAttrs"
                                    :items="departmentOptions"
                                    item-title="name"
                                    item-value="id"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    hide-details="auto"
                                    :error-messages="errors.department_id"
                                />
                            </FormField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <FormField label="Намуди шуғл" class="mb-4">
                                <v-select
                                    v-model="employmentType"
                                    v-bind="employmentTypeAttrs"
                                    :items="employmentTypes"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    clearable
                                    hide-details="auto"
                                    :error-messages="errors.employment_type"
                                />
                            </FormField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <FormField label="Ҷадвали корӣ" class="mb-4">
                                <v-text-field
                                    v-model="schedule"
                                    v-bind="scheduleAttrs"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    :error-messages="errors.schedule"
                                />
                            </FormField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <FormField label="Маош" class="mb-4">
                                <v-text-field
                                    v-model="salary"
                                    v-bind="salaryAttrs"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    :error-messages="errors.salary"
                                />
                            </FormField>
                        </v-col>
                    </v-row>

                    <FormField label="Талабот ба номзад" class="mb-4">
                        <v-textarea
                            v-model="requirements"
                            v-bind="requirementsAttrs"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="3"
                            auto-grow
                            hide-details="auto"
                            :error-messages="errors.requirements"
                        />
                    </FormField>

                    <FormField label="Тавсиф / Иловагӣ" class="mb-4">
                        <v-textarea
                            v-model="description"
                            v-bind="descriptionAttrs"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            rows="2"
                            auto-grow
                            hide-details="auto"
                            :error-messages="errors.description"
                        />
                    </FormField>

                    <v-row dense>
                        <v-col cols="12" md="6">
                            <FormField label="Санаи кушодашавӣ">
                                <v-text-field
                                    v-model="openedAt"
                                    v-bind="openedAtAttrs"
                                    type="date"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    :error-messages="errors.opened_at"
                                />
                            </FormField>
                        </v-col>
                        <v-col v-if="vacancy" cols="12" md="6">
                            <FormField label="Ҳолат">
                                <v-select
                                    v-model="status"
                                    v-bind="statusAttrs"
                                    :items="[{ value: 'open', title: 'Кушода' }, { value: 'closed', title: 'Баста' }]"
                                    item-title="title"
                                    item-value="value"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                />
                            </FormField>
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5">
                <v-btn variant="text" rounded="lg" size="large" :disabled="inertia.processing" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-spacer />
                <v-btn color="indigo" variant="flat" rounded="lg" size="large" :loading="inertia.processing" class="px-6 font-weight-medium text-white bg-indigo" @click="submit">
                    Захира кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
