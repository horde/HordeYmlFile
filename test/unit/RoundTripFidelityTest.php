<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Horde\HordeYmlFile\HordeYmlFile;

/**
 * Round-trip fidelity for HordeYmlFile.
 *
 * Asserts that loading a .horde.yml that contains comments and
 * blank lines, then emitting it without mutation, reproduces the
 * source byte-for-byte. After mutating one top-level entry, the
 * surrounding comments and blank lines must survive.
 */
#[CoversClass(HordeYmlFile::class)]
class RoundTripFidelityTest extends TestCase
{
    private const FIXTURE_DIR = __DIR__ . '/../fixtures/roundtrip';

    public function testHeaderCommentsRoundTripWithoutMutation(): void
    {
        $path = self::FIXTURE_DIR . '/with-header-comments.horde.yml';
        $source = file_get_contents($path);

        $file = new HordeYmlFile($path);
        $output = (string) $file;

        $this->assertSame($source, $output);
    }

    public function testEolCommentsRoundTripWithoutMutation(): void
    {
        $path = self::FIXTURE_DIR . '/with-eol-comments.horde.yml';
        $source = file_get_contents($path);

        $file = new HordeYmlFile($path);
        $output = (string) $file;

        $this->assertSame($source, $output);
    }

    public function testHeaderCommentsSurviveSingleEntryMutation(): void
    {
        $path = self::FIXTURE_DIR . '/with-header-comments.horde.yml';

        $file = new HordeYmlFile($path);
        $file->setName('Renamed');
        $output = (string) $file;

        // The new name lands at the same position.
        $this->assertStringContainsString("name: Renamed\n", $output);

        // The header banner survives.
        $this->assertStringContainsString("# Component manifest header\n", $output);
        $this->assertStringContainsString("# Maintained by hand. Do not auto-rewrite blindly.\n", $output);

        // Inter-entry comments survive.
        $this->assertStringContainsString("# Bump on every release.\n", $output);
        $this->assertStringContainsString("# Version block\n", $output);
        $this->assertStringContainsString("# State block\n", $output);

        // Untouched entries emit verbatim.
        $this->assertStringContainsString("id: TestComponent\n", $output);
        $this->assertStringContainsString("vendor: horde\n", $output);
        $this->assertStringContainsString("type: library\n", $output);
    }

    public function testEolCommentsSurviveOnUnchangedEntries(): void
    {
        $path = self::FIXTURE_DIR . '/with-eol-comments.horde.yml';

        $file = new HordeYmlFile($path);
        // Mutate vendor only. Other EOL comments stay.
        $file->setVendor('maintaina');
        $output = (string) $file;

        // Mutation took effect.
        $this->assertStringContainsString("vendor: maintaina", $output);

        // EOL comments on unchanged entries survive verbatim.
        $this->assertStringContainsString("id: TestComponent  # the stable component id\n", $output);
        $this->assertStringContainsString("type: library  # always library for our packages\n", $output);
    }

    public function testNewKeyAppendsWithoutDisturbingComments(): void
    {
        $path = self::FIXTURE_DIR . '/with-header-comments.horde.yml';

        $file = new HordeYmlFile($path);
        $file->setHomePage('https://www.horde.org/example');
        $output = (string) $file;

        // New key is in the output.
        $this->assertStringContainsString('homepage: ', $output);
        $this->assertStringContainsString('https://www.horde.org/example', $output);

        // Original comments survive.
        $this->assertStringContainsString("# Component manifest header\n", $output);
        $this->assertStringContainsString("# Bump on every release.\n", $output);
    }

    public function testValueStaysUntouchedWhenSetToSameValue(): void
    {
        // Setting an entry to its current value should be a no-op
        // for round-trip purposes: the AST entry's value node and
        // surrounding trivia stay untouched.
        $path = self::FIXTURE_DIR . '/with-header-comments.horde.yml';
        $source = file_get_contents($path);

        $file = new HordeYmlFile($path);
        $file->setName($file->getName());
        $output = (string) $file;

        $this->assertSame($source, $output);
    }
}
