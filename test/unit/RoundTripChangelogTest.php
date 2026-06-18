<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Horde\HordeYmlFile\ChangelogYmlFile;

/**
 * Round-trip fidelity for ChangelogYmlFile.
 *
 * Asserts comments and blank lines around unchanged version
 * entries survive an addVersionEntry call. Asserts a clean
 * load-and-emit reproduces the source byte-for-byte.
 */
#[CoversClass(ChangelogYmlFile::class)]
class RoundTripChangelogTest extends TestCase
{
    private const FIXTURE_DIR = __DIR__ . '/../fixtures/roundtrip';

    public function testChangelogRoundTripsWithoutMutation(): void
    {
        $path = self::FIXTURE_DIR . '/changelog-with-comments.yml';
        $source = file_get_contents($path);

        $file = new ChangelogYmlFile($path);
        $output = (string) $file;

        $this->assertSame($source, $output);
    }

    public function testCommentsSurviveAddingNewVersion(): void
    {
        $path = self::FIXTURE_DIR . '/changelog-with-comments.yml';

        $file = new ChangelogYmlFile($path);
        $file->addVersionEntry('3.1.0', [
            'api' => '3.0.0',
            'state' => ['release' => 'stable', 'api' => 'stable'],
            'date' => '2026-09-01',
            'license' => [
                'identifier' => 'BSD-2-Clause',
                'uri' => 'http://www.horde.org/licenses/bsd',
            ],
            'notes' => 'New stable.',
        ]);
        $output = (string) $file;

        // Header banner survives.
        $this->assertStringContainsString("# Changelog for the Test component.\n", $output);
        $this->assertStringContainsString("# Newest entries first.\n", $output);

        // Inter-entry comment between 3.0.0 and 2.1.0 survives.
        $this->assertStringContainsString("# Pre-release line below.\n", $output);

        // The new version is in the output.
        $this->assertStringContainsString("3.1.0:\n", $output);
        $this->assertStringContainsString('New stable.', $output);

        // Existing entries are still there verbatim.
        $this->assertStringContainsString("3.0.0:\n", $output);
        $this->assertStringContainsString("2.1.0:\n", $output);
    }

    public function testNewVersionLandsAtVersionCompareSortedPosition(): void
    {
        $path = self::FIXTURE_DIR . '/changelog-with-comments.yml';

        $file = new ChangelogYmlFile($path);
        $file->addVersionEntry('3.1.0', [
            'api' => '3.0.0',
            'state' => ['release' => 'stable', 'api' => 'stable'],
            'date' => '2026-09-01',
            'license' => [
                'identifier' => 'BSD-2-Clause',
                'uri' => 'http://www.horde.org/licenses/bsd',
            ],
            'notes' => 'New stable.',
        ]);
        $output = (string) $file;

        // 3.1.0 must appear before 3.0.0 on disk; 3.0.0 must appear
        // before 2.1.0. version_compare order is reflected in the
        // emitted YAML, not just in getVersions().
        $pos310 = strpos($output, '3.1.0:');
        $pos300 = strpos($output, '3.0.0:');
        $pos210 = strpos($output, '2.1.0:');
        $this->assertNotFalse($pos310);
        $this->assertNotFalse($pos300);
        $this->assertNotFalse($pos210);
        $this->assertLessThan($pos300, $pos310);
        $this->assertLessThan($pos210, $pos300);
    }

    /**
     * Regression: when the file on disk is not in version-compare
     * order, add multiple new versions, the emitted YAML must end
     * up sorted by version_compare. This catches the case where a
     * naive insert-before-next-existing-key respects stale AST
     * positions and produces wrong order.
     */
    public function testReorderEntriesWhenSourceIsUnsortedAndNewVersionsAdded(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cl_') . '.yml';
        // Source is intentionally unsorted: 2.0.0 before 3.0.0.
        file_put_contents($path, "---\n2.0.0:\n  notes: old\n3.0.0:\n  notes: middle\n");

        try {
            $file = new ChangelogYmlFile($path);
            $file->addVersionEntry('2.5.0', ['notes' => 'between']);
            $file->addVersionEntry('4.0.0', ['notes' => 'newest']);
            $output = (string) $file;

            $pos400 = strpos($output, '4.0.0:');
            $pos300 = strpos($output, '3.0.0:');
            $pos250 = strpos($output, '2.5.0:');
            $pos200 = strpos($output, '2.0.0:');

            $this->assertNotFalse($pos400);
            $this->assertNotFalse($pos300);
            $this->assertNotFalse($pos250);
            $this->assertNotFalse($pos200);
            $this->assertLessThan($pos300, $pos400);
            $this->assertLessThan($pos250, $pos300);
            $this->assertLessThan($pos200, $pos250);
        } finally {
            @unlink($path);
        }
    }
}
