<?php

declare(strict_types=1);

namespace App\Pricing\Entity;

use App\Pricing\Repository\PriceHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Append-only persisted snapshot of one immutable PriceSet revision.
 */
#[ORM\Entity(repositoryClass: PriceHistoryRepository::class)]
#[ORM\Table(name: 'pricing_price_history')]
#[ORM\UniqueConstraint(name: 'uniq_pricing_price_history_set_revision', columns: ['price_set_id', 'revision'])]
#[ORM\Index(name: 'idx_pricing_price_history_reference', columns: ['priceable_reference'])]
final class PriceHistoryEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // @phpstan-ignore property.unusedType (Doctrine assigns generated identifiers after persistence.)
    private ?int $id = null;

    /** @param array<string, mixed> $payload */
    public function __construct(
        #[ORM\Column(name: 'price_set_id', type: 'string', length: 160)]
        private readonly string $priceSetId,
        #[ORM\Column(type: 'integer')]
        private readonly int $revision,
        #[ORM\Column(name: 'priceable_reference', type: 'string', length: 255)]
        private readonly string $priceableReference,
        #[ORM\Column(type: 'json')]
        private readonly array $payload,
        #[ORM\Column(name: 'payload_hash', type: 'string', length: 64)]
        private readonly string $payloadHash,
        #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $recordedAt,
    ) {
        if ('' === trim($this->priceSetId) || '' === trim($this->priceableReference)) {
            throw new \InvalidArgumentException('Price history identity and priceable reference must not be empty.');
        }
        if ($this->revision < 1) {
            throw new \InvalidArgumentException('Price history revision must be at least one.');
        }
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $this->payloadHash)) {
            throw new \InvalidArgumentException('Price history payload hash must be a lowercase SHA-256 digest.');
        }
    }

    /** Returns the persistence identifier assigned by Doctrine after insertion. */
    public function id(): ?int
    {
        return $this->id;
    }

    /** Returns the immutable PriceSet identity captured by this history record. */
    public function priceSetId(): string
    {
        return $this->priceSetId;
    }

    /** Returns the immutable PriceSet revision captured by this history record. */
    public function revision(): int
    {
        return $this->revision;
    }

    /** Returns the external priceable-resource reference captured by this revision. */
    public function priceableReference(): string
    {
        return $this->priceableReference;
    }

    /** Returns the canonical JSON-compatible PriceSet revision payload.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /** Returns the SHA-256 integrity digest of the canonical payload. */
    public function payloadHash(): string
    {
        return $this->payloadHash;
    }

    /** Returns when this immutable revision snapshot was first recorded. */
    public function recordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
