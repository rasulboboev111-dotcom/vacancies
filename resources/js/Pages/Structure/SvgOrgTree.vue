<script setup>
import { ChevronsDownUp, ChevronsUpDown, Maximize2, Minimize2, Network } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { buildOrgTree, expandedToDepth } from '@/composables/useOrgChart';

const props = defineProps({
    structure: { type: Array, required: true },
});

const emit = defineEmits(['node-click']);

/* ------------------------------------------------------------------ *
 * Geometry — same card size and spacing the source site uses.
 * ------------------------------------------------------------------ */
const NODE_W = 160;
const NODE_H = 90;
const H_GAP = 24;
const V_GAP = 80;

/* ------------------------------------------------------------------ *
 * Palette (light / dark), mirroring the source: blue for the company
 * and branches, green for departments.
 * ------------------------------------------------------------------ */
const LIGHT = {
    lineStart: '#3b82f6',
    lineEnd: '#6366f1',
    buCardBg: '#ffffff',
    buBorder: '#3b82f6',
    buAccentStart: '#3b82f6',
    buAccentEnd: '#8b5cf6',
    buIconBg: '#eff6ff',
    buIconColor: '#3b82f6',
    buText: '#1e293b',
    buSubText: '#64748b',
    deptCardBg: '#ffffff',
    deptBorder: '#22c55e',
    deptAccentStart: '#22c55e',
    deptAccentEnd: '#10b981',
    deptIconBg: '#f0fdf4',
    deptIconColor: '#22c55e',
    deptText: '#1e293b',
    deptSubText: '#64748b',
    divider: '#e2e8f0',
    expandBg: '#ffffff',
    expandText: '#3b82f6',
    shadowOpacity: 0.12,
};
const DARK = {
    lineStart: '#60a5fa',
    lineEnd: '#818cf8',
    buCardBg: '#1e293b',
    buBorder: '#3b82f6',
    buAccentStart: '#3b82f6',
    buAccentEnd: '#8b5cf6',
    buIconBg: '#1e3a5f',
    buIconColor: '#60a5fa',
    buText: '#f1f5f9',
    buSubText: '#94a3b8',
    deptCardBg: '#1e293b',
    deptBorder: '#22c55e',
    deptAccentStart: '#22c55e',
    deptAccentEnd: '#10b981',
    deptIconBg: '#14532d',
    deptIconColor: '#4ade80',
    deptText: '#f1f5f9',
    deptSubText: '#94a3b8',
    divider: '#334155',
    expandBg: '#1e293b',
    expandText: '#60a5fa',
    shadowOpacity: 0.4,
};

const isDark = ref(false);
const c = computed(() => (isDark.value ? DARK : LIGHT));

/* ------------------------------------------------------------------ *
 * Tree + expansion state. Opens the top three tiers by default and
 * resets to that whenever the structure reloads (Inertia/CRUD).
 * ------------------------------------------------------------------ */
const tree = computed(() => buildOrgTree(props.structure));
const expanded = ref(expandedToDepth(tree.value));

watch(tree, (next) => {
    expanded.value = expandedToDepth(next);
});

const isOpen = id => expanded.value.has(id);

function toggle(id, event) {
    if (event) {
        event.stopPropagation();
    }
    const next = new Set(expanded.value);
    if (next.has(id)) {
        next.delete(id);
    }
    else {
        next.add(id);
    }
    expanded.value = next;
}

/* ------------------------------------------------------------------ *
 * Layout — recursive subtree-width packing, top-down. A collapsed node
 * is measured as a single column so the canvas hugs what's visible.
 * ------------------------------------------------------------------ */
// Total width the open children occupy side by side, gaps included.
function childrenSpan(node) {
    return node.children.reduce((sum, child) => sum + subtreeWidth(child) + H_GAP, -H_GAP);
}

function subtreeWidth(node) {
    if (!node.children.length || !isOpen(node.id)) {
        return NODE_W;
    }
    return Math.max(NODE_W, childrenSpan(node));
}

function subtreeDepth(node, depth = 1) {
    if (!node.children.length || !isOpen(node.id)) {
        return depth;
    }
    return Math.max(...node.children.map(child => subtreeDepth(child, depth + 1)));
}

const layout = computed(() => {
    const positions = new Map();
    const root = tree.value;
    if (!root) {
        return { positions, width: 800, height: 200 };
    }

    const treeW = subtreeWidth(root);
    const depth = subtreeDepth(root);
    const width = Math.max(treeW + 100, 800);
    const height = depth * (NODE_H + V_GAP) + 100;

    const place = (node, left, top, alloc) => {
        positions.set(node.id, { x: left + (alloc - NODE_W) / 2, y: top });
        if (!node.children.length || !isOpen(node.id)) {
            return;
        }
        const span = childrenSpan(node);
        let cursor = left + (alloc - span) / 2;
        const childTop = top + NODE_H + V_GAP;
        node.children.forEach((child) => {
            const childWidth = subtreeWidth(child);
            place(child, cursor, childTop, childWidth);
            cursor += childWidth + H_GAP;
        });
    };

    place(root, (width - treeW) / 2, 30, treeW);
    return { positions, width, height };
});

const canvas = computed(() => ({ width: layout.value.width, height: layout.value.height }));

// Wrap a label into at most two lines of ≤14 chars, as the source cards do.
function wrapLabel(label) {
    const lines = [];
    String(label ?? '').split(' ').forEach((word) => {
        const last = lines[lines.length - 1];
        if (last && `${last} ${word}`.length <= 14) {
            lines[lines.length - 1] = `${last} ${word}`;
        }
        else {
            lines.push(word);
        }
    });
    return lines.slice(0, 2);
}

const placed = computed(() => {
    const out = [];
    const root = tree.value;
    if (!root) {
        return out;
    }
    const walk = (node) => {
        const pos = layout.value.positions.get(node.id);
        if (!pos) {
            return;
        }
        out.push({
            id: node.id,
            kind: node.kind,
            isBu: node.kind !== 'dept',
            lines: wrapLabel(node.label),
            label: node.label,
            employeeCount: node.employeeCount,
            hasChildren: node.children.length > 0,
            childCount: node.children.length,
            open: isOpen(node.id),
            popupId: node.popupId ?? null,
            popupKind: node.popupKind ?? null,
            x: pos.x,
            y: pos.y,
        });
        if (node.children.length && isOpen(node.id)) {
            node.children.forEach(walk);
        }
    };
    walk(root);
    return out;
});

// Square elbow connectors: a stem down from the parent, a trunk across the
// children, and a drop into each child — exactly the source's path shapes.
const links = computed(() => {
    const out = [];
    const root = tree.value;
    if (!root) {
        return out;
    }
    const { positions } = layout.value;
    const walk = (node) => {
        const pos = positions.get(node.id);
        if (!pos || !node.children.length || !isOpen(node.id)) {
            return;
        }
        const cx = pos.x + NODE_W / 2;
        const startY = pos.y + NODE_H + 18;
        const midY = startY + Math.round(V_GAP * 0.4);
        const centers = node.children
            .map(child => positions.get(child.id))
            .filter(Boolean)
            .map(p => p.x + NODE_W / 2);
        if (!centers.length) {
            return;
        }
        const minC = Math.min(...centers);
        const maxC = Math.max(...centers);
        out.push({ id: `stem-${node.id}`, path: `M ${cx} ${startY} L ${cx} ${midY}` });
        if (minC !== maxC) {
            out.push({ id: `trunk-${node.id}`, path: `M ${minC} ${midY} L ${maxC} ${midY}` });
        }
        node.children.forEach((child) => {
            const cp = positions.get(child.id);
            if (!cp) {
                return;
            }
            const ccx = cp.x + NODE_W / 2;
            out.push({ id: `drop-${node.id}-${child.id}`, path: `M ${ccx} ${midY} L ${ccx} ${cp.y}` });
            walk(child);
        });
    };
    walk(root);
    return out;
});

/* ------------------------------------------------------------------ *
 * Controls
 * ------------------------------------------------------------------ */
function expandAll() {
    const set = new Set();
    const walk = (node) => {
        set.add(node.id);
        node.children.forEach(walk);
    };
    if (tree.value) {
        walk(tree.value);
    }
    expanded.value = set;
}

function collapseAll() {
    expanded.value = new Set();
}

const fullscreen = ref(false);

function toggleFullscreen() {
    fullscreen.value = !fullscreen.value;
    centerScroll();
}

function onCardClick(node) {
    if (node.kind === 'root') {
        return;
    }
    // A filial whose header department was absorbed routes its click to that
    // department's people (the filial itself holds no branch-level staff).
    if (node.popupId) {
        emit('node-click', { id: node.popupId, data: { kind: node.popupKind, label: node.label } });
        return;
    }
    emit('node-click', { id: node.id, data: { kind: node.kind, label: node.label } });
}

/* ------------------------------------------------------------------ *
 * Container — keep the chart centred horizontally and track dark mode.
 * ------------------------------------------------------------------ */
const container = ref(null);

function centerScroll() {
    nextTick(() => {
        const el = container.value;
        if (el) {
            el.scrollLeft = Math.max(0, (el.scrollWidth - el.clientWidth) / 2);
        }
    });
}

watch(() => canvas.value.width, centerScroll);

let observer = null;

function syncDark() {
    isDark.value = document.documentElement.classList.contains('dark');
}

onMounted(() => {
    syncDark();
    observer = new MutationObserver(syncDark);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    centerScroll();
});

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <v-card
        elevation="0"
        class="rounded-xl border bg-surface-glass overflow-hidden mb-6"
        :class="{ 'org-fullscreen': fullscreen }"
        :style="fullscreen ? null : 'height: 540px;'"
    >
        <div v-if="!tree" class="text-center py-12 h-100 d-flex flex-column justify-center align-center">
            <div class="text-h6 text-grey font-weight-medium">
                Барои намоиши сохтор маълумот нест.
            </div>
        </div>

        <div v-else class="org-wrap">
            <div class="org-header">
                <div class="org-header__title">
                    <Network style="width: 18px; height: 18px;" class="text-indigo" />
                    <span>Сохтор</span>
                </div>
                <div class="org-header__actions">
                    <button type="button" class="org-btn org-btn--icon" title="Печондани ҳама" @click="collapseAll">
                        <ChevronsDownUp style="width: 16px; height: 16px;" />
                    </button>
                    <button type="button" class="org-btn org-btn--icon" title="Кушодани ҳама" @click="expandAll">
                        <ChevronsUpDown style="width: 16px; height: 16px;" />
                    </button>
                    <button
                        type="button"
                        class="org-btn org-btn--icon"
                        :title="fullscreen ? 'Хуруҷ аз экрани пурра' : 'Экрани пурра'"
                        @click="toggleFullscreen"
                    >
                        <Minimize2 v-if="fullscreen" style="width: 18px; height: 18px;" />
                        <Maximize2 v-else style="width: 18px; height: 18px;" />
                    </button>
                </div>
            </div>

            <div ref="container" class="svg-org-tree">
                <svg :width="canvas.width" :height="canvas.height" class="d-block">
                    <defs>
                        <linearGradient id="orgLineGrad" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="0" :y2="canvas.height">
                            <stop offset="0%" :stop-color="c.lineStart" stop-opacity="0.8" />
                            <stop offset="100%" :stop-color="c.lineEnd" stop-opacity="0.8" />
                        </linearGradient>
                        <linearGradient id="orgBuAccent" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" :stop-color="c.buAccentStart" />
                            <stop offset="100%" :stop-color="c.buAccentEnd" />
                        </linearGradient>
                        <linearGradient id="orgDeptAccent" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" :stop-color="c.deptAccentStart" />
                            <stop offset="100%" :stop-color="c.deptAccentEnd" />
                        </linearGradient>
                        <filter id="orgCardShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#000" :flood-opacity="c.shadowOpacity * 0.5" />
                            <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#000" :flood-opacity="c.shadowOpacity" />
                        </filter>
                        <filter id="orgIconShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="1" stdDeviation="2" flood-color="#000" flood-opacity="0.15" />
                        </filter>
                    </defs>

                    <!-- Connectors -->
                    <path
                        v-for="link in links"
                        :key="link.id"
                        :d="link.path"
                        fill="none"
                        stroke="url(#orgLineGrad)"
                        stroke-width="2"
                        stroke-linecap="round"
                    />

                    <!-- Nodes -->
                    <g
                        v-for="node in placed"
                        :key="node.id"
                        class="node-group"
                        :transform="`translate(${node.x}, ${node.y})`"
                        @click="onCardClick(node)"
                    >
                        <rect
                            class="card-base"
                            :width="NODE_W"
                            :height="NODE_H"
                            rx="12"
                            :fill="node.isBu ? c.buCardBg : c.deptCardBg"
                            filter="url(#orgCardShadow)"
                        />
                        <rect
                            :width="NODE_W"
                            :height="NODE_H"
                            rx="12"
                            fill="none"
                            :stroke="node.isBu ? c.buBorder : c.deptBorder"
                            stroke-width="1.5"
                            stroke-opacity="0.3"
                        />

                        <!-- Icon -->
                        <g transform="translate(14, 14)">
                            <rect width="32" height="32" rx="8" :fill="node.isBu ? c.buIconBg : c.deptIconBg" />
                            <g v-if="node.isBu" transform="translate(8, 8)" fill="none" :stroke="c.buIconColor" stroke-width="1.5">
                                <rect x="2" y="3" width="12" height="13" rx="1" />
                                <line x1="5" y1="6.5" x2="7" y2="6.5" />
                                <line x1="9" y1="6.5" x2="11" y2="6.5" />
                                <line x1="5" y1="9.5" x2="7" y2="9.5" />
                                <line x1="9" y1="9.5" x2="11" y2="9.5" />
                                <line x1="7" y1="16" x2="9" y2="16" />
                            </g>
                            <g v-else transform="translate(8, 9)" fill="none" :stroke="c.deptIconColor" stroke-width="1.5">
                                <path d="M0 2a1 1 0 0 1 1-1h4l2 2h7a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1z" />
                            </g>
                        </g>

                        <!-- Title -->
                        <text
                            transform="translate(54, 16)"
                            text-anchor="start"
                            dominant-baseline="hanging"
                            :fill="node.isBu ? c.buText : c.deptText"
                            font-size="11"
                            font-weight="600"
                            letter-spacing="0.01em"
                            class="select-none"
                        >
                            <tspan
                                v-for="(line, i) in node.lines"
                                :key="i"
                                x="0"
                                :dy="i === 0 ? 0 : 13"
                            >{{ line }}</tspan>
                        </text>

                        <!-- Divider + employee count -->
                        <template v-if="node.employeeCount">
                            <line
                                x1="12"
                                :y1="NODE_H - 28"
                                :x2="NODE_W - 12"
                                :y2="NODE_H - 28"
                                :stroke="c.divider"
                                stroke-width="1"
                            />
                            <g :transform="`translate(16, ${NODE_H - 22})`">
                                <circle cx="5" cy="5" r="5" :fill="node.isBu ? c.buIconBg : c.deptIconBg" />
                                <text
                                    x="14"
                                    y="8"
                                    text-anchor="start"
                                    :fill="node.isBu ? c.buAccentStart : c.deptAccentStart"
                                    font-size="9"
                                    font-weight="500"
                                    class="select-none"
                                >{{ node.employeeCount }} корм.</text>
                            </g>
                        </template>

                        <!-- BU / DEP tag -->
                        <text
                            :transform="`translate(${NODE_W - 10}, 14)`"
                            text-anchor="end"
                            :fill="node.isBu ? 'url(#orgBuAccent)' : 'url(#orgDeptAccent)'"
                            font-size="8"
                            font-weight="700"
                            opacity="0.9"
                            class="select-none"
                        >{{ node.isBu ? 'BU' : 'DEP' }}</text>

                        <!-- Expand / collapse circle -->
                        <g
                            v-if="node.hasChildren"
                            class="expand-btn"
                            :transform="`translate(${NODE_W / 2}, ${NODE_H})`"
                            @click.stop="toggle(node.id, $event)"
                        >
                            <circle r="14" :fill="c.expandBg" filter="url(#orgIconShadow)" />
                            <circle
                                class="expand-circle"
                                r="13"
                                fill="none"
                                :stroke="node.isBu ? 'url(#orgBuAccent)' : 'url(#orgDeptAccent)'"
                                stroke-width="2"
                            />
                            <g :fill="c.expandText">
                                <rect x="-5" y="-1" width="10" height="2" rx="1" />
                                <rect v-if="!node.open" x="-1" y="-5" width="2" height="10" rx="1" />
                            </g>
                            <g transform="translate(10, -7)">
                                <rect width="18" height="14" rx="7" :fill="node.isBu ? 'url(#orgBuAccent)' : 'url(#orgDeptAccent)'" />
                                <text x="9" y="10" text-anchor="middle" fill="#ffffff" font-size="9" font-weight="600" class="select-none">{{ node.childCount }}</text>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
        </div>
    </v-card>
</template>

<style scoped>
.bg-surface-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
}
.org-fullscreen {
    position: fixed;
    inset: 0;
    /* Above Vuetify's app bar / navigation drawer (~1005) so the header and its
       exit button stay visible, but below dialogs (~2400) so the employees
       popup still opens on top. */
    z-index: 2000;
    height: 100vh !important;
    width: 100vw;
    margin: 0 !important;
    border-radius: 0 !important;
}
.org-wrap {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.org-header {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
}
.org-header__title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1e293b;
}
.org-header__actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.org-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.org-btn:hover {
    background: #e5e7eb;
}
.org-btn--icon {
    padding: 6px;
}
.svg-org-tree {
    flex: 1 1 auto;
    width: 100%;
    min-height: 0;
    overflow: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.svg-org-tree::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.svg-org-tree::-webkit-scrollbar-track {
    background: transparent;
}
.svg-org-tree::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
.node-group {
    cursor: pointer;
}
.node-group .card-base {
    transition: opacity 0.15s ease-out;
}
.node-group:hover .card-base {
    opacity: 0.95;
}
.expand-btn {
    cursor: pointer;
}
.expand-btn .expand-circle {
    transition: stroke-width 0.15s ease-out;
}
.expand-btn:hover .expand-circle {
    stroke-width: 2.5;
}
.select-none {
    user-select: none;
}
</style>
