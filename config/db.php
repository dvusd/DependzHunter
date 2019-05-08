<?php
/**
 * Configure doctrine database
 */

return [
    'driver' => 'pdo_mysql',
    'dbname' => 'dependz_hunter',
    'host' => '127.0.0.1',
    'driver_options' => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"],
    'user' => 'dependz',
    'password' => 'CHANGE_THIS_PASSWORD'
];