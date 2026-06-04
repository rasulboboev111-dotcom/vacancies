// Shared role presentation helpers for the Users pages.

export const roleLabels = {
    Admin: 'Маъмур',
    User: 'Корбар',
};

const roleColors = {
    Admin: 'red',
    User: 'indigo',
};

export function getRoleLabel(roleName) {
    if (!roleName)
        return 'Нақш надорад';
    return roleLabels[roleName] ?? roleName;
}

export function getRoleColor(roleName) {
    if (!roleName)
        return 'grey';
    return roleColors[roleName] ?? 'grey';
}
