<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    Shield, 
    Plus, 
    MoreVertical, 
    Pencil, 
    Trash2, 
    User, 
    AlertTriangle,
    Mail,
    Building2,
    Lock,
    Search
} from '@lucide/vue';

// Custom debounce function
const debounce = (fn, delay) => {
    let timeoutId = null;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    }
});

const search = ref(props.filters.search || '');
const dialog = ref(false);
const deleteDialog = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    branch_id: null,
    role: '',
});

// Watch search input to trigger search
watch(search, debounce((value) => {
    router.get(route('users.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

function openCreateDialog() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    dialog.value = true;
}

function openEditDialog(user) {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.branch_id = user.branch_id;
    form.role = user.roles[0]?.name || '';
    form.clearErrors();
    dialog.value = true;
}

function openDeleteDialog(user) {
    userToDelete.value = user;
    deleteDialog.value = true;
}

function submit() {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    }
}

function confirmDelete() {
    if (userToDelete.value) {
        form.delete(route('users.destroy', userToDelete.value.id), {
            onSuccess: () => {
                deleteDialog.value = false;
                userToDelete.value = null;
            },
        });
    }
}

const getRoleLabel = (roleName) => {
    if (!roleName) return 'Нет роли';
    const rolesMap = {
        'Admin': 'Администратор',
        'HR Manager': 'HR-Менеджер',
        'Branch Manager': 'Руководитель филиала',
        'Viewer': 'Наблюдатель'
    };
    return rolesMap[roleName] || roleName;
};

const getRoleColor = (roleName) => {
    if (!roleName) return 'grey';
    const colorsMap = {
        'Admin': 'red',
        'HR Manager': 'indigo',
        'Branch Manager': 'teal',
        'Viewer': 'blue-grey'
    };
    return colorsMap[roleName] || 'grey';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Пользователи" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Shield style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Управление пользователями</span>
            </div>
        </template>

        <!-- Search and Action Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" md="6">
                <v-text-field
                    v-model="search"
                    label="Поиск по имени или email..."
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                    clearable
                    color="indigo"
                    class="search-field bg-white"
                >
                    <template v-slot:prepend-inner>
                        <Search style="width: 18px; height: 18px; margin-right: 8px;" class="text-grey" />
                    </template>
                </v-text-field>
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-md-end">
                <v-btn
                    color="indigo"
                    rounded="lg"
                    elevation="2"
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold"
                    @click="openCreateDialog"
                >
                    <template v-slot:prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px;" />
                    </template>
                    Добавить пользователя
                </v-btn>
            </v-col>
        </v-row>

        <!-- Users Table -->
        <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
            <v-table class="bg-transparent text-left">
                <thead>
                    <tr class="bg-indigo-lighten-5">
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Пользователь</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Email</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Роль</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Филиал</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Регистрация</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-right">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id" class="hover-row transition-colors">
                        <td class="py-4 px-6">
                            <div class="d-flex align-center">
                                <v-avatar color="indigo-lighten-4" class="mr-3" size="38">
                                    <span class="text-indigo-darken-3 font-weight-black text-subtitle-2">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </v-avatar>
                                <div>
                                    <div class="font-weight-bold text-grey-darken-4">{{ user.name }}</div>
                                    <div v-if="user.id === $page.props.auth.user.id" class="text-caption text-indigo font-weight-bold">Это вы</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 font-weight-medium text-grey-darken-2">
                            {{ user.email }}
                        </td>
                        <td class="py-4 px-6">
                            <v-chip
                                :color="getRoleColor(user.roles[0]?.name)"
                                size="small"
                                variant="tonal"
                                class="font-weight-black"
                            >
                                {{ getRoleLabel(user.roles[0]?.name) }}
                            </v-chip>
                        </td>
                        <td class="py-4 px-6">
                            <div v-if="user.branch" class="d-flex align-center">
                                <Building2 style="width: 14px; height: 14px; margin-right: 6px;" class="text-grey" />
                                <span class="font-weight-medium text-grey-darken-3">{{ user.branch.name }}</span>
                            </div>
                            <span v-else class="text-grey font-weight-bold text-caption text-uppercase">Все филиалы</span>
                        </td>
                        <td class="py-4 px-6 font-weight-medium text-grey-darken-2">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="py-4 px-6 text-right">
                            <v-menu>
                                <template v-slot:activator="{ props: menuProps }">
                                    <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                                        <MoreVertical style="width: 16px; height: 16px;" />
                                    </v-btn>
                                </template>
                                <v-list density="comfortable" rounded="xl" class="border py-1">
                                    <v-list-item
                                        title="Редактировать"
                                        class="font-weight-bold"
                                        @click="openEditDialog(user)"
                                    >
                                        <template v-slot:prepend>
                                            <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                        </template>
                                    </v-list-item>
                                    <v-list-item
                                        v-if="user.id !== $page.props.auth.user.id"
                                        title="Удалить"
                                        class="text-error font-weight-bold"
                                        @click="openDeleteDialog(user)"
                                    >
                                        <template v-slot:prepend>
                                            <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="6" class="text-center py-12">
                            <User style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                            <div class="text-h6 text-grey font-weight-medium">
                                Пользователи не найдены.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="d-flex justify-center mt-6">
            <v-pagination
                :length="users.last_page"
                v-model="users.current_page"
                :total-visible="7"
                rounded="circle"
                active-color="indigo"
                @update:model-value="(page) => router.visit(route('users.index', { page, search }))"
            ></v-pagination>
        </div>

        <!-- Add/Edit User Dialog -->
        <v-dialog v-model="dialog" max-width="520px" persistent>
            <v-card class="rounded-xl border pa-4">
                <v-card-title class="d-flex justify-space-between align-center font-weight-black text-indigo-darken-4 text-h5">
                    <span>{{ editingUser ? 'Редактировать пользователя' : 'Создать пользователя' }}</span>
                </v-card-title>
                
                <v-divider class="my-3"></v-divider>

                <form @submit.prevent="submit">
                    <v-card-text class="pa-2">
                        <!-- Name Field -->
                        <v-text-field
                            v-model="form.name"
                            label="Имя пользователя"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :error-messages="form.errors.name"
                            required
                        >
                            <template v-slot:prepend-inner>
                                <User style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                        </v-text-field>

                        <!-- Email Field -->
                        <v-text-field
                            v-model="form.email"
                            label="Электронная почта"
                            type="email"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :error-messages="form.errors.email"
                            required
                        >
                            <template v-slot:prepend-inner>
                                <Mail style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                        </v-text-field>

                        <!-- Password Field -->
                        <v-text-field
                            v-model="form.password"
                            label="Пароль"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :placeholder="editingUser ? 'Оставьте пустым для сохранения текущего' : ''"
                            :error-messages="form.errors.password"
                            :required="!editingUser"
                        >
                            <template v-slot:prepend-inner>
                                <Lock style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                        </v-text-field>

                        <!-- Password Confirmation -->
                        <v-text-field
                            v-model="form.password_confirmation"
                            label="Подтверждение пароля"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :required="!!form.password"
                        >
                            <template v-slot:prepend-inner>
                                <Lock style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                        </v-text-field>

                        <!-- Role Field -->
                        <v-select
                            v-model="form.role"
                            :items="roles"
                            item-title="name"
                            item-value="name"
                            label="Роль пользователя"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :error-messages="form.errors.role"
                            required
                        >
                            <template v-slot:prepend-inner>
                                <Shield style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                            <template v-slot:item="{ props, item }">
                                <v-list-item v-bind="props" :title="getRoleLabel(item.value)"></v-list-item>
                            </template>
                            <template v-slot:selection="{ item }">
                                {{ getRoleLabel(item.value) }}
                            </template>
                        </v-select>

                        <!-- Branch Field -->
                        <v-select
                            v-model="form.branch_id"
                            :items="branches"
                            item-title="name"
                            item-value="id"
                            label="Привязка к филиалу"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :error-messages="form.errors.branch_id"
                            clearable
                            placeholder="Все филиалы (Администратор/HR)"
                        >
                            <template v-slot:prepend-inner>
                                <Building2 style="width: 18px; height: 18px;" class="text-grey mr-2" />
                            </template>
                        </v-select>
                    </v-card-text>

                    <v-divider class="my-3"></v-divider>

                    <v-card-actions class="px-2">
                        <v-spacer></v-spacer>
                        <v-btn
                            variant="text"
                            rounded="lg"
                            class="px-4 font-weight-bold"
                            @click="dialog = false"
                        >
                            Отмена
                        </v-btn>
                        <v-btn
                            type="submit"
                            color="indigo"
                            variant="elevated"
                            rounded="lg"
                            class="px-5 font-weight-bold text-white bg-indigo"
                            :loading="form.processing"
                        >
                            Сохранить
                        </v-btn>
                    </v-card-actions>
                </form>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="480px" persistent>
            <v-card class="rounded-xl border pa-4">
                <v-card-title class="d-flex align-center font-weight-black text-error text-h5">
                    <AlertTriangle style="width: 28px; height: 28px; margin-right: 12px;" class="text-error animate-pulse" />
                    <span>Удалить пользователя?</span>
                </v-card-title>

                <v-card-text class="py-4 text-body-1 text-grey-darken-3 font-weight-medium">
                    Вы действительно хотите удалить пользователя <strong class="text-indigo-darken-4">{{ userToDelete?.name }}</strong>? Он потеряет доступ к системе. Это действие можно отменить в Корзине.
                </v-card-text>

                <v-divider class="my-2"></v-divider>

                <v-card-actions class="px-2">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        rounded="lg"
                        class="px-4 font-weight-bold"
                        @click="deleteDialog = false"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        rounded="lg"
                        class="px-5 font-weight-bold text-white bg-error"
                        :loading="form.processing"
                        @click="confirmDelete"
                    >
                        Удалить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.search-field {
    max-width: 100%;
}
.hover-row:hover {
    background-color: rgba(224, 231, 255, 0.25) !important;
}
.transition-hover-btn {
    transition: all 0.2s ease-in-out;
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
}
</style>
