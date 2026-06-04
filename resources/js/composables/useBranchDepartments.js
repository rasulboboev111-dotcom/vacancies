import { computed, watch } from 'vue';

/**
 * Keeps a form's department selection consistent with its chosen branch.
 *
 * Returns `branchDepartments` — the departments belonging to the form's current
 * branch — and installs a watcher that clears `form.department_id` whenever the
 * selected branch changes such that the department no longer belongs to it.
 *
 * `departmentsGetter` is a function returning the full departments list, so the
 * composable stays reactive to prop changes. `form` is the Inertia form holding
 * `branch_id` and `department_id`.
 */
export function useBranchDepartments(form, departmentsGetter) {
    const branchDepartments = computed(() =>
        departmentsGetter().filter(d => Number(d.branch_id) === Number(form.branch_id)),
    );

    watch(() => form.branch_id, (newBranchId) => {
        if (
            form.department_id
            && !departmentsGetter().some(
                d => Number(d.id) === Number(form.department_id) && Number(d.branch_id) === Number(newBranchId),
            )
        ) {
            form.department_id = null;
        }
    });

    return { branchDepartments };
}
