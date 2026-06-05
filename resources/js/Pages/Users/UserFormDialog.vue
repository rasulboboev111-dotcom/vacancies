<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { UserCog } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, watch } from 'vue';
import FormField from '@/Components/FormField.vue';
import { userSchema } from '@/lib/schemas';
import { getRoleLabel } from '@/Pages/Users/userRoles';

const props = defineProps({
    user: { type: Object, default: null }, // null → create
    branches: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const open = defineModel({ type: Boolean, default: false });

const roleOptions = computed(() =>
    (props.roles ?? []).map(role => ({
        value: role.name,
        title: role.label ?? getRoleLabel(role.name),
    })),
);

// Schema reacts to create-vs-edit (password is required only on create).
const validationSchema = computed(() => toTypedSchema(userSchema({ isCreate: !props.user })));

const { defineField, errors, handleSubmit, resetForm, setFieldError } = useVeeForm({
    validationSchema,
    initialValues: { name: '', email: '', password: '', password_confirmation: '', branch_id: null, role: '' },
});
const [name, nameAttrs] = defineField('name');
const [email, emailAttrs] = defineField('email');
const [password, passwordAttrs] = defineField('password');
const [passwordConfirmation, passwordConfirmationAttrs] = defineField('password_confirmation');
const [role, roleAttrs] = defineField('role');
const [branchId, branchIdAttrs] = defineField('branch_id');

const inertia = useInertiaForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    branch_id: null,
    role: '',
});

watch(open, (visible) => {
    if (!visible)
        return;
    resetForm({
        values: {
            name: props.user?.name ?? '',
            email: props.user?.email ?? '',
            password: '',
            password_confirmation: '',
            branch_id: props.user?.branch_id ?? null,
            role: props.user?.roles?.[0]?.name ?? '',
        },
    });
});

const submit = handleSubmit((values) => {
    Object.assign(inertia, values);

    const onSuccess = () => {
        open.value = false;
    };
    const onError = (serverErrors) => {
        for (const field of ['name', 'email', 'password', 'password_confirmation', 'branch_id', 'role']) {
            if (serverErrors[field])
                setFieldError(field, serverErrors[field]);
        }
    };

    if (props.user)
        inertia.put(route('users.update', props.user.id), { onSuccess, onError });
    else
        inertia.post(route('users.store'), { onSuccess, onError });
});
</script>

<template>
    <v-dialog v-model="open" max-width="520px" persistent scrollable>
        <v-card class="rounded-xl overflow-hidden d-flex flex-column" elevation="8" style="max-height: 90vh;">
            <div style="background: #009cf1; padding: 20px 24px; flex-shrink: 0;">
                <div class="d-flex align-center">
                    <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                        <UserCog style="width: 22px; height: 22px; color: white;" />
                    </v-avatar>
                    <div class="ml-4">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                            {{ user ? 'Таҳрир' : 'Корбари нав' }}
                        </div>
                        <div style="color: white; font-size: 1.1rem; font-weight: 700;">
                            {{ user ? name || 'Корбар' : 'Эҷод кардани корбар' }}
                        </div>
                    </div>
                </div>
            </div>

            <form class="app-form d-flex flex-column" style="min-height: 0; flex: 1 1 auto; overflow: hidden;" @submit.prevent="submit">
                <v-card-text class="pa-6" style="overflow-y: auto;">
                    <FormField label="Номи корбар" required>
                        <v-text-field v-model="name" v-bind="nameAttrs" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.name" />
                    </FormField>

                    <FormField label="Почтаи электронӣ" required class="mt-4">
                        <v-text-field v-model="email" v-bind="emailAttrs" type="email" placeholder="namuna@mail.tj" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.email" />
                    </FormField>

                    <FormField label="Парол" :required="!user" class="mt-4">
                        <v-text-field v-model="password" v-bind="passwordAttrs" type="password" :placeholder="user ? 'Холӣ монед барои нигоҳ доштани парол' : ''" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.password" />
                    </FormField>

                    <FormField label="Тасдиқи парол" class="mt-4">
                        <v-text-field v-model="passwordConfirmation" v-bind="passwordConfirmationAttrs" type="password" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.password_confirmation" />
                    </FormField>

                    <FormField label="Нақши корбар" required class="mt-4">
                        <v-select v-model="role" v-bind="roleAttrs" :items="roleOptions" item-title="title" item-value="value" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.role" />
                    </FormField>

                    <FormField label="Пайвасткунӣ ба филиал" :required="role === 'User'" class="mt-4">
                        <v-select v-model="branchId" v-bind="branchIdAttrs" :items="branches" item-title="name" item-value="id" variant="outlined" density="comfortable" rounded="lg" hide-details="auto" :error-messages="errors.branch_id" clearable />
                    </FormField>
                </v-card-text>

                <v-divider />

                <v-card-actions class="pa-5">
                    <v-btn variant="text" rounded="lg" size="large" :disabled="inertia.processing" @click="open = false">
                        Бекор кардан
                    </v-btn>
                    <v-spacer />
                    <v-btn
                        type="submit"
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        class="px-6 font-weight-medium text-white bg-indigo"
                        :loading="inertia.processing"
                    >
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </v-dialog>
</template>
