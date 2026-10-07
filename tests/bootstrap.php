<?php declare(strict_types=1);

/*
 * Die Unit-Tests der Berechnungslogik benötigen keine Shopware-Installation.
 * Innerhalb einer Shopware-Installation wird der Autoloader des Shops verwendet,
 * ansonsten ein minimaler PSR-4-Autoloader für Plugin-Klassen und PHPUnit aus tests/vendor.
 */
$shopAutoloader = __DIR__ . '/../../../../vendor/autoload.php';
if (is_file($shopAutoloader)) {
    require $shopAutoloader;
}

$standaloneAutoloader = __DIR__ . '/vendor/autoload.php';
if (is_file($standaloneAutoloader)) {
    require $standaloneAutoloader;
}

spl_autoload_register(static function (string $class): void {
    $map = [
        'DrDotHouse\\MonthlyDelivery\\Tests\\' => __DIR__ . '/',
        'DrDotHouse\\MonthlyDelivery\\' => __DIR__ . '/../src/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, \strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
});
