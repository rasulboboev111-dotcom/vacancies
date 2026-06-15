<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { BadgeCheck, Briefcase, ClipboardList, Clock, FileText, Flag, ListChecks } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, watch } from 'vue';
import ChoiceBoxGroup from '@/Components/ChoiceBoxGroup.vue';
import DialogHeader from '@/Components/DialogHeader.vue';
import FormField from '@/Components/FormField.vue';
import { useBranchDepartments } from '@/composables/useBranchDepartments';
import { applyServerErrors } from '@/lib/errors';
import { vacancySchema } from '@/lib/schemas';
import { mergeLanguages, PROBATION_OTHER, SCHEDULE_OTHER, splitLanguages, VACANCY_STATUS_CLOSED, VACANCY_STATUS_OPEN } from '@/lib/vacancy';

const props = defineProps({
    vacancy: { type: Object, default: null }, // null → создание
    isAdmin: { type: Boolean, default: false },
    branches: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    formOptions: { type: Object, default: () => ({}) },
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

const knownLanguages = computed(() => props.formOptions.knownLanguages ?? []);
const languageOptions = computed(() => knownLanguages.value.map(name => ({ value: name, label: name })));

const EMPTY_VALUES = {
    branch_id: null,
    department_id: null,
    position: null,
    location: '',
    openings: 1,
    supervisor: '',
    education: null,
    experience: null,
    languages: [],
    language_other: '',
    skills: '',
    requirements: '',
    responsibilities: '',
    employment_type: null,
    schedule_type: null,
    schedule_other: '',
    work_format: null,
    salary: '',
    probation: null,
    probation_other: '',
    opening_reason: null,
    priority: null,
    opened_at: null,
    deadline: null,
    status: VACANCY_STATUS_OPEN,
};

// Поля, проверяемые сервером; используются для проброса серверных ошибок обратно в форму.
// `language_other` — только клиентское, перед отправкой сливается в `languages`.
const FIELDS = Object.keys(EMPTY_VALUES).filter(field => field !== 'language_other');

// vee-validate отвечает за клиентскую валидацию; Inertia — за отправку и серверные ошибки.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(vacancySchema),
    initialValues: { ...EMPTY_VALUES },
});

const [branchId, branchIdAttrs] = defineField('branch_id');
const [departmentId, departmentIdAttrs] = defineField('department_id');
const [position, positionAttrs] = defineField('position');
const [location, locationAttrs] = defineField('location');
const [openings, openingsAttrs] = defineField('openings');
const [supervisor, supervisorAttrs] = defineField('supervisor');
const [education] = defineField('education');
const [experience] = defineField('experience');
const [languages] = defineField('languages');
const [languageOther, languageOtherAttrs] = defineField('language_other');
const [skills, skillsAttrs] = defineField('skills');
const [requirements, requirementsAttrs] = defineField('requirements');
const [responsibilities, responsibilitiesAttrs] = defineField('responsibilities');
const [employmentType] = defineField('employment_type');
const [scheduleType] = defineField('schedule_type');
const [scheduleOther, scheduleOtherAttrs] = defineField('schedule_other');
const [workFormat] = defineField('work_format');
const [salary, salaryAttrs] = defineField('salary');
const [probation] = defineField('probation');
const [probationOther, probationOtherAttrs] = defineField('probation_other');
const [openingReason] = defineField('opening_reason');
const [priority] = defineField('priority');
const [openedAt, openedAtAttrs] = defineField('opened_at');
const [deadline, deadlineAttrs] = defineField('deadline');
const [status, statusAttrs] = defineField('status');

const inertia = useInertiaForm(Object.fromEntries(FIELDS.map(f => [f, null])));

const formBranchId = computed(() => (props.isAdmin ? branchId.value : props.userBranchId));

// Сбрасываем устаревший отдел, когда (изменённый админом) филиал им больше не
// владеет, чтобы id отдела из чужого филиала не попал на backend.
const { branchDepartments: departmentOptions } = useBranchDepartments({
    getBranchId: () => formBranchId.value,
    getDepartmentId: () => departmentId.value,
    setDepartmentId: (value) => { departmentId.value = value; },
    getDepartments: () => props.departments,
});

watch(open, (visible) => {
    if (!visible)
        return;
    const v = props.vacancy;
    if (!v) {
        resetForm({
            values: {
                ...EMPTY_VALUES,
                branch_id: props.isAdmin ? props.defaultBranchId : props.userBranchId,
            },
        });
        return;
    }
    // Язык «Другой» — всё, что вакансия хранит сверх печатных чекбоксов формы
    // (целиком, через запятую — а не только первый).
    const { selected: selectedLanguages, other: otherLanguages } = splitLanguages(v.languages, knownLanguages.value);
    resetForm({
        values: {
            branch_id: v.branch_id,
            department_id: v.department_id,
            position: v.position?.name ?? null,
            location: v.location ?? '',
            openings: v.openings ?? 1,
            supervisor: v.supervisor ?? '',
            education: v.education,
            experience: v.experience,
            languages: selectedLanguages,
            language_other: otherLanguages,
            skills: v.skills ?? '',
            requirements: v.requirements ?? '',
            responsibilities: v.responsibilities ?? '',
            employment_type: v.employment_type,
            schedule_type: v.schedule_type,
            schedule_other: v.schedule_other ?? '',
            work_format: v.work_format,
            salary: v.salary ?? '',
            probation: v.probation,
            probation_other: v.probation_other ?? '',
            opening_reason: v.opening_reason,
            priority: v.priority,
            opened_at: v.opened_at,
            deadline: v.deadline,
            status: v.status,
        },
    });
});

const submit = handleSubmit((values) => {
    const { language_other: otherLanguage, ...payload } = values;
    payload.languages = mergeLanguages(values.languages, otherLanguage);

    Object.assign(inertia, payload);

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        applyServerErrors(serverErrors, FIELDS, setFieldError);
    };

    if (props.vacancy) {
        inertia.put(route('vacancies.update', { id: props.vacancy.id, ...props.filterParams }), { onSuccess, onError });
        return;
    }
    inertia.post(route('vacancies.store', props.filterParams), { onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="780px" persistent scrollable>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <DialogHeader
                :kicker="vacancy ? 'Редактирование' : 'Новая заявка'"
                title="Заявка на подбор персонала"
                subtitle="описание вакансии: заполняется руководителем подразделения"
            >
                <template #icon>
                    <ClipboardList style="width: 22px; height: 22px; color: white;" />
                </template>
            </DialogHeader>

            <v-card-text class="pa-0" style="background: #f8fafc;">
                <v-form class="app-form pa-5 pt-4" @submit.prevent="submit">
                    <!-- 1. Информация о вакансии -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <Briefcase style="width: 16px; height: 16px;" />
                            </v-avatar>
                            1. Информация о вакансии
                        </div>
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

                        <v-row density="comfortable">
                            <v-col cols="12" md="6">
                                <FormField label="Должность (позиция)" class="mb-4">
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
                                <FormField label="Структурное подразделение" class="mb-4">
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
                                <FormField label="Место деятельности / локация" class="mb-4">
                                    <v-text-field
                                        v-model="location"
                                        v-bind="locationAttrs"
                                        variant="outlined"
                                        density="comfortable"
                                        rounded="lg"
                                        hide-details="auto"
                                        :error-messages="errors.location"
                                    />
                                </FormField>
                            </v-col>
                            <v-col cols="12" md="6">
                                <FormField label="Количество вакансий" required class="mb-4">
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
                            </v-col>
                        </v-row>

                        <FormField label="Непосредственный руководитель" class="mb-1">
                            <v-text-field
                                v-model="supervisor"
                                v-bind="supervisorAttrs"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                hide-details="auto"
                                :error-messages="errors.supervisor"
                            />
                        </FormField>
                    </v-card>

                    <!-- 2. Требования к кандидату -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <FileText style="width: 16px; height: 16px;" />
                            </v-avatar>
                            2. Требования к кандидату
                        </div>
                        <FormField label="Образование" class="mb-4">
                            <ChoiceBoxGroup v-model="education" :options="formOptions.educations ?? []" />
                        </FormField>

                        <FormField label="Опыт работы" class="mb-4">
                            <ChoiceBoxGroup v-model="experience" :options="formOptions.experiences ?? []" />
                        </FormField>

                        <FormField label="Знание языков" class="mb-4">
                            <div class="d-flex flex-wrap align-center" style="gap: 6px 18px;">
                                <ChoiceBoxGroup v-model="languages" :options="languageOptions" multiple />
                                <v-text-field
                                    v-model="languageOther"
                                    v-bind="languageOtherAttrs"
                                    placeholder="через запятую"
                                    variant="outlined"
                                    density="compact"
                                    rounded="lg"
                                    hide-details="auto"
                                    style="max-width: 200px;"
                                    :error-messages="errors.language_other"
                                />
                            </div>
                        </FormField>

                        <FormField label="Ключевые навыки и знание программ" class="mb-4">
                            <v-textarea
                                v-model="skills"
                                v-bind="skillsAttrs"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                rows="2"
                                auto-grow
                                hide-details="auto"
                                :error-messages="errors.skills"
                            />
                        </FormField>

                        <FormField label="Дополнительные требования к кандидату" class="mb-1">
                            <v-textarea
                                v-model="requirements"
                                v-bind="requirementsAttrs"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                rows="2"
                                auto-grow
                                hide-details="auto"
                                :error-messages="errors.requirements"
                            />
                        </FormField>
                    </v-card>

                    <!-- 3. Должностные обязанности -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <ListChecks style="width: 16px; height: 16px;" />
                            </v-avatar>
                            3. Должностные обязанности
                        </div>
                        <FormField label="Основные обязанности" class="mb-1">
                            <v-textarea
                                v-model="responsibilities"
                                v-bind="responsibilitiesAttrs"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                rows="3"
                                auto-grow
                                hide-details="auto"
                                :error-messages="errors.responsibilities"
                            />
                        </FormField>
                    </v-card>

                    <!-- 4. Условия работы -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <Clock style="width: 16px; height: 16px;" />
                            </v-avatar>
                            4. Условия работы
                        </div>
                        <FormField label="Тип занятости" class="mb-4">
                            <ChoiceBoxGroup v-model="employmentType" :options="formOptions.employmentTypes ?? []" />
                        </FormField>

                        <FormField label="График работы" class="mb-4">
                            <div class="d-flex flex-wrap align-center" style="gap: 6px 18px;">
                                <ChoiceBoxGroup v-model="scheduleType" :options="formOptions.scheduleTypes ?? []" />
                                <v-text-field
                                    v-model="scheduleOther"
                                    v-bind="scheduleOtherAttrs"
                                    label="Укажите"
                                    variant="outlined"
                                    density="compact"
                                    rounded="lg"
                                    hide-details="auto"
                                    style="max-width: 240px;"
                                    :disabled="scheduleType !== SCHEDULE_OTHER"
                                    :error-messages="errors.schedule_other"
                                />
                            </div>
                        </FormField>

                        <FormField label="Формат работы" class="mb-4">
                            <ChoiceBoxGroup v-model="workFormat" :options="formOptions.workFormats ?? []" />
                        </FormField>

                        <FormField label="Предполагаемый уровень дохода (оклад / диапазон, по согласованию с HR)" class="mb-4">
                            <v-text-field
                                v-model="salary"
                                v-bind="salaryAttrs"
                                type="number"
                                min="0"
                                step="1"
                                suffix="сомонӣ"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                hide-details="auto"
                                :error-messages="errors.salary"
                            />
                        </FormField>

                        <FormField label="Испытательный срок" class="mb-1">
                            <div class="d-flex flex-wrap align-center" style="gap: 6px 18px;">
                                <ChoiceBoxGroup v-model="probation" :options="formOptions.probations ?? []" />
                                <v-text-field
                                    v-model="probationOther"
                                    v-bind="probationOtherAttrs"
                                    label="Иное"
                                    variant="outlined"
                                    density="compact"
                                    rounded="lg"
                                    hide-details="auto"
                                    style="max-width: 180px;"
                                    :disabled="probation !== PROBATION_OTHER"
                                    :error-messages="errors.probation_other"
                                />
                            </div>
                        </FormField>
                    </v-card>

                    <!-- 5. Причина открытия позиции и сроки -->
                    <v-card elevation="0" class="rounded-xl border pa-5 bg-white mb-5">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <Flag style="width: 16px; height: 16px;" />
                            </v-avatar>
                            5. Причина открытия позиции и сроки
                        </div>
                        <FormField label="Причина открытия позиции" class="mb-4">
                            <ChoiceBoxGroup v-model="openingReason" :options="formOptions.openingReasons ?? []" />
                        </FormField>

                        <FormField label="Приоритет / срочность" class="mb-4">
                            <ChoiceBoxGroup v-model="priority" :options="formOptions.priorities ?? []" />
                        </FormField>

                        <v-row density="comfortable" align="end">
                            <v-col cols="12" md="6">
                                <FormField label="Дата подачи заявки" class="mb-1">
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
                            <v-col cols="12" md="6">
                                <FormField label="Планируемая дата закрытия вакансии (Дедлайн)" class="mb-1">
                                    <v-text-field
                                        v-model="deadline"
                                        v-bind="deadlineAttrs"
                                        type="date"
                                        variant="outlined"
                                        density="comfortable"
                                        rounded="lg"
                                        hide-details="auto"
                                        :error-messages="errors.deadline"
                                    />
                                </FormField>
                            </v-col>
                        </v-row>
                    </v-card>

                    <!-- 6. Согласование заявки (статус — только при редактировании) -->
                    <v-card v-if="vacancy" elevation="0" class="rounded-xl border pa-5 bg-white">
                        <div class="d-flex align-center section-title mb-4">
                            <v-avatar color="indigo-lighten-5" size="32" class="mr-3 text-indigo">
                                <BadgeCheck style="width: 16px; height: 16px;" />
                            </v-avatar>
                            6. Согласование заявки
                        </div>
                        <FormField label="Статус вакансии" class="mb-1">
                            <v-select
                                v-model="status"
                                v-bind="statusAttrs"
                                :items="[{ value: VACANCY_STATUS_OPEN, title: 'Открыта' }, { value: VACANCY_STATUS_CLOSED, title: 'Закрыта' }]"
                                item-title="title"
                                item-value="value"
                                variant="outlined"
                                density="comfortable"
                                rounded="lg"
                                hide-details="auto"
                            />
                        </FormField>
                    </v-card>
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5">
                <v-btn variant="text" rounded="lg" size="large" :disabled="inertia.processing" @click="open = false">
                    Отмена
                </v-btn>
                <v-spacer />
                <v-btn color="indigo" variant="flat" rounded="lg" size="large" :loading="inertia.processing" class="bg-indigo px-6 font-weight-medium text-white" @click="submit">
                    Сохранить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
/* Диалог сохраняет нумерованные разделы печатной формы, но оформляет их как
   белые карточки сайта с общей палитрой шапки/акцента (шапка — DialogHeader). */
.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e1b4b;
}
</style>
