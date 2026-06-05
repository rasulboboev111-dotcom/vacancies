<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by TrashService when a restore/force-delete cannot proceed because a
 * live record now occupies a unique slot, or a RESTRICT dependant still exists.
 * The message is user-facing (already localised) and surfaced as a flash error.
 */
class TrashConflictException extends RuntimeException {}
