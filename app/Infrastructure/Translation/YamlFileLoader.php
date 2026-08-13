<?php

namespace App\Infrastructure\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends FileLoader
{
    /** @param array<int, string> $paths */
    public function __construct(Filesystem $files, array $paths)
    {
        parent::__construct($files, $paths);
    }

    /** @return array<string, mixed> */
    protected function loadPaths(array $paths, $locale, $group): array
    {
        $lines = parent::loadPaths($paths, $locale, $group);

        foreach ($paths as $path) {
            $file = sprintf('%s/%s/%s.yml', $path, $locale, $group);

            if ($this->files->exists($file)) {
                $parsed = Yaml::parseFile($file);
                $lines = array_replace_recursive($lines, is_array($parsed) ? $parsed : []);
            }
        }

        return $lines;
    }
}
