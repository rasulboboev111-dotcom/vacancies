import { describe, expect, it } from 'vitest';
import { buildOrgTree, expandedToDepth } from '@/composables/useOrgChart';

const structure = [
    {
        id: 1,
        name: 'Branch A',
        code: 'A',
        employees_count: 5,
        open_vacancies: 0,
        departments: [
            {
                id: 10,
                name: 'Dept 10',
                code: null,
                employees_count: 2,
                open_vacancies: 0,
                children: [
                    { id: 11, name: 'Sub 11', children: [] },
                ],
            },
        ],
    },
];

describe('buildOrgTree', () => {
    it('returns null for an empty structure', () => {
        expect(buildOrgTree([])).toBeNull();
    });

    it('wraps branches under a synthetic root and recurses departments', () => {
        const root = buildOrgTree(structure);

        expect(root.id).toBe('root');
        expect(root.kind).toBe('root');

        const branch = root.children[0];
        expect(branch.id).toBe('b-1');
        expect(branch.kind).toBe('branch');
        expect(branch.employeeCount).toBe(5);

        const dept = branch.children[0];
        expect(dept.id).toBe('d-10');
        expect(dept.kind).toBe('dept');
        expect(dept.children[0].id).toBe('d-11');
    });

    it('absorbs a "Филиал…" header department so the filial shows once', () => {
        const filial = [
            {
                id: 2,
                name: 'Филиал дар вилояти Хатлон',
                employees_count: 10,
                departments: [
                    {
                        id: 81,
                        name: 'Филиал дар вилояти Хатлон',
                        employees_count: 4,
                        children: [{ id: 90, name: 'Шуъбаи 1', children: [] }],
                    },
                ],
            },
        ];

        const branch = buildOrgTree(filial).children[0];

        // The header (d-81) is gone as a node; its child is hoisted onto the branch.
        expect(branch.children.map(child => child.id)).toEqual(['d-90']);
        // Clicking the filial opens the absorbed header's employees.
        expect(branch.popupId).toBe('d-81');
        expect(branch.popupKind).toBe('dept');
    });

    it('leaves a real department (not "Филиал…") untouched', () => {
        const branch = buildOrgTree(structure).children[0];

        expect(branch.popupId).toBeUndefined();
        expect(branch.children[0].id).toBe('d-10');
    });
});

describe('expandedToDepth', () => {
    it('opens tiers of the built tree up to the depth', () => {
        const open = expandedToDepth(buildOrgTree(structure), 3);

        expect(open.has('root')).toBe(true); // depth 0
        expect(open.has('b-1')).toBe(true); // depth 1
        expect(open.has('d-10')).toBe(true); // depth 2
        expect(open.has('d-11')).toBe(false); // depth 3 stays collapsed
    });

    it('returns an empty set for a null tree', () => {
        expect(expandedToDepth(null).size).toBe(0);
    });
});
