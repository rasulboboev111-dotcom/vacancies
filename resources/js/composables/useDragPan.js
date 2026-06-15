import { ref } from 'vue';

/**
 * Перетаскивание для панорамирования скролл-контейнера мышью (для касаний
 * остаётся нативная инерционная прокрутка). Перетаскивание за порог поглощает
 * завершающий клик, чтобы жест панорамирования не открывал попап/узел.
 *
 * Вынесено из SvgOrgTree (God-компонент): это самодостаточная интеракция.
 *
 * @param {import('vue').Ref<HTMLElement|null>} containerRef — ссылка на скролл-контейнер
 */
export function useDragPan(containerRef) {
    const PAN_THRESHOLD = 6;
    const isPanning = ref(false);

    let active = false;
    let moved = false;
    let startX = 0;
    let startY = 0;
    let scrollLeft = 0;
    let scrollTop = 0;

    function onPanStart(e) {
        if (e.pointerType !== 'mouse' || e.button !== 0) {
            return;
        }
        const el = containerRef.value;
        if (!el) {
            return;
        }
        active = true;
        moved = false;
        startX = e.clientX;
        startY = e.clientY;
        scrollLeft = el.scrollLeft;
        scrollTop = el.scrollTop;
    }

    function onPanMove(e) {
        if (!active) {
            return;
        }
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        if (!moved && Math.hypot(dx, dy) < PAN_THRESHOLD) {
            return;
        }
        const el = containerRef.value;
        // Контейнер мог размонтироваться, пока кнопка зажата (смена страницы и т.п.).
        if (!el) {
            return;
        }
        if (!moved) {
            moved = true;
            isPanning.value = true;
            el.setPointerCapture?.(e.pointerId);
        }
        el.scrollLeft = scrollLeft - dx;
        el.scrollTop = scrollTop - dy;
    }

    function onPanEnd(e) {
        if (!active) {
            return;
        }
        active = false;
        isPanning.value = false;
        const el = containerRef.value;
        if (el?.hasPointerCapture?.(e.pointerId)) {
            el.releasePointerCapture(e.pointerId);
        }
        // pointerup оставляет moved выставленным, чтобы click-capture поглотил клик,
        // завершающий жест. pointercancel завершающего клика не даёт, поэтому
        // сбрасываем флаг сразу — иначе он повис бы и проглотил следующий клик.
        if (e.type === 'pointercancel') {
            moved = false;
        }
    }

    // Выполняется в фазе перехвата раньше обработчиков клика узла: клик, лишь
    // завершающий жест панорамирования, поглощается вместо открытия попапа.
    function onContainerClickCapture(e) {
        if (moved) {
            e.stopPropagation();
            moved = false;
        }
    }

    return { isPanning, onPanStart, onPanMove, onPanEnd, onContainerClickCapture };
}
