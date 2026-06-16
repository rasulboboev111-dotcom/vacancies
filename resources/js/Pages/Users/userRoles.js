// Хелперы отображения ролей для страниц пользователей.

const roleLabels = {
    Superadmin: 'Суперадмин',
    Admin: 'Админ',
    User: 'Корбар',
};

const roleColors = {
    Superadmin: 'deep-purple-darken-1',
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

// Superadmin и Admin — привилегированные роли, поэтому у них сплошной заметный
// чип; остальные остаются спокойными и приглушёнными (tonal). Так роль читается
// с первого взгляда, без необходимости вчитываться в подпись.
export function getRoleVariant(roleName) {
    return roleName === 'Admin' || roleName === 'Superadmin' ? 'flat' : 'tonal';
}
