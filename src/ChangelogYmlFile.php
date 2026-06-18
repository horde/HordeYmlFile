<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Horde\Yaml\Document\Exception as DocumentException;
use Horde\Yaml\Document\Node\AliasNode;
use Horde\Yaml\Document\Node\MapEntry;
use Horde\Yaml\Document\Node\MapNode;
use Horde\Yaml\Document\Node\Node;
use Horde\Yaml\Document\Node\ScalarNode;
use Horde\Yaml\Document\Node\SequenceItem;
use Horde\Yaml\Document\Node\SequenceNode;
use Horde\Yaml\Document\YamlDocument;
use Horde\Yaml\Document\YamlFileDumper;
use Horde\Yaml\Document\YamlFileLoader;
use Horde\Yaml\Document\YamlStream;
use Horde\Yaml\Document\YamlStringDumper;

class ChangelogYmlFile implements Stringable
{
    private stdClass $changelogYml;
    private string $originalContent;
    private YamlStream $stream;

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
        // Load YAML representation via the document layer. The
        // document layer preserves comments, blank lines, and
        // formatting on the AST so a future save() can write back
        // byte-identical for untouched parts of the file.
        try {
            $this->stream = (new YamlFileLoader())->load($this->filePath);
            $array = $this->streamToArray($this->stream);
            $array = $this->normalizeChangelogData($array);
            $this->changelogYml = $this->arrayToObject($array);
        } catch (DocumentException $e) {
            throw new InvalidChangelogFileException("Failed to parse YAML: {$this->filePath}", 0, $e);
        }
        $this->originalContent = $content;
    }

    /**
     * Reduce a YamlStream to a plain PHP array shaped like what
     * Yaml::load() returned. An empty stream (zero documents or a
     * null root) yields an empty array.
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
            return [];
        }
        return $value;
    }

    /**
     * Recursively convert a document-layer node into a plain PHP
     * value.
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
        $this->syncStreamFromChangelogYml();
        return (new YamlStringDumper())->dump($this->stream);
    }

    public function save(): void
    {
        $this->syncStreamFromChangelogYml();
        try {
            (new YamlFileDumper())->dump($this->stream, $this->filePath);
        } catch (DocumentException $e) {
            throw new RuntimeException("Failed to write to file: {$this->filePath}", 0, $e);
        }
    }

    /**
     * Reflect the current stdClass facade into the AST. Top-level
     * diff-and-apply: each version key becomes (or replaces) an
     * entry in the document's root MapNode. Trivia around
     * unchanged version entries (file headers, blank lines,
     * inter-version comments) is preserved. When a version
     * entry's value subtree changes, only that subtree is rebuilt;
     * trivia inside a rebuilt subtree is lost.
     *
     * After applying entry-level diffs the children list is
     * reordered to match the stdClass property order, so
     * addVersionEntry's version_compare-based sort surfaces in the
     * emitted YAML.
     */
    private function syncStreamFromChangelogYml(): void
    {
        if ($this->stream->documentCount() === 0) {
            $doc = new YamlDocument();
            $doc->setRootInternal(new MapNode());
            $this->stream->appendInternalDocument($doc);
        }
        $doc = $this->stream->getDocument(0);
        $root = $doc->root();
        if (!$root instanceof MapNode) {
            $newRoot = $this->buildNode($this->changelogYml) ?? new MapNode();
            if (!$newRoot instanceof MapNode
                && !$newRoot instanceof SequenceNode
                && !$newRoot instanceof ScalarNode
            ) {
                $newRoot = new MapNode();
            }
            $doc->setRootInternal($newRoot);
            return;
        }

        $current = (array) $this->changelogYml;
        $existingKeys = [];
        foreach ($root->entries() as $entry) {
            $keyNode = $entry->getKey();
            if ($keyNode instanceof ScalarNode) {
                $keyValue = $keyNode->getValue();
                if ($keyValue !== null) {
                    $existingKeys[(string) $keyValue] = $entry;
                }
            }
        }

        // Remove keys present in AST but absent from the stdClass.
        foreach ($existingKeys as $key => $entry) {
            if (!array_key_exists($key, $current)) {
                $root->removeEntry($entry);
            }
        }

        // Update or insert. New keys land at the position dictated
        // by the stdClass property order: between the previous and
        // the next existing key. Append only when no existing key
        // follows. This makes addVersionEntry's version_compare
        // sort surface in the emitted YAML.
        $stdKeys = array_keys($current);
        foreach ($current as $key => $value) {
            $key = (string) $key;
            $existing = $root->entry($key);
            if ($existing === null) {
                $newNode = $this->buildNode($value) ?? new MapNode();
                $stdIdx = array_search($key, $stdKeys, true);
                $insertedBefore = false;
                if ($stdIdx !== false) {
                    for ($i = $stdIdx + 1; $i < count($stdKeys); $i++) {
                        $followingKey = (string) $stdKeys[$i];
                        $followingEntry = $root->entry($followingKey);
                        if ($followingEntry !== null) {
                            $root->insertEntryBefore($followingEntry, $key, $newNode);
                            $insertedBefore = true;
                            break;
                        }
                    }
                }
                if (!$insertedBefore) {
                    $root->appendChildInternal(new MapEntry(new ScalarNode($key), $newNode));
                }
                continue;
            }
            if ($this->valuesEqual($existing->getValue(), $value)) {
                continue;
            }
            $existing->setValueInternal($this->buildNode($value) ?? new MapNode());
        }
    }

    /**
     * Build a value-position AST node from a plain PHP value.
     */
    private function buildNode(mixed $value): MapNode|SequenceNode|ScalarNode|null
    {
        if ($value instanceof stdClass) {
            $map = new MapNode();
            foreach ((array) $value as $key => $child) {
                $childNode = $this->buildNode($child) ?? new MapNode();
                $entry = new MapEntry(new ScalarNode((string) $key), $childNode);
                $map->appendChildInternal($entry);
            }
            return $map;
        }
        if (is_array($value)) {
            $isList = $value === [] || array_is_list($value);
            if ($isList) {
                $seq = new SequenceNode();
                foreach ($value as $child) {
                    $childNode = $this->buildNode($child) ?? new ScalarNode(null);
                    $seq->appendChildInternal(new SequenceItem($childNode));
                }
                return $seq;
            }
            $map = new MapNode();
            foreach ($value as $key => $child) {
                $childNode = $this->buildNode($child) ?? new MapNode();
                $entry = new MapEntry(new ScalarNode((string) $key), $childNode);
                $map->appendChildInternal($entry);
            }
            return $map;
        }
        if ($value === null || is_scalar($value)) {
            return new ScalarNode($value);
        }
        if ($value instanceof Stringable) {
            return new ScalarNode((string) $value);
        }
        return null;
    }

    /**
     * Compare an existing AST node's logical value against a
     * stdClass-or-array-or-scalar value drawn from the stdClass
     * facade. Returns true when they would emit identically.
     */
    private function valuesEqual(Node $node, mixed $value): bool
    {
        $nodeValue = $this->nodeToArray($node);
        $stdValue = $this->stdClassToArray($value);
        return $nodeValue === $stdValue;
    }

    /**
     * Recursively convert a stdClass-or-array tree into the same
     * shape that nodeToArray produces.
     */
    private function stdClassToArray(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $out = [];
            foreach ((array) $value as $k => $v) {
                $out[(string) $k] = $this->stdClassToArray($v);
            }
            return $out;
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->stdClassToArray($v);
            }
            return $out;
        }
        return $value;
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

        // Sort by version (newest first). Use version_compare so semver
        // pre-release tokens (alpha/beta/RC) are recognized regardless of
        // whether the dash separator is present (e.g. legacy "3.0.0alpha6"
        // mixes correctly with "3.0.0-beta2"). strnatcmp would order these
        // by raw bytes and bury "3.0.0-beta2" below the "3.0.0alphaN" set.
        uksort($changelog, fn($a, $b) => version_compare($b, $a));

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
