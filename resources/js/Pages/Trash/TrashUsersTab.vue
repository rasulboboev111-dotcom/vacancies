<script setup>
import { Mail, RotateCcw, Search, Trash2, User } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/lib/date';

const props = defineProps({
    users: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
    currentUserId: { type: Number, default: null },
});

defineEmits(['restore', 'force']);

const search = ref('');

const filtered = computed(() => {
    if (!search.value)
        return props.users;
    const q = search.value.toLowerCase();
    return props.users.filter(u =>
        u.name?.toLowerCase().includes(q)
        || u.email?.toLowerCase().includes(q)
        || u.branch?.name?.toLowerCase().includes(q),
    );
});
</script>

<template>
    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
        <div class="text-subtitle-1 font-weight-bold text-grey-darken-4 mb-3 mb-sm-0">
            Корбарони несткардашуда
        </div>
        <div class="trash-search">
            <span class="trash-search__label">Ҷустуҷӯи зуд аз рӯи ном ё email</span>
            <v-text-field
                v-model="search"
                :prepend-inner-icon="Search"
                placeholder="Ном ё email..."
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
                clearable
                color="rose"
            />
        </div>
    </div>

    <v-table class="table-modern border rounded-xl overflow-hidden">
        <thead>
            <tr class="bg-slate-50">
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Ному насаб / Логин
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    E-mail
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Филиал
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Несткардашуда
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 120px;">
                    Амалҳо
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="usr in filtered" :key="usr.id" class="trash-row">
                <td class="pa-4 font-weight-bold text-slate-800">
                    {{ usr.name }}
                </td>
                <td class="pa-4 text-body-2 font-weight-medium">
                    <div class="d-flex align-center">
                        <Mail style="width: 14px; height: 14px; margin-right: 6px;" class="text-grey" />
                        {{ usr.email }}
                    </div>
                </td>
                <td class="pa-4 text-body-2">
                    {{ usr.branch?.name || 'Ҳамаи филиалҳо / Глобалӣ' }}
                </td>
                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                    {{ formatDateTime(usr.deleted_at) }}
                </td>
                <td class="pa-4 text-center">
                    <div class="d-flex justify-center ga-2">
                        <v-btn
                            v-if="canManage"
                            variant="text"
                            size="small"
                            icon
                            rounded="lg"
                            class="trash-action-btn trash-action-btn--restore"
                            @click="$emit('restore', usr)"
                        >
                            <RotateCcw style="width: 16px; height: 16px;" />
                            <v-tooltip activator="parent" location="top">
                                Барқарор кардан
                            </v-tooltip>
                        </v-btn>

                        <!-- Only Admin can force delete users -->
                        <v-btn
                            v-if="isAdmin && currentUserId !== usr.id"
                            variant="text"
                            size="small"
                            icon
                            rounded="lg"
                            class="trash-action-btn trash-action-btn--delete"
                            @click="$emit('force', usr)"
                        >
                            <Trash2 style="width: 16px; height: 16px;" />
                            <v-tooltip activator="parent" location="top">
                                Нест кардан
                            </v-tooltip>
                        </v-btn>
                        <span v-else-if="currentUserId === usr.id" class="text-caption text-grey">Сеанси ҷорӣ</span>
                        <span v-else-if="!canManage" class="text-caption text-grey">Танҳо тамошо</span>
                        <span v-else class="text-caption text-grey font-weight-medium">Танҳо барои Админ</span>
                    </div>
                </td>
            </tr>
            <tr v-if="filtered.length === 0">
                <td colspan="5" class="text-center py-12">
                    <div class="d-flex flex-column align-center justify-center text-grey">
                        <User style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                        <div class="font-weight-medium">
                            {{ search ? 'Мутобиқат дар сабад ёфт нашуд' : 'Сабади корбарон холӣ аст' }}
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </v-table>
</template>
