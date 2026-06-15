<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { ref, watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { applyServerErrors } from '@/lib/errors';
import { applicationSchema } from '@/lib/schemas';

const props = defineProps({
    application: { type: Object, default: null }, // null → create
    branches: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
});

const emit = defineEmits(['saved']);

const open = defineModel({ type: Boolean, default: false });

const resumeFile = ref(null);

const FIELDS = ['name', 'phone', 'email', 'source', 'branch_id'];

const EMPTY = {
    name: '',
    phone: '',
    email: '',
    source: 'manual',
    branch_id: null,
};

const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema: toTypedSchema(applicationSchema),
    initialValues: { ...EMPTY },
});

const [name, nameAttrs] = defineField('name');
const [phone, phoneAttrs] = defineField('phone');
const [email, emailAttrs] = defineField('email');
const [source, sourceAttrs] = defineField('source');
const [branchId, branchIdAttrs] = defineField('branch_id');

const inertia = useInertiaForm({
    name: '',
    phone: '',
    email: '',
    source: 'manual',
    branch_id: null,
    resume: null,
    _method: 'post',
});

watch(open, (visible) => {
    if (!visible)
        return;

    resumeFile.value = null;

    const a = props.application;
    if (a) {
        resetForm({
            values: {
                name: a.name || '',
                phone: a.phone || '',
                email: a.email || '',
                source: a.source || 'manual',
                branch_id: a.branch_id ? Number(a.branch_id) : null,
            },
        });
    }
    else {
        resetForm({ values: { ...EMPTY } });
    }
});

const submit = handleSubmit((values) => {
    Object.assign(inertia, values);
    inertia.resume = resumeFile.value ?? null;

    const onSuccess = () => {
        open.value = false;
        emit('saved');
    };

    const onError = (serverErrors) => {
        applyServerErrors(serverErrors, FIELDS, setFieldError);
    };

    if (props.application) {
        // Multipart PUT via method spoofing: POST with _method=put
        inertia._method = 'put';
        inertia.post(route('applications.update', props.application.id), {
            forceFormData: true,
            onSuccess,
            onError,
        });
    }
    else {
        inertia._method = 'post';
        inertia.post(route('applications.store'), {
            forceFormData: true,
            onSuccess,
            onError,
        });
    }
});
</script>

<template>
    <v-dialog v-model="open" max-width="600px" persistent scrollable>
        <v-card class="rounded-xl overflow-hidden" elevation="8">
            <!-- Premium Gradient Header -->
            <div style="background: #009cf1; padding: 20px 28px;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <ClipboardList style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ application ? 'Таҳрир' : 'Аризаи нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                            {{ application ? name || 'Таҳрири ариза' : 'Илова кардани ариза' }}
                        </div>
                    </div>
                </div>
            </div>

            <v-card-text class="px-6 pt-5 overflow-y-auto" style="max-height: 62vh;">
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

                <v-form class="app-form" @submit.prevent="submit">
                    <v-row>
                        <v-col cols="12">
                            <FormField label="Ному насаб" required>
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
                        </v-col>

                        <v-col cols="12" sm="6">
                            <FormField label="Телефон">
                                <v-text-field
                                    v-model="phone"
                                    v-bind="phoneAttrs"
                                    placeholder="+992 90 000 0000"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    :error-messages="errors.phone"
                                />
                            </FormField>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <FormField label="Email">
                                <v-text-field
                                    v-model="email"
                                    v-bind="emailAttrs"
                                    type="email"
                                    placeholder="namuna@mail.tj"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    :error-messages="errors.email"
                                />
                            </FormField>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <FormField label="Манбаъ">
                                <v-select
                                    v-model="source"
                                    v-bind="sourceAttrs"
                                    :items="sources.map(s => ({ title: s, value: s }))"
                                    item-title="title"
                                    item-value="value"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    clearable
                                    :error-messages="errors.source"
                                />
                            </FormField>
                        </v-col>

                        <v-col v-if="isAdmin" cols="12" sm="6">
                            <FormField label="Филиал">
                                <v-select
                                    v-model="branchId"
                                    v-bind="branchIdAttrs"
                                    :items="branches"
                                    item-title="name"
                                    item-value="id"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    clearable
                                    :error-messages="errors.branch_id"
                                />
                            </FormField>
                        </v-col>

                        <v-col cols="12">
                            <FormField label="Резюме">
                                <v-file-input
                                    v-model="resumeFile"
                                    accept=".pdf,.doc,.docx,.rtf,.odt"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    hide-details="auto"
                                    prepend-inner-icon="$file"
                                    prepend-icon=""
                                    :label="application?.has_resume ? 'Резюме (барои иваз кардан интихоб кунед)' : 'Резюме интихоб кунед'"
                                    show-size
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
