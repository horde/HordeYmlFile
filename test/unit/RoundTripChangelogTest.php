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
}
