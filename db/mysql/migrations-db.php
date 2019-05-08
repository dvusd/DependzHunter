<?php
/**
 * Configure doctrine database
 */

if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    throw new Exception(
        'Composer autoload script not found. Run \'composer install\''
    );
}

require_once __DIR__.'/../../vendor/autoload.php';

$dbConfig = include __DIR__ . '/../../config/db.php';
$adapter = new \Zend\Db\Adapter\Adapter($dbConfig);

return [
    'pdo' => $adapter->getDriver()->getConnection()->getResource()
];