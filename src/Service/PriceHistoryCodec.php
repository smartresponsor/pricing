<?php

declare(strict_types=1);

namespace App\Pricing\Service;

use App\Pricing\DTO\PriceDefinitionDTO;
use App\Pricing\DTO\PriceListDTO;
use App\Pricing\DTO\PriceSetDTO;
use App\Pricing\ServiceInterface\PriceHistoryCodecInterface;

/**
 * Produces stable JSON-compatible PriceSet revision payloads and restores immutable DTOs.
 */
final class PriceHistoryCodec implements PriceHistoryCodecInterface
{
    public function encode(PriceSetDTO $priceSet): array
    {
        return [
            'id' => $priceSet->id,
            'priceableReference' => $priceSet->priceableReference,
            'revision' => $priceSet->revision,
            'priceLists' => array_map(static fn (PriceListDTO $list): array => [
                'id' => $list->id,
                'enabled' => $list->enabled,
                'priority' => $list->priority,
                'context' => $list->context,
                'startsAt' => $list->startsAt?->format(\DateTimeInterface::ATOM),
                'endsAt' => $list->endsAt?->format(\DateTimeInterface::ATOM),
                'revision' => $list->revision,
            ], $priceSet->priceLists),
            'prices' => array_map(static fn (PriceDefinitionDTO $price): array => [
                'id' => $price->id,
                'priceSetId' => $price->priceSetId,
                'priceableReference' => $price->priceableReference,
                'currencyCode' => $price->currencyCode,
                'amountMinor' => $price->amountMinor,
                'priceListId' => $price->priceListId,
                'minimumQuantity' => $price->minimumQuantity,
                'maximumQuantity' => $price->maximumQuantity,
                'context' => $price->context,
                'startsAt' => $price->startsAt?->format(\DateTimeInterface::ATOM),
                'endsAt' => $price->endsAt?->format(\DateTimeInterface::ATOM),
                'referenceAmountMinor' => $price->referenceAmountMinor,
                'taxIncluded' => $price->taxIncluded,
                'revision' => $price->revision,
            ], $priceSet->prices),
        ];
    }

    public function decode(array $payload): PriceSetDTO
    {
        $lists = [];
        foreach ($this->listOfMaps($payload, 'priceLists') as $data) {
            $lists[] = new PriceListDTO(
                id: $this->string($data, 'id'),
                enabled: $this->bool($data, 'enabled'),
                priority: $this->int($data, 'priority'),
                context: $this->context($data, 'context'),
                startsAt: $this->date($data, 'startsAt'),
                endsAt: $this->date($data, 'endsAt'),
                revision: $this->int($data, 'revision'),
            );
        }

        $prices = [];
        foreach ($this->listOfMaps($payload, 'prices') as $data) {
            $prices[] = new PriceDefinitionDTO(
                id: $this->string($data, 'id'),
                priceSetId: $this->string($data, 'priceSetId'),
                priceableReference: $this->string($data, 'priceableReference'),
                currencyCode: $this->string($data, 'currencyCode'),
                amountMinor: $this->int($data, 'amountMinor'),
                priceListId: $this->nullableString($data, 'priceListId'),
                minimumQuantity: $this->int($data, 'minimumQuantity'),
                maximumQuantity: $this->nullableInt($data, 'maximumQuantity'),
                context: $this->context($data, 'context'),
                startsAt: $this->date($data, 'startsAt'),
                endsAt: $this->date($data, 'endsAt'),
                referenceAmountMinor: $this->nullableInt($data, 'referenceAmountMinor'),
                taxIncluded: $this->bool($data, 'taxIncluded'),
                revision: $this->int($data, 'revision'),
            );
        }

        return new PriceSetDTO(
            id: $this->string($payload, 'id'),
            priceableReference: $this->string($payload, 'priceableReference'),
            prices: $prices,
            priceLists: $lists,
            revision: $this->int($payload, 'revision'),
        );
    }

    public function hash(array $payload): string
    {
        try {
            return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Price history payload cannot be encoded as canonical JSON.', previous: $exception);
        }
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be a string.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if (null !== $value && !is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be a string or null.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be an integer.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if (null !== $value && !is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be an integer or null.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function bool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;
        if (!is_bool($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be a boolean.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function context(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be a context map.', $key));
        }

        $context = [];
        foreach ($value as $name => $item) {
            if (!is_string($name) || !is_string($item)) {
                throw new \InvalidArgumentException(sprintf('Price history field %s must contain string keys and values.', $key));
            }
            $context[$name] = $item;
        }

        return $context;
    }

    /** @param array<string, mixed> $data */
    private function date(array $data, string $key): ?\DateTimeImmutable
    {
        $value = $this->nullableString($data, $key);

        return null === $value ? null : new \DateTimeImmutable($value);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function listOfMaps(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new \InvalidArgumentException(sprintf('Price history field %s must be a list.', $key));
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('Price history field %s must contain maps.', $key));
            }
            $result[] = $item;
        }

        return $result;
    }
}
