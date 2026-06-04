import { Position } from '@vue-flow/core';

const X_GAP = 260;
const Y_GAP = 170;

/**
 * Turn the server `structure` payload (branches → departments tree) into the
 * VueFlow `{ nodes, edges }` an org chart needs. Pure function: no component
 * state, easy to reason about and test.
 */
export function buildOrgGraph(structure) {
    const nodes = [];
    const edges = [];
    let leafIndex = 0;

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
        let x;
        if (!node.children || node.children.length === 0) {
            x = leafIndex * X_GAP;
            leafIndex += 1;
        }
        else {
            const childXs = node.children.map(child => layout(child, depth + 1, node.uid));
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
