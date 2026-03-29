# horde/hordeymlfile

Handles the .horde.yml and changelog.yml file formats.

## Purpose

The .horde.yml format is the source of truth for composer.json generation and other package meta information.

## Supported Package Types

- **PHP Libraries** - "library", Standard Composer libraries (PSR-0, PSR-4)
- **Horde Libraries** - "horde-library", libraries with horde-specific handling in the horde/horde-installer-plugin (PSR-0, PSR-4)
- **Horde Applications** - "horde-application", Horde framework applications
- **Applications** - "application", composer default application type
- **PHP Extensions** - "extension", C extensions distributed via PIE (PHP Installer for Extensions)
- **Themes** - "horde-theme", Horde themes
- **Composer Plugins** - Composer plugins

## Documentation

Format documentation is available at [doc/FORMAT.md](doc/FORMAT.md).

## Origin

Same or similar implementations existed in:
- horde/horde-installer-plugin Composer plugin
- horde/components Developer CLI
- horde/hordectl Admin CLI

Refactoring into a separate library facilitates reuse.