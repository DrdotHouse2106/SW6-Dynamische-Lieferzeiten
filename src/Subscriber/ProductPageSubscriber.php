<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Subscriber;

use DrDotHouse\MonthlyDelivery\Service\MonthlyDeliveryService;
use DrDotHouse\MonthlyDelivery\Struct\DeliverySchedule;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hängt den berechneten Versandtermin an alle in der Storefront geladenen Produkte
 * (Produktdetailseite, CMS-Buy-Box, Listing, Cross-Selling, Warenkorb).
 *
 * Es werden ausschließlich Zusatzfelder gelesen – deliveryTime/restockTime bleiben unberührt.
 */
class ProductPageSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MonthlyDeliveryService $monthlyDeliveryService)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.product.loaded' => 'onProductsLoaded',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductsLoaded(SalesChannelEntityLoadedEvent $event): void
    {
        $products = array_filter(
            $event->getEntities(),
            static fn ($entity) => $entity instanceof ProductEntity
        );

        if ($products !== []) {
            $this->monthlyDeliveryService->enrichProducts($products, $event->getSalesChannelContext());
        }
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();

        if (!$product->hasExtension(DeliverySchedule::KEY)) {
            $this->monthlyDeliveryService->enrichProducts([$product], $event->getSalesChannelContext());
        }

        // Zusätzlich an der Seite verfügbar machen: page.extensions.monthlyDelivery
        $extension = $product->getExtension(DeliverySchedule::KEY);
        if ($extension !== null) {
            $event->getPage()->addExtension(DeliverySchedule::KEY, $extension);
        }
    }
}
