/**
 * Build the nested node tree the SVG org chart walks: a single synthetic root
 * (the organisation) → branches → the recursive department tree. Each node is
 * flattened to just what a card needs (uid, kind, label, employee count, child
 * list), with uids prefixed so branch/department numeric ids never collide.
 *
 * A regional filial is mirrored in the source as both a businessUnit (→ our
 * branch) and a root "Филиал …" header department of the same place, which would
 * otherwise render the filial twice. We absorb that header into its branch: its
 * children are hoisted onto the branch node, and the branch carries a `popupId`
 * so a click opens the header's employees (filials hold no staff at branch
 * level). Real departments ("Шуъбаи …", "ҶДММ …") never start with "Филиал", so
 * they are left untouched.
 *
 * @param {Array} structure  branches → departments tree from the server
 * @param {string|null} orgName  the head organisation name, used for the root
 *   when the head company branch is not in the visible set (e.g. a branch user
 *   who only sees their own filial)
 * @returns {object|null} the root node, or null when there is nothing to show
 */
export function buildOrgTree(structure, orgName = null) {
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

    const isFilialHeader = dept => String(dept.name ?? '').trim().toLowerCase().startsWith('филиал');

    const mapBranch = (branch) => {
        const roots = branch.departments || [];
        const header = roots.find(isFilialHeader);
        const rest = header ? roots.filter(dept => dept !== header) : roots;

        const node = {
            id: `b-${branch.id}`,
            kind: 'branch',
            label: branch.name,
            code: branch.code ?? null,
            employeeCount: branch.employees_count ?? 0,
            vacancies: branch.open_vacancies ?? 0,
            children: [...(header?.children ?? []), ...rest].map(mapDept),
        };

        if (header) {
            node.popupId = `d-${header.id}`;
            node.popupKind = 'dept';
        }

        return node;
    };

    const branches = structure.map(mapBranch);

    // The head company is the Business Unit at the top of the org — the only
    // branch whose name is not a regional "Филиал …". It becomes the tree root
    // (showing its real name, e.g. ҶСК "Тоҷиктелеком"), with the regional
    // filials hanging beneath it. This shows the company once, as the top BU,
    // instead of a synthetic "Organisation" root duplicated by a same-named
    // company branch.
    const isFilialBranch = branch => String(branch.label ?? '').trim().toLowerCase().startsWith('филиал');
    const headIndex = branches.findIndex(branch => !isFilialBranch(branch));

    if (headIndex === -1) {
        // The head company branch is not visible (e.g. a branch user who only
        // sees their own filial). Root the tree on the real organisation name
        // when the server provides it, otherwise a neutral placeholder.
        return {
            id: 'root',
            kind: 'root',
            label: orgName || 'Ташкилот',
            code: null,
            employeeCount: 0,
            vacancies: 0,
            children: branches,
        };
    }

    const head = branches[headIndex];
    const filials = branches.filter((_, index) => index !== headIndex);

    return {
        ...head,
        children: [...head.children, ...filials],
    };
}

/**
 * The node ids to open on first render — every tier of an assembled tree above
 * `maxDepth` (root is depth 0). Walking the built tree (not the raw payload)
 * keeps the default correct after buildOrgTree merges filial headers.
 *
 * @param {object|null} root  the root node from buildOrgTree
 * @param {number} maxDepth  number of tiers to open
 * @returns {Set<string>} ids whose children should be drawn on first render
 */
export function expandedToDepth(root, maxDepth = 3) {
    const expanded = new Set();
    const walk = (node, depth) => {
        if (depth >= maxDepth) {
            return;
        }
        expanded.add(node.id);
        node.children.forEach(child => walk(child, depth + 1));
    };
    if (root) {
        walk(root, 0);
    }
    return expanded;
}
