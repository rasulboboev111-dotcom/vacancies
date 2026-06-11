<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Бросается TrashService, когда восстановление/жёсткое удаление не может пройти,
 * потому что живая запись теперь занимает уникальный слот или всё ещё существует
 * зависимость с RESTRICT. Сообщение предназначено для пользователя (уже
 * локализовано) и показывается как flash-ошибка.
 */
class TrashConflictException extends RuntimeException {}
