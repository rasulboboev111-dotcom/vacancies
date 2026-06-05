<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by PositionService when a position cannot be deleted because active or
 * archived employees are still assigned to it. The message is user-facing
 * (already localised) and surfaced as a flash error.
 */
class PositionInUseException extends RuntimeException {}
