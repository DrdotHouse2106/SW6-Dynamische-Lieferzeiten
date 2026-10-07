<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Core\Content\DeliveryProfile;

use DrDotHouse\MonthlyDelivery\Struct\DeliveryRule;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class DeliveryProfileEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;

    protected string $technicalName;

    protected bool $active = true;

    protected int $arrivalWeekday = 3;

    protected int $arrivalOccurrence = -1;

    protected int $deadlineOffsetDays = 7;

    protected string $deadlineTime = '23:59:59';

    protected int $shipWeekday = 1;

    protected ?string $excludedMonths = null;

    protected ?string $description = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getArrivalWeekday(): int
    {
        return $this->arrivalWeekday;
    }

    public function setArrivalWeekday(int $arrivalWeekday): void
    {
        $this->arrivalWeekday = $arrivalWeekday;
    }

    public function getArrivalOccurrence(): int
    {
        return $this->arrivalOccurrence;
    }

    public function setArrivalOccurrence(int $arrivalOccurrence): void
    {
        $this->arrivalOccurrence = $arrivalOccurrence;
    }

    public function getDeadlineOffsetDays(): int
    {
        return $this->deadlineOffsetDays;
    }

    public function setDeadlineOffsetDays(int $deadlineOffsetDays): void
    {
        $this->deadlineOffsetDays = $deadlineOffsetDays;
    }

    public function getDeadlineTime(): string
    {
        return $this->deadlineTime;
    }

    public function setDeadlineTime(string $deadlineTime): void
    {
        $this->deadlineTime = $deadlineTime;
    }

    public function getShipWeekday(): int
    {
        return $this->shipWeekday;
    }

    public function setShipWeekday(int $shipWeekday): void
    {
        $this->shipWeekday = $shipWeekday;
    }

    public function getExcludedMonths(): ?string
    {
        return $this->excludedMonths;
    }

    public function setExcludedMonths(?string $excludedMonths): void
    {
        $this->excludedMonths = $excludedMonths;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @throws \InvalidArgumentException bei ungültiger Konfiguration
     */
    public function toRule(): DeliveryRule
    {
        return new DeliveryRule(
            $this->technicalName,
            $this->name,
            $this->arrivalWeekday,
            $this->arrivalOccurrence,
            $this->deadlineOffsetDays,
            $this->deadlineTime,
            $this->shipWeekday,
            DeliveryRule::parseExcludedMonths($this->excludedMonths),
        );
    }
}
