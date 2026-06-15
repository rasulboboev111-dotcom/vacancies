import { ref } from 'vue';

/**
 * Общее состояние диалогов CRUD на index-страницах: create/edit (форма),
 * просмотр и удаление. Убирает повторяющиеся ref(false)/ref(null) + openCreate/
 * openEdit/openView/openDelete, дублировавшиеся по страницам списков.
 *
 * Возвращает реактивные флаги видимости и «текущие» записи плюс открывающие
 * функции. Страничные диалоги вне этого набора (ротация, сотрудники отдела и
 * т.п.) остаются локальными в самой странице.
 */
export function useCrudDialogs() {
    const formDialog = ref(false);
    const viewDialog = ref(false);
    const deleteDialog = ref(false);

    const editing = ref(null);
    const viewing = ref(null);
    const toDelete = ref(null);

    function openCreate() {
        editing.value = null;
        formDialog.value = true;
    }

    function openEdit(item) {
        editing.value = item;
        formDialog.value = true;
    }

    function openView(item) {
        viewing.value = item;
        viewDialog.value = true;
    }

    function openDelete(item) {
        toDelete.value = item;
        deleteDialog.value = true;
    }

    return {
        formDialog,
        viewDialog,
        deleteDialog,
        editing,
        viewing,
        toDelete,
        openCreate,
        openEdit,
        openView,
        openDelete,
    };
}
