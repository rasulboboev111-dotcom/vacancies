<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { watch } from 'vue';
import { branchSchema } from '@/lib/schemas';

const props = defineProps({
    branch: { type: Object, default: null }, // null → create
});

const open = defineModel({ type: Boolean, default: false });

// vee-validate owns client-side validation; Inertia owns submit + server errors.
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
        for (const field of ['name', 'code', 'address']) {
            if (serverErrors[field])
                setFieldError(field, serverErrors[field]);
        }
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
                <v-form @submit.prevent="submit">
                    <v-text-field
                        v-model="name"
                        v-bind="nameAttrs"
                        label="Номи филиал"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        :error-messages="errors.name"
                        class="mb-4"
                    />

                    <v-text-field
                        v-model="code"
                        v-bind="codeAttrs"
                        label="Рамзи филиал (масалан, DSH)"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        :error-messages="errors.code"
                        class="mb-4"
                    />

                    <v-text-field
                        v-model="address"
                        v-bind="addressAttrs"
                        label="Суроға"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        :error-messages="errors.address"
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
