// Хелперы отображения ролей для страниц пользователей.

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

// Admin — привилегированная роль, поэтому у неё сплошной заметный чип; остальные
// остаются спокойными и приглушёнными (tonal). Так роль читается с первого
// взгляда, без необходимости вчитываться в подпись.
export function getRoleVariant(roleName) {
    return roleName === 'Admin' ? 'flat' : 'tonal';
}
