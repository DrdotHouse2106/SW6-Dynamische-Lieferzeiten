<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Tests\Unit;

use DrDotHouse\MonthlyDelivery\Service\DeliveryCalculatorService;
use DrDotHouse\MonthlyDelivery\Struct\DeliveryRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeliveryCalculatorServiceTest extends TestCase
{
    private DeliveryCalculatorService $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DeliveryCalculatorService();
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function standardCases(): iterable
    {
        // Oktober 2026: Mittwoche 7., 14., 21., 28. → L = 28.10., D = 21.10. 23:59:59, V = 02.11.
        yield 'Anfang des Monats' => ['2026-10-01 08:00:00', '2026-10-28', '2026-10-21 23:59:59', '2026-11-02'];
        yield 'genau am Bestellschluss' => ['2026-10-21 23:59:59', '2026-10-28', '2026-10-21 23:59:59', '2026-11-02'];
        // November 2026: Mittwoche 4., 11., 18., 25. → L = 25.11., D = 18.11., V = 30.11.
        yield 'eine Sekunde nach Bestellschluss' => ['2026-10-22 00:00:00', '2026-11-25', '2026-11-18 23:59:59', '2026-11-30'];
        yield 'nach Eintreffen im selben Monat' => ['2026-10-30 12:00:00', '2026-11-25', '2026-11-18 23:59:59', '2026-11-30'];
        // Dezember 2026: letzter Mittwoch 30.12. → D = 23.12., V = 04.01.2027 (Jahreswechsel)
        yield 'Jahreswechsel beim Versand' => ['2026-12-01 00:00:00', '2026-12-30', '2026-12-23 23:59:59', '2027-01-04'];
        // Januar 2027: letzter Mittwoch 27.01. → D = 20.01., V = 01.02.
        yield 'Jahreswechsel beim Folgemonat' => ['2026-12-24 00:00:00', '2027-01-27', '2027-01-20 23:59:59', '2027-02-01'];
        // Februar 2028 (Schaltjahr): letzter Mittwoch 23.02. → D = 16.02., V = 28.02.
        yield 'Schaltjahr' => ['2028-02-01 00:00:00', '2028-02-23', '2028-02-16 23:59:59', '2028-02-28'];
        // Juli 2026 beginnt mit einem Mittwoch → letzter Mittwoch 29.07.
        yield 'Silvester' => ['2026-12-31 00:00:00', '2027-01-27', '2027-01-20 23:59:59', '2027-02-01'];
        yield 'Juli 2026' => ['2026-07-01 00:00:00', '2026-07-29', '2026-07-22 23:59:59', '2026-08-03'];
    }

    #[DataProvider('standardCases')]
    public function testStandardRule(string $now, string $arrival, string $deadline, string $ship): void
    {
        $tz = new \DateTimeZone('Europe/Berlin');
        $schedule = $this->calculator->calculate(new DeliveryRule(), new \DateTimeImmutable($now, $tz));

        static::assertSame($arrival, $schedule->getArrivalDate()->format('Y-m-d'));
        static::assertSame($deadline, $schedule->getDeadline()->format('Y-m-d H:i:s'));
        static::assertSame($ship, $schedule->getShipDate()->format('Y-m-d'));
        static::assertSame('1', $schedule->getShipDate()->format('N'), 'Versand muss an einem Montag sein');
        static::assertSame('3', $schedule->getDeadline()->format('N'), 'Bestellschluss muss an einem Mittwoch sein');
    }

    public function testCalculationUsesTimezoneOfNow(): void
    {
        // 21.10.2026 22:30 UTC = 22.10.2026 00:30 in Berlin → Bestellschluss in Berlin bereits vorbei
        $utc = new \DateTimeImmutable('2026-10-21 22:30:00', new \DateTimeZone('UTC'));
        $berlin = $utc->setTimezone(new \DateTimeZone('Europe/Berlin'));

        static::assertSame('2026-11-02', $this->calculator->calculate(new DeliveryRule(), $utc)->getShipDate()->format('Y-m-d'));
        static::assertSame('2026-11-30', $this->calculator->calculate(new DeliveryRule(), $berlin)->getShipDate()->format('Y-m-d'));
    }

    public function testSupplierSpecificRule(): void
    {
        // Lieferant B: kommt am ersten Dienstag, Bestellschluss 10 Tage vorher um 12:00, Versand am Donnerstag
        $rule = new DeliveryRule('lieferant-b', 'Lieferant B', 2, 1, 10, '12:00', 4);

        // November 2026: erster Dienstag 03.11. → D = 24.10. 12:00, V = 05.11.
        $schedule = $this->calculator->calculate($rule, new \DateTimeImmutable('2026-10-07 10:00:00'));
        static::assertSame('2026-11-03', $schedule->getArrivalDate()->format('Y-m-d'));
        static::assertSame('2026-10-24 12:00:00', $schedule->getDeadline()->format('Y-m-d H:i:s'));
        static::assertSame('2026-11-05', $schedule->getShipDate()->format('Y-m-d'));

        // Oktober-Termin (06.10.) liegt in der Vergangenheit → November wird verwendet,
        // sobald dessen Deadline vorbei ist → Dezember (erster Dienstag 01.12., D = 21.11. 12:00)
        $schedule = $this->calculator->calculate($rule, new \DateTimeImmutable('2026-10-24 12:00:01'));
        static::assertSame('2026-12-01', $schedule->getArrivalDate()->format('Y-m-d'));
        static::assertSame('2026-11-21 12:00:00', $schedule->getDeadline()->format('Y-m-d H:i:s'));
    }

    public function testSecondToLastOccurrence(): void
    {
        $rule = new DeliveryRule(arrivalOccurrence: -2);
        // Oktober 2026: vorletzter Mittwoch 21.10. → D = 14.10.
        $schedule = $this->calculator->calculate($rule, new \DateTimeImmutable('2026-10-07'));
        static::assertSame('2026-10-21', $schedule->getArrivalDate()->format('Y-m-d'));
        static::assertSame('2026-10-14', $schedule->getDeadline()->format('Y-m-d'));
        static::assertSame('2026-10-26', $schedule->getShipDate()->format('Y-m-d'));
    }

    public function testExcludedMonthsAreSkipped(): void
    {
        $rule = new DeliveryRule(excludedMonths: [11, 12]);
        $schedule = $this->calculator->calculate($rule, new \DateTimeImmutable('2026-10-22'));

        static::assertSame('2027-01-27', $schedule->getArrivalDate()->format('Y-m-d'));
        static::assertSame('2027-02-01', $schedule->getShipDate()->format('Y-m-d'));
    }

    public function testShipWeekdayEqualToArrivalWeekdayMeansOneWeekLater(): void
    {
        $rule = new DeliveryRule(shipWeekday: 3);
        $schedule = $this->calculator->calculate($rule, new \DateTimeImmutable('2026-10-01'));

        static::assertSame('2026-11-04', $schedule->getShipDate()->format('Y-m-d'));
    }

    public function testPayloadArray(): void
    {
        $schedule = $this->calculator->calculate(new DeliveryRule(), new \DateTimeImmutable('2026-10-07 10:00:00', new \DateTimeZone('Europe/Berlin')));
        $data = $schedule->toArray();

        static::assertSame('02.11.2026', $data['shipDateFormatted']);
        static::assertSame(1, $data['shipWeekday']);
        static::assertSame('21.10.2026', $data['deadlineFormatted']);
        static::assertSame('23:59', $data['deadlineTime']);
        static::assertSame(3, $data['deadlineWeekday']);
        static::assertSame('2026-10-21T23:59:59+02:00', $data['deadline']);
    }

    /**
     * Vertrag mit der App und der Store-API: Diese Schlüssel liest die App
     * (product.extensions.monthlyDelivery bzw. lineItem.payload.monthlyDelivery).
     */
    public function testPayloadContainsAllKeysUsedByTheApp(): void
    {
        $schedule = $this->calculator->calculate(new DeliveryRule(), new \DateTimeImmutable('2026-10-07 10:00:00', new \DateTimeZone('Europe/Berlin')));
        $data = $schedule->toArray();

        foreach (['profile', 'profileName', 'shipDate', 'shipDateFormatted', 'shipWeekday', 'deadline', 'deadlineFormatted', 'deadlineTime', 'deadlineWeekday', 'arrivalDateFormatted'] as $key) {
            static::assertArrayHasKey($key, $data, $key);
        }

        static::assertSame('2026-11-02', $data['shipDate']);
        static::assertSame('28.10.2026', $data['arrivalDateFormatted']);
    }

    public function testParseExcludedMonths(): void
    {
        static::assertSame([8, 12], DeliveryRule::parseExcludedMonths(' 8, 12;13 abc 0 8'));
        static::assertSame([], DeliveryRule::parseExcludedMonths(null));
    }

    public function testInvalidRuleIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeliveryRule(arrivalWeekday: 8);
    }

    public function testAllMonthsExcludedIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeliveryRule(excludedMonths: range(1, 12));
    }
}
