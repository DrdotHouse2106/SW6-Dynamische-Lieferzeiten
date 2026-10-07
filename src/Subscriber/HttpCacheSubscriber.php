<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Subscriber;

use DrDotHouse\MonthlyDelivery\Service\DeliveryProfileResolver;
use DrDotHouse\MonthlyDelivery\Service\MonthlyDeliveryService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Verhindert, dass der HTTP-Cache nach dem Bestellschluss noch den alten Versandtermin ausliefert:
 * Enthält eine Seite einen monatlichen Liefertermin, wird die Cache-Lebensdauer (s-maxage)
 * höchstens bis zum frühesten Bestellschluss auf dieser Seite gesetzt.
 *
 * Läuft nach Shopwares CacheResponseSubscriber::setResponseCache (Priorität -1500).
 */
class HttpCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MonthlyDeliveryService $monthlyDeliveryService,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['limitCacheLifetime', -1600],
        ];
    }

    public function limitCacheLifetime(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->systemConfigService->getBool(DeliveryProfileResolver::CONFIG_DOMAIN . 'limitHttpCache')) {
            return;
        }

        $deadline = $this->monthlyDeliveryService->getEarliestDeadline();
        $response = $event->getResponse();
        $sharedMaxAge = $response->headers->getCacheControlDirective('s-maxage');

        if ($deadline === null || $sharedMaxAge === null) {
            return;
        }

        $secondsUntilDeadline = max(1, $deadline->getTimestamp() - time() + 1);

        if ($secondsUntilDeadline < (int) $sharedMaxAge) {
            $response->setSharedMaxAge($secondsUntilDeadline);
        }
    }
}
