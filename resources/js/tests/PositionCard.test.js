import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PositionCard from '@/Pages/Positions/PositionCard.vue';

const position = { id: 1, name: 'Developer', employees_count: 3 };

describe('positionCard', () => {
    it('emits view when the card is clicked', async () => {
        const w = mount(PositionCard, { props: { position } });

        await w.get('.position-card').trigger('click');

        expect(w.emitted('view')).toHaveLength(1);
        expect(w.emitted('view')[0]).toEqual([position]);
    });

    it('does not open the view when the admin actions menu button is clicked', async () => {
        const w = mount(PositionCard, { props: { position, isAdmin: true } });

        await w.get('.hover-scale-btn').trigger('click');

        expect(w.emitted('view')).toBeUndefined();
    });
});
