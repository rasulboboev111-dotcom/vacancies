<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    Building2, 
    Plus, 
    MoreVertical, 
    Pencil, 
    Trash2, 
    MapPin, 
    Users, 
    AlertTriangle 
} from '@lucide/vue';

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
            <div class="d-flex align-center">
                <Building2 style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Управление филиалами</span>
            </div>
        </template>

        <!-- Action Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" class="d-flex justify-end">
                <v-btn
                    v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')"
                    color="indigo"
                    rounded="lg"
                    elevation="2"
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold"
                    @click="openCreateDialog"
                >
                    <template v-slot:prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px;" />
                    </template>
                    Добавить филиал
                </v-btn>
            </v-col>
        </v-row>

        <!-- Branches Grid -->
        <v-row>
            <v-col v-for="branch in branches" :key="branch.id" cols="12" sm="6" md="4">
                <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column bg-surface-glass transition-hover position-relative overflow-hidden">
                    <div class="d-flex justify-space-between align-start mb-3">
                        <div>
                            <v-chip color="indigo" variant="flat" size="small" class="font-weight-black text-uppercase tracking-wider mb-2">
                                {{ branch.code }}
                            </v-chip>
                            <h3 class="text-h6 font-weight-black text-indigo-darken-3">{{ branch.name }}</h3>
                        </div>
                        
                        <!-- Actions menu for Admin/HR Manager -->
                        <v-menu v-if="$page.props.auth.user.roles.includes('Admin') || $page.props.auth.user.roles.includes('HR Manager')">
                            <template v-slot:activator="{ props: menuProps }">
                                <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                                    <MoreVertical style="width: 16px; height: 16px;" />
                                </v-btn>
                            </template>
                            <v-list density="comfortable" rounded="xl" class="border py-1">
                                <v-list-item
                                    title="Редактировать"
                                    class="font-weight-bold"
                                    @click="openEditDialog(branch)"
                                >
                                    <template v-slot:prepend>
                                        <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                    </template>
                                </v-list-item>
                                <v-list-item
                                    title="Удалить"
                                    class="text-error font-weight-bold"
                                    @click="openDeleteDialog(branch)"
                                >
                                    <template v-slot:prepend>
                                        <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                    </div>

                    <p class="text-body-2 text-grey-darken-2 mb-4 flex-grow-1 font-weight-medium d-flex align-center">
                        <MapPin style="width: 16px; height: 16px; margin-right: 8px;" class="text-indigo" />
                        {{ branch.address || 'Адрес не указан' }}
                    </p>

                    <v-divider class="my-3"></v-divider>

                    <div class="d-flex justify-space-between align-center">
                        <span class="text-subtitle-2 text-grey font-weight-bold text-uppercase">Штат</span>
                        <v-chip color="teal" variant="tonal" class="font-weight-black px-3" size="medium">
                            <Users style="width: 16px; height: 16px; margin-right: 4px;" class="text-teal" />
                            {{ branch.employees_count }} чел.
                        </v-chip>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
            </v-col>

            <v-col v-if="branches.length === 0" cols="12" class="text-center py-12">
                <Building2 style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                <div class="text-h6 text-grey font-weight-medium">Филиалы не найдены.</div>
            </v-col>
        </v-row>

        <v-dialog v-model="dialog" max-width="520px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header -->
                <div style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <Building2 style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">{{ editingBranch ? 'Редактирование' : 'Новый филиал' }}</div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">{{ editingBranch ? form.name || 'Филиал' : 'Добавить филиал' }}</div>
                        </div>
                    </div>
                </div>
                
                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
                            label="Название филиала"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.name"
                            class="mb-4"
                        ></v-text-field>

                        <v-text-field
                            v-model="form.code"
                            label="Код филиала (например, DSH)"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.code"
                            class="mb-4"
                        ></v-text-field>

                        <v-text-field
                            v-model="form.address"
                            label="Адрес"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            :error-messages="form.errors.address"
                        ></v-text-field>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="dialog = false"
                        :disabled="form.processing"
                        class="px-6 font-weight-bold"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        @click="submit"
                        :loading="form.processing"
                        class="px-6 font-weight-bold"
                        style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    >
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="deleteDialog" max-width="460px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Red Gradient Header -->
                <div style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Подтверждение</div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">Удаление филиала</div>
                        </div>
                    </div>
                </div>
                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    Вы уверены, что хотите удалить филиал <strong class="text-red-darken-2">{{ branchToDelete?.name }}</strong>?
                    <div class="mt-3 pa-3 rounded-lg d-flex align-center" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
                        <span class="text-error font-weight-bold text-body-2">Все связанные сотрудники будут также безвозвратно удалены!</span>
                    </div>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="deleteDialog = false"
                        :disabled="form.processing"
                        class="px-6 font-weight-bold"
                    >
                        Отмена
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        class="px-6 font-weight-bold"
                        @click="confirmDelete"
                        :loading="form.processing"
                        style="box-shadow: 0 8px 20px -6px rgba(239, 68, 68, 0.4);"
                    >
                        <template v-slot:prepend>
                            <Trash2 style="width: 18px; height: 18px;" />
                        </template>
                        Удалить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.transition-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px -10px rgba(79, 70, 229, 0.15);
}
.transition-hover-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-hover-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}
.hover-scale-btn {
    transition: all 0.2s ease;
}
.hover-scale-btn:hover {
    transform: scale(1.15);
}
.tracking-wider {
    letter-spacing: 0.05em;
}
.glass-shine {
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0) 100%);
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
}
</style>
