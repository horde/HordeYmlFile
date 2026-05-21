<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Horde\Yaml\Yaml;
use Horde\Yaml\Exception as YamlException;

class ChangelogYmlFile implements Stringable
{
    private stdClass $changelogYml;
    private string $originalContent;

    public function __construct(
        private string $filePath
    ) {
        if (!file_exists($this->filePath)) {
            throw new InvalidChangelogFileException("File does not exist: {$this->filePath}");
        }
        if (!is_readable($this->filePath)) {
            throw new InvalidChangelogFileException("File is not readable: {$this->filePath}");
        }
        $content = file_get_contents($this->filePath);
        // Load YAML representation
        try {
            $data = Yaml::load($content);
            $data = $this->normalizeChangelogData($data ?? []);
            $this->changelogYml = $this->arrayToObject($data);
        } catch (YamlException $e) {
            throw new InvalidChangelogFileException("Failed to parse YAML: {$this->filePath}", 0, $e);
        }
        $this->originalContent = $content;
    }

    /**
     * Normalize list-format changelogs (array of objects with 'version' key)
     * into the expected associative format (version string => entry).
     */
    private function normalizeChangelogData(array $data): array
    {
        if (empty($data)) {
            return [];
        }
        // Already associative format (version as key)
        if (!array_is_list($data)) {
            return $data;
        }
        // List format: each element has a 'version' key
        $normalized = [];
        foreach ($data as $entry) {
            if (is_array($entry) && isset($entry['version'])) {
                $version = $entry['version'];
                unset($entry['version']);
                $normalized[$version] = $entry;
            }
        }
        return $normalized;
    }

    /**
     * Recursively convert arrays to stdClass objects.
     */
    private function arrayToObject(mixed $data): mixed
    {
        if (is_array($data)) {
            // Check if it's an associative array (should be object) or numeric array (stay as array)
            if (empty($data)) {
                return new stdClass();
            }
            // If all keys are numeric and sequential, keep as array
            $keys = array_keys($data);
            if ($keys === range(0, count($data) - 1)) {
                // Numeric array - process values but keep as array
                return array_map([$this, 'arrayToObject'], $data);
            }
            // Associative array - convert to object
            $obj = new stdClass();
            foreach ($data as $key => $value) {
                $obj->$key = $this->arrayToObject($value);
            }
            return $obj;
        }
        return $data;
    }

    public function __toString(): string
    {
        // Convert objects back to arrays for YAML dumping
        $data = json_decode(json_encode($this->changelogYml), true);
        return Yaml::dump($data, ['wordwrap' => 0, 'indent' => 2]);
    }

    public function save(): void
    {
        if (file_put_contents($this->filePath, $this) === false) {
            throw new RuntimeException("Failed to write to file: {$this->filePath}");
        }
    }

    // ========== Version Checking ==========

    public function hasVersion(string $version): bool
    {
        $changelog = (array) $this->changelogYml;
        return isset($changelog[$version]);
    }

    public function getVersionEntry(string $version): ?stdClass
    {
        $changelog = (array) $this->changelogYml;
        return isset($changelog[$version]) ? (object) $changelog[$version] : null;
    }

    public function addVersionEntry(string $version, array $entry): self
    {
        $changelog = (array) $this->changelogYml;
        $changelog[$version] = (object) $entry;

        // Sort by version (newest first) using natural comparison
        uksort($changelog, fn($a, $b) => strnatcmp($b, $a));

        $this->changelogYml = (object) $changelog;
        return $this;
    }

    public function getVersions(): array
    {
        return array_keys((array) $this->changelogYml);
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this->changelogYml), true);
    }
}
