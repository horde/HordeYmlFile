<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Horde\Yaml\Yaml;
use Horde\Yaml\Exception as YamlException;
use Horde\Yaml\Document\Exception as DocumentException;
use Horde\Yaml\Document\Node\AliasNode;
use Horde\Yaml\Document\Node\MapNode;
use Horde\Yaml\Document\Node\Node;
use Horde\Yaml\Document\Node\ScalarNode;
use Horde\Yaml\Document\Node\SequenceNode;
use Horde\Yaml\Document\YamlFileLoader;
use Horde\Yaml\Document\YamlStream;

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
        // Load YAML representation via the document layer. The
        // document layer preserves comments, blank lines, and
        // formatting on the AST so a future save() can write back
        // byte-identical for untouched parts of the file.
        try {
            $stream = (new YamlFileLoader())->load($this->filePath);
            $array = $this->streamToArray($stream);
            $this->hordeYml = $this->arrayToObject($array);
        } catch (DocumentException $e) {
            throw new InvalidHordeYmlFileException("Failed to parse YAML: {$this->filePath}", 0, $e);
        }
        $this->originalContent = $content;
    }

    /**
     * Reduce a YamlStream to a plain PHP array shaped like what
     * Yaml::load() returned. An empty stream (zero documents or a
     * null root) yields an empty array. Multi-document files
     * collapse to the first document's root, matching the legacy
     * loader's behaviour.
     */
    private function streamToArray(YamlStream $stream): array
    {
        if ($stream->documentCount() === 0) {
            return [];
        }
        $root = $stream->getDocument(0)->root();
        if ($root === null) {
            return [];
        }
        $value = $this->nodeToArray($root);
        if (!is_array($value)) {
            // A document whose root is a bare scalar does not match
            // the .horde.yml shape. Surface as an empty top-level
            // map; callers asking for typed accessors will see
            // missing keys rather than a type error.
            return [];
        }
        return $value;
    }

    /**
     * Recursively convert a document-layer node into a plain PHP
     * value. Maps become associative arrays. Sequences become
     * 0-indexed arrays. Scalars unwrap to their typed value.
     * Aliases dereference to their target's typed value (with a
     * fallback to the alias name as a string when the target is
     * absent, matching the legacy loader).
     */
    private function nodeToArray(Node $node): mixed
    {
        if ($node instanceof ScalarNode) {
            return $node->getValue();
        }
        if ($node instanceof MapNode) {
            $out = [];
            foreach ($node->entries() as $entry) {
                $key = $entry->getKey();
                $keyValue = $key instanceof ScalarNode ? $key->getValue() : null;
                if ($keyValue === null) {
                    continue;
                }
                $out[(string) $keyValue] = $this->nodeToArray($entry->getValue());
            }
            return $out;
        }
        if ($node instanceof SequenceNode) {
            $out = [];
            foreach ($node->items() as $item) {
                $value = $item->getValue();
                if ($value === null) {
                    $out[] = null;
                    continue;
                }
                $out[] = $this->nodeToArray($value);
            }
            return $out;
        }
        if ($node instanceof AliasNode) {
            $target = $node->target();
            if ($target === null) {
                return $node->getTargetName();
            }
            return $this->nodeToArray($target);
        }
        return null;
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
        $data = json_decode(json_encode($this->hordeYml), true);
        return Yaml::dump($data, ['wordwrap' => 78, 'indent' => 2]);
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
        return new ChangelogYmlFile(dirname($this->filePath) . '/doc/changelog.yml');
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
        $validTypes = [
            // Horde types
            'library', 'application', 'component', 'horde-theme', 'extension',
            // Direct Composer types
            'metapackage', 'composer-plugin', 'project',
            // PHP extension types
            'php-ext', 'php-ext-zend',
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid type: {$type}. Must be one of: " . implode(', ', $validTypes)
            );
        }
        $this->hordeYml->type = (string) $type;
        return $this;
    }

    // ========== List Methods ==========

    public function getList(): string
    {
        return $this->hordeYml->list ?? '';
    }

    public function setList(string $list): self
    {
        $this->hordeYml->list = $list;
        return $this;
    }

    // ========== Keywords Methods ==========

    public function getKeywords(): array
    {
        if (!isset($this->hordeYml->keywords)) {
            return [];
        }
        $keywords = $this->hordeYml->keywords;
        // Convert to simple array of strings
        $keywords = json_decode(json_encode($keywords), true) ?: [];
        // Normalize: filter out garbage, lowercase, deduplicate
        $keywords = array_filter($keywords, fn($k) => is_string($k) && trim($k) !== '');
        $keywords = array_map('strtolower', $keywords);
        $keywords = array_map('trim', $keywords);
        $keywords = array_unique($keywords);
        return array_values($keywords);
    }

    public function setKeywords(array $keywords): self
    {
        $this->hordeYml->keywords = $keywords;
        return $this;
    }

    // ========== Support Methods ==========

    public function getSupport(): ?Support
    {
        if (!isset($this->hordeYml->support)) {
            return null;
        }
        return Support::fromStdClass($this->hordeYml->support);
    }

    public function setSupport(Support $support): self
    {
        if ($support->isEmpty()) {
            unset($this->hordeYml->support);
        } else {
            $this->hordeYml->support = $support->toStdClass();
        }
        return $this;
    }

    // ========== Autoload Methods ==========

    public function getAutoload(): ?Autoload
    {
        if (!isset($this->hordeYml->autoload)) {
            return null;
        }
        return Autoload::fromStdClass($this->hordeYml->autoload);
    }

    public function setAutoload(Autoload $autoload): self
    {
        $this->hordeYml->autoload = $autoload->toStdClass();
        return $this;
    }

    // ========== Provides Methods ==========

    public function getProvides(): ?Provides
    {
        if (!isset($this->hordeYml->provides)) {
            return null;
        }
        return Provides::fromStdClass($this->hordeYml->provides);
    }

    public function setProvides(Provides $provides): self
    {
        $this->hordeYml->provides = $provides->toStdClass();
        return $this;
    }

    // ========== Version Methods ==========

    public function getReleaseVersion(): string
    {
        return $this->hordeYml->version->release ?? '';
    }

    public function setReleaseVersion(string $version): self
    {
        if (!isset($this->hordeYml->version)) {
            $this->hordeYml->version = new stdClass();
        }
        $this->hordeYml->version->release = $version;
        return $this;
    }

    public function getApiVersion(): string
    {
        return $this->hordeYml->version->api ?? '';
    }

    public function setApiVersion(string $version): self
    {
        if (!isset($this->hordeYml->version)) {
            $this->hordeYml->version = new stdClass();
        }
        $this->hordeYml->version->api = $version;
        return $this;
    }

    // ========== State Methods ==========

    public function getReleaseState(): string
    {
        return $this->hordeYml->state->release ?? 'alpha';
    }

    public function setReleaseState(string $state): self
    {
        if (!isset($this->hordeYml->state)) {
            $this->hordeYml->state = new stdClass();
        }
        $this->hordeYml->state->release = $state;
        return $this;
    }

    public function getApiState(): string
    {
        return $this->hordeYml->state->api ?? 'alpha';
    }

    public function setApiState(string $state): self
    {
        if (!isset($this->hordeYml->state)) {
            $this->hordeYml->state = new stdClass();
        }
        $this->hordeYml->state->api = $state;
        return $this;
    }

    // ========== Computed Properties ==========

    public function getComposerName(): string
    {
        $vendor = $this->getVendor();
        $name = $this->getName();
        return strtolower($vendor . '/' . $name);
    }

    public function getId(): string
    {
        return $this->hordeYml->id ?? '';
    }

    public function setId(string $id): self
    {
        $this->hordeYml->id = $id;
        return $this;
    }

    // ========== Full Name and Description ==========

    public function getFullName(): string
    {
        return $this->hordeYml->full ?? $this->getName();
    }

    public function setFullName(string $full): self
    {
        $this->hordeYml->full = $full;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->hordeYml->description ?? '';
    }

    public function setDescription(string $description): self
    {
        $this->hordeYml->description = $description;
        return $this;
    }

    // ========== License ==========

    public function getLicense(): ?stdClass
    {
        return isset($this->hordeYml->license) ? (object) $this->hordeYml->license : null;
    }

    public function setLicense(string $identifier, string $uri): self
    {
        $this->hordeYml->license = (object) [
            'identifier' => $identifier,
            'uri' => $uri,
        ];
        return $this;
    }

    // ========== Authors ==========

    public function getAuthors(): array
    {
        if (!isset($this->hordeYml->authors)) {
            return [];
        }
        $authors = $this->hordeYml->authors;
        // Convert to array (handles both array and object cases)
        // Need to convert deeply - author entries may be objects even if authors is an array
        return json_decode(json_encode($authors), true) ?: [];
    }

    public function setAuthors(array $authors): self
    {
        $this->hordeYml->authors = $authors;
        return $this;
    }

    // ========== Dependencies ==========

    public function getDependencies(): ?Dependencies
    {
        if (!isset($this->hordeYml->dependencies)) {
            return null;
        }
        return Dependencies::fromStdClass($this->hordeYml->dependencies);
    }

    public function setDependencies(Dependencies $deps): self
    {
        $this->hordeYml->dependencies = $deps->toStdClass();
        return $this;
    }

    public function getRequiredPhp(): string
    {
        return $this->getDependencies()?->getRequiredPhp() ?? '';
    }

    public function getRequiredExtensions(): array
    {
        return $this->getDependencies()?->getRequiredExtensions() ?? [];
    }

    // ========== Vendor Assets ==========

    /**
     * Get vendor asset declarations.
     *
     * @return VendorAsset[]
     */
    public function getVendorAssets(): array
    {
        if (!isset($this->hordeYml->{'vendor-assets'})) {
            return [];
        }
        $assets = $this->hordeYml->{'vendor-assets'};
        if (!is_array($assets)) {
            return [];
        }
        return array_map(
            fn($item) => VendorAsset::fromStdClass($item),
            $assets,
        );
    }

    /**
     * Set vendor asset declarations.
     *
     * @param VendorAsset[] $assets
     */
    public function setVendorAssets(array $assets): self
    {
        $this->hordeYml->{'vendor-assets'} = array_map(
            fn(VendorAsset $a) => $a->toStdClass(),
            $assets,
        );
        return $this;
    }

    // ========== Allowed Plugins ==========

    public function getAllowedPlugins(): array
    {
        if (!isset($this->hordeYml->{'allow-plugins'})) {
            return [];
        }
        $plugins = $this->hordeYml->{'allow-plugins'};
        if ($plugins === true) {
            return ['*' => true];
        }
        return is_array($plugins) ? $plugins : (array) $plugins;
    }

    public function setAllowedPlugins(array $plugins): self
    {
        $this->hordeYml->{'allow-plugins'} = (object) $plugins;
        return $this;
    }

    // ========== Raw Access ==========

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->hordeYml->$key ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->hordeYml->$key = $value;
        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->hordeYml->$key);
    }

    // ========== Array Access ==========

    public function toArray(): array
    {
        return json_decode(json_encode($this->hordeYml), true);
    }

    // ========== Graceful Updates ==========

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
