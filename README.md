# horde/hordeymlfile

Handles the .horde.yml and changelog.yml file formats.

## Purpose

The .horde.yml format is the source of truth for composer.json generation and other package meta information.

## Documentation

Format documentation is available at [doc/FORMAT.md](doc/FORMAT.md).

## Origin

Same or similar implementations existed in:
- horde/horde-installer-plugin Composer plugin
- horde/components Developer CLI
- horde/hordectl Admin CLI

Refactoring into a separate library facilitates reuse.