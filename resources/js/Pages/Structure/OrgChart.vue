<script setup>
import { Building2, ChevronDown, ChevronRight, DoorOpen, Maximize2, Minimize2, Network, Users, Workflow } from '@lucide/vue';
import { useVueFlow, VueFlow } from '@vue-flow/core';
import { computed, nextTick, ref, watch } from 'vue';
import { buildOrgGraph, defaultExpanded } from '@/composables/useOrgChart';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';

const props = defineProps({
    structure: { type: Array, required: true },
});

const emit = defineEmits(['node-click']);

// Which nodes have their children drawn. Like the source site, the chart opens
// with the top three tiers expanded (organisation → branches → departments) and
// anything deeper collapsed behind a toggle.
const expanded = ref(defaultExpanded(props.structure));

const graph = computed(() => buildOrgGraph(props.structure, expanded.value));
const nodes = ref(graph.value.nodes);
const edges = ref(graph.value.edges);

const { fitView } = useVueFlow();

// Recompute on every expand/collapse and after Inertia reloads (CRUD), then
// refit so the visible subset is always framed.
watch(graph, (next) => {
    nodes.value = next.nodes;
    edges.value = next.edges;
    nextTick(() => fitView({ padding: 0.2 }));
});

// A fresh structure (Inertia reload after CRUD) resets back to the default tiers.
watch(() => props.structure, (next) => {
    expanded.value = defaultExpanded(next);
});

function onPaneReady() {
    fitView({ padding: 0.2 });
}

function toggleNode(uid) {
    const next = new Set(expanded.value);
    if (next.has(uid)) {
        next.delete(uid);
    }
    else {
        next.add(uid);
    }
    expanded.value = next;
}

// Every uid in the tree — used to open the whole chart in one click.
function allUids() {
    const set = new Set(['root']);
    const walkDept = (dept) => {
        set.add(`d-${dept.id}`);
        (dept.children || []).forEach(walkDept);
    };
    props.structure.forEach((branch) => {
        set.add(`b-${branch.id}`);
        (branch.departments || []).forEach(walkDept);
    });
    return set;
}

function expandAll() {
    expanded.value = allUids();
}

function collapseAll() {
    expanded.value = new Set();
}

function onNodeClick({ node }) {
    if (!node || node.data?.kind === 'root') {
        return;
    }
    emit('node-click', node);
}
</script>

<template>
    <v-card elevation="0" class="rounded-xl border bg-surface-glass overflow-hidden mb-6" style="height: 460px;">
        <div v-if="nodes.length === 0" class="text-center py-12 h-100 d-flex flex-column justify-center align-center">
            <Network style="width: 48px; height: 48px; margin-bottom: 8px; opacity: 0.5;" class="text-grey" />
            <div class="text-h6 text-grey font-weight-medium">
                Барои намоиши сохтор маълумот нест.
            </div>
        </div>

        <VueFlow
            v-else
            :nodes="nodes"
            :edges="edges"
            :nodes-connectable="false"
            :nodes-draggable="true"
            :elements-selectable="false"
            fit-view-on-init
            :min-zoom="0.2"
            :max-zoom="1.5"
            class="structure-flow"
            @pane-ready="onPaneReady"
            @node-click="onNodeClick"
        >
            <template #node-org="{ id, data }">
                <div class="org-node" :class="`org-node--${data.kind}`">
                    <button
                        v-if="data.hasChildren"
                        type="button"
                        class="org-node__toggle nodrag"
                        :title="data.expanded ? 'Печондани зершуъбаҳо' : 'Кушодани зершуъбаҳо'"
                        @click.stop="toggleNode(id)"
                        @mousedown.stop
                    >
                        <ChevronDown v-if="data.expanded" style="width: 14px; height: 14px;" />
                        <ChevronRight v-else style="width: 14px; height: 14px;" />
                    </button>

                    <div class="org-node__icon">
                        <Building2 v-if="data.kind === 'root'" style="width: 18px; height: 18px;" />
                        <Network v-else-if="data.kind === 'branch'" style="width: 18px; height: 18px;" />
                        <Workflow v-else style="width: 18px; height: 18px;" />
                    </div>
                    <div class="org-node__body">
                        <div class="org-node__title">
                            {{ data.label }}
                        </div>
                        <div v-if="data.code" class="org-node__code">
                            {{ data.code }}
                        </div>
                        <div v-if="data.subtitle" class="org-node__sub">
                            {{ data.subtitle }}
                        </div>
                        <div v-if="data.kind !== 'root'" class="org-node__stats">
                            <span class="org-node__stat" title="Кормандон">
                                <Users style="width: 13px; height: 13px;" /> {{ data.employees ?? 0 }}
                            </span>
                            <span v-if="data.vacancies > 0" class="org-node__stat org-node__stat--vac" title="Вакансияҳои кушода">
                                <DoorOpen style="width: 13px; height: 13px;" /> {{ data.vacancies }}
                            </span>
                            <span v-if="data.hasChildren && !data.expanded" class="org-node__stat org-node__stat--more" title="Зершуъбаҳои пинҳон">
                                <Network style="width: 13px; height: 13px;" /> +{{ data.childCount }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </VueFlow>

        <!-- Overlay controls; absolutely positioned so DOM order is irrelevant. -->
        <div v-if="nodes.length > 0" class="org-toolbar">
            <v-btn size="small" variant="tonal" color="indigo" rounded="lg" @click="expandAll">
                <Maximize2 style="width: 14px; height: 14px; margin-right: 4px;" /> Кушодани ҳама
            </v-btn>
            <v-btn size="small" variant="tonal" color="indigo" rounded="lg" @click="collapseAll">
                <Minimize2 style="width: 14px; height: 14px; margin-right: 4px;" /> Печондани ҳама
            </v-btn>
        </div>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.structure-flow {
    height: 100%;
    width: 100%;
}
.org-toolbar {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 5;
    display: flex;
    gap: 8px;
}
.org-node {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 180px;
    max-width: 220px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 20px -10px rgba(15, 45, 136, 0.25);
}
.org-node__toggle {
    position: absolute;
    top: -11px;
    left: -11px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 2px 6px -2px rgba(15, 45, 136, 0.3);
    transition: background-color 0.15s ease, color 0.15s ease;
}
.org-node__toggle:hover {
    background: #eef2ff;
    color: #4f46e5;
}
.org-node__stat--more {
    color: #4f46e5;
    background: rgba(99, 102, 241, 0.12);
}
.org-node--root {
    background: #009cf1;
    border: none;
    color: #fff;
}
.org-node--branch {
    border-left: 4px solid #009cf1;
}
.org-node--dept {
    border-left: 4px solid #94a3b8;
}
.org-node__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 9px;
    flex-shrink: 0;
    background: rgba(0, 156, 241, 0.12);
    color: #0284c7;
}
.org-node--root .org-node__icon {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}
.org-node__title {
    font-weight: 800;
    font-size: 0.9rem;
    line-height: 1.2;
    color: inherit;
}
.org-node--root .org-node__title {
    color: #fff;
}
.org-node__code {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-top: 2px;
}
.org-node__sub {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 2px;
}
.org-node__stats {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}
.org-node__stat {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 7px;
    border-radius: 999px;
}
.org-node__stat--vac {
    color: #b45309;
    background: rgba(245, 158, 11, 0.14);
}
</style>
