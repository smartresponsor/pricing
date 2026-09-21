<?php

declare(strict_types=1);

namespace App\Pricing\Exception;

/**
 * Signals that a requested historical PriceSet revision is unavailable.
 */
final class PriceHistoryNotFoundException extends \RuntimeException
{
}
