<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Currencing\ServiceInterface\CurrencyCodeValidatorInterface;
use App\Currencing\ServiceInterface\CurrencyPrecisionResolverInterface;
use App\Pricing\DTO\PriceCurrencyMetadataDTO;
use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceQuoteDTO;
use App\Pricing\DTO\PriceSelectionCriteriaDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\Service\PriceCurrencyValidationService;
use App\Pricing\Service\PriceQuoteService;
use App\Pricing\Service\PriceSelectionService;
use App\Pricing\Service\PriceSelectionSnapshotService;
use PHPUnit\Framework\TestCase;

/** Verifies Currencing-backed validation and the typed outbound Pricing quote contract. */
final class PriceQuoteServiceTest extends TestCase
{
    /** Proves a supported currency quote exposes authoritative precision and immutable provenance. */
    public function testQuoteCarriesCurrencingPrecisionAndSelectionProvenance(): void
    {
        $set = new PriceSetDTO(
            'set-quote',
            'catalog:variant:2001',
            [new PriceDefinitionDTO('usd-price', 'set-quote', 'catalog:variant:2001', 'USD', 1234, revision: 2)],
            revision: 4,
        );
        $criteria = new PriceSelectionCriteriaDTO(
            'USD',
            1,
            new \DateTimeImmutable('2026-09-21T13:30:00+00:00'),
        );
        $service = new PriceQuoteService(
            new PriceSelectionService(),
            new PriceSelectionSnapshotService(),
            new PriceCurrencyValidationService(),
        );

        $quote = $service->quote(
            $set,
            $criteria,
            new PriceCurrencyCodeValidator(['USD']),
            new PriceCurrencyPrecisionResolver(['USD' => 2]),
        );

        self::assertSame(1234, $quote->amountMinor());
        self::assertSame('catalog:variant:2001', $quote->priceableReference());
        self::assertSame('USD', $quote->currency->currencyCode);
        self::assertSame(2, $quote->currency->minorUnit);
        self::assertSame(100, $quote->currency->factor);
        self::assertSame(4, $quote->snapshot->priceSetRevision);
        self::assertSame(2, $quote->snapshot->priceRevision);
    }

    /** Proves unsupported selection currencies are rejected by Currencing before Pricing selection. */
    public function testQuoteRejectsUnsupportedSelectionCurrency(): void
    {
        $set = new PriceSetDTO(
            'set-quote',
            'catalog:variant:2002',
            [new PriceDefinitionDTO('usd-price', 'set-quote', 'catalog:variant:2002', 'USD', 1000)],
        );
        $service = new PriceQuoteService(
            new PriceSelectionService(),
            new PriceSelectionSnapshotService(),
            new PriceCurrencyValidationService(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->quote(
            $set,
            new PriceSelectionCriteriaDTO('ZZZ', 1, new \DateTimeImmutable()),
            new PriceCurrencyCodeValidator(['USD']),
            new PriceCurrencyPrecisionResolver(['USD' => 2]),
        );
    }

    /** Proves every price definition is validated against Currencing, not only the requested currency. */
    public function testQuoteRejectsUnsupportedDefinedCurrency(): void
    {
        $set = new PriceSetDTO(
            'set-quote',
            'catalog:variant:2003',
            [
                new PriceDefinitionDTO('usd-price', 'set-quote', 'catalog:variant:2003', 'USD', 1000),
                new PriceDefinitionDTO('unsupported-price', 'set-quote', 'catalog:variant:2003', 'ZZZ', 900),
            ],
        );
        $service = new PriceQuoteService(
            new PriceSelectionService(),
            new PriceSelectionSnapshotService(),
            new PriceCurrencyValidationService(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->quote(
            $set,
            new PriceSelectionCriteriaDTO('USD', 1, new \DateTimeImmutable()),
            new PriceCurrencyCodeValidator(['USD']),
            new PriceCurrencyPrecisionResolver(['USD' => 2]),
        );
    }

    /** Proves currency metadata rejects inconsistent precision factors. */
    public function testCurrencyMetadataRejectsInconsistentFactor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PriceCurrencyMetadataDTO('USD', 2, 1000);
    }

    /** Proves a quote cannot combine snapshot provenance with another currency. */
    public function testQuoteRejectsCurrencyMetadataMismatch(): void
    {
        $snapshot = new PriceSelectionSnapshotDTO(
            priceSetId: 'set-quote',
            priceSetRevision: 1,
            priceableReference: 'catalog:variant:2004',
            priceId: 'usd-price',
            priceRevision: 1,
            priceListId: null,
            priceListRevision: null,
            currencyCode: 'USD',
            quantity: 1,
            amountMinor: 1000,
            referenceAmountMinor: null,
            taxIncluded: false,
            selectedAt: new \DateTimeImmutable('2026-09-21T13:30:00+00:00'),
            context: [],
            explanation: 'test',
        );

        $this->expectException(\InvalidArgumentException::class);
        new PriceQuoteDTO($snapshot, new PriceCurrencyMetadataDTO('EUR', 2, 100));
    }
}

/** Minimal Currencing validator test double preserving the public consumer contract. */
final class PriceCurrencyCodeValidator implements CurrencyCodeValidatorInterface
{
    /** @param list<string> $supported */
    public function __construct(private array $supported)
    {
    }

    public function supports(string $currencyCode): bool
    {
        return in_array($currencyCode, $this->supported, true);
    }

    public function assertSupported(string $currencyCode): void
    {
        if (!$this->supports($currencyCode)) {
            throw new \InvalidArgumentException(sprintf('Unsupported currency %s.', $currencyCode));
        }
    }
}

/** Minimal Currencing precision test double preserving the public consumer contract. */
final class PriceCurrencyPrecisionResolver implements CurrencyPrecisionResolverInterface
{
    /** @param array<string, int> $minorUnits */
    public function __construct(private array $minorUnits)
    {
    }

    public function minorUnitFor(string $currencyCode): int
    {
        return $this->minorUnits[$currencyCode] ?? throw new \InvalidArgumentException('Unsupported currency precision.');
    }

    public function factorFor(string $currencyCode): int
    {
        return 10 ** $this->minorUnitFor($currencyCode);
    }
}
