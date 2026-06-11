<script setup>
import { Building2, ChevronRight, MoreVertical, Pencil, Plus, Trash2, Workflow } from '@lucide/vue';
import { computed, ref } from 'vue';
import DepartmentTreeNode from '@/Pages/Structure/DepartmentTreeNode.vue';

const props = defineProps({
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

// Зершуъбаҳо скрыты до запроса, чтобы глубокая ветка читалась как короткий
// список заголовков, а не как сплошная стена строк.
const hasChildren = computed(() => (props.department.children?.length ?? 0) > 0);
const open = ref(false);

function toggle() {
    if (hasChildren.value) {
        open.value = !open.value;
    }
}
</script>

<template>
    <div class="department-node" :style="{ marginLeft: `${level * 20}px` }">
        <div class="d-flex align-center justify-space-between py-3 px-2 rounded-lg department-row">
            <div
                class="d-flex align-center ga-3 flex-grow-1 department-label"
                :class="{ 'department-label--clickable': hasChildren }"
                @click="toggle"
            >
                <ChevronRight
                    v-if="hasChildren"
                    class="dept-chevron"
                    :class="{ 'dept-chevron--open': open }"
                    style="width: 18px; height: 18px;"
                />
                <span v-else class="dept-chevron-spacer" />
                <span class="dept-icon" :class="level === 0 ? 'dept-icon--branch' : 'dept-icon--dept'">
                    <Building2 v-if="level === 0" style="width: 16px; height: 16px;" />
                    <Workflow v-else style="width: 16px; height: 16px;" />
                </span>
                <span class="text-body-1 font-weight-bold dept-name">{{ department.name }}</span>
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
                    <template #activator="{ props: menuProps }">
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
                            <template #prepend>
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
                            <template #prepend>
                                <Trash2 style="width: 16px; height: 16px; margin-right: 8px;" class="text-error" />
                            </template>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>
        </div>

        <template v-if="open">
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
        </template>
    </div>
</template>

<style scoped>
.department-row {
    transition: background-color 0.2s ease;
}
.department-row:hover {
    background: rgba(99, 102, 241, 0.06);
}
.department-label--clickable {
    cursor: pointer;
    user-select: none;
}
.dept-chevron {
    flex-shrink: 0;
    color: #6366f1;
    transition: transform 0.2s ease;
}
.dept-chevron--open {
    transform: rotate(90deg);
}
.dept-chevron-spacer {
    display: inline-block;
    width: 18px;
    flex-shrink: 0;
}
.dept-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    flex-shrink: 0;
}
.dept-icon--branch {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.dept-icon--dept {
    background: rgba(34, 197, 94, 0.12);
    color: #22c55e;
}
.dept-name {
    color: #1a1a2e;
}
</style>
