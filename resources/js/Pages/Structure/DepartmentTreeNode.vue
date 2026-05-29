<script setup>
import DepartmentTreeNode from '@/Pages/Structure/DepartmentTreeNode.vue';
import { Plus, MoreVertical, Pencil, Trash2 } from '@lucide/vue';

defineProps({
    department: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    },
    canManage: {
        type: Function,
        required: true,
    },
    canDelete: {
        type: Function,
        required: true,
    },
    canCreate: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete', 'add-child']);
</script>

<template>
    <div class="department-node" :style="{ marginLeft: `${level * 20}px` }">
        <div class="d-flex align-center justify-space-between py-3 px-2 rounded-lg department-row">
            <div class="d-flex align-center ga-3">
                <v-chip
                    v-if="department.code"
                    color="indigo"
                    variant="flat"
                    size="small"
                    class="font-weight-black text-uppercase"
                >
                    {{ department.code }}
                </v-chip>
                <span class="text-body-1 font-weight-bold text-indigo-darken-3">{{ department.name }}</span>
                <v-chip
                    v-if="department.children_count > 0"
                    color="teal"
                    variant="tonal"
                    size="small"
                    class="font-weight-bold"
                >
                    {{ department.children_count }} зершуъба
                </v-chip>
            </div>

            <div class="d-flex align-center ga-1">
                <v-btn
                    v-if="canCreate"
                    icon
                    variant="text"
                    size="small"
                    title="Илова кардани зершуъба"
                    @click="emit('add-child', department.id)"
                >
                    <Plus style="width: 16px; height: 16px;" />
                </v-btn>

                <v-menu v-if="canManage(department) || canDelete(department)">
                    <template v-slot:activator="{ props: menuProps }">
                        <v-btn icon variant="text" size="small" v-bind="menuProps">
                            <MoreVertical style="width: 16px; height: 16px;" />
                        </v-btn>
                    </template>
                    <v-list density="comfortable" rounded="xl" class="border py-1">
                        <v-list-item
                            v-if="canManage(department)"
                            title="Таҳрир"
                            class="font-weight-bold"
                            @click="emit('edit', department)"
                        >
                            <template v-slot:prepend>
                                <Pencil style="width: 16px; height: 16px; margin-right: 8px;" class="text-primary" />
                            </template>
                        </v-list-item>
                        <v-list-item
                            v-if="canDelete(department)"
                            title="Нест кардан"
                            class="text-error font-weight-bold"
                            :disabled="department.children_count > 0"
                            @click="emit('delete', department)"
                        >
                            <template v-slot:prepend>
                                <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                            </template>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>
        </div>

        <DepartmentTreeNode
            v-for="child in department.children"
            :key="child.id"
            :department="child"
            :level="level + 1"
            :can-manage="canManage"
            :can-delete="canDelete"
            :can-create="canCreate"
            @edit="emit('edit', $event)"
            @delete="emit('delete', $event)"
            @add-child="emit('add-child', $event)"
        />
    </div>
</template>

<style scoped>
.department-row {
    transition: background-color 0.2s ease;
}
.department-row:hover {
    background: rgba(99, 102, 241, 0.06);
}
</style>
