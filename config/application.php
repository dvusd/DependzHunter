<?php

$version = '0'; //update composer.json instead

if (file_exists(__DIR__ . '/../composer.json')) {

    @$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
    if (isset($composer['version'])) {
        $version = $composer['version'];
    }
}

define('VERSION', $version);

return array(
    'name' => 'Dependz Hunter',
    'version' => VERSION,
);