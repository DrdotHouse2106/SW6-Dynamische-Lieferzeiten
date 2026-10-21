<?php declare(strict_types=1);

namespace DrDotHouse\MonthlyDelivery;

use Doctrine\DBAL\Connection;
use DrDotHouse\MonthlyDelivery\Service\CustomFieldInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MonthlyDeliveryPlugin extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->getCustomFieldInstaller()->install($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        $this->getCustomFieldInstaller()->install($updateContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        // Idempotent: stellt sicher, dass die Zusatzfelder vorhanden sind (z. B. nach manueller Löschung)
        $this->getCustomFieldInstaller()->install($activateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->getCustomFieldInstaller()->uninstall($uninstallContext->getContext());

        /** @var Connection $connection */
        $connection = $this->getServiceContainer()->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `dmd_delivery_profile`');
    }

    public function executeComposerCommands(): bool
    {
        return false;
    }

    private function getServiceContainer(): ContainerInterface
    {
        if ($this->container === null) {
            throw new \RuntimeException('Der Service-Container ist nicht verfügbar.');
        }

        return $this->container;
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $this->getServiceContainer()->get('custom_field_set.repository');

        return new CustomFieldInstaller($customFieldSetRepository);
    }
}
