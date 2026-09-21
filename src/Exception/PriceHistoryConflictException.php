<?php

declare(strict_types=1);

namespace App\Pricing\Exception;

/**
 * Signals conflicting immutable payloads for one PriceSet revision.
 */
final class PriceHistoryConflictException extends \RuntimeException
{
}
