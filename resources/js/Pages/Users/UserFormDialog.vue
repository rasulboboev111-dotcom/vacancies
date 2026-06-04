<script setup>
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Building2, Lock, Mail, Shield, User } from '@lucide/vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, watch } from 'vue';
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
    <v-dialog v-model="open" max-width="520px" persistent>
        <v-card class="rounded-xl border pa-4">
            <v-card-title class="d-flex justify-space-between align-center font-weight-black text-indigo-darken-4 text-h5">
                <span>{{ user ? 'Таҳрири корбар' : 'Эҷод кардани корбар' }}</span>
            </v-card-title>

            <v-divider class="my-3" />

            <form @submit.prevent="submit">
                <v-card-text class="pa-2">
                    <!-- Name Field -->
                    <v-text-field
                        v-model="name"
                        v-bind="nameAttrs"
                        label="Номи корбар"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.name"
                    >
                        <template #prepend-inner>
                            <User style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-text-field>

                    <!-- Email Field -->
                    <v-text-field
                        v-model="email"
                        v-bind="emailAttrs"
                        label="Почтаи электронӣ"
                        type="email"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.email"
                    >
                        <template #prepend-inner>
                            <Mail style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-text-field>

                    <!-- Password Field -->
                    <v-text-field
                        v-model="password"
                        v-bind="passwordAttrs"
                        label="Парол"
                        type="password"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.password"
                    >
                        <template #prepend-inner>
                            <Lock style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-text-field>

                    <!-- Password Confirmation -->
                    <v-text-field
                        v-model="passwordConfirmation"
                        v-bind="passwordConfirmationAttrs"
                        label="Тасдиқи парол"
                        type="password"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.password_confirmation"
                    >
                        <template #prepend-inner>
                            <Lock style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-text-field>

                    <!-- Role Field -->
                    <v-select
                        v-model="role"
                        v-bind="roleAttrs"
                        :items="roleOptions"
                        item-title="title"
                        item-value="value"
                        label="Нақши корбар"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.role"
                    >
                        <template #prepend-inner>
                            <Shield style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-select>

                    <!-- Branch Field -->
                    <v-select
                        v-model="branchId"
                        v-bind="branchIdAttrs"
                        :items="branches"
                        item-title="name"
                        item-value="id"
                        label="Пайвасткунӣ ба филиал"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        class="mb-3"
                        color="indigo"
                        :error-messages="errors.branch_id"
                        clearable
                        placeholder="Барои нақши «Корбар» ҳатмист"
                    >
                        <template #prepend-inner>
                            <Building2 style="width: 18px; height: 18px;" class="text-grey mr-2" />
                        </template>
                    </v-select>
                </v-card-text>

                <v-divider class="my-3" />

                <v-card-actions class="px-2">
                    <v-spacer />
                    <v-btn variant="text" rounded="lg" class="px-4 font-weight-bold" @click="open = false">
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        type="submit"
                        color="indigo"
                        variant="elevated"
                        rounded="lg"
                        class="px-5 font-weight-bold text-white bg-indigo"
                        :loading="inertia.processing"
                    >
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </v-dialog>
</template>
