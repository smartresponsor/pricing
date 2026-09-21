<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Smoke;

use App\Pricing\PricingBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Verifies the reusable Pricing bundle surface without introducing product behavior.
 */
final class PricingBundleTest extends TestCase
{
    /**
     * Confirms that the component exposes the Symfony bundle contract required for host composition.
     */
    public function testBundleSurface(): void
    {
        self::assertInstanceOf(Bundle::class, new PricingBundle());
    }
}
