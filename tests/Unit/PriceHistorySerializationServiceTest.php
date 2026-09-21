<?php

declare(strict_types=1);

namespace App\Pricing\Tests\Unit;

use App\Pricing\Service\PriceHistorySerializationService;
use PHPUnit\Framework\TestCase;

/** Verifies strict decoding of persisted price-history payloads at the storage boundary. */
final class PriceHistorySerializationServiceTest extends TestCase
{
    /** Proves every persisted scalar/container type is validated before DTO reconstruction. */
    public function testDecodeRejectsMalformedPersistenceFields(): void
    {
        $service = new PriceHistorySerializationService();

        foreach ([
            static function (array $payload): array {
                $payload['priceLists'] = 'invalid';

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'] = ['not-a-list' => []];

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'] = ['invalid'];

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['id'] = 10;

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['enabled'] = 'yes';

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['priority'] = '10';

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['context'] = 'invalid';

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['context'] = [0 => 'us'];

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['context'] = ['region' => 1];

                return $payload;
            },
            static function (array $payload): array {
                $payload['priceLists'][0]['startsAt'] = 1;

                return $payload;
            },
            static function (array $payload): array {
                $payload['prices'][0]['priceListId'] = 1;

                return $payload;
            },
            static function (array $payload): array {
                $payload['prices'][0]['maximumQuantity'] = '5';

                return $payload;
            },
        ] as $mutate) {
            try {
                $service->decode($mutate($this->payload()));
                self::fail('Expected malformed persisted price history to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** Proves nullable persisted values and valid date/context/list shapes reconstruct exactly. */
    public function testDecodeAcceptsCanonicalNullableAndTypedPayload(): void
    {
        $restored = (new PriceHistorySerializationService())->decode($this->payload());

        self::assertSame('set-history', $restored->id);
        self::assertSame('vip', $restored->priceLists[0]->id);
        self::assertSame('us', $restored->priceLists[0]->context['region']);
        self::assertSame('2026-09-01T00:00:00+00:00', $restored->priceLists[0]->startsAt?->format(\DateTimeInterface::ATOM));
        self::assertNull($restored->priceLists[0]->endsAt);
        self::assertSame(5, $restored->prices[0]->maximumQuantity);
        self::assertNull($restored->prices[0]->referenceAmountMinor);
    }

    /** Proves non-JSON numeric values fail hashing instead of producing unstable persistence digests. */
    public function testHashRejectsNonJsonPayload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PriceHistorySerializationService())->hash(['amount' => INF]);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'id' => 'set-history',
            'priceableReference' => 'catalog:variant:900',
            'revision' => 7,
            'priceLists' => [
                [
                    'id' => 'vip',
                    'enabled' => true,
                    'priority' => 20,
                    'context' => ['region' => 'us'],
                    'startsAt' => '2026-09-01T00:00:00+00:00',
                    'endsAt' => null,
                    'revision' => 3,
                ],
            ],
            'prices' => [
                [
                    'id' => 'vip-price',
                    'priceSetId' => 'set-history',
                    'priceableReference' => 'catalog:variant:900',
                    'currencyCode' => 'USD',
                    'amountMinor' => 1200,
                    'priceListId' => 'vip',
                    'minimumQuantity' => 2,
                    'maximumQuantity' => 5,
                    'context' => [],
                    'startsAt' => null,
                    'endsAt' => null,
                    'referenceAmountMinor' => null,
                    'taxIncluded' => false,
                    'revision' => 4,
                ],
            ],
        ];
    }
}
