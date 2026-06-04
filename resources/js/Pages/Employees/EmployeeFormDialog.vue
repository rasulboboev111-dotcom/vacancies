<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { FileText, IdCard, User, UserPlus } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { watchDebounced } from '@vueuse/core';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, ref, watch } from 'vue';
import { employeeSchema } from '@/lib/schemas';

const props = defineProps({
    employee: { type: Object, default: null }, // null → create
    branches: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    educations: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    birthPlaces: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    userBranchId: { type: Number, default: null },
});

const open = defineModel({ type: Boolean, default: false });

const activeTab = ref(0);

// Gender options: canonical enum value submitted to the backend, label shown.
const genderOptions = [
    { value: 'мужской', title: 'Мард' },
    { value: 'женский', title: 'Зан' },
];

// Suggested dismissal reasons; the field is a free-text combobox so any custom
// reason can be typed in as well.
const dismissalReasonOptions = [
    'Бо хоҳиши худ',
    'Ихтисор',
    'Ба нафақа баромадан',
    'Анҷоми мӯҳлати шартнома',
    'Вайронкунии интизом',
    'Рухсатии таваллуд',
    'Рухсатии бемузд',
];

const FIELDS = [
    'branch_id',
    'category',
    'type_id',
    'full_name',
    'gender',
    'position_id',
    'department_id',
    'manager_id',
    'hire_date',
    'dismissal_date',
    'dismissal_reason',
    'birth_date',
    'nationality',
    'passport_number',
    'passport_start_date',
    'passport_end_date',
    'passport_issued_by',
    'inn',
    'sin',
    'address',
    'phone_number',
    'email',
    'birth_place',
    'education',
    'specialty',
    'employment_start_date',
];

const EMPTY = {
    branch_id: null,
    category: null,
    type_id: null,
    full_name: '',
    gender: null,
    position_id: null,
    department_id: null,
    manager_id: null,
    hire_date: '',
    dismissal_date: '',
    dismissal_reason: '',
    birth_date: '',
    nationality: null,
    passport_number: '',
    passport_start_date: '',
    passport_end_date: '',
    passport_issued_by: '',
    inn: '',
    sin: '',
    address: '',
    phone_number: '',
    email: '',
    birth_place: null,
    education: null,
    specialty: null,
    employment_start_date: '',
};

// vee-validate owns client-side validation; Inertia owns submit + server errors.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(employeeSchema),
    initialValues: { ...EMPTY },
});

const [branchId, branchIdAttrs] = defineField('branch_id');
const [category, categoryAttrs] = defineField('category');
const [typeId, typeIdAttrs] = defineField('type_id');
const [fullName, fullNameAttrs] = defineField('full_name');
const [gender, genderAttrs] = defineField('gender');
const [positionId, positionIdAttrs] = defineField('position_id');
const [departmentId, departmentIdAttrs] = defineField('department_id');
const [managerId, managerIdAttrs] = defineField('manager_id');

// Direct-manager picker: searched server-side (employees.managers, capped at
// 20) instead of shipping the whole workforce. Options are refreshed as the
// user types; the currently-selected manager is always kept in the list so its
// name renders even when it isn't in the latest search results.
const managerOptions = ref([]);
const managerSearch = ref('');
const managerLoading = ref(false);

async function fetchManagers() {
    managerLoading.value = true;
    try {
        const { data } = await window.axios.get(route('employees.managers'), {
            params: { search: managerSearch.value },
        });
        const selected = managerOptions.value.find(m => m.id === managerId.value);
        managerOptions.value = selected && !data.some(m => m.id === selected.id)
            ? [selected, ...data]
            : data;
    }
    finally {
        managerLoading.value = false;
    }
}

watchDebounced(managerSearch, fetchManagers, { debounce: 300 });
const [hireDate, hireDateAttrs] = defineField('hire_date');
const [dismissalDate, dismissalDateAttrs] = defineField('dismissal_date');
const [dismissalReason, dismissalReasonAttrs] = defineField('dismissal_reason');
const [birthDate, birthDateAttrs] = defineField('birth_date');
const [nationality, nationalityAttrs] = defineField('nationality');
const [passportNumber, passportNumberAttrs] = defineField('passport_number');
const [passportStartDate, passportStartDateAttrs] = defineField('passport_start_date');
const [passportEndDate, passportEndDateAttrs] = defineField('passport_end_date');
const [passportIssuedBy, passportIssuedByAttrs] = defineField('passport_issued_by');
const [inn, innAttrs] = defineField('inn');
const [sin, sinAttrs] = defineField('sin');
const [address, addressAttrs] = defineField('address');
const [phoneNumber, phoneNumberAttrs] = defineField('phone_number');
const [email, emailAttrs] = defineField('email');
const [birthPlace, birthPlaceAttrs] = defineField('birth_place');
const [education, educationAttrs] = defineField('education');
const [specialty, specialtyAttrs] = defineField('specialty');
const [employmentStartDate, employmentStartDateAttrs] = defineField('employment_start_date');

const inertia = useInertiaForm({ ...EMPTY });

// Departments filtered by the chosen branch; clear the department when it no
// longer belongs to the selected branch (was the useBranchDepartments
// composable, which assumed an Inertia form).
const branchDepartments = computed(() =>
    props.departments.filter(d => Number(d.branch_id) === Number(branchId.value)),
);
watch(branchId, (newBranchId) => {
    if (
        departmentId.value
        && !props.departments.some(
            d => Number(d.id) === Number(departmentId.value) && Number(d.branch_id) === Number(newBranchId),
        )
    ) {
        departmentId.value = null;
    }
});

// Clearing the dismissal date drops the now-irrelevant dismissal reason.
watch(dismissalDate, (date) => {
    if (!date)
        dismissalReason.value = '';
});

// Maps each form field to the tab it lives on, so a validation error can
// surface the right tab.
const fieldTabMap = {
    full_name: 0,
    branch_id: 0,
    position_id: 0,
    department_id: 0,
    category: 0,
    type_id: 0,
    manager_id: 0,
    employment_start_date: 0,
    hire_date: 0,
    dismissal_date: 0,
    dismissal_reason: 0,
    gender: 1,
    birth_date: 1,
    nationality: 1,
    phone_number: 1,
    email: 1,
    birth_place: 1,
    education: 1,
    specialty: 1,
    address: 1,
    passport_number: 2,
    passport_start_date: 2,
    passport_end_date: 2,
    inn: 2,
    sin: 2,
    passport_issued_by: 2,
};

function focusFirstErrorTab(errs) {
    const tabs = Object.keys(errs)
        .map(field => fieldTabMap[field])
        .filter(t => t !== undefined);
    if (tabs.length)
        activeTab.value = Math.min(...tabs);
}

const iso10 = value => (value ? String(value).substring(0, 10) : '');

// Populate the form whenever the dialog opens (create vs edit).
watch(open, (visible) => {
    if (!visible)
        return;
    activeTab.value = 0;

    const e = props.employee;
    // Seed the manager picker so the current manager's name shows on edit, then
    // (re)load the first page of options for the dropdown.
    managerOptions.value = e?.manager_id && e?.manager
        ? [{ id: Number(e.manager_id), full_name: e.manager.full_name }]
        : [];
    managerSearch.value = '';
    fetchManagers();
    if (e) {
        resetForm({
            values: {
                branch_id: e.branch_id ? Number(e.branch_id) : null,
                category: e.category || null,
                type_id: e.employment_type || null,
                full_name: e.full_name,
                gender: e.gender || null,
                position_id: e.position_id ? Number(e.position_id) : null,
                department_id: e.department_id ? Number(e.department_id) : null,
                manager_id: e.manager_id ? Number(e.manager_id) : null,
                hire_date: iso10(e.hire_date),
                dismissal_date: iso10(e.dismissal_date),
                dismissal_reason: e.dismissal_reason || '',
                birth_date: iso10(e.birth_date),
                nationality: e.nationality || null,
                passport_number: e.passport_number || '',
                passport_start_date: iso10(e.passport_start_date),
                passport_end_date: iso10(e.passport_end_date),
                passport_issued_by: e.passport_issued_by || '',
                inn: e.inn || '',
                sin: e.sin || '',
                address: e.address || '',
                phone_number: e.phone_number || '',
                email: e.email || '',
                birth_place: e.birth_place || null,
                education: e.education || null,
                specialty: e.specialty || null,
                employment_start_date: iso10(e.employment_start_date),
            },
        });
    }
    else {
        const branch = props.isAdmin && props.branches.length > 0
            ? Number(props.branches[0].id)
            : (props.userBranchId ?? null);
        resetForm({ values: { ...EMPTY, branch_id: branch } });
    }
});

const submit = handleSubmit(
    (values) => {
        Object.assign(inertia, values);

        const options = {
            onSuccess: () => {
                open.value = false;
            },
            onError: (serverErrors) => {
                for (const field of FIELDS) {
                    if (serverErrors[field])
                        setFieldError(field, serverErrors[field]);
                }
                focusFirstErrorTab(serverErrors);
            },
        };

        if (props.employee)
            inertia.put(route('employees.update', props.employee.id), options);
        else
            inertia.post(route('employees.store'), options);
    },
    ({ errors: clientErrors }) => focusFirstErrorTab(clientErrors),
);
</script>

<template>
    <v-dialog v-model="open" max-width="850px" persistent>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <!-- Premium Gradient Header -->
            <div style="background: #009cf1; padding: 20px 28px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <UserPlus style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ employee ? 'Таҳрир' : 'Корманди нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ employee ? fullName || 'Таҳрири корманд' : 'Илова кардани корманд' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Tabs for neat grouping -->
            <v-tabs v-model="activeTab" color="indigo" align-tabs="start" class="px-4 pt-2" show-arrows>
                <v-tab :value="0">
                    <div class="d-flex align-center">
                        <User style="width: 16px; height: 16px; margin-right: 6px;" />
                        <span>Маълумоти асосӣ</span>
                    </div>
                </v-tab>
                <v-tab :value="1">
                    <div class="d-flex align-center">
                        <IdCard style="width: 16px; height: 16px; margin-right: 6px;" />
                        <span>Маълумоти шахсӣ</span>
                    </div>
                </v-tab>
                <v-tab :value="2">
                    <div class="d-flex align-center">
                        <FileText style="width: 16px; height: 16px; margin-right: 6px;" />
                        <span>Шиноснома ва рамзҳо</span>
                    </div>
                </v-tab>
            </v-tabs>

            <v-card-text class="px-6 pt-4 overflow-y-auto" style="max-height: 62vh;">
                <v-alert
                    v-if="Object.keys(errors).length"
                    type="error"
                    variant="tonal"
                    density="comfortable"
                    rounded="lg"
                    class="mb-4"
                >
                    Маълумотро пурра кунед — баъзе майдонҳои ҳатмӣ хатогӣ доранд.
                </v-alert>
                <v-form @submit.prevent="submit">
                    <v-window v-model="activeTab">
                        <!-- Tab 1: Маълумоти асосии корӣ -->
                        <v-window-item :value="0">
                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="fullName" v-bind="fullNameAttrs" label="Ному насаби корманд" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.full_name" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-select v-model="branchId" v-bind="branchIdAttrs" :items="branches" item-title="name" item-value="id" label="Филиал" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.branch_id" :disabled="!isAdmin" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-autocomplete v-model="positionId" v-bind="positionIdAttrs" :items="positions" item-title="name" item-value="id" label="Вазифа" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.position_id" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-autocomplete v-model="departmentId" v-bind="departmentIdAttrs" :items="branchDepartments" item-title="name" item-value="id" label="Шуъба" variant="outlined" density="comfortable" rounded="lg" clearable :no-data-text="branchId ? 'Шуъбаҳо ёфт нашуданд' : 'Аввал филиалро интихоб кунед'" :error-messages="errors.department_id" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-select v-model="category" v-bind="categoryAttrs" :items="categories" item-title="label" item-value="value" label="Категория" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.category" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-select v-model="typeId" v-bind="typeIdAttrs" :items="types" item-title="name" item-value="id" label="Намуди шуғл" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.type_id" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-autocomplete v-model="managerId" v-bind="managerIdAttrs" v-model:search="managerSearch" :items="managerOptions" :loading="managerLoading" no-filter item-title="full_name" item-value="id" label="Роҳбари бевосита" variant="outlined" density="comfortable" rounded="lg" clearable :error-messages="errors.manager_id" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="employmentStartDate" v-bind="employmentStartDateAttrs" label="Собиқаи корӣ" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.employment_start_date" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="hireDate" v-bind="hireDateAttrs" label="Санаи қабул" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.hire_date" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="dismissalDate" v-bind="dismissalDateAttrs" label="Санаи озодшавӣ" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.dismissal_date" />
                                </v-col>

                                <v-col v-if="dismissalDate" cols="12">
                                    <v-combobox v-model="dismissalReason" v-bind="dismissalReasonAttrs" :items="dismissalReasonOptions" label="Сабаби озодшавӣ" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.dismissal_reason" />
                                </v-col>
                            </v-row>
                        </v-window-item>

                        <!-- Tab 2: Маълумоти шахсӣ -->
                        <v-window-item :value="1">
                            <v-row>
                                <v-col cols="12" sm="4">
                                    <v-select v-model="gender" v-bind="genderAttrs" :items="genderOptions" item-title="title" item-value="value" label="Ҷинс" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.gender" />
                                </v-col>

                                <v-col cols="12" sm="4">
                                    <v-text-field v-model="birthDate" v-bind="birthDateAttrs" label="Санаи таваллуд" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.birth_date" />
                                </v-col>

                                <v-col cols="12" sm="4">
                                    <v-combobox v-model="nationality" v-bind="nationalityAttrs" :items="nationalities" label="Миллат" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.nationality" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="phoneNumber" v-bind="phoneNumberAttrs" label="Рақами телефон" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.phone_number" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="email" v-bind="emailAttrs" label="Почтаи электронӣ" type="email" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.email" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-combobox v-model="birthPlace" v-bind="birthPlaceAttrs" :items="birthPlaces" label="Зодгоҳ" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.birth_place" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-combobox v-model="education" v-bind="educationAttrs" :items="educations" label="Маълумот" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.education" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-combobox v-model="specialty" v-bind="specialtyAttrs" :items="specialties" label="Ихтисос" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.specialty" />
                                </v-col>

                                <v-col cols="12">
                                    <v-text-field v-model="address" v-bind="addressAttrs" label="Суроғаи истиқомат" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.address" />
                                </v-col>
                            </v-row>
                        </v-window-item>

                        <!-- Tab 3: Шиноснома ва рамзҳо -->
                        <v-window-item :value="2">
                            <v-row>
                                <v-col cols="12" sm="4">
                                    <v-text-field v-model="passportNumber" v-bind="passportNumberAttrs" label="Рақами шиноснома" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.passport_number" />
                                </v-col>

                                <v-col cols="12" sm="4">
                                    <v-text-field v-model="passportStartDate" v-bind="passportStartDateAttrs" label="Мӯҳлати эътибор (Аз)" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.passport_start_date" />
                                </v-col>

                                <v-col cols="12" sm="4">
                                    <v-text-field v-model="passportEndDate" v-bind="passportEndDateAttrs" label="Мӯҳлати эътибор (То)" type="date" max="3000-12-31" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.passport_end_date" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="inn" v-bind="innAttrs" label="ИНН / РМА" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.inn" />
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="sin" v-bind="sinAttrs" label="СИН (Рамз)" variant="outlined" density="comfortable" rounded="lg" :error-messages="errors.sin" />
                                </v-col>

                                <v-col cols="12">
                                    <v-textarea v-model="passportIssuedBy" v-bind="passportIssuedByAttrs" label="Аз ҷониби додашуда (Мақоми шиноснома диҳанда)" variant="outlined" density="comfortable" rounded="lg" rows="2" :error-messages="errors.passport_issued_by" />
                                </v-col>
                            </v-row>
                        </v-window-item>
                    </v-window>
                </v-form>
            </v-card-text>

            <v-card-actions class="px-2 pt-4">
                <v-btn v-if="activeTab > 0" variant="tonal" color="indigo" rounded="lg" @click="activeTab--">
                    Қафо
                </v-btn>
                <v-spacer />
                <v-btn variant="text" rounded="lg" :disabled="inertia.processing" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-btn v-if="activeTab < 2" color="indigo" variant="flat" class="bg-indigo px-5 text-white" rounded="lg" @click="activeTab++">
                    Минбаъд
                </v-btn>
                <v-btn v-else color="indigo" variant="flat" class="bg-indigo px-5 text-white" rounded="lg" :loading="inertia.processing" @click="submit">
                    Захира кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
