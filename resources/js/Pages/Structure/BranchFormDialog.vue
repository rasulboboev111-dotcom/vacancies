<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { applyServerErrors } from '@/lib/errors';
import { branchSchema } from '@/lib/schemas';

const props = defineProps({
    branch: { type: Object, default: null }, // null → создание
});

const open = defineModel({ type: Boolean, default: false });

// vee-validate отвечает за клиентскую валидацию; Inertia — за отправку и серверные ошибки.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(branchSchema),
    initialValues: { name: '', code: '', address: '' },
});
const [name, nameAttrs] = defineField('name');
const [code, codeAttrs] = defineField('code');
const [address, addressAttrs] = defineField('address');

const inertia = useInertiaForm({ name: '', code: '', address: '' });

watch(open, (visible) => {
    if (!visible)
        return;
    resetForm({
        values: {
            name: props.branch?.name ?? '',
            code: props.branch?.code ?? '',
            address: props.branch?.address ?? '',
        },
    });
});

const submit = handleSubmit((values) => {
    inertia.name = values.name;
    inertia.code = values.code;
    inertia.address = values.address ?? '';

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        applyServerErrors(serverErrors, ['name', 'code', 'address'], setFieldError);
    };

    if (props.branch)
        inertia.put(route('branches.update', props.branch.id), { preserveScroll: true, onSuccess, onError });
    else
        inertia.post(route('branches.store'), { preserveScroll: true, onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="520px" persistent>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <Building2 style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ branch ? 'Таҳрир' : 'Филиали нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ branch ? name || 'Филиал' : 'Илова кардани филиал' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6">
                <v-form class="app-form" @submit.prevent="submit">
                    <FormField label="Номи филиал" required>
                        <v-text-field
                            v-model="name"
                            v-bind="nameAttrs"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.name"
                        />
                    </FormField>

                    <FormField label="Рамзи филиал" required class="mt-4">
                        <v-text-field
                            v-model="code"
                            v-bind="codeAttrs"
                            placeholder="масалан, DSH"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.code"
                        />
                    </FormField>

                    <FormField label="Суроға" class="mt-4">
                        <v-text-field
                            v-model="address"
                            v-bind="addressAttrs"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details="auto"
                            :error-messages="errors.address"
                        />
                    </FormField>
                </v-form>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-5">
                <v-btn variant="text" rounded="lg" size="large" :disabled="inertia.processing" @click="open = false">
                    Бекор кардан
                </v-btn>
                <v-spacer />
                <v-btn
                    color="indigo"
                    variant="flat"
                    rounded="lg"
                    size="large"
                    :loading="inertia.processing"
                    class="px-6 font-weight-medium text-white bg-indigo"
                    @click="submit"
                >
                    Захира кардан
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
