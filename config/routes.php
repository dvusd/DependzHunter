<?php
return [
    /**
     * The route with an __invoke handler
     */
    [
        'name' => 'scan',
        /**
         * Specify a valid PHP callback for the "handler" value. Here the handler's __invoke method is used
         */
        'handler' => new \DependzHunter\ScanHandler(),
        'route' => '[--dir=] [--exclude=] [--max-depth=] [--dry-run] [--debug]',
        'description' => 'This command scans the specified folder for package files and aggregates the data accordingly.',
        'short_description' => 'This command scans for project dependencies',
        /**
         * Option descriptions are printed when `php bin/cli.php example` is ran
         */
        'options_descriptions' => [
            '--dir' => 'The root folder to begin scanning. This tool will recurse all sub-directories. See max-depth.',
            '--exclude' => 'Specify a pattern to exclude from scan results.  Example --exclude="/vendor|node_modules|composer.json|package.json/i"',
            '--max-depth' => 'Specify the maximum number of folders to recursively process. Default is 0 for all.',
            '--dry-run' => 'Skip writing the results to the database.',
            '--debug' => 'Enable debug verbose information',
        ],
        /**
         * Default argument values
         */
        'defaults' => [
            'dir' => '.',
            'exclude' => '',
            'max-depth' => '0',
        ],
        'filters' => [],
    ],
];