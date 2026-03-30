<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

/**
 * Builder for Support objects
 *
 * Provides fluent interface for constructing Support objects.
 *
 * Example:
 *   $support = (new SupportBuilder())
 *       ->email('dev@lists.horde.org')
 *       ->issues('https://github.com/horde/horde/issues')
 *       ->docs('https://www.horde.org/libraries/Horde_Http')
 *       ->build();
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class SupportBuilder
{
    private ?string $email = null;
    private ?string $issues = null;
    private ?string $forum = null;
    private ?string $wiki = null;
    private ?string $chat = null;
    private ?string $docs = null;
    private ?string $source = null;
    private ?string $irc = null;
    private ?string $rss = null;
    private ?string $security = null;

    public function email(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function issues(string $issues): self
    {
        $this->issues = $issues;
        return $this;
    }

    public function forum(string $forum): self
    {
        $this->forum = $forum;
        return $this;
    }

    public function wiki(string $wiki): self
    {
        $this->wiki = $wiki;
        return $this;
    }

    public function chat(string $chat): self
    {
        $this->chat = $chat;
        return $this;
    }

    public function docs(string $docs): self
    {
        $this->docs = $docs;
        return $this;
    }

    public function source(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function irc(string $irc): self
    {
        $this->irc = $irc;
        return $this;
    }

    public function rss(string $rss): self
    {
        $this->rss = $rss;
        return $this;
    }

    public function security(string $security): self
    {
        $this->security = $security;
        return $this;
    }

    public function build(): Support
    {
        return new Support(
            email: $this->email,
            issues: $this->issues,
            forum: $this->forum,
            wiki: $this->wiki,
            chat: $this->chat,
            docs: $this->docs,
            source: $this->source,
            irc: $this->irc,
            rss: $this->rss,
            security: $this->security,
        );
    }
}
