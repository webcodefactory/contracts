<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Shipping;

/**
 * How long a shop needs to hand an order over to the carrier, in business
 * days ("czas wysyłki"). Set on a warehouse in offer-api; a product takes
 * the time of the warehouse its stock comes from, an order the longest
 * time among its items. One vocabulary for offer-api, order-api and the
 * mails, so "5-7 dni roboczych" reads the same everywhere.
 *
 * Not the carrier's delivery time - that belongs to the shipping method.
 */
final readonly class DispatchTime implements \JsonSerializable
{
    public const MAX_DAYS = 365;

    private function __construct(
        public int $minDays,
        public int $maxDays,
    ) {
    }

    /**
     * @throws \InvalidArgumentException when the range is not 1..MAX_DAYS or runs backwards
     */
    public static function ofDays(int $minDays, int $maxDays): self
    {
        if ($minDays < 1 || $maxDays > self::MAX_DAYS || $minDays > $maxDays) {
            throw new \InvalidArgumentException(sprintf('Dispatch time %d-%d is not a range of 1 to %d business days', $minDays, $maxDays, self::MAX_DAYS));
        }

        return new self($minDays, $maxDays);
    }

    /** Both null means "not stated"; one without the other is a mistake */
    public static function ofNullableDays(?int $minDays, ?int $maxDays): ?self
    {
        if ($minDays === null && $maxDays === null) {
            return null;
        }
        if ($minDays === null || $maxDays === null) {
            throw new \InvalidArgumentException('Dispatch time needs both ends of the range');
        }

        return self::ofDays($minDays, $maxDays);
    }

    /**
     * From a serialized form ({minDays, maxDays}); null for anything that
     * does not describe a time (missing, malformed).
     */
    public static function fromArray(mixed $data): ?self
    {
        if (!is_array($data) || !isset($data['minDays'], $data['maxDays']) || !is_numeric($data['minDays']) || !is_numeric($data['maxDays'])) {
            return null;
        }

        try {
            return self::ofDays((int) $data['minDays'], (int) $data['maxDays']);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** The one that keeps the customer waiting longer; null counts as "nothing stated" */
    public static function longest(?self ...$times): ?self
    {
        $longest = null;
        foreach ($times as $time) {
            if ($time !== null && ($longest === null || $time->isLongerThan($longest))) {
                $longest = $time;
            }
        }

        return $longest;
    }

    public function isLongerThan(self $other): bool
    {
        return [$this->maxDays, $this->minDays] > [$other->maxDays, $other->minDays];
    }

    public function equals(?self $other): bool
    {
        return $other !== null && $this->minDays === $other->minDays && $this->maxDays === $other->maxDays;
    }

    /**
     * Completes "Wysyłka w ciągu ...": "24 h", "3 dni roboczych",
     * "5–7 dni roboczych".
     */
    public function label(): string
    {
        if ($this->minDays === $this->maxDays) {
            return $this->maxDays === 1 ? '24 h' : sprintf('%d dni roboczych', $this->maxDays);
        }

        return sprintf('%d–%d dni roboczych', $this->minDays, $this->maxDays);
    }

    /**
     * @return array{minDays: int, maxDays: int, label: string}
     */
    public function jsonSerialize(): array
    {
        return ['minDays' => $this->minDays, 'maxDays' => $this->maxDays, 'label' => $this->label()];
    }
}
