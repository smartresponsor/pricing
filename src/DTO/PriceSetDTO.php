<?php

declare(strict_types=1);

namespace App\Pricing\DTO;

/**
 * Groups reusable prices for one external priceable reference.
 */
final readonly class PriceSetDTO
{
    /**
     * @param list<PriceDefinitionDTO> $prices
     * @param list<PriceListDTO>       $priceLists
     */
    public function __construct(
        public string $id,
        public string $priceableReference,
        public array $prices,
        public array $priceLists = [],
        public int $revision = 1,
    ) {
        if ('' === trim($this->id) || '' === trim($this->priceableReference)) {
            throw new \InvalidArgumentException('Price set id and priceable reference must not be empty.');
        }
        if ($this->revision < 1) {
            throw new \InvalidArgumentException('Price set revision must be at least one.');
        }

        $listIds = [];
        foreach ($this->priceLists as $priceList) {
            if (isset($listIds[$priceList->id])) {
                throw new \InvalidArgumentException('Price list ids must be unique inside a price set.');
            }
            $listIds[$priceList->id] = true;
        }

        $priceIds = [];
        foreach ($this->prices as $price) {
            if ($price->priceSetId !== $this->id || $price->priceableReference !== $this->priceableReference) {
                throw new \InvalidArgumentException('Every price must belong to the containing price set and priceable reference.');
            }
            if (isset($priceIds[$price->id])) {
                throw new \InvalidArgumentException('Price ids must be unique inside a price set.');
            }
            if (null !== $price->priceListId && !isset($listIds[$price->priceListId])) {
                throw new \InvalidArgumentException('Price references an unknown price list.');
            }
            $priceIds[$price->id] = true;
        }
    }

    /** Returns a price list by id without exposing persistence concerns. */
    public function priceList(?string $id): ?PriceListDTO
    {
        if (null === $id) {
            return null;
        }

        foreach ($this->priceLists as $priceList) {
            if ($priceList->id === $id) {
                return $priceList;
            }
        }

        return null;
    }
}
