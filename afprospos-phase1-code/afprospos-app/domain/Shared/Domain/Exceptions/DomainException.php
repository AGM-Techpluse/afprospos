<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Exceptions;

use RuntimeException;

/**
 * Base type for every business-rule exception in the system (ADD §37).
 * Presentation-layer code maps subclasses to HTTP responses; it must
 * never expose a raw provider/database error to the user instead.
 */
abstract class DomainException extends RuntimeException {}
