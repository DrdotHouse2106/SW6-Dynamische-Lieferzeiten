<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Core\Content\DeliveryProfile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<DeliveryProfileEntity>
 */
class DeliveryProfileCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DeliveryProfileEntity::class;
    }
}
