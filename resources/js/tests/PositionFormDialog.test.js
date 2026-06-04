import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import PositionFormDialog from '@/Pages/Positions/PositionFormDialog.vue';

function button(wrapper, text) {
    return wrapper.findAll('button').find(b => b.text().includes(text));
}

const flush = () => new Promise(resolve => setTimeout(resolve, 0));

describe('positionFormDialog (vee-validate + zod)', () => {
    it('blocks submit and shows an error when the name is empty', async () => {
        const w = mount(PositionFormDialog, { props: { modelValue: true, position: null } });
        await nextTick();

        await button(w, 'Захира кардан').trigger('click');
        await flush();
        await nextTick();

        // The zod schema rejects an empty name before Inertia is ever called,
        // so the field-level error is rendered.
        expect(w.html()).toContain('Номи вазифа ҳатмист');
    });
});
