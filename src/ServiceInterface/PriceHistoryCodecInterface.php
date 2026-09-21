<?php

declare(strict_types=1);

namespace App\Pricing\ServiceInterface;

use App\Pricing\DTO\PriceSetDTO;

/**
 * Encodes immutable PriceSet revisions into stable persistence payloads and restores them.
 */
interface PriceHistoryCodecInterface
{
    /** @return array<string, mixed> */
    public function encode(PriceSetDTO $priceSet): array;

    /** Restores an immutable PriceSet from one canonical persisted payload.
     *
     * @param array<string, mixed> $payload
     */
    public function decode(array $payload): PriceSetDTO;

    /** Produces the stable SHA-256 integrity digest for a canonical payload.
     *
     * @param array<string, mixed> $payload
     */
    public function hash(array $payload): string;
}
