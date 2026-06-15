import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SvgOrgTree from '@/Pages/Structure/SvgOrgTree.vue';

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
                children: [{ id: 11, name: 'Sub 11', children: [] }],
            },
        ],
    },
];

function mountTree() {
    return mount(SvgOrgTree, { props: { structure } });
}

// Expand every tier so a clickable (non-root) leaf node is rendered.
async function expandAll(w) {
    await w.get('[title="Кушодани ҳама"]').trigger('click');
}

function lastNode(w) {
    const nodes = w.findAll('.node-group');
    return nodes[nodes.length - 1];
}

// jsdom lacks PointerEvent and makes MouseEvent.clientX read-only, so build the
// event through the MouseEvent constructor and stamp pointerType on it.
function firePointer(el, type, { clientX = 0, clientY = 0, button = 0 } = {}) {
    const event = new MouseEvent(type, { clientX, clientY, button, bubbles: true, cancelable: true });
    Object.defineProperty(event, 'pointerType', { value: 'mouse' });
    el.dispatchEvent(event);
}

describe('svgOrgTree drag-to-pan', () => {
    it('opens a node on a plain click', async () => {
        const w = mountTree();
        await expandAll(w);

        await lastNode(w).trigger('click');

        expect(w.emitted('node-click')?.length).toBeGreaterThan(0);
    });

    it('swallows the click that ends a mouse drag, so panning never opens a node', async () => {
        const w = mountTree();
        await expandAll(w);
        const el = w.get('.svg-org-tree').element;

        firePointer(el, 'pointerdown', { clientX: 200, clientY: 100 });
        firePointer(el, 'pointermove', { clientX: 110, clientY: 100 });
        firePointer(el, 'pointerup', { clientX: 110, clientY: 100 });

        await lastNode(w).trigger('click');

        expect(w.emitted('node-click')).toBeUndefined();
    });

    it('does not swallow the next click after a cancelled pan', async () => {
        const w = mountTree();
        await expandAll(w);
        const el = w.get('.svg-org-tree').element;

        // Pan past the threshold, then the browser cancels the pointer — no
        // trailing click follows a pointercancel, so panMoved must not linger.
        firePointer(el, 'pointerdown', { clientX: 200, clientY: 100 });
        firePointer(el, 'pointermove', { clientX: 110, clientY: 100 });
        firePointer(el, 'pointercancel', { clientX: 110, clientY: 100 });

        // A fresh, genuine click must still open the node.
        await lastNode(w).trigger('click');

        expect(w.emitted('node-click')?.length).toBeGreaterThan(0);
    });

    it('treats sub-threshold movement as a normal click, not a drag', async () => {
        const w = mountTree();
        await expandAll(w);
        const el = w.get('.svg-org-tree').element;

        firePointer(el, 'pointerdown', { clientX: 200, clientY: 100 });
        firePointer(el, 'pointermove', { clientX: 203, clientY: 100 });
        firePointer(el, 'pointerup', { clientX: 203, clientY: 100 });

        await lastNode(w).trigger('click');

        expect(w.emitted('node-click')?.length).toBeGreaterThan(0);
    });
});
