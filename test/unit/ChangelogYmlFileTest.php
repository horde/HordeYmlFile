<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\ChangelogYmlFile;
use Horde\HordeYmlFile\InvalidChangelogFileException;
use Horde\Yaml\Yaml;

/**
 * Comprehensive tests for ChangelogYmlFile.
 *
 * Note: Many tests will initially fail - this is expected and demonstrates
 * the bugs that need to be fixed in the library.
 */
#[CoversClass(ChangelogYmlFile::class)]
class ChangelogYmlFileTest extends TestCase
{
    private string $testDataDir;

    protected function setUp(): void
    {
        $this->testDataDir = dirname(__DIR__) . '/fixtures/generated';
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0755, true);
        }
    }

    // ========== Basic Loading Tests ==========

    public function testLoadValidFile(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => [
                'notes' => 'Initial release',
                'date' => '2024-01-01',
            ],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertTrue($changelog->hasVersion('1.0.0'));
    }

    public function testLoadEmptyFile(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertInstanceOf(ChangelogYmlFile::class, $changelog);
    }

    public function testLoadExistingFixtureChangelog(): void
    {
        $changelog = new ChangelogYmlFile(dirname(__DIR__) . '/fixtures/othervendor/doc/changelog.yml');
        $this->assertInstanceOf(ChangelogYmlFile::class, $changelog);

        // Verify it loads multiple versions
        $this->assertTrue($changelog->hasVersion('2.0.0'));
        $this->assertTrue($changelog->hasVersion('1.5.2'));
        $this->assertTrue($changelog->hasVersion('1.5.0'));
        $this->assertTrue($changelog->hasVersion('1.0.0'));
        $this->assertFalse($changelog->hasVersion('3.0.0'));

        // Verify versions are in correct order (newest first)
        $versions = $changelog->getVersions();
        $this->assertEquals('2.0.0', $versions[0]);
        $this->assertEquals('1.5.2', $versions[1]);
        $this->assertEquals('1.5.0', $versions[2]);
        $this->assertEquals('1.0.0', $versions[3]);

        // Verify we can read version details
        $v200 = $changelog->getVersionEntry('2.0.0');
        $this->assertNotNull($v200);
        $this->assertEquals('2.0.0', $v200->api);
        $this->assertEquals('stable', $v200->state->release);
        $this->assertEquals('2024-03-15', $v200->date);
        $this->assertStringContainsString('Major version 2.0', $v200->notes);
    }

    public function testLoadMissingFileThrowsException(): void
    {
        $this->expectException(InvalidChangelogFileException::class);
        new ChangelogYmlFile('/nonexistent/changelog.yml');
    }

    // ========== Version Checking Tests ==========

    public function testHasVersion(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'Release'],
            '1.1.0' => ['notes' => 'Update'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertTrue($changelog->hasVersion('1.0.0'));
        $this->assertTrue($changelog->hasVersion('1.1.0'));
        $this->assertFalse($changelog->hasVersion('2.0.0'));
    }

    public function testHasVersionReturnsFalseForEmptyChangelog(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertFalse($changelog->hasVersion('1.0.0'));
    }

    // ========== Getting Version Entries Tests ==========

    public function testGetVersionEntry(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => [
                'notes' => 'Initial release',
                'date' => '2024-01-01',
                'security' => false,
            ],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $entry = $changelog->getVersionEntry('1.0.0');

        $this->assertNotNull($entry);
        $this->assertEquals('Initial release', $entry->notes);
        $this->assertEquals('2024-01-01', $entry->date);
        $this->assertFalse($entry->security);
    }

    public function testGetVersionEntryReturnsNullForMissingVersion(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'Release'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertNull($changelog->getVersionEntry('2.0.0'));
    }

    public function testGetVersionEntryWithComplexData(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => [
                'notes' => 'Release notes',
                'date' => '2024-01-01',
                'security' => true,
                'changes' => [
                    ['type' => 'feature', 'description' => 'Added feature'],
                    ['type' => 'bugfix', 'description' => 'Fixed bug'],
                ],
            ],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $entry = $changelog->getVersionEntry('1.0.0');

        $this->assertNotNull($entry);
        $this->assertTrue($entry->security);
        $this->assertIsArray($entry->changes);
        $this->assertCount(2, $entry->changes);
    }

    // ========== Adding Version Entries Tests ==========

    public function testAddVersionEntry(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.0.0', [
            'notes' => 'New release',
            'date' => '2024-01-01',
        ]);

        $this->assertTrue($changelog->hasVersion('1.0.0'));
        $entry = $changelog->getVersionEntry('1.0.0');
        $this->assertEquals('New release', $entry->notes);
        $this->assertEquals('2024-01-01', $entry->date);
    }

    public function testAddVersionEntryToExistingChangelog(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'First release'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.1.0', [
            'notes' => 'Second release',
            'date' => '2024-02-01',
        ]);

        $this->assertTrue($changelog->hasVersion('1.0.0'));
        $this->assertTrue($changelog->hasVersion('1.1.0'));
    }

    public function testAddVersionEntryOverwritesExisting(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'Original notes'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.0.0', [
            'notes' => 'Updated notes',
        ]);

        $entry = $changelog->getVersionEntry('1.0.0');
        $this->assertEquals('Updated notes', $entry->notes);
    }

    // ========== Sorting Tests ==========

    public function testAddVersionEntrySortsNewestFirst(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'First'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('2.0.0', ['notes' => 'Second']);
        $changelog->addVersionEntry('1.5.0', ['notes' => 'Middle']);

        $versions = $changelog->getVersions();
        $this->assertEquals(['2.0.0', '1.5.0', '1.0.0'], $versions);
    }

    public function testAddVersionEntrySortsWithPrereleases(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.0.0', ['notes' => 'Stable']);
        $changelog->addVersionEntry('1.0.0-beta.1', ['notes' => 'Beta']);
        $changelog->addVersionEntry('1.0.0-alpha.1', ['notes' => 'Alpha']);
        $changelog->addVersionEntry('2.0.0', ['notes' => 'Major']);

        $versions = $changelog->getVersions();
        // Natural sort should put 2.0.0 first, then 1.0.0 variants
        $this->assertEquals('2.0.0', $versions[0]);
    }

    // ========== Getting Versions List Tests ==========

    public function testGetVersions(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'First'],
            '1.1.0' => ['notes' => 'Second'],
            '2.0.0' => ['notes' => 'Third'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $versions = $changelog->getVersions();

        $this->assertCount(3, $versions);
        $this->assertContains('1.0.0', $versions);
        $this->assertContains('1.1.0', $versions);
        $this->assertContains('2.0.0', $versions);
    }

    public function testGetVersionsReturnsEmptyArrayForEmptyChangelog(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertEquals([], $changelog->getVersions());
    }

    // ========== Serialization Tests ==========

    public function testToStringReturnsYaml(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => [
                'notes' => 'Release notes',
                'date' => '2024-01-01',
            ],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $yaml = (string)$changelog;

        $this->assertStringContainsString('1.0.0', $yaml);
        $this->assertStringContainsString('notes', $yaml);
        $this->assertStringContainsString('Release notes', $yaml);
    }

    public function testToStringWithMultipleVersions(): void
    {
        $file = $this->createTempChangelog([
            '1.0.0' => ['notes' => 'First'],
            '2.0.0' => ['notes' => 'Second'],
        ]);

        $changelog = new ChangelogYmlFile($file);
        $yaml = (string)$changelog;

        $this->assertStringContainsString('1.0.0', $yaml);
        $this->assertStringContainsString('2.0.0', $yaml);
        $this->assertStringContainsString('First', $yaml);
        $this->assertStringContainsString('Second', $yaml);
    }

    public function testSaveWritesFile(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.0.0', [
            'notes' => 'Test release',
            'date' => '2024-01-01',
        ]);
        $changelog->save();

        // Reload and verify
        $reloaded = new ChangelogYmlFile($file);
        $this->assertTrue($reloaded->hasVersion('1.0.0'));
        $entry = $reloaded->getVersionEntry('1.0.0');
        $this->assertEquals('Test release', $entry->notes);
    }

    public function testSavePreservesComplexStructures(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $changelog->addVersionEntry('1.0.0', [
            'notes' => 'Complex release',
            'date' => '2024-01-01',
            'security' => true,
            'changes' => [
                ['type' => 'feature', 'description' => 'Added feature'],
                ['type' => 'bugfix', 'description' => 'Fixed bug'],
            ],
        ]);
        $changelog->save();

        // Reload and verify
        $reloaded = new ChangelogYmlFile($file);
        $entry = $reloaded->getVersionEntry('1.0.0');
        $this->assertTrue($entry->security);
        $this->assertIsArray($entry->changes);
        $this->assertCount(2, $entry->changes);
    }

    // ========== Array Conversion Tests ==========

    public function testToArray(): void
    {
        $data = [
            '1.0.0' => [
                'notes' => 'First',
                'date' => '2024-01-01',
            ],
            '1.1.0' => [
                'notes' => 'Second',
                'date' => '2024-02-01',
            ],
        ];
        $file = $this->createTempChangelog($data);

        $changelog = new ChangelogYmlFile($file);
        $array = $changelog->toArray();

        $this->assertArrayHasKey('1.0.0', $array);
        $this->assertArrayHasKey('1.1.0', $array);
        $this->assertEquals('First', $array['1.0.0']['notes']);
        $this->assertEquals('Second', $array['1.1.0']['notes']);
    }

    public function testToArrayReturnsEmptyArrayForEmptyChangelog(): void
    {
        $file = $this->createTempChangelog([]);

        $changelog = new ChangelogYmlFile($file);
        $this->assertEquals([], $changelog->toArray());
    }

    // ========== Helper Methods ==========

    private function createTempChangelog(array $data): string
    {
        $file = tempnam($this->testDataDir, 'changelog_') . '.yml';
        if (empty($data)) {
            file_put_contents($file, "---\n");
        } else {
            $yaml = Yaml::dump($data, ['wordwrap' => 0, 'indent' => 2]);
            file_put_contents($file, $yaml);
        }
        return $file;
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->testDataDir)) {
            foreach (glob($this->testDataDir . '/changelog_*') as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
