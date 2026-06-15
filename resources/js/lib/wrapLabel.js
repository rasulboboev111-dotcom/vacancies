// Переносит подпись максимум в `maxLines` строк по ≤`maxChars` символов, чтобы она
// не вылезала за край карточки оргсхемы: длинные слова жёстко разрываются, а не
// помещающаяся подпись обрезается многоточием на последней видимой строке.
// Чистая функция, вынесена из SvgOrgTree.

export function wrapLabel(label, maxChars = 14, maxLines = 2) {
    const words = String(label ?? '').trim().split(/\s+/).filter(Boolean);
    const lines = [];

    words.forEach((word) => {
        // Разрываем отдельное слово, которое никогда не поместится в одну строку.
        while (word.length > maxChars) {
            lines.push(word.slice(0, maxChars));
            word = word.slice(maxChars);
        }
        const last = lines[lines.length - 1];
        if (last && `${last} ${word}`.length <= maxChars) {
            lines[lines.length - 1] = `${last} ${word}`;
        }
        else {
            lines.push(word);
        }
    });

    if (lines.length > maxLines) {
        const kept = lines.slice(0, maxLines);
        kept[maxLines - 1] = `${kept[maxLines - 1].slice(0, maxChars - 1)}…`;
        return kept;
    }
    return lines;
}
