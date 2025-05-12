<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Horde_Yaml;

class HordeYmlFile implements Stringable
{
    private stdClass $hordeYml;
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
        $json = json_encode($this->composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new JsonException("Failed to encode JSON: " . json_last_error_msg());
        }
        return $json;
    }

    public function save(): void
    {
        $this->applyGracefulUpdates();
        if (file_put_contents($this->filePath, $this) === false) {
            throw new RuntimeException("Failed to write to file: {$this->filePath}");
        }
    }

    public function getName(bool $failIfMissing = false): string
    {
        if ($failIfMissing && !isset($this->hordeYml->name)) {
            throw new RuntimeException("Package name not found in composer.json");
        }
        return $this->hordeYml->name ?? '';
    }

    public function setName(string|Stringable $name): self
    {
        if (mb_strpos((string) $name, '/') !== false) {
            throw new InvalidArgumentException("Invalid name: {$name}");
        }
        $this->hordeYml->name = (string) $name;
        return $this;
    }

    public function getChangelog(): ChangelogYmlFile
    {
        return new ChangelogYmlFile(dirname($this->filePath()) . '/doc/changelog.yml');
    }

    /**
     * TODO: Upgrade to property hooks when PHP 8.4 becomes the bottom supported version
     */
    public function setVendor(string $vendor): self
    {
        $this->hordeYml->vendor = $vendor;
        return $this;
    }
    public function getVendor(bool $failIfMissing = false): string
    {
        if ($failIfMissing && !isset($this->hordeYml->vendor)) {
            throw new RuntimeException("Package name not found in composer.json");
        }
        return $this->hordeYml->vendor ?? '';
    }

    public function getType(bool $failIfMissing = false): string
    {
        if ($failIfMissing && !isset($this->hordeYml->type)) {
            throw new RuntimeException("Package type not found in composer.json");
        }
        return $this->hordeYml->type ?? '';
    }

    public function getHomePage(bool $failIfMissing = false): string
    {
        return $this->hordeYml->homepage ?? '';
    }

    public function setHomePage(string $homepage): self
    {
        // TODO: Should look like a URL
        $this->hordeYml->homepage = (string) $homepage;
        return $this;
    }

    public function setType(string $type): self
    {
        // TODO: Validate type
        $this->hordeYml->type = (string) $type;
        return $this;
    }

    /**
     * Render out default-if-missing values for the .horde.yml file.
     */
    public function applyGracefulUpdates(): self
    {
        if ($this->getVendor() === '') {
            $this->setVendor('horde');
        }
        if ($this->getName() === '') {
            $this->setName(basename(dirname($this->filePath)));
        }
        return $this;
    }
}
