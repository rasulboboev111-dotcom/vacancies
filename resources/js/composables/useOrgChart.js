/**
 * Строит вложенное дерево узлов, которое обходит SVG-оргсхема: один синтетический
 * корень (организация) → филиалы → рекурсивное дерево отделов. Каждый узел
 * сведён к тому, что нужно карточке (uid, kind, label, число сотрудников, список
 * детей), а uid снабжены префиксом, чтобы числовые id филиалов и отделов никогда
 * не пересекались.
 *
 * Региональный филиал в источнике продублирован одновременно как businessUnit
 * (→ наш branch) и как корневой отдел-заголовок «Филиал …» того же места, что
 * иначе отрисовало бы филиал дважды. Мы поглощаем этот заголовок его филиалом:
 * его дети поднимаются на узел филиала, а филиал несёт `popupId`, чтобы клик
 * открывал сотрудников заголовка (на уровне филиала штата нет). Реальные отделы
 * («Шуъбаи …», «ҶДММ …») никогда не начинаются с «Филиал», поэтому остаются
 * нетронутыми.
 *
 * @param {Array} structure  дерево филиалы → отделы с сервера
 * @param {string|null} orgName  имя головной организации, используется для корня,
 *   когда филиала головной компании нет в видимом наборе (например, пользователь
 *   филиала, видящий только свой филиал)
 * @returns {object|null} корневой узел или null, когда показывать нечего
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

    // Головная компания — это Business Unit на вершине организации, единственный
    // филиал, чьё имя не является региональным «Филиал …». Он становится корнем
    // дерева (показывая своё настоящее имя, например ҶСК "Тоҷиктелеком"), а
    // региональные филиалы висят под ним. Так компания показывается один раз, как
    // верхний BU, вместо синтетического корня «Организация», продублированного
    // одноимённым филиалом компании.
    const isFilialBranch = branch => String(branch.label ?? '').trim().toLowerCase().startsWith('филиал');
    const headIndex = branches.findIndex(branch => !isFilialBranch(branch));

    if (headIndex === -1) {
        // Филиал головной компании не виден (например, пользователь филиала,
        // видящий только свой филиал). Корнем дерева берём настоящее имя
        // организации, когда сервер его передаёт, иначе нейтральную заглушку.
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
 * Id узлов, раскрываемых при первой отрисовке — каждый ярус собранного дерева
 * выше `maxDepth` (корень — глубина 0). Обход построенного дерева (а не сырого
 * payload) сохраняет правильное значение по умолчанию после того, как buildOrgTree
 * слил заголовки филиалов.
 *
 * @param {object|null} root  корневой узел из buildOrgTree
 * @param {number} maxDepth  число раскрываемых ярусов
 * @returns {Set<string>} id узлов, чьи дети должны отрисоваться при первой отрисовке
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
