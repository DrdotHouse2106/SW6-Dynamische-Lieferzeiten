<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Service;

use DrDotHouse\MonthlyDelivery\Struct\DeliveryRule;
use DrDotHouse\MonthlyDelivery\Struct\DeliverySchedule;

/**
 * Reine Berechnungslogik ohne Shopware-Abhängigkeiten (vollständig unit-testbar).
 *
 * Für den Standardfall gilt:
 *  L = letzter Mittwoch des Monats (Eintreffen beim Shop)
 *  D = L - 7 Tage, 23:59:59 Uhr (Bestellschluss = vorletzter Mittwoch)
 *  V = Montag nach L (= L + 5 Tage, Versand an den Kunden)
 *
 *  $now <= D  → Termine dieses Monats
 *  $now >  D  → Termine des Folgemonats (bzw. des nächsten nicht ausgeschlossenen Monats)
 *
 * Alle Berechnungen erfolgen in der Zeitzone von $now.
 */
class DeliveryCalculatorService
{
    public function calculate(DeliveryRule $rule, \DateTimeInterface $now): DeliverySchedule
    {
        $now = \DateTimeImmutable::createFromInterface($now);
        $timezone = $now->getTimezone();

        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        // Höchstens 13 Monate vorausschauen (alle 12 Monate auszuschließen ist per DeliveryRule verboten).
        for ($i = 0; $i <= 12; ++$i) {
            if (!\in_array($month, $rule->getExcludedMonths(), true)) {
                $arrival = $this->getArrivalDate($rule, $year, $month, $timezone);
                $deadline = $this->getDeadline($rule, $arrival);

                if ($now <= $deadline) {
                    return new DeliverySchedule($arrival, $deadline, $this->getShipDate($rule, $arrival), $rule);
                }
            }

            ++$month;
            if ($month > 12) {
                $month = 1;
                ++$year;
            }
        }

        throw new \LogicException('Es konnte kein Liefertermin berechnet werden.');
    }

    /**
     * Eintreffdatum L: n-ter (bzw. letzter / vorletzter) Wochentag im angegebenen Monat, 00:00 Uhr.
     */
    public function getArrivalDate(DeliveryRule $rule, int $year, int $month, ?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        $timezone ??= new \DateTimeZone(date_default_timezone_get());
        $weekday = $rule->getArrivalWeekday();
        $occurrence = $rule->getArrivalOccurrence();

        if ($occurrence > 0) {
            $first = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $timezone);
            $diff = ($weekday - (int) $first->format('N') + 7) % 7;

            return $first->modify(sprintf('+%d days', $diff + 7 * ($occurrence - 1)));
        }

        $last = (new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $timezone))->modify('last day of this month');
        $diff = ((int) $last->format('N') - $weekday + 7) % 7;

        return $last->modify(sprintf('-%d days', $diff + 7 * (-$occurrence - 1)));
    }

    /**
     * Bestellschluss D: Eintreffdatum minus Vorlauf (Standard 7 Tage) zur konfigurierten Uhrzeit.
     */
    public function getDeadline(DeliveryRule $rule, \DateTimeImmutable $arrival): \DateTimeImmutable
    {
        [$hour, $minute, $second] = array_map('intval', explode(':', $rule->getDeadlineTime()));

        return $arrival
            ->modify(sprintf('-%d days', $rule->getDeadlineOffsetDays()))
            ->setTime($hour, $minute, $second);
    }

    /**
     * Versanddatum V: der nächste konfigurierte Wochentag strikt nach dem Eintreffdatum (Standard: Montag).
     */
    public function getShipDate(DeliveryRule $rule, \DateTimeImmutable $arrival): \DateTimeImmutable
    {
        $diff = ($rule->getShipWeekday() - (int) $arrival->format('N') + 7) % 7;

        return $arrival->modify(sprintf('+%d days', $diff === 0 ? 7 : $diff));
    }
}
