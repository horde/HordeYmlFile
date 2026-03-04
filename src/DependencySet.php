<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use stdClass;

/**
 * Represents a dependency set (required/optional/dev) in .horde.yml
 *
 * Each dependency set can contain:
 * - php: PHP version constraint
 * - composer: Composer package dependencies
 * - pear: PEAR package dependencies (legacy)
 * - ext: PHP extension requirements
 *
 * Example:
 *   required:
 *     php: ^7.4 || ^8
 *     composer:
 *       horde/exception: ^3
 *       horde/util: ^3
 *     ext:
 *       - json
 *       - mbstring
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class DependencySet
{
    /**
     * @param string|null $php PHP version constraint
     * @param array<string, string> $composer Composer packages
     * @param array<string, string> $pear PEAR packages
     * @param array<int, string> $ext PHP extensions
     */
    public function __construct(
        private ?string $php = null,
        private array $composer = [],
        private array $pear = [],
        private array $ext = [],
    ) {}

    /**
     * Create from stdClass representation
     */
    public static function fromStdClass(stdClass $data): self
    {
        $composer = [];
        if (isset($data->composer)) {
            // Convert stdClass to array
            $composer = json_decode(json_encode($data->composer), true) ?: [];
        }

        $pear = [];
        if (isset($data->pear)) {
            // Convert stdClass to array
            $pear = json_decode(json_encode($data->pear), true) ?: [];
        }

        $ext = [];
        if (isset($data->ext)) {
            // Extensions can be array or object (legacy formats)
            $ext = is_array($data->ext) ? $data->ext : (array)$data->ext;
        }

        return new self(
            php: $data->php ?? null,
            composer: $composer,
            pear: $pear,
            ext: $ext,
        );
    }

    /**
     * Get PHP version constraint
     */
    public function getPhp(): ?string
    {
        return $this->php;
    }

    /**
     * Get Composer package dependencies
     *
     * @return array<string, string>
     */
    public function getComposerPackages(): array
    {
        return $this->composer;
    }

    /**
     * Get PEAR package dependencies (legacy)
     *
     * @return array<string, string>
     */
    public function getPearPackages(): array
    {
        return $this->pear;
    }

    /**
     * Get PHP extension requirements
     *
     * @return array<int, string>
     */
    public function getExtensions(): array
    {
        return $this->ext;
    }

    /**
     * Check if this set has any dependencies
     */
    public function isEmpty(): bool
    {
        return $this->php === null
            && empty($this->composer)
            && empty($this->pear)
            && empty($this->ext);
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        $result = new stdClass();
        if ($this->php !== null) {
            $result->php = $this->php;
        }
        if (!empty($this->composer)) {
            $result->composer = (object)$this->composer;
        }
        if (!empty($this->pear)) {
            $result->pear = (object)$this->pear;
        }
        if (!empty($this->ext)) {
            $result->ext = $this->ext;
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
        if ($this->php !== null) {
            $result['php'] = $this->php;
        }
        if (!empty($this->composer)) {
            $result['composer'] = $this->composer;
        }
        if (!empty($this->pear)) {
            $result['pear'] = $this->pear;
        }
        if (!empty($this->ext)) {
            $result['ext'] = $this->ext;
        }
        return $result;
    }
}
