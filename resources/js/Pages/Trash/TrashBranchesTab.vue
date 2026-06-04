<script setup>
import { Building2, RotateCcw, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/lib/date';

const props = defineProps({
    branches: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
});

defineEmits(['restore', 'force']);

const search = ref('');

const filtered = computed(() => {
    if (!search.value)
        return props.branches;
    const q = search.value.toLowerCase();
    return props.branches.filter(b => b.name?.toLowerCase().includes(q));
});
</script>

<template>
    <div class="d-flex flex-column flex-sm-row justify-space-between align-sm-center mb-6">
        <div class="text-subtitle-1 font-weight-bold text-indigo-darken-4 mb-3 mb-sm-0">
            Филиалҳои несткардашуда
        </div>
        <v-text-field
            v-model="search"
            :prepend-inner-icon="Search"
            label="Ҷустуҷӯи зуд аз рӯи ном..."
            variant="outlined"
            density="comfortable"
            rounded="lg"
            hide-details
            clearable
            color="rose"
            style="max-width: 380px; width: 100%;"
        />
    </div>

    <v-table class="table-modern border rounded-xl overflow-hidden">
        <thead>
            <tr class="bg-slate-50">
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Номи филиал
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose">
                    Несткардашуда
                </th>
                <th class="font-weight-black text-subtitle-2 pa-4 text-rose text-center" style="width: 180px;">
                    Амалҳо
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="branch in filtered" :key="branch.id" class="trash-row">
                <td class="pa-4 font-weight-bold text-slate-800">
                    {{ branch.name }}
                </td>
                <td class="pa-4 text-body-2 font-weight-bold text-rose-darken-2">
                    {{ formatDateTime(branch.deleted_at) }}
                </td>
                <td class="pa-4 text-center">
                    <div class="d-flex justify-center g-2">
                        <template v-if="isAdmin">
                            <v-btn
                                color="success"
                                variant="tonal"
                                size="small"
                                rounded="lg"
                                class="mr-2 hover-scale-btn font-weight-bold"
                                @click="$emit('restore', branch)"
                            >
                                <template #prepend>
                                    <RotateCcw style="width: 14px; height: 14px;" />
                                </template>
                                Барқарор кардан
                            </v-btn>
                            <v-btn
                                color="error"
                                variant="tonal"
                                size="small"
                                rounded="lg"
                                class="hover-scale-btn font-weight-bold"
                                @click="$emit('force', branch)"
                            >
                                <template #prepend>
                                    <Trash2 style="width: 14px; height: 14px;" />
                                </template>
                                Нест кардан
                            </v-btn>
                        </template>
                        <span v-else class="text-caption text-grey font-weight-medium">
                            Танҳо барои Администраторон дастрас аст
                        </span>
                    </div>
                </td>
            </tr>
            <tr v-if="filtered.length === 0">
                <td colspan="3" class="text-center py-12">
                    <div class="d-flex flex-column align-center justify-center text-grey">
                        <Building2 style="width: 48px; height: 48px;" class="opacity-30 mb-2" />
                        <div class="font-weight-medium">
                            {{ search ? 'Мутобиқат дар сабад ёфт нашуд' : 'Сабади филиалҳо холӣ аст' }}
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </v-table>
</template>
