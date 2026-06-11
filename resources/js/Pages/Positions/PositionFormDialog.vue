<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Briefcase } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { positionSchema } from '@/lib/schemas';

const props = defineProps({
    position: { type: Object, default: null }, // null → создание
});

const open = defineModel({ type: Boolean, default: false });

// vee-validate отвечает за мгновенную клиентскую валидацию (zod-схема); Inertia —
// за саму отправку и проброс серверных ошибок обратно в форму.
const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(positionSchema),
    initialValues: { name: '' },
});
const [name, nameAttrs] = defineField('name');

const inertia = useInertiaForm({ name: '' });

watch(open, (visible) => {
    if (visible) {
        resetForm({ values: { name: props.position?.name ?? '' } });
    }
});

const submit = handleSubmit((values) => {
    inertia.name = values.name;

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        if (serverErrors.name)
            setFieldError('name', serverErrors.name);
    };

    if (props.position)
        inertia.put(route('positions.update', props.position.id), { onSuccess, onError });
    else
        inertia.post(route('positions.store'), { onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="520px" persistent>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <div style="background: #009cf1; padding: 20px 24px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <Briefcase style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ position ? 'Таҳрир' : 'Вазифаи нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ position ? name || 'Вазифа' : 'Илова кардани вазифа' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-6">
                <v-form class="app-form" @submit.prevent="submit">
                    <FormField label="Номи вазифа" required>
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
