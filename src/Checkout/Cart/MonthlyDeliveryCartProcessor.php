<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Checkout\Cart;

use DrDotHouse\MonthlyDelivery\Service\MonthlyDeliveryService;
use DrDotHouse\MonthlyDelivery\Struct\DeliverySchedule;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Schreibt den berechneten Versandtermin in den Payload jedes betroffenen Produkt-Line-Items.
 *
 * Der Payload wird bei der Bestellung nach order_line_item.payload übernommen. Damit steht der
 * zum Bestellzeitpunkt zugesagte Termin dauerhaft in Bestellbestätigung, Kundenkonto und E-Mails
 * zur Verfügung (lineItem.payload.monthlyDelivery).
 *
 * Läuft nach dem ProductCartProcessor (Priorität 5000), der den Payload neu aufbaut.
 */
class MonthlyDeliveryCartProcessor implements CartProcessorInterface
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly MonthlyDeliveryService $monthlyDeliveryService,
        private readonly EntityRepository $productRepository,
    ) {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $lineItems = $toCalculate->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        if ($lineItems === []) {
            return;
        }

        $products = [];
        $missing = [];
        foreach ($lineItems as $lineItem) {
            $productId = $lineItem->getReferencedId();
            if ($productId === null) {
                continue;
            }

            $product = $data->get('product-' . $productId);
            if ($product instanceof ProductEntity) {
                $products[$productId] = $product;
            } else {
                $missing[$productId] = $productId;
            }
        }

        if ($missing !== []) {
            /** @var ProductCollection $loaded */
            $loaded = $this->productRepository->search(new Criteria(array_values($missing)), $context->getContext())->getEntities();
            foreach ($loaded as $product) {
                $products[$product->getId()] = $product;
            }
        }

        $schedules = $this->monthlyDeliveryService->getSchedules($products, $context);

        foreach ($lineItems as $lineItem) {
            $schedule = $schedules[$lineItem->getReferencedId() ?? ''] ?? null;

            if ($schedule === null) {
                if ($lineItem->hasPayloadValue(DeliverySchedule::KEY)) {
                    $lineItem->removePayloadValue(DeliverySchedule::KEY);
                }

                continue;
            }

            $lineItem->setPayloadValue(DeliverySchedule::KEY, $schedule->toArray());
        }
    }
}
