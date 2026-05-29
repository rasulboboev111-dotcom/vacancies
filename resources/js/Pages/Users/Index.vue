<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
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

const roleLabels = {
    Admin: 'Маъмур',
    User: 'Корбар',
};

const getRoleLabel = (roleName) => {
    if (!roleName) return 'Нақш надорад';
    return roleLabels[roleName] ?? roleName;
};

const roleOptions = computed(() =>
    (props.roles ?? []).map((role) => ({
        value: role.name,
        title: role.label ?? getRoleLabel(role.name),
    }))
);

const getRoleColor = (roleName) => {
    if (!roleName) return 'grey';
    const colorsMap = {
        'Admin': 'red',
        'User': 'indigo',
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
    <Head title="Корбарон" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Shield style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Идоракунии корбарон</span>
            </div>
        </template>

        <!-- Search and Action Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" md="6">
                <v-text-field
                    v-model="search"
                    label="Ҷустуҷӯ аз рӯи ном ё почтаи электронӣ..."
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
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                    @click="openCreateDialog"
                >
                    <template v-slot:prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                    </template>
                    Илова кардани корбар
                </v-btn>
            </v-col>
        </v-row>

        <!-- Users Table -->
        <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden">
            <v-table class="bg-transparent text-left">
                <thead>
                    <tr class="bg-indigo-lighten-5">
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Корбар</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Почтаи электронӣ</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Нақш</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Филиал</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase">Бақайдгирӣ</th>
                        <th class="font-weight-black text-indigo-darken-4 py-4 px-6 text-subtitle-2 text-uppercase text-right">Амалҳо</th>
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
                                    <div v-if="user.id === $page.props.auth.user.id" class="text-caption text-indigo font-weight-bold">Ин шумо</div>
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
                            <span v-else class="text-grey font-weight-bold text-caption text-uppercase">Ҳамаи филиалҳо</span>
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
                                        title="Таҳрир"
                                        class="font-weight-bold"
                                        @click="openEditDialog(user)"
                                    >
                                        <template v-slot:prepend>
                                            <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                        </template>
                                    </v-list-item>
                                    <v-list-item
                                        v-if="user.id !== $page.props.auth.user.id"
                                        title="Нест кардан"
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
                                Корбарон ёфт нашуданд.
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
                    <span>{{ editingUser ? 'Таҳрири корбар' : 'Эҷод кардани корбар' }}</span>
                </v-card-title>
                
                <v-divider class="my-3"></v-divider>

                <form @submit.prevent="submit">
                    <v-card-text class="pa-2">
                        <!-- Name Field -->
                        <v-text-field
                            v-model="form.name"
                            label="Номи корбар"
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
                            label="Почтаи электронӣ"
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
                            label="Парол"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :placeholder="editingUser ? 'Барои нигоҳ доштани пароли ҷорӣ холӣ гузоред' : ''"
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
                            label="Тасдиқи парол"
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
                            :items="roleOptions"
                            item-title="title"
                            item-value="value"
                            label="Нақши корбар"
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
                        </v-select>

                        <!-- Branch Field -->
                        <v-select
                            v-model="form.branch_id"
                            :items="branches"
                            item-title="name"
                            item-value="id"
                            label="Пайвасткунӣ ба филиал"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="mb-3"
                            color="indigo"
                            :error-messages="form.errors.branch_id"
                            clearable
                            :required="form.role === 'User'"
                            placeholder="Барои нақши «Корбар» ҳатмист"
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
                            Бекор кардан
                        </v-btn>
                        <v-btn
                            type="submit"
                            color="indigo"
                            variant="elevated"
                            rounded="lg"
                            class="px-5 font-weight-bold text-white bg-indigo"
                            :loading="form.processing"
                        >
                            Захира кардан
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
                    <span>Корбар нест карда шавад?</span>
                </v-card-title>

                <v-card-text class="py-4 text-body-1 text-grey-darken-3 font-weight-medium">
                    Шумо мутмаин ҳастед, ки мехоҳед корбари <strong class="text-indigo-darken-4">{{ userToDelete?.name }}</strong>-ро нест кунед? Ӯ дастрасӣ ба системаро аз даст медиҳад. Ин амалро дар Сабад бекор кардан мумкин аст.
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
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        rounded="lg"
                        class="px-5 font-weight-bold text-white bg-error"
                        :loading="form.processing"
                        @click="confirmDelete"
                    >
                        Нест кардан
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
