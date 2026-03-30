<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test;

use Horde\HordeYmlFile\Support;
use Horde\HordeYmlFile\SupportBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Horde\Composer\Support as ComposerSupport;

#[CoversClass(Support::class)]
class SupportTest extends TestCase
{
    public function testConstructorWithAllFields(): void
    {
        $support = new Support(
            email: 'dev@lists.horde.org',
            issues: 'https://github.com/horde/horde/issues',
            source: 'https://github.com/horde/Http',
            docs: 'https://www.horde.org/libraries/Horde_Http',
            wiki: 'https://wiki.horde.org',
            forum: 'https://forum.horde.org',
            chat: 'https://gitter.im/horde',
            irc: 'irc://irc.freenode.net/horde',
            rss: 'https://www.horde.org/feed.xml',
            security: 'https://www.horde.org/security'
        );

        $this->assertEquals('dev@lists.horde.org', $support->getEmail());
        $this->assertEquals('https://github.com/horde/horde/issues', $support->getIssues());
        $this->assertEquals('https://github.com/horde/Http', $support->getSource());
        $this->assertEquals('https://www.horde.org/libraries/Horde_Http', $support->getDocs());
        $this->assertEquals('https://wiki.horde.org', $support->getWiki());
        $this->assertEquals('https://forum.horde.org', $support->getForum());
        $this->assertEquals('https://gitter.im/horde', $support->getChat());
        $this->assertEquals('irc://irc.freenode.net/horde', $support->getIrc());
        $this->assertEquals('https://www.horde.org/feed.xml', $support->getRss());
        $this->assertEquals('https://www.horde.org/security', $support->getSecurity());
    }

    public function testConstructorWithNoFields(): void
    {
        $support = new Support();

        $this->assertNull($support->getEmail());
        $this->assertNull($support->getIssues());
        $this->assertNull($support->getSource());
        $this->assertNull($support->getDocs());
        $this->assertNull($support->getWiki());
        $this->assertNull($support->getForum());
        $this->assertNull($support->getChat());
        $this->assertNull($support->getIrc());
        $this->assertNull($support->getRss());
        $this->assertNull($support->getSecurity());
        $this->assertTrue($support->isEmpty());
    }

    public function testFromStdClass(): void
    {
        $data = new stdClass();
        $data->email = 'test@example.org';
        $data->issues = 'https://example.org/issues';
        $data->docs = 'https://example.org/docs';

        $support = Support::fromStdClass($data);

        $this->assertEquals('test@example.org', $support->getEmail());
        $this->assertEquals('https://example.org/issues', $support->getIssues());
        $this->assertEquals('https://example.org/docs', $support->getDocs());
        $this->assertNull($support->getSource());
        $this->assertNull($support->getWiki());
    }

    public function testFromArray(): void
    {
        $data = [
            'email' => 'test@example.org',
            'issues' => 'https://example.org/issues',
            'wiki' => 'https://example.org/wiki',
        ];

        $support = Support::fromArray($data);

        $this->assertEquals('test@example.org', $support->getEmail());
        $this->assertEquals('https://example.org/issues', $support->getIssues());
        $this->assertEquals('https://example.org/wiki', $support->getWiki());
        $this->assertNull($support->getSource());
    }

    public function testFromArrayRejectsInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid support key: invalid');

        Support::fromArray([
            'email' => 'test@example.org',
            'invalid' => 'value',
        ]);
    }

    public function testToStdClass(): void
    {
        $support = new Support(
            email: 'dev@lists.horde.org',
            issues: 'https://github.com/horde/horde/issues',
        );

        $obj = $support->toStdClass();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertEquals('dev@lists.horde.org', $obj->email);
        $this->assertEquals('https://github.com/horde/horde/issues', $obj->issues);
        $this->assertObjectNotHasProperty('source', $obj);
        $this->assertObjectNotHasProperty('docs', $obj);
    }

    public function testToStdClassOmitsNullFields(): void
    {
        $support = new Support(
            email: 'dev@lists.horde.org',
        );

        $obj = $support->toStdClass();

        $this->assertObjectHasProperty('email', $obj);
        $this->assertObjectNotHasProperty('issues', $obj);
        $this->assertObjectNotHasProperty('source', $obj);
    }

    public function testToArray(): void
    {
        $support = new Support(
            email: 'dev@lists.horde.org',
            docs: 'https://www.horde.org/docs',
        );

        $array = $support->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('dev@lists.horde.org', $array['email']);
        $this->assertEquals('https://www.horde.org/docs', $array['docs']);
        $this->assertArrayNotHasKey('issues', $array);
        $this->assertArrayNotHasKey('source', $array);
    }

    public function testIsEmpty(): void
    {
        $empty = new Support();
        $this->assertTrue($empty->isEmpty());

        $notEmpty = new Support(email: 'test@example.org');
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testGetAllFields(): void
    {
        $support = new Support(
            email: 'email@example.org',
            issues: 'https://example.org/issues',
            forum: 'https://example.org/forum',
            wiki: 'https://example.org/wiki',
            chat: 'https://example.org/chat',
            docs: 'https://example.org/docs',
            source: 'https://example.org/source',
            irc: 'irc://irc.example.org/channel',
            rss: 'https://example.org/rss',
            security: 'https://example.org/security',
        );

        $this->assertEquals('email@example.org', $support->getEmail());
        $this->assertEquals('https://example.org/issues', $support->getIssues());
        $this->assertEquals('https://example.org/forum', $support->getForum());
        $this->assertEquals('https://example.org/wiki', $support->getWiki());
        $this->assertEquals('https://example.org/chat', $support->getChat());
        $this->assertEquals('https://example.org/docs', $support->getDocs());
        $this->assertEquals('https://example.org/source', $support->getSource());
        $this->assertEquals('irc://irc.example.org/channel', $support->getIrc());
        $this->assertEquals('https://example.org/rss', $support->getRss());
        $this->assertEquals('https://example.org/security', $support->getSecurity());
    }

    public function testRoundTripStdClass(): void
    {
        $original = new stdClass();
        $original->email = 'dev@lists.horde.org';
        $original->issues = 'https://github.com/horde/horde/issues';
        $original->docs = 'https://www.horde.org/docs';

        $support = Support::fromStdClass($original);
        $roundtrip = $support->toStdClass();

        $this->assertEquals($original->email, $roundtrip->email);
        $this->assertEquals($original->issues, $roundtrip->issues);
        $this->assertEquals($original->docs, $roundtrip->docs);
    }

    public function testRoundTripArray(): void
    {
        $original = [
            'email' => 'dev@lists.horde.org',
            'source' => 'https://github.com/horde/Http',
        ];

        $support = Support::fromArray($original);
        $roundtrip = $support->toArray();

        $this->assertEquals($original, $roundtrip);
    }

    public function testFromComposerSupport(): void
    {
        $composerSupport = new ComposerSupport(
            email: 'dev@lists.horde.org',
            issues: 'https://github.com/horde/horde/issues',
            docs: 'https://www.horde.org/libraries/Horde_Http',
            source: 'https://github.com/horde/Http',
        );

        $hordeYmlSupport = Support::fromComposerSupport($composerSupport);

        $this->assertSame('dev@lists.horde.org', $hordeYmlSupport->getEmail());
        $this->assertSame('https://github.com/horde/horde/issues', $hordeYmlSupport->getIssues());
        $this->assertSame('https://www.horde.org/libraries/Horde_Http', $hordeYmlSupport->getDocs());
        $this->assertSame('https://github.com/horde/Http', $hordeYmlSupport->getSource());
        $this->assertNull($hordeYmlSupport->getForum());
    }

    public function testToComposerSupport(): void
    {
        $hordeYmlSupport = new Support(
            email: 'dev@lists.horde.org',
            issues: 'https://github.com/horde/horde/issues',
            docs: 'https://www.horde.org/libraries/Horde_Http',
        );

        $composerSupport = $hordeYmlSupport->toComposerSupport();

        $this->assertInstanceOf(ComposerSupport::class, $composerSupport);
        $this->assertSame('dev@lists.horde.org', $composerSupport->email);
        $this->assertSame('https://github.com/horde/horde/issues', $composerSupport->issues);
        $this->assertSame('https://www.horde.org/libraries/Horde_Http', $composerSupport->docs);
        $this->assertNull($composerSupport->forum);
    }

    public function testRoundTripComposerConversion(): void
    {
        $original = new Support(
            email: 'test@example.com',
            issues: 'https://github.com/test/issues',
            wiki: 'https://wiki.test.com',
        );

        $composerSupport = $original->toComposerSupport();
        $converted = Support::fromComposerSupport($composerSupport);

        $this->assertSame($original->getEmail(), $converted->getEmail());
        $this->assertSame($original->getIssues(), $converted->getIssues());
        $this->assertSame($original->getWiki(), $converted->getWiki());
        $this->assertNull($converted->getDocs());
    }
}
