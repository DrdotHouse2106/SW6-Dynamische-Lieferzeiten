<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Core\Content\Product;

use DrDotHouse\MonthlyDelivery\Struct\DeliverySchedule;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Macht die Laufzeit-Extension „monthlyDelivery“ für die Store-API sichtbar.
 *
 * Shopware gibt in Store-API-Antworten nur Extensions aus, die in der Entity-Definition als
 * Feld mit ApiAware registriert sind (StructEncoder). Ohne diese Registrierung würde der
 * berechnete Versandtermin z. B. in der App nie ankommen. Das Feld ist ein reines
 * Laufzeitfeld (Runtime) – es wird weder gespeichert noch aus der Datenbank gelesen.
 */
class ProductMonthlyDeliveryExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new JsonField('monthly_delivery', DeliverySchedule::KEY))->addFlags(new ApiAware(), new Runtime())
        );
    }

    public function getEntityName(): string
    {
        return 'product';
    }
}
