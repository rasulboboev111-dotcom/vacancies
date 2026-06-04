import { Position } from '@vue-flow/core';

const X_GAP = 260;
const Y_GAP = 170;

/**
 * Build the nested node tree the SVG org chart walks: a single synthetic root
 * (the organisation) → branches → the recursive department tree. Each node is
 * flattened to just what a card needs (uid, kind, label, employee count, child
 * list), with uids prefixed so branch/department numeric ids never collide.
 *
 * @param {Array} structure  branches → departments tree from the server
 * @returns {object|null} the root node, or null when there is nothing to show
 */
export function buildOrgTree(structure) {
    if (!structure || structure.length === 0) {
        return null;
    }

    const mapDept = dept => ({
        id: `d-${dept.id}`,
        kind: 'dept',
        label: dept.name,
        code: dept.code ?? null,
        employeeCount: dept.employees_count ?? 0,
        vacancies: dept.open_vacancies ?? 0,
        children: (dept.children || []).map(mapDept),
    });

    return {
        id: 'root',
        kind: 'root',
        label: 'Ташкилот',
        code: null,
        employeeCount: 0,
        vacancies: 0,
        children: structure.map(branch => ({
            id: `b-${branch.id}`,
            kind: 'branch',
            label: branch.name,
            code: branch.code ?? null,
            employeeCount: branch.employees_count ?? 0,
            vacancies: branch.open_vacancies ?? 0,
            children: (branch.departments || []).map(mapDept),
        })),
    };
}

/**
 * The node uids to open on first render: every tier above `maxDepth`, mirroring
 * the source site which auto-expands the top three levels (organisation →
 * branches → departments) and leaves anything deeper collapsed behind a toggle.
 * A node at depth 2 is opened, so its depth-3 children are visible but stay
 * collapsed themselves — exactly the source's `depth < 3` rule.
 *
 * @param {Array} structure  branches → departments tree from the server
 * @param {number} maxDepth  number of tiers to open (root is depth 0)
 * @returns {Set<string>} uids whose children should be drawn on first render
 */
export function defaultExpanded(structure, maxDepth = 3) {
    const expanded = new Set();
    if (!structure || structure.length === 0 || maxDepth < 1) {
        return expanded;
    }

    const walkDept = (dept, depth) => {
        if (depth >= maxDepth) {
            return;
        }
        expanded.add(`d-${dept.id}`);
        (dept.children || []).forEach(child => walkDept(child, depth + 1));
    };

    expanded.add('root'); // depth 0
    structure.forEach((branch) => {
        if (maxDepth > 1) {
            expanded.add(`b-${branch.id}`); // depth 1
        }
        if (maxDepth > 2) {
            (branch.departments || []).forEach(dept => walkDept(dept, 2)); // depth 2+
        }
    });

    return expanded;
}

/**
 * Turn the server `structure` payload (branches → departments tree) into the
 * VueFlow `{ nodes, edges }` an org chart needs. Pure function: no component
 * state, easy to reason about and test.
 *
 * @param {Array} structure  branches → departments tree from the server
 * @param {Set<string>|null} expanded  uids whose children should be drawn. A
 *   node outside the set is laid out as a leaf and its whole subtree is omitted,
 *   which keeps a large org chart readable. `null` (the default) expands
 *   everything, so the full tree renders exactly as before.
 */
export function buildOrgGraph(structure, expanded = null) {
    const nodes = [];
    const edges = [];
    let leafIndex = 0;

    const isOpen = uid => expanded === null || expanded.has(uid);

    const root = {
        uid: 'root',
        kind: 'root',
        label: 'Ташкилот',
        subtitle: `${structure.length} филиал`,
        children: structure.map(branch => ({
            uid: `b-${branch.id}`,
            kind: 'branch',
            label: branch.name,
            code: branch.code,
            employees: branch.employees_count,
            vacancies: branch.open_vacancies,
            children: (branch.departments || []).map(mapDepartment),
        })),
    };

    function mapDepartment(dept) {
        return {
            uid: `d-${dept.id}`,
            kind: 'dept',
            label: dept.name,
            code: dept.code,
            employees: dept.employees_count,
            vacancies: dept.open_vacancies,
            children: (dept.children || []).map(mapDepartment),
        };
    }

    function layout(node, depth, parentUid) {
        const children = node.children || [];
        const hasChildren = children.length > 0;
        const open = isOpen(node.uid);

        let x;
        if (!hasChildren || !open) {
            // A leaf — or a collapsed node we deliberately treat as one — takes
            // a single column, so the layout stays tight around what's visible.
            x = leafIndex * X_GAP;
            leafIndex += 1;
        }
        else {
            const childXs = children.map(child => layout(child, depth + 1, node.uid));
            x = (childXs[0] + childXs[childXs.length - 1]) / 2;
        }

        nodes.push({
            id: node.uid,
            type: 'org',
            position: { x, y: depth * Y_GAP },
            sourcePosition: Position.Bottom,
            targetPosition: Position.Top,
            data: {
                label: node.label,
                code: node.code,
                kind: node.kind,
                employees: node.employees,
                vacancies: node.vacancies,
                subtitle: node.subtitle,
                hasChildren,
                expanded: open,
                childCount: children.length,
            },
        });

        if (parentUid) {
            edges.push({
                id: `${parentUid}->${node.uid}`,
                source: parentUid,
                target: node.uid,
                type: 'smoothstep',
                animated: node.vacancies > 0,
                style: { stroke: '#94a3b8', strokeWidth: 1.5 },
            });
        }

        return x;
    }

    if (structure.length > 0) {
        layout(root, 0, null);
    }

    return { nodes, edges };
}
