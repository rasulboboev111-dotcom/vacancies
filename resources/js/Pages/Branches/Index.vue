<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    branches: {
        type: Array,
        required: true,
    },
});

const dialog = ref(false);
const deleteDialog = ref(false);
const editingBranch = ref(null);
const branchToDelete = ref(null);

const form = useForm({
    name: '',
    code: '',
    address: '',
});

function openCreateDialog() {
    editingBranch.value = null;
    form.reset();
    form.clearErrors();
    dialog.value = true;
}

function openEditDialog(branch) {
    editingBranch.value = branch;
    form.name = branch.name;
    form.code = branch.code;
    form.address = branch.address || '';
    form.clearErrors();
    dialog.value = true;
}

function openDeleteDialog(branch) {
    branchToDelete.value = branch;
    deleteDialog.value = true;
}

function submit() {
    if (editingBranch.value) {
        form.put(route('branches.update', editingBranch.value.id), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('branches.store'), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    }
}

function confirmDelete() {
    if (branchToDelete.value) {
        form.delete(route('branches.destroy', branchToDelete.value.id), {
            onSuccess: () => {
                deleteDialog.value = false;
                branchToDelete.value = null;
            },
        });
    }
}
</script>

<template>
    <Head title="Филиалы" />

    <AuthenticatedLayout>
        <template #header>
            Управление филиалами
        </template>

        <!-- Search / Actions Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" class="d-flex justify-end">
                <v-btn
                    v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')"
                    color="primary"
                    prepend-icon="mdi-plus"
                    rounded="lg"
                    elevation="2"
                    @click="openCreateDialog"
                >
                    Добавить филиал
                </v-btn>
            </v-col>
        </v-row>

        <!-- Branches Grid -->
        <v-row>
            <v-col v-for="branch in branches" :key="branch.id" cols="12" sm="6" md="4">
                <v-card elevation="2" class="rounded-xl overflow-hidden pa-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-space-between align-start mb-2">
                        <div>
                            <v-chip color="primary" variant="flat" size="small" class="font-weight-medium mb-2">
                                {{ branch.code }}
                            </v-chip>
                            <h3 class="text-h6 font-weight-bold">{{ branch.name }}</h3>
                        </div>
                        
                        <!-- Actions menu for Admin/HR Manager -->
                        <v-menu v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')">
                            <template v-slot:activator="{ props }">
                                <v-btn icon="mdi-dots-vertical" variant="text" size="small" v-bind="props"></v-btn>
                            </template>
                            <v-list density="compact" rounded="lg">
                                <v-list-item
                                    prepend-icon="mdi-pencil"
                                    title="Редактировать"
                                    @click="openEditDialog(branch)"
                                ></v-list-item>
                                <v-list-item
                                    prepend-icon="mdi-delete"
                                    title="Удалить"
                                    class="text-error"
                                    @click="openDeleteDialog(branch)"
                                ></v-list-item>
                            </v-list>
                        </v-menu>
                    </div>

                    <p class="text-body-2 text-grey-darken-1 mb-4 flex-grow-1">
                        <v-icon icon="mdi-map-marker" size="small" class="mr-1"></v-icon>
                        {{ branch.address || 'Адрес не указан' }}
                    </p>

                    <v-divider class="my-3"></v-divider>

                    <div class="d-flex justify-space-between align-center">
                        <span class="text-body-2 text-grey-darken-1 font-weight-medium">Сотрудники</span>
                        <v-chip color="primary" variant="tonal" size="small" class="font-weight-bold">
                            {{ branch.employees_count }} чел.
                        </v-chip>
                    </div>
                </v-card>
            </v-col>

            <v-col v-if="branches.length === 0" cols="12" class="text-center py-12">
                <v-icon icon="mdi-office-building-off" size="x-large" color="grey" class="mb-2"></v-icon>
                <div class="text-h6 text-grey">Филиалы не найдены.</div>
            </v-col>
        </v-row>

        <!-- Create / Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500px" persistent>
            <v-card class="rounded-xl pa-4">
                <v-card-title class="font-weight-bold text-h6 px-2">
                    {{ editingBranch ? 'Редактировать филиал' : 'Добавить филиал' }}
                </v-card-title>
                
                <v-card-text class="px-2 pt-4">
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
                            label="Название филиала"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.name"
                            class="mb-3"
                        ></v-text-field>

                        <v-text-field
                            v-model="form.code"
                            label="Код филиала (например, DSH)"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.code"
                            class="mb-3"
                        ></v-text-field>

                        <v-text-field
                            v-model="form.address"
                            label="Адрес"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            :error-messages="form.errors.address"
                            class="mb-3"
                        ></v-text-field>
                    </v-form>
                </v-card-text>

                <v-card-actions class="px-2 pt-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        rounded="lg"
                        @click="dialog = false"
                        :disabled="form.processing"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        rounded="lg"
                        @click="submit"
                        :loading="form.processing"
                    >
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400px">
            <v-card class="rounded-xl pa-4">
                <v-card-title class="font-weight-bold text-h6 px-2 text-error">
                    Подтверждение удаления
                </v-card-title>
                <v-card-text class="px-2 pt-2 text-body-1">
                    Вы уверены, что хотите удалить филиал <strong>{{ branchToDelete?.name }}</strong>? Все связанные сотрудники будут также удалены!
                </v-card-text>
                <v-card-actions class="px-2 pt-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        rounded="lg"
                        @click="deleteDialog = false"
                        :disabled="form.processing"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        @click="confirmDelete"
                        :loading="form.processing"
                    >
                        Удалить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>
