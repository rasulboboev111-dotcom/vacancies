import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import VacancyDeleteDialog from '@/Pages/Vacancies/VacancyDeleteDialog.vue';

function button(wrapper, text) {
    return wrapper.findAll('button').find(b => b.text().includes(text));
}

describe('vacancyDeleteDialog', () => {
    it('emits confirm when the delete button is clicked', async () => {
        const w = mount(VacancyDeleteDialog, {
            props: { modelValue: true, vacancy: { id: 1, title: 'Барномасоз' } },
        });

        await button(w, 'Нест кардан').trigger('click');
        expect(w.emitted('confirm')).toBeTruthy();
    });

    it('disables the confirm button while processing', () => {
        const w = mount(VacancyDeleteDialog, {
            props: { modelValue: true, vacancy: { id: 1, title: 'Барномасоз' }, processing: true },
        });

        expect(button(w, 'Нест кардан').attributes('disabled')).toBeDefined();
    });
});
