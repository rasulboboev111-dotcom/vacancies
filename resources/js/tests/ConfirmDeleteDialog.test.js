import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog.vue';

// Capture the Inertia form so we can assert on the delete call.
const { formMock } = vi.hoisted(() => ({
    formMock: { processing: false, delete: vi.fn() },
}));

vi.mock('@inertiajs/vue3', () => ({ useForm: () => formMock }));

function button(wrapper, text) {
    return wrapper.findAll('button').find(b => b.text().includes(text));
}

function mountDialog(props = {}) {
    return mount(ConfirmDeleteDialog, {
        props: { modelValue: true, title: 'Нест кардан', deleteUrl: '/items/1', ...props },
        slots: { default: 'Мутмаин ҳастед?' },
    });
}

beforeEach(() => {
    formMock.delete.mockClear();
    formMock.processing = false;
});

describe('confirmDeleteDialog', () => {
    it('renders the title and body slot', () => {
        const w = mountDialog();
        expect(w.text()).toContain('Нест кардан');
        expect(w.text()).toContain('Мутмаин ҳастед?');
    });

    it('submits a DELETE to deleteUrl when confirmed', async () => {
        const w = mountDialog({ deleteUrl: '/employees/7', preserveScroll: true });
        await button(w, 'Нест кардан').trigger('click');

        expect(formMock.delete).toHaveBeenCalledTimes(1);
        expect(formMock.delete).toHaveBeenCalledWith('/employees/7', expect.objectContaining({ preserveScroll: true }));
    });

    it('does nothing when deleteUrl is null', async () => {
        const w = mountDialog({ deleteUrl: null });
        await button(w, 'Нест кардан').trigger('click');
        expect(formMock.delete).not.toHaveBeenCalled();
    });

    it('does not submit when confirmDisabled is set', async () => {
        const w = mountDialog({ confirmDisabled: true });
        await button(w, 'Нест кардан').trigger('click');
        expect(formMock.delete).not.toHaveBeenCalled();
    });

    it('closes (emits update:modelValue=false) on cancel', async () => {
        const w = mountDialog();
        await button(w, 'Бекор').trigger('click');
        expect(w.emitted('update:modelValue')?.at(-1)).toEqual([false]);
    });
});
