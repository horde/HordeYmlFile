<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use stdClass;

/**
 * Represents the dependencies section of .horde.yml
 *
 * Dependencies are organized into three sets:
 * - required: Dependencies needed for normal operation
 * - optional: Dependencies for optional features
 * - dev: Dependencies needed only for development/testing
 *
 * Example:
 *   dependencies:
 *     required:
 *       php: ^7.4 || ^8
 *       composer:
 *         horde/exception: ^3
 *     optional:
 *       ext:
 *         - gettext
 *     dev:
 *       composer:
 *         horde/test: ^3
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class Dependencies
{
    public function __construct(
        private ?DependencySet $required = null,
        private ?DependencySet $optional = null,
        private ?DependencySet $dev = null,
    ) {}

    /**
     * Create from stdClass representation
     */
    public static function fromStdClass(stdClass $data): self
    {
        return new self(
            required: isset($data->required) ? DependencySet::fromStdClass($data->required) : null,
            optional: isset($data->optional) ? DependencySet::fromStdClass($data->optional) : null,
            dev: isset($data->dev) ? DependencySet::fromStdClass($data->dev) : null,
        );
    }

    /**
     * Get required dependencies
     */
    public function getRequired(): ?DependencySet
    {
        return $this->required;
    }

    /**
     * Get optional dependencies
     */
    public function getOptional(): ?DependencySet
    {
        return $this->optional;
    }

    /**
     * Get development dependencies
     */
    public function getDev(): ?DependencySet
    {
        return $this->dev;
    }

    // ========== Convenience Methods ==========

    /**
     * Get required PHP version constraint
     *
     * Convenience method to access required->php directly.
     */
    public function getRequiredPhp(): string
    {
        return $this->required?->getPhp() ?? '';
    }

    /**
     * Get required PHP extensions
     *
     * Convenience method to access required->ext directly.
     *
     * @return array<int, string>
     */
    public function getRequiredExtensions(): array
    {
        return $this->required?->getExtensions() ?? [];
    }

    /**
     * Get required Composer packages
     *
     * Convenience method to access required->composer directly.
     *
     * @return array<string, string>
     */
    public function getRequiredComposer(): array
    {
        return $this->required?->getComposerPackages() ?? [];
    }

    /**
     * Get required PEAR packages (legacy)
     *
     * Convenience method to access required->pear directly.
     *
     * @return array<string, string>
     */
    public function getRequiredPear(): array
    {
        return $this->required?->getPearPackages() ?? [];
    }

    /**
     * Check if there are any dependencies defined
     */
    public function isEmpty(): bool
    {
        return ($this->required === null || $this->required->isEmpty())
            && ($this->optional === null || $this->optional->isEmpty())
            && ($this->dev === null || $this->dev->isEmpty());
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        $result = new stdClass();
        if ($this->required !== null && !$this->required->isEmpty()) {
            $result->required = $this->required->toStdClass();
        }
        if ($this->optional !== null && !$this->optional->isEmpty()) {
            $result->optional = $this->optional->toStdClass();
        }
        if ($this->dev !== null && !$this->dev->isEmpty()) {
            $result->dev = $this->dev->toStdClass();
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
        if ($this->required !== null && !$this->required->isEmpty()) {
            $result['required'] = $this->required->toArray();
        }
        if ($this->optional !== null && !$this->optional->isEmpty()) {
            $result['optional'] = $this->optional->toArray();
        }
        if ($this->dev !== null && !$this->dev->isEmpty()) {
            $result['dev'] = $this->dev->toArray();
        }
        return $result;
    }
}
