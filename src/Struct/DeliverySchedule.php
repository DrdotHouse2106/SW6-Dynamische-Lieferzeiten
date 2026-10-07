<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Struct;

/**
 * Ergebnis einer Berechnung: Eintreffdatum (L), Bestellschluss (D) und Versanddatum (V).
 */
final class DeliverySchedule
{
    /** Schlüssel für Produkt-Extension und Line-Item-Payload */
    public const KEY = 'monthlyDelivery';

    public function __construct(
        private readonly \DateTimeImmutable $arrivalDate,
        private readonly \DateTimeImmutable $deadline,
        private readonly \DateTimeImmutable $shipDate,
        private readonly DeliveryRule $rule,
    ) {
    }

    public function getArrivalDate(): \DateTimeImmutable
    {
        return $this->arrivalDate;
    }

    public function getDeadline(): \DateTimeImmutable
    {
        return $this->deadline;
    }

    public function getShipDate(): \DateTimeImmutable
    {
        return $this->shipDate;
    }

    public function getRule(): DeliveryRule
    {
        return $this->rule;
    }

    /**
     * Zeitzonenneutrale, serialisierbare Darstellung. Wird als Produkt-Extension in der Storefront
     * und als Line-Item-Payload (→ order_line_item.payload, Bestellbestätigung, E-Mails) verwendet.
     *
     * Die Daten sind bereits in der Shop-Zeitzone formatiert, damit Twig-Filter mit Zeitzonen-
     * umrechnung (Timezone-Cookie der Storefront) das Datum nicht verschieben können.
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->rule->getTechnicalName(),
            'profileName' => $this->rule->getName(),
            'shipDate' => $this->shipDate->format('Y-m-d'),
            'shipDateFormatted' => $this->shipDate->format('d.m.Y'),
            'shipWeekday' => (int) $this->shipDate->format('N'),
            'deadline' => $this->deadline->format(\DateTimeInterface::ATOM),
            'deadlineDate' => $this->deadline->format('Y-m-d'),
            'deadlineFormatted' => $this->deadline->format('d.m.Y'),
            'deadlineTime' => $this->deadline->format('H:i'),
            'deadlineWeekday' => (int) $this->deadline->format('N'),
            'arrivalDate' => $this->arrivalDate->format('Y-m-d'),
            'arrivalDateFormatted' => $this->arrivalDate->format('d.m.Y'),
            'arrivalWeekday' => (int) $this->arrivalDate->format('N'),
        ];
    }
}
