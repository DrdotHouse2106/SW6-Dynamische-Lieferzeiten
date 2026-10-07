<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Service;

use DrDotHouse\MonthlyDelivery\Core\Content\DeliveryProfile\DeliveryProfileDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldTypes;

/**
 * Legt die Zusatzfelder an, über die das Plugin gesteuert wird.
 *
 * Bewusst werden NICHT die Standardfelder product.deliveryTime / product.restockTime verwendet,
 * da diese von der WaWi (ERPNext / WeClapp) überschrieben werden. Zusatzfelder werden von Shopware
 * beim Update per JSON zusammengeführt – ein WaWi-Sync, der eigene Zusatzfelder schreibt, entfernt
 * unsere Felder daher nicht.
 */
class CustomFieldInstaller
{
    public const SET_PRODUCT = 'custom_supplier_monthly_delivery_set';
    public const SET_MANUFACTURER = 'custom_supplier_monthly_delivery_manufacturer_set';

    /** Aktivierung am Produkt (Pflicht, damit das Plugin für ein Produkt greift) */
    public const FIELD_ACTIVE = 'custom_supplier_monthly_delivery';

    /** Lieferprofil am Produkt (ID oder technischer Name) – überschreibt Hersteller/Lieferant und Standard */
    public const FIELD_PRODUCT_PROFILE = 'custom_supplier_monthly_delivery_profile';

    /** Lieferprofil am Hersteller/Lieferanten (ID oder technischer Name) */
    public const FIELD_MANUFACTURER_PROFILE = 'custom_supplier_monthly_delivery_manufacturer_profile';

    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(private readonly EntityRepository $customFieldSetRepository)
    {
    }

    public function install(Context $context): void
    {
        $this->customFieldSetRepository->upsert([
            [
                'id' => $this->id(self::SET_PRODUCT),
                'name' => self::SET_PRODUCT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Monatliche Lieferanten-Lieferung',
                        'en-GB' => 'Monthly supplier delivery',
                    ],
                ],
                'relations' => [
                    ['id' => $this->id(self::SET_PRODUCT . '.product'), 'entityName' => 'product'],
                ],
                'customFields' => [
                    [
                        'id' => $this->id(self::FIELD_ACTIVE),
                        'name' => self::FIELD_ACTIVE,
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'type' => 'switch',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'switch',
                            'customFieldPosition' => 1,
                            'label' => [
                                'de-DE' => 'Fixer monatlicher Versandtermin aktiv',
                                'en-GB' => 'Fixed monthly shipping date active',
                            ],
                            'helpText' => [
                                'de-DE' => 'Ersetzt in der Storefront die Standard-Lieferzeit durch den berechneten Versandtermin und Bestellschluss. Die WaWi-Felder Lieferzeit/Wiederauffüllzeit bleiben unverändert.',
                                'en-GB' => 'Replaces the default delivery time in the storefront with the calculated shipping date and order deadline. The ERP fields delivery time/restock time stay untouched.',
                            ],
                        ],
                    ],
                    [
                        'id' => $this->id(self::FIELD_PRODUCT_PROFILE),
                        'name' => self::FIELD_PRODUCT_PROFILE,
                        'type' => CustomFieldTypes::ENTITY,
                        'config' => $this->profileSelectConfig(2, [
                            'de-DE' => 'Lieferprofil (optional, überschreibt Hersteller/Lieferant)',
                            'en-GB' => 'Delivery profile (optional, overrides manufacturer/supplier)',
                        ]),
                    ],
                ],
            ],
            [
                'id' => $this->id(self::SET_MANUFACTURER),
                'name' => self::SET_MANUFACTURER,
                'config' => [
                    'label' => [
                        'de-DE' => 'Monatliche Lieferanten-Lieferung',
                        'en-GB' => 'Monthly supplier delivery',
                    ],
                ],
                'relations' => [
                    ['id' => $this->id(self::SET_MANUFACTURER . '.product_manufacturer'), 'entityName' => 'product_manufacturer'],
                ],
                'customFields' => [
                    [
                        'id' => $this->id(self::FIELD_MANUFACTURER_PROFILE),
                        'name' => self::FIELD_MANUFACTURER_PROFILE,
                        'type' => CustomFieldTypes::ENTITY,
                        'config' => $this->profileSelectConfig(1, [
                            'de-DE' => 'Lieferprofil für alle Produkte dieses Herstellers/Lieferanten',
                            'en-GB' => 'Delivery profile for all products of this manufacturer/supplier',
                        ]),
                    ],
                ],
            ],
        ], $context);
    }

    public function uninstall(Context $context): void
    {
        $this->customFieldSetRepository->delete([
            ['id' => $this->id(self::SET_PRODUCT)],
            ['id' => $this->id(self::SET_MANUFACTURER)],
        ], $context);
    }

    /**
     * @param array<string, string> $label
     *
     * @return array<string, mixed>
     */
    private function profileSelectConfig(int $position, array $label): array
    {
        return [
            'componentName' => 'sw-entity-single-select',
            'customFieldType' => 'entity',
            'entity' => DeliveryProfileDefinition::ENTITY_NAME,
            'labelProperty' => 'name',
            'customFieldPosition' => $position,
            'label' => $label,
            'helpText' => [
                'de-DE' => 'Leer lassen, um das Profil des Herstellers/Lieferanten bzw. das Standardprofil aus der Plugin-Konfiguration zu verwenden. Per API/WaWi kann statt der ID auch der technische Name des Profils gesetzt werden.',
                'en-GB' => 'Leave empty to use the manufacturer/supplier profile or the default profile from the plugin configuration. Via API/ERP the technical name of the profile can be set instead of the ID.',
            ],
        ];
    }

    private function id(string $name): string
    {
        return Uuid::fromStringToHex($name);
    }
}
