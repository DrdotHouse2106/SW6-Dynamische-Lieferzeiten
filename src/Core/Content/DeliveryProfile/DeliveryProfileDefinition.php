<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Core\Content\DeliveryProfile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Lieferprofil: eine Berechnungsregel je Lieferant (Admin: Einstellungen → Erweiterungen → Monats-Lieferprofile).
 */
class DeliveryProfileDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'dmd_delivery_profile';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return DeliveryProfileEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DeliveryProfileCollection::class;
    }

    public function getDefaults(): array
    {
        return [
            'active' => true,
            'arrivalWeekday' => 3,
            'arrivalOccurrence' => -1,
            'deadlineOffsetDays' => 7,
            'deadlineTime' => '23:59:59',
            'shipWeekday' => 1,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware(), new Required()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new IntField('arrival_weekday', 'arrivalWeekday', 1, 7))->addFlags(new ApiAware(), new Required()),
            (new IntField('arrival_occurrence', 'arrivalOccurrence', -2, 4))->addFlags(new ApiAware(), new Required()),
            (new IntField('deadline_offset_days', 'deadlineOffsetDays', 0, 27))->addFlags(new ApiAware(), new Required()),
            (new StringField('deadline_time', 'deadlineTime', 8))->addFlags(new ApiAware(), new Required()),
            (new IntField('ship_weekday', 'shipWeekday', 1, 7))->addFlags(new ApiAware(), new Required()),
            (new StringField('excluded_months', 'excludedMonths', 64))->addFlags(new ApiAware()),
            (new LongTextField('description', 'description'))->addFlags(new ApiAware()),
        ]);
    }
}
