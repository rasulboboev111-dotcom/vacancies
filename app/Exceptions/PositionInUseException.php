<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Бросается PositionService, когда вазифу нельзя удалить, потому что к ней всё
 * ещё прикреплены активные или архивные корманды. Сообщение предназначено для
 * пользователя (уже локализовано) и показывается как flash-ошибка.
 */
class PositionInUseException extends RuntimeException {}
