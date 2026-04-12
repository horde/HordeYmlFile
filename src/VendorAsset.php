<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use stdClass;

/**
 * Represents a vendor asset mapping in .horde.yml
 *
 * Maps assets from a third-party composer package to a web-accessible
 * location. The installer plugin reads these declarations and symlinks
 * or copies the assets during reconfiguration.
 *
 * Example:
 *   vendor-assets:
 *     - package: tinymce/tinymce
 *       type: js
 *       source: ""
 *       target: tinymce
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
final readonly class VendorAsset
{
    /**
     * @param string $package Composer package name (e.g., "tinymce/tinymce")
     * @param string $type    Asset type: "js" (determines which linker handles it)
     * @param string $source  Subdirectory within vendor package to expose (empty = root)
     * @param string $target  Target directory name relative to the type's base dir
     */
    public function __construct(
        public string $package,
        public string $type,
        public string $source,
        public string $target,
    ) {}

    /**
     * Create from stdClass representation (as parsed from YAML)
     */
    public static function fromStdClass(stdClass $data): self
    {
        return new self(
            package: $data->package ?? '',
            type: $data->type ?? '',
            source: $data->source ?? '',
            target: $data->target ?? '',
        );
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        $result = new stdClass();
        $result->package = $this->package;
        $result->type = $this->type;
        if ($this->source !== '') {
            $result->source = $this->source;
        }
        $result->target = $this->target;
        return $result;
    }

    /**
     * Convert to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = [
            'package' => $this->package,
            'type' => $this->type,
            'target' => $this->target,
        ];
        if ($this->source !== '') {
            $result['source'] = $this->source;
        }
        return $result;
    }
}
