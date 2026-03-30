<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test;

use Horde\HordeYmlFile\Support;
use Horde\HordeYmlFile\SupportBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SupportBuilder::class)]
class SupportBuilderTest extends TestCase
{
    public function testBuilderWithAllFields(): void
    {
        $builder = new SupportBuilder();
        $support = $builder
            ->email('dev@lists.horde.org')
            ->issues('https://github.com/horde/horde/issues')
            ->source('https://github.com/horde/Http')
            ->docs('https://www.horde.org/libraries/Horde_Http')
            ->wiki('https://wiki.horde.org')
            ->forum('https://forum.horde.org')
            ->chat('https://gitter.im/horde')
            ->irc('irc://irc.freenode.net/horde')
            ->rss('https://www.horde.org/feed.xml')
            ->security('https://www.horde.org/security')
            ->build();

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

    public function testBuilderWithPartialFields(): void
    {
        $builder = new SupportBuilder();
        $support = $builder
            ->email('dev@lists.horde.org')
            ->issues('https://github.com/horde/horde/issues')
            ->build();

        $this->assertEquals('dev@lists.horde.org', $support->getEmail());
        $this->assertEquals('https://github.com/horde/horde/issues', $support->getIssues());
        $this->assertNull($support->getSource());
        $this->assertNull($support->getDocs());
        $this->assertNull($support->getWiki());
    }

    public function testBuilderEmptyBuild(): void
    {
        $builder = new SupportBuilder();
        $support = $builder->build();

        $this->assertTrue($support->isEmpty());
        $this->assertNull($support->getEmail());
        $this->assertNull($support->getIssues());
    }

    public function testBuilderChaining(): void
    {
        $support = (new SupportBuilder())
            ->email('test@example.org')
            ->issues('https://example.org/issues')
            ->docs('https://example.org/docs')
            ->build();

        $this->assertEquals('test@example.org', $support->getEmail());
        $this->assertEquals('https://example.org/issues', $support->getIssues());
        $this->assertEquals('https://example.org/docs', $support->getDocs());
    }

    public function testBuilderIndividualFields(): void
    {
        $support = (new SupportBuilder())
            ->email('email')
            ->build();
        $this->assertEquals('email', $support->getEmail());

        $support = (new SupportBuilder())
            ->issues('issues')
            ->build();
        $this->assertEquals('issues', $support->getIssues());

        $support = (new SupportBuilder())
            ->forum('forum')
            ->build();
        $this->assertEquals('forum', $support->getForum());

        $support = (new SupportBuilder())
            ->wiki('wiki')
            ->build();
        $this->assertEquals('wiki', $support->getWiki());

        $support = (new SupportBuilder())
            ->chat('chat')
            ->build();
        $this->assertEquals('chat', $support->getChat());

        $support = (new SupportBuilder())
            ->docs('docs')
            ->build();
        $this->assertEquals('docs', $support->getDocs());

        $support = (new SupportBuilder())
            ->source('source')
            ->build();
        $this->assertEquals('source', $support->getSource());

        $support = (new SupportBuilder())
            ->irc('irc')
            ->build();
        $this->assertEquals('irc', $support->getIrc());

        $support = (new SupportBuilder())
            ->rss('rss')
            ->build();
        $this->assertEquals('rss', $support->getRss());

        $support = (new SupportBuilder())
            ->security('security')
            ->build();
        $this->assertEquals('security', $support->getSecurity());
    }
}
