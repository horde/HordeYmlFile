<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Horde_Yaml;

class ChangelogYmlFile implements Stringable
{
    private stdClass $changelogYml;
    private string $originalContent;

    public function __construct(
        private string $filePath
    ) {
        if (!file_exists($this->filePath)) {
            throw new InvalidHordeYmlFileException("File does not exist: {$this->filePath}");
        }
        if (!is_readable($this->filePath)) {
            throw new InvalidHordeYmlFileException("File is not readable: {$this->filePath}");
        }
        $content = file_get_contents($this->filePath);
        // Load YAML representation
        try {
            $this->hordeYml = (object)Horde_Yaml::loadFile($this->filePath);
        } catch (Horde_Yaml_Exception $e) {
            throw new InvalidHordeYmlFileException("Failed to parse YAML: {$this->filePath}", 0, $e);
        }
        $this->originalContent = $content;
    }

    public function __toString(): string
    {
        return Horde_Yaml::dump($this->changelogYml);
    }

    public function save(): void
    {
        if (file_put_contents($this->filePath, $this) === false) {
            throw new RuntimeException("Failed to write to file: {$this->filePath}");
        }
    }
}
