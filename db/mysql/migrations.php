<?php
/**
 * This config file is for doctrine-migration
 */

return [
    'name' => 'Db Migrations',
    'migrations_namespace' => 'DependzHunter\Db\Mysql\Migrations',
    'table_name' => 'doctrine_migration_versions',
    'column_name' => 'version',
    'column_length' => 14,
    'executed_at_column_name' => 'executed_at',
    'migrations_directory' => __DIR__ . '/migrations',
    'all_or_nothing' => true
];