<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Search, Shield } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserDeleteDialog from '@/Pages/Users/UserDeleteDialog.vue';
import UserFormDialog from '@/Pages/Users/UserFormDialog.vue';
import UsersTable from '@/Pages/Users/UsersTable.vue';

const props = defineProps({
    users: { type: Object, required: true },
    branches: { type: Array, required: true },
    roles: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const search = ref(props.filters.search || '');
const dialog = ref(false);
const deleteDialog = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);

function filterQuery() {
    return { filter: { search: search.value || undefined } };
}

function applyFilters() {
    router.get(route('users.index'), filterQuery(), {
        preserveState: true,
        replace: true,
    });
}

function changePage(p) {
    router.get(route('users.index'), { page: p, ...filterQuery() }, {
        preserveState: true,
    });
}

// Auto-apply the text search while typing, debounced so we don't fire a
// request on every keystroke (@vueuse/core).
watchDebounced(search, applyFilters, { debounce: 300 });

function openCreateDialog() {
    editingUser.value = null;
    dialog.value = true;
}

function openEditDialog(user) {
    editingUser.value = user;
    dialog.value = true;
}

function openDeleteDialog(user) {
    userToDelete.value = user;
    deleteDialog.value = true;
}
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
        <v-row class="mb-6 align-end">
            <v-col cols="12" md="6">
                <label class="filter-label">Ҷустуҷӯ</label>
                <v-text-field
                    v-model="search"
                    placeholder="Аз рӯи ном ё почтаи электронӣ..."
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    hide-details
                    clearable
                    color="indigo"
                    class="search-field bg-white"
                >
                    <template #prepend-inner>
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
                    <template #prepend>
                        <Plus style="width: 16px; height: 16px; margin-right: 4px; color: #ffffff;" />
                    </template>
                    Илова кардани корбар
                </v-btn>
            </v-col>
        </v-row>

        <UsersTable
            :users="users.data"
            :current-user-id="currentUserId"
            @edit="openEditDialog"
            @delete="openDeleteDialog"
        />

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="d-flex justify-center mt-6">
            <v-pagination
                :length="users.last_page"
                :model-value="users.current_page"
                :total-visible="7"
                rounded="circle"
                active-color="indigo"
                @update:model-value="changePage"
            />
        </div>

        <UserFormDialog v-model="dialog" :user="editingUser" :branches="branches" :roles="roles" />
        <UserDeleteDialog v-model="deleteDialog" :user="userToDelete" />
    </AuthenticatedLayout>
</template>

<style scoped>
.search-field {
    max-width: 100%;
}
</style>
