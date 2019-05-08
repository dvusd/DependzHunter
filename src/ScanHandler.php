<?php

namespace DependzHunter;

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use ZF\Console\Route;

/**
 * This class is the CLI command handler for scanning
 * Class ScanHandler
 * @package DependzHunter
 */
class ScanHandler
{

    private $packageTypes = [
        ['name' => 'composer.json', 'json' => true, 'keys' => ['require','require-dev']],
        ['name' => 'package.json', 'json' => true, 'keys' => ['dependencies','devDependencies']]
    ];

    /**
     * In this example the __invoke magic method is used to simplify the
     * configuration of the route. All that must be declared in the route
     * configuration is the class name
     * @param Route $route
     * @param Console $console
     * @return integer
     */
    public function __invoke(Route $route, Console $console)
    {
        // should be boolean
        $debug = $route->getMatchedParam('debug', false);
        $debug && $console->write("Output includes DEBUG info\n");

        // dir should be a string
        $dir = $route->getMatchedParam('dir');
        if (!is_string($dir) || !is_dir($dir)) {
            $console->write("Invalid directory.\n");
            return 1;
        }
        $dir = realpath($dir);
        $console->write("Root path: $dir\n");

        // exclude should be a string
        $exclude = $route->getMatchedParam('exclude');
        if (!is_string($exclude)) {
            $console->write("Invalid exclusion pattern");
            return 1;
        }
        $debug && $console->write("Exclusion pattern: $exclude\n");

        // max-depth should be a number
        $maxDepth = (int) $route->getMatchedParam('max-depth');
        if (!is_numeric($maxDepth)) {
            $console->write("Invalid value for max-depth. Must be numeric");
            return 1;
        }
        $debug && $console->write("Max depth: $maxDepth\n");

        $options = [
            'debug' => $debug,
            'exclude' => $exclude,
            'max-depth' => $maxDepth
        ];

        $results = [];
        foreach ($this->packageTypes as $type) {
            $name = $type['name'];
//            $console->write("Searching for '$name' in '$dir'\n");
            $temp = $this->scanFiles($console, "$dir/$name", $type, $options);
            $results = array_merge_recursive($results, $temp);
        }

        $console->write("Total folders with matches: " . count($results) . "\n");
        if ($debug) {
            $console->write("Results:\n");
            $json = json_encode($results, JSON_PRETTY_PRINT);
            $console->write($json);
            $console->write("\n");
        }

        $console->write("Saving results to db\n");
        $total = $this->save($results);
        $console->write("Inserted $total records into db\n");

        return 0;
    }

    private function scanFiles(Console $console, string $pattern, array $packageType, array $options)
    {
        $results = [];
        $exclude = $options['exclude'];
        //search recursively for all files that match the pattern filename
        $files = $this->rglob($pattern, 0, 0, $options['max-depth']);
        foreach ($files as $filename) {
            if (is_string($exclude) && strlen($exclude) > 0) {
                if (preg_match($exclude, $filename, $matches)){
                    $options['debug'] && $console->write("Excluding '$filename' from results\n");
                    continue;
                }
            }
            // build an assoc array with path > file > section > dependencies
            $path = dirname($filename);
            $file = basename($filename);
            $results[$path] = array($file => []);
            $console->write("Found '$file' in '$path'\n");
            // currently we only support json file types
            if ($packageType['json']) {
                $decoded = json_decode(file_get_contents($filename), true);
                if (!is_array($decoded)){
                    $console->write("Unable to decode JSON in '$filename'\n");
                    continue;
                }
                // search for specific keys within the json structure - this is the payload
                $sections = $packageType['keys'];
                foreach ($sections as $section){
                    // just fyi - not a failure
                    if (!array_key_exists($section, $decoded)){
                        $console->write("JSON key '$section' not available in '$filename'\n");
                        continue;
                    }
                    if (!array_key_exists($section, $results[$path][$file])) {
                        $results[$path][$file][$section] = [];
                    }
                    $results[$path][$file][$section] = array_merge($results[$path][$file][$section], $decoded[$section]);
                }
            } else {
                $console->write("Only JSON file types are supported: {$packageType['name']} \n");
            }
        }
        return $results;
    }

    // Does not support flag GLOB_BRACE
    private function rglob($pattern, $flags = 0, $currentDepth = 0, $maxDepth = 0)
    {
        $files = glob($pattern, $flags);
        $currentDepth++;
        if ($maxDepth === 0 || $maxDepth > $currentDepth) {
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $newFiles = $this->rglob($dir . '/' . basename($pattern), $flags, $currentDepth, $maxDepth);
                $files = array_merge($files, $newFiles);
            }
        }
        return $files;
    }

    /**
     * @param array $results
     * @return int
     */
    private function save(array $results)
    {
        $dbConfig = include __DIR__ . '/../config/db.php';
        $adapter = new Adapter($dbConfig);
        $conn = $adapter->getDriver()->getConnection();
        $assetTable = new TableGateway('asset', $adapter);
        $total = 0;
        try {
            $conn->beginTransaction();
            foreach ($results as $path => $files) {
                foreach ($files as $file => $sections) {
                    foreach ($sections as $section => $depends) {
                        foreach ($depends as $depend => $version) {
                            $data = [
                                'path' => $path,
                                'asset_type' => $file,
                                'section' => $section,
                                'dependency' => $depend,
                                'version' => $version
                            ];
                            $affected = $assetTable->insert($data);
                            $total += $affected;
                        }
                    }
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new \Exception($e);
        }
        return $total;
    }
}
