<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1791360000CreateDeliveryProfileTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1791360000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `dmd_delivery_profile` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `technical_name` VARCHAR(255) NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `arrival_weekday` TINYINT NOT NULL DEFAULT 3,
                `arrival_occurrence` TINYINT NOT NULL DEFAULT -1,
                `deadline_offset_days` TINYINT NOT NULL DEFAULT 7,
                `deadline_time` VARCHAR(8) NOT NULL DEFAULT \'23:59:59\',
                `ship_weekday` TINYINT NOT NULL DEFAULT 1,
                `excluded_months` VARCHAR(64) NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.dmd_delivery_profile.technical_name` (`technical_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Standardprofil gemäß Anforderung: letzter Mittwoch, Bestellschluss 7 Tage vorher, Versand am Montag danach
        $connection->executeStatement(
            'INSERT IGNORE INTO `dmd_delivery_profile`
                (`id`, `name`, `technical_name`, `active`, `arrival_weekday`, `arrival_occurrence`,
                 `deadline_offset_days`, `deadline_time`, `ship_weekday`, `description`, `created_at`)
             VALUES (:id, :name, :technicalName, 1, 3, -1, 7, \'23:59:59\', 1, :description, NOW(3))',
            [
                'id' => Uuid::fromHexToBytes(Uuid::fromStringToHex('dmd_delivery_profile.standard')),
                'name' => 'Standard (letzter Mittwoch)',
                'technicalName' => 'standard',
                'description' => 'Ware trifft am letzten Mittwoch des Monats ein, Bestellschluss am vorletzten Mittwoch 23:59:59 Uhr, Versand am Montag danach.',
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
