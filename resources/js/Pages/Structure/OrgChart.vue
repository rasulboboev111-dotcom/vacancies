<script setup>
import { Building2, DoorOpen, Network, Users, Workflow } from '@lucide/vue';
import { useVueFlow, VueFlow } from '@vue-flow/core';
import { computed, ref, watch } from 'vue';
import { buildOrgGraph } from '@/composables/useOrgChart';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';

const props = defineProps({
    structure: { type: Array, required: true },
});

const emit = defineEmits(['node-click']);

const graph = computed(() => buildOrgGraph(props.structure));
const nodes = ref(graph.value.nodes);
const edges = ref(graph.value.edges);

// Keep the chart in sync after Inertia reloads (e.g. after CRUD actions).
watch(graph, (next) => {
    nodes.value = next.nodes;
    edges.value = next.edges;
});

const { fitView } = useVueFlow();

function onPaneReady() {
    fitView({ padding: 0.2 });
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
            <template #node-org="{ data }">
                <div class="org-node" :class="`org-node--${data.kind}`">
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
                        </div>
                    </div>
                </div>
            </template>
        </VueFlow>
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
.org-node {
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
