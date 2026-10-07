<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Service;

use DrDotHouse\MonthlyDelivery\Struct\DeliverySchedule;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Fassade für Storefront und Warenkorb: ermittelt Regel + Termine und hängt sie an Produkte.
 */
class MonthlyDeliveryService implements ResetInterface
{
    private ?\DateTimeImmutable $earliestDeadline = null;

    public function __construct(
        private readonly DeliveryCalculatorService $calculator,
        private readonly DeliveryProfileResolver $resolver,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    /**
     * @param iterable<ProductEntity> $products
     *
     * @return array<string, DeliverySchedule> Termine je Produkt-ID (nur für aktivierte Produkte)
     */
    public function getSchedules(iterable $products, SalesChannelContext $context): array
    {
        $rules = $this->resolver->resolveRules($products, $context->getContext(), $context->getSalesChannelId());
        if ($rules === []) {
            return [];
        }

        $now = $this->now($context->getSalesChannelId());

        $schedules = [];
        foreach ($rules as $productId => $rule) {
            $schedules[$productId] = $this->calculator->calculate($rule, $now);
            $this->trackDeadline($schedules[$productId]->getDeadline());
        }

        return $schedules;
    }

    /**
     * Hängt die Termine als Extension "monthlyDelivery" an die Produkte
     * (Twig: product.extensions.monthlyDelivery.shipDateFormatted).
     *
     * @param iterable<ProductEntity> $products
     */
    public function enrichProducts(iterable $products, SalesChannelContext $context): void
    {
        $byId = [];
        foreach ($products as $product) {
            $byId[$product->getId()] = $product;
        }

        foreach ($this->getSchedules($byId, $context) as $productId => $schedule) {
            $byId[$productId]->addExtension(DeliverySchedule::KEY, new ArrayStruct($schedule->toArray()));
        }
    }

    public function now(?string $salesChannelId = null): \DateTimeImmutable
    {
        $timezone = $this->systemConfigService->getString(DeliveryProfileResolver::CONFIG_DOMAIN . 'timezone', $salesChannelId);

        try {
            $tz = new \DateTimeZone($timezone !== '' ? $timezone : 'Europe/Berlin');
        } catch (\Exception) {
            $tz = new \DateTimeZone('Europe/Berlin');
        }

        return new \DateTimeImmutable('now', $tz);
    }

    /**
     * Frühester Bestellschluss aller in diesem Request berechneten Termine.
     * Wird genutzt, um die HTTP-Cache-Lebensdauer bis zum Bestellschluss zu begrenzen.
     */
    public function getEarliestDeadline(): ?\DateTimeImmutable
    {
        return $this->earliestDeadline;
    }

    public function reset(): void
    {
        $this->earliestDeadline = null;
    }

    private function trackDeadline(\DateTimeImmutable $deadline): void
    {
        if ($this->earliestDeadline === null || $deadline < $this->earliestDeadline) {
            $this->earliestDeadline = $deadline;
        }
    }
}
