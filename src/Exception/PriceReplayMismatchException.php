<?php

declare(strict_types=1);

namespace App\Pricing\Exception;

/**
 * Signals that historical pricing material no longer reproduces a captured selection.
 */
final class PriceReplayMismatchException extends \RuntimeException
{
}
