<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Network } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { watch } from 'vue';
import { departmentSchema } from '@/lib/schemas';

const props = defineProps({
    department: { type: Object, default: null }, // null → create
    parentOptions: { type: Array, default: () => [] },
    branchId: { type: Number, default: null },
    initialParentId: { type: Number, default: null }, // preset parent on create
});

const open = defineModel({ type: Boolean, default: false });

// vee-validate owns client-side validation; branch_id is fixed server-side
// (from the branchId prop), so only the user-editable fields are validated.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(departmentSchema),
    initialValues: { parent_id: null, name: '', code: '' },
});
const [parentId, parentIdAttrs] = defineField('parent_id');
const [name, nameAttrs] = defineField('name');
const [code, codeAttrs] = defineField('code');

const inertia = useInertiaForm({ branch_id: null, parent_id: null, name: '', code: '' });

watch(open, (visible) => {
    if (!visible)
        return;
    resetForm({
        values: {
            parent_id: props.department ? props.department.parent_id : props.initialParentId,
            name: props.department?.name ?? '',
            code: props.department?.code ?? '',
        },
    });
});

const submit = handleSubmit((values) => {
    inertia.branch_id = props.branchId != null ? Number(props.branchId) : null;
    inertia.parent_id = values.parent_id != null && values.parent_id !== '' ? Number(values.parent_id) : null;
    inertia.name = values.name;
    inertia.code = values.code ?? '';

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        for (const field of ['parent_id', 'name', 'code']) {
            if (serverErrors[field])
                setFieldError(field, serverErrors[field]);
        }
    };

    if (props.department)
        inertia.put(route('departments.update', props.department.id), { preserveScroll: true, onSuccess, onError });
    else
        inertia.post(route('departments.store'), { preserveScroll: true, onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="560px" persistent>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <Network style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ department ? 'Таҳрир' : 'Шуъбаи нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ department ? name || 'Шуъба' : 'Илова кардани шуъба' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6">
                <v-form @submit.prevent="submit">
                    <v-select
                        v-model="parentId"
                        v-bind="parentIdAttrs"
                        :items="parentOptions"
                        item-title="name"
                        item-value="id"
                        label="Шуъбаи болоӣ"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        clearable
                        :error-messages="errors.parent_id"
                        class="mb-4"
                        hint="Барои шуъбаи решагӣ холӣ монед"
                        persistent-hint
                    />

                    <v-text-field
                        v-model="name"
                        v-bind="nameAttrs"
                        label="Номи шуъба"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        :error-messages="errors.name"
                        class="mb-4"
                    />

                    <v-text-field
                        v-model="code"
                        v-bind="codeAttrs"
                        label="Рамзи шуъба"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        :error-messages="errors.code"
                    />
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                <v-btn
                    variant="tonal"
                    color="grey"
                    rounded="lg"
                    size="large"
                    :disabled="inertia.processing"
                    class="px-6 font-weight-bold"
                    @click="open = false"
                >
                    Бекор кардан
                </v-btn>
                <v-btn
                    color="indigo"
                    variant="flat"
                    rounded="lg"
                    size="large"
                    :loading="inertia.processing"
                    class="px-6 font-weight-bold text-white"
                    style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    @click="submit"
                >
                    Захира кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
