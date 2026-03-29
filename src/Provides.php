<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use stdClass;

/**
 * Represents the provides section of .horde.yml
 *
 * Declares PSR interface implementations that this package provides.
 *
 * Example:
 *   provides:
 *     psr/log-implementation: 3.0.0
 *     psr/container-implementation: 2.0.1
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class Provides
{
    /**
     * @param array<string, string> $implementations PSR interface implementations
     */
    public function __construct(
        private array $implementations = []
    ) {}

    /**
     * Create from stdClass representation
     */
    public static function fromStdClass(stdClass $data): self
    {
        return new self((array) $data);
    }

    /**
     * Check if this package provides a specific PSR interface
     */
    public function has(string $interface): bool
    {
        return isset($this->implementations[$interface]);
    }

    /**
     * Get the version constraint for a PSR interface
     */
    public function get(string $interface): ?string
    {
        return $this->implementations[$interface] ?? null;
    }

    /**
     * Get all PSR interface implementations
     *
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->implementations;
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        return (object) $this->implementations;
    }

    /**
     * Convert to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->implementations;
    }
}
