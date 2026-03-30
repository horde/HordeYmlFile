<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use InvalidArgumentException;
use stdClass;
use Horde\Composer\Support as ComposerSupport;

/**
 * Represents the support section of .horde.yml
 *
 * Provides contact and resource information for package users.
 *
 * Example:
 *   support:
 *     email: dev@lists.horde.org
 *     issues: https://github.com/horde/horde/issues
 *     source: https://github.com/horde/Http
 *     docs: https://www.horde.org/libraries/Horde_Http
 *     wiki: https://wiki.horde.org
 *     chat: https://gitter.im/horde/community
 *     forum: https://forum.horde.org
 *     irc: irc://irc.freenode.net/horde
 *     rss: https://www.horde.org/feed.xml
 *     security: https://www.horde.org/security
 *
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
class Support
{
    private const VALID_KEYS = [
        'email', 'issues', 'forum', 'wiki', 'chat',
        'docs', 'source', 'irc', 'rss', 'security',
    ];

    public function __construct(
        private ?string $email = null,
        private ?string $issues = null,
        private ?string $forum = null,
        private ?string $wiki = null,
        private ?string $chat = null,
        private ?string $docs = null,
        private ?string $source = null,
        private ?string $irc = null,
        private ?string $rss = null,
        private ?string $security = null,
    ) {}

    /**
     * Create from Composer\Support value object
     */
    public static function fromComposerSupport(ComposerSupport $support): self
    {
        return new self(
            email: $support->email,
            issues: $support->issues,
            forum: $support->forum,
            wiki: $support->wiki,
            chat: $support->chat,
            docs: $support->docs,
            source: $support->source,
            irc: $support->irc,
            rss: $support->rss,
            security: $support->security,
        );
    }

    /**
     * Create from stdClass representation
     */
    public static function fromStdClass(stdClass $data): self
    {
        return new self(
            email: $data->email ?? null,
            issues: $data->issues ?? null,
            forum: $data->forum ?? null,
            wiki: $data->wiki ?? null,
            chat: $data->chat ?? null,
            docs: $data->docs ?? null,
            source: $data->source ?? null,
            irc: $data->irc ?? null,
            rss: $data->rss ?? null,
            security: $data->security ?? null,
        );
    }

    /**
     * Create from array representation
     */
    public static function fromArray(array $data): self
    {
        // Validate keys
        foreach (array_keys($data) as $key) {
            if (!in_array($key, self::VALID_KEYS, true)) {
                throw new InvalidArgumentException("Invalid support key: {$key}");
            }
        }

        return new self(
            email: $data['email'] ?? null,
            issues: $data['issues'] ?? null,
            forum: $data['forum'] ?? null,
            wiki: $data['wiki'] ?? null,
            chat: $data['chat'] ?? null,
            docs: $data['docs'] ?? null,
            source: $data['source'] ?? null,
            irc: $data['irc'] ?? null,
            rss: $data['rss'] ?? null,
            security: $data['security'] ?? null,
        );
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getIssues(): ?string
    {
        return $this->issues;
    }

    public function getForum(): ?string
    {
        return $this->forum;
    }

    public function getWiki(): ?string
    {
        return $this->wiki;
    }

    public function getChat(): ?string
    {
        return $this->chat;
    }

    public function getDocs(): ?string
    {
        return $this->docs;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getIrc(): ?string
    {
        return $this->irc;
    }

    public function getRss(): ?string
    {
        return $this->rss;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    /**
     * Check if support section is empty
     */
    public function isEmpty(): bool
    {
        return $this->email === null
            && $this->issues === null
            && $this->forum === null
            && $this->wiki === null
            && $this->chat === null
            && $this->docs === null
            && $this->source === null
            && $this->irc === null
            && $this->rss === null
            && $this->security === null;
    }

    /**
     * Convert to stdClass for YAML serialization
     */
    public function toStdClass(): stdClass
    {
        $result = new stdClass();
        if ($this->email !== null) {
            $result->email = $this->email;
        }
        if ($this->issues !== null) {
            $result->issues = $this->issues;
        }
        if ($this->forum !== null) {
            $result->forum = $this->forum;
        }
        if ($this->wiki !== null) {
            $result->wiki = $this->wiki;
        }
        if ($this->chat !== null) {
            $result->chat = $this->chat;
        }
        if ($this->docs !== null) {
            $result->docs = $this->docs;
        }
        if ($this->source !== null) {
            $result->source = $this->source;
        }
        if ($this->irc !== null) {
            $result->irc = $this->irc;
        }
        if ($this->rss !== null) {
            $result->rss = $this->rss;
        }
        if ($this->security !== null) {
            $result->security = $this->security;
        }
        return $result;
    }

    /**
     * Convert to Composer\Support value object
     */
    public function toComposerSupport(): ComposerSupport
    {
        return new ComposerSupport(
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

    /**
     * Convert to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = [];
        if ($this->email !== null) {
            $result['email'] = $this->email;
        }
        if ($this->issues !== null) {
            $result['issues'] = $this->issues;
        }
        if ($this->forum !== null) {
            $result['forum'] = $this->forum;
        }
        if ($this->wiki !== null) {
            $result['wiki'] = $this->wiki;
        }
        if ($this->chat !== null) {
            $result['chat'] = $this->chat;
        }
        if ($this->docs !== null) {
            $result['docs'] = $this->docs;
        }
        if ($this->source !== null) {
            $result['source'] = $this->source;
        }
        if ($this->irc !== null) {
            $result['irc'] = $this->irc;
        }
        if ($this->rss !== null) {
            $result['rss'] = $this->rss;
        }
        if ($this->security !== null) {
            $result['security'] = $this->security;
        }
        return $result;
    }
}
