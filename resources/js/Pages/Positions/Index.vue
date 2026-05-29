<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Briefcase, 
    Plus, 
    MoreVertical, 
    Pencil, 
    Trash2, 
    Users, 
    AlertTriangle 
} from '@lucide/vue';

const props = defineProps({
    positions: {
        type: Array,
        required: true,
    },
});

const search = ref('');
const dialog = ref(false);
const deleteDialog = ref(false);
const editingPosition = ref(null);
const positionToDelete = ref(null);

const form = useForm({
    name: '',
});

// Search filter
const filteredPositions = computed(() => {
    if (!search.value) return props.positions;
    const q = search.value.toLowerCase();
    return props.positions.filter(p => p.name?.toLowerCase().includes(q));
});

function openCreateDialog() {
    editingPosition.value = null;
    form.reset();
    form.clearErrors();
    dialog.value = true;
}

function openEditDialog(position) {
    editingPosition.value = position;
    form.name = position.name;
    form.clearErrors();
    dialog.value = true;
}

function openDeleteDialog(position) {
    positionToDelete.value = position;
    deleteDialog.value = true;
}

function submit() {
    if (editingPosition.value) {
        form.put(route('positions.update', editingPosition.value.id), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('positions.store'), {
            onSuccess: () => {
                dialog.value = false;
                form.reset();
            },
        });
    }
}

function confirmDelete() {
    if (positionToDelete.value) {
        form.delete(route('positions.destroy', positionToDelete.value.id), {
            onSuccess: () => {
                deleteDialog.value = false;
                positionToDelete.value = null;
            },
        });
    }
}
</script>

<template>
    <Head title="Вазифаҳо" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex align-center">
                <Briefcase style="width: 24px; height: 24px; margin-right: 12px;" class="text-indigo-accent-2" />
                <span>Идоракунии вазифаҳо</span>
            </div>
        </template>

        <!-- Search and Action Bar -->
        <v-row class="mb-6 align-center">
            <v-col cols="12" md="6">
                <v-text-field
                    v-model="search"
                    prepend-inner-icon="mdi-magnify"
                    label="Ҷустуҷӯ аз рӯи номи вазифа..."
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                    clearable
                    color="indigo"
                    class="search-field"
                ></v-text-field>
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-md-end">
                <v-btn
                    v-if="$page.props.auth.user.roles.includes('Admin')"
                    color="indigo"
                    rounded="lg"
                    elevation="2"
                    class="px-5 bg-indigo transition-hover-btn font-weight-bold text-white"
                    @click="openCreateDialog"
                >
                    <template v-slot:prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                    </template>
                    Илова кардани вазифа
                </v-btn>
            </v-col>
        </v-row>

        <!-- Positions Grid -->
        <v-row>
            <v-col v-for="position in filteredPositions" :key="position.id" cols="12" sm="6" md="4">
                <v-card elevation="0" class="rounded-xl border pa-5 h-100 d-flex flex-column bg-surface-glass transition-hover position-relative overflow-hidden">
                    <div class="d-flex justify-space-between align-start mb-3">
                        <div class="d-flex align-center">
                            <v-avatar color="indigo-lighten-5" class="mr-3" size="40">
                                <Briefcase style="width: 20px; height: 20px;" class="text-indigo" />
                            </v-avatar>
                            <div>
                                <h3 class="text-h6 font-weight-black text-indigo-darken-3">{{ position.name }}</h3>
                            </div>
                        </div>
                        
                        <!-- Actions menu for Admin -->
                        <v-menu v-if="$page.props.auth.user.roles.includes('Admin')">
                            <template v-slot:activator="{ props: menuProps }">
                                <v-btn icon variant="text" size="small" class="hover-scale-btn" v-bind="menuProps">
                                    <MoreVertical style="width: 16px; height: 16px;" />
                                </v-btn>
                            </template>
                            <v-list density="comfortable" rounded="xl" class="border py-1">
                                <v-list-item
                                    title="Таҳрир"
                                    class="font-weight-bold"
                                    @click="openEditDialog(position)"
                                >
                                    <template v-slot:prepend>
                                        <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                                    </template>
                                </v-list-item>
                                <v-list-item
                                    title="Нест кардан"
                                    class="text-error font-weight-bold"
                                    @click="openDeleteDialog(position)"
                                >
                                    <template v-slot:prepend>
                                        <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                    </div>

                    <v-divider class="my-3"></v-divider>

                    <div class="d-flex justify-space-between align-center flex-grow-1 align-end">
                        <span class="text-subtitle-2 text-grey font-weight-bold text-uppercase">Ҳайат</span>
                        <v-chip :color="position.employees_count > 0 ? 'teal' : 'grey'" variant="tonal" class="font-weight-black px-3" size="medium">
                            <template v-slot:prepend>
                                <Users style="width: 16px; height: 16px; margin-right: 4px;" :class="position.employees_count > 0 ? 'text-teal' : 'text-grey'" />
                            </template>
                            {{ position.employees_count }} нафар
                        </v-chip>
                    </div>
                    <div class="glass-shine"></div>
                </v-card>
            </v-col>

            <v-col v-if="filteredPositions.length === 0" cols="12" class="text-center py-12">
                <Briefcase style="width: 48px; height: 48px; margin: 0 auto 8px; opacity: 0.5;" class="text-grey" />
                <div class="text-h6 text-grey font-weight-medium">
                    {{ search ? 'Вазифа бо чунин ном ёфт нашуд' : 'Вазифаҳо ёфт нашуд.' }}
                </div>
            </v-col>
        </v-row>

        <!-- Edit/Create Dialog -->
        <v-dialog v-model="dialog" max-width="520px" persistent>
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Premium Gradient Header -->
                <div style="background: #0f2d88; padding: 20px 24px;">
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <Briefcase style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-4">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                {{ editingPosition ? 'Таҳрир' : 'Вазифаи нав' }}
                            </div>
                            <div style="color: white; font-size: 1.1rem; font-weight: 800;">
                                {{ editingPosition ? form.name || 'Вазифа' : 'Илова кардани вазифа' }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
                            label="Номи вазифа"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            required
                            :error-messages="form.errors.name"
                            class="mb-2"
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
                        Бекор кардан
                    </v-btn>
                    <v-btn
                        color="indigo"
                        variant="flat"
                        rounded="lg"
                        size="large"
                        @click="submit"
                        :loading="form.processing"
                        class="px-6 font-weight-bold text-white"
                        style="box-shadow: 0 8px 20px -6px rgba(79, 70, 229, 0.4);"
                    >
                        Захира кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog with smart inline verification -->
        <v-dialog v-model="deleteDialog" max-width="460px">
            <v-card class="rounded-xl overflow-hidden" elevation="8">
                <!-- Red/Yellow Gradient Header based on safety checks -->
                <div 
                    :style="{
                        background: positionToDelete?.employees_count > 0 
                            ? '#d97706'
                            : '#dc2626',
                        padding: '20px 24px'
                    }"
                >
                    <div class="d-flex align-center">
                        <v-avatar size="42" rounded="lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(4px);">
                            <AlertTriangle style="width: 22px; height: 22px; color: white;" />
                        </v-avatar>
                        <div class="ml-3">
                            <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
                                {{ positionToDelete?.employees_count > 0 ? 'Огоҳӣ' : 'Тасдиқ' }}
                            </div>
                            <div style="color: white; font-size: 1.05rem; font-weight: 800;">
                                {{ positionToDelete?.employees_count > 0 ? 'Нест кардан манъ аст' : 'Нест кардани вазифа' }}
                            </div>
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-6 text-body-1 text-grey-darken-3 font-weight-medium">
                    <template v-if="positionToDelete?.employees_count > 0">
                        Шумо наметавонед вазифаи <strong class="text-amber-darken-3">{{ positionToDelete?.name }}</strong>-ро нест кунед.
                        <div class="mt-4 pa-3 rounded-lg d-flex align-start" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2);">
                            <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #d97706; flex-shrink: 0; margin-top: 2px;" />
                            <span class="text-amber-darken-4 font-weight-bold text-body-2 leading-snug">
                                Ин вазифа ба кормандон таъин шудааст, ба шумораи: <strong>{{ positionToDelete?.employees_count }} нафар.</strong>
                                <br><span class="text-grey-darken-2 font-weight-medium">Аввал онҳоро дар бахши «Кормандон» ба вазифаҳои дигар гузаронед, баъд ин вазифаро нест кунед.</span>
                            </span>
                        </div>
                    </template>
                    <template v-else>
                        Шумо мутмаин ҳастед, ки мехоҳед вазифаи <strong>{{ positionToDelete?.name }}</strong>-ро бебозгашт нест кунед?
                        <div class="mt-3 pa-3 rounded-lg d-flex align-center" style="background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.15);">
                            <AlertTriangle style="width: 18px; height: 18px; margin-right: 8px; color: #ef4444; flex-shrink: 0;" />
                            <span class="text-error font-weight-bold text-body-2">Ин амал номи вазифаро аз пойгоҳи додаҳо пурра нест мекунад!</span>
                        </div>
                    </template>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-5 d-flex justify-end" style="gap: 12px;">
                    <v-btn
                        variant="tonal"
                        color="grey"
                        rounded="lg"
                        size="large"
                        @click="deleteDialog = false"
                        class="px-6 font-weight-bold"
                    >
                        {{ positionToDelete?.employees_count > 0 ? 'Фаҳмо' : 'Бекор кардан' }}
                    </v-btn>
                    <v-btn
                        v-if="!(positionToDelete?.employees_count > 0)"
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
                        Нест кардан
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.75) !important;
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
.glass-shine {
    position: absolute;
    top: 0;
    left: -50%;
    width: 100%;
    height: 100%;
    background: transparent;
    transform: skewX(-25deg);
    transition: 0.75s;
    pointer-events: none;
}
.v-card:hover .glass-shine {
    left: 120%;
}
.leading-snug {
    line-height: 1.35;
}
</style>
