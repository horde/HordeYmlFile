<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use stdClass;

/**
 * Represents the autoload section of .horde.yml
 *
 * Defines PSR-4, classmap, and file autoloading for Composer.
 *
 * Example:
 *   autoload:
 *     psr-4:
 *       Horde\Example\: src/
 *     classmap:
 *       - lib/
 *     files:
 *       - functions.php
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class Autoload
{
    /**
     * @param array<string, string> $psr4 PSR-4 namespace to directory mappings
     * @param array<int, string> $classmap Directories for classmap scanning
     * @param array<int, string> $files Files to always include
     */
    public function __construct(
        private array $psr4 = [],
        private array $classmap = [],
        private array $files = [],
    ) {}

    /**
     * Create from stdClass representation
     */
    public static function fromStdClass(stdClass $data): self
    {
        $psr4 = [];
        if (isset($data->{'psr-4'})) {
            // Convert stdClass to array
            $psr4 = json_decode(json_encode($data->{'psr-4'}), true) ?: [];
        }

        return new self(
            psr4: $psr4,
            classmap: isset($data->classmap) ? (array) $data->classmap : [],
            files: isset($data->files) ? (array) $data->files : [],
        );
    }

    /**
     * Get PSR-4 namespace mappings
     *
     * @return array<string, string>
     */
    public function getPsr4(): array
    {
        return $this->psr4;
    }

    /**
     * Get classmap directories
     *
     * @return array<int, string>
     */
    public function getClassmap(): array
    {
        return $this->classmap;
    }

    /**
     * Get files to always include
     *
     * @return array<int, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        $result = new stdClass();
        if (!empty($this->psr4)) {
            $result->{'psr-4'} = (object) $this->psr4;
        }
        if (!empty($this->classmap)) {
            $result->classmap = $this->classmap;
        }
        if (!empty($this->files)) {
            $result->files = $this->files;
        }
        return $result;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        if (!empty($this->psr4)) {
            $result['psr-4'] = $this->psr4;
        }
        if (!empty($this->classmap)) {
            $result['classmap'] = $this->classmap;
        }
        if (!empty($this->files)) {
            $result['files'] = $this->files;
        }
        return $result;
    }
}
