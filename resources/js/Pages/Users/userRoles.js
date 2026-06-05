// Shared role presentation helpers for the Users pages.

export const roleLabels = {
    Admin: 'Админ',
    User: 'Корбар',
};

const roleColors = {
    Admin: 'blue-darken-2',
    User: 'blue-grey-lighten-1',
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

// Admin is the privileged role, so it gets a solid, prominent chip; everyone
// else stays calm and subtle (tonal). This makes the role legible at a glance
// without having to read the label.
export function getRoleVariant(roleName) {
    return roleName === 'Admin' ? 'flat' : 'tonal';
}
