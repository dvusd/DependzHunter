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
    private $debug = false;

    private $packageTypes = [
        ['name' => 'composer.json', 'json' => true, 'keys' => ['require', 'require-dev']],
        ['name' => 'composer.lock', 'json' => true, 'keys' => ['packages', 'packages-dev']],
        ['name' => 'package.json', 'json' => true, 'keys' => ['dependencies', 'devDependencies']],
        ['name' => 'package-lock.json', 'json' => true, 'keys' => ['dependencies', 'devDependencies']],
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
        $this->debug = $debug;
        $this->debug && $console->writeLine("Output includes DEBUG info");

        // dir should be a string
        $dir = $route->getMatchedParam('dir');
        if (!is_string($dir) || !is_dir($dir)) {
            $console->writeLine("Invalid directory.");
            return 1;
        }
        $dir = realpath($dir);
        $console->writeLine("Root path: $dir");

        // exclude should be a string
        $exclude = $route->getMatchedParam('exclude');
        if (!is_string($exclude)) {
            $console->writeLine("Invalid exclusion pattern: {$exclude}");
            return 1;
        }
        $this->debug && $console->writeLine("Exclusion pattern: $exclude");

        // max-depth should be a number
        $maxDepth = (int) $route->getMatchedParam('max-depth');
        if (!is_numeric($maxDepth)) {
            $console->write("Invalid value for max-depth. Must be numeric");
            return 1;
        }
        $this->debug && $console->writeLine("Max depth: $maxDepth");

        $options = [
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

        $console->writeLine("Total folders with matches: " . count($results));
        $results = $this->transform($console, $results);
        if ($this->debug) {
            $console->writeLine("Results:");
            $console->writeLine(json_encode($results, JSON_PRETTY_PRINT));
        }
        $console->writeLine("Total dependencies found: " . count($results));

        $dryRun = $route->getMatchedParam('dry-run', false);
        if (!$dryRun) {
            $this->save($console, $results);
        }

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
                    $this->debug && $console->writeLine("Excluding '$filename' from results");
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
                    $console->writeLine("Unable to decode JSON in '$filename'");
                    continue;
                }
                // search for specific keys within the json structure - this is the payload
                $sections = $packageType['keys'];
                foreach ($sections as $section){
                    // just fyi - not a failure
                    if (!array_key_exists($section, $decoded)){
                        $this->debug && $console->writeLine("JSON key '$section' not available in '$filename'");
                        continue;
                    }
                    if (!array_key_exists($section, $results[$path][$file])) {
                        $results[$path][$file][$section] = [];
                    }
                    $results[$path][$file][$section] = array_merge($results[$path][$file][$section], $decoded[$section]);
                }
            } else {
                $console->writeLine("Only JSON file types are supported: {$packageType['name']}");
            }
        }
        return $results;
    }

    private function transform(Console $console, array $items)
    {
        $results = [];
        $unique = uniqid('', true);
        foreach ($items as $path => $files) {
            foreach ($files as $file => $sections) {
                foreach ($sections as $section => $depends) {
                    foreach ($depends as $depend => $entry) {
                        if (is_array($entry)) {
                            if (is_string($depend)) {
                                $dependency = $depend;
                            } else if (array_key_exists('name', $entry)) {
                                $dependency = $entry['name'];
                            } else {
                                $console->writeLine("WARN: Dependency name not found: " . json_encode($entry));
                                continue;
                            }
                            if (array_key_exists('version', $entry)) {
                                $version = $entry['version'];
                            } else {
                                $console->writeLine("WARN: Dependency version not found: " . json_encode($entry));
                                continue;
                            }
                        } else {
                            $dependency = $depend;
                            $version = $entry;
                        }
                        $data = [
                            'path' => $path,
                            'asset_type' => $file,
                            'section' => $section,
                            'dependency' => $dependency,
                            'version' => $version,
                            'group_id' => $unique
                        ];
//                        $this->debug && $console->writeLine(json_encode($data));
                        $results[] = $data;
                    }
                }
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
     * @param Console $console
     * @param array $records
     * @return int
     * @throws \Exception
     */
    private function save(Console $console, array $records)
    {
        $dbConfig = include __DIR__ . '/../config/db.php';
        $adapter = new Adapter($dbConfig);
        $conn = $adapter->getDriver()->getConnection();
        $assetTable = new TableGateway('asset', $adapter);
        $total = 0;
        $console->writeLine("Saving results to db...");
        try {
            $conn->beginTransaction();
            foreach ($records as $record) {
                $affected = $assetTable->insert($record);
                $total += $affected;
            }
            $conn->commit();
            $console->writeLine("Inserted $total records into db");
        } catch (\Exception $e) {
            $conn->rollback();
            throw new \Exception($e);
        }
        return $total;
    }
}
