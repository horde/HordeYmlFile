# .horde.yml Format Specification

Version: 1.0
Last Updated: 2026-03-29

## Overview

The `.horde.yml` file is the source of truth for metadata in the horde framework.
It is used to generate various formats (composer.json, website content etc.).

**Location:** Must be named `.horde.yml` and located in the root directory of a component.

**Format:** YAML (YAML Ain't Markup Language) - specifically, the subset supported by the Horde_Yaml library.

## File Structure

```yaml
---
id: string
name: string
vendor: string
full: string
description: string
type: string
homepage: string
list: string
keywords: [string, ...]
version:
  release: string
  api: string
state:
  release: string
  api: string
license:
  identifier: string
  uri: string
authors:
  - name: string
    user: string
    email: string
    active: boolean
    role: string
dependencies:
  uses: string
  required:
    php: string
    ext: {extension: version, ...}
    pear: {package: version, ...}
    composer: {package: version, ...}
  optional:
    ext: {extension: version, ...}
    pear: {package: version, ...}
    composer: {package: version, ...}
  dev:
    composer: {package: version, ...}
autoload:
  psr-0: {namespace: path, ...}
  psr-4: {namespace: path, ...}
  classmap: [path, ...]
  files: [path, ...]
autoload-dev:
  psr-4: {namespace: path, ...}
  classmap: [path, ...]
provides:
  interface: version
allow-plugins:
  plugin/name: boolean
config:
  key: value
extra:
  key: value
commands: [path, ...]
nocommands: [path, ...]
```

## Field Reference

### Core Identification Fields

#### `id` (string, required)
Internal identifier for the component. Typically matches the component name.

**Example:**
```yaml
id: Http
```

#### `name` (string, required)
The component name. Uppercase for libraries, lowercase for apps.

**Example:**
```yaml
name: Http
```

**Generated Composer Name:** Combined with `vendor` to create the composer package name: `vendor/name` (lowercase). 

Example: horde/http

#### `vendor` (string, required)
The vendor/organization name. Defaults to `horde` if not specified.

**Example:**
```yaml
vendor: horde
```

#### `full` (string, required)
The full human-readable name of the component.

**Example:**
```yaml
full: HTTP Client Library
```

#### `description` (string, required)
A detailed description of what the component does. Used as the package description in composer.json.

**Example:**
```yaml
description: Provides HTTP client functionality with support for multiple adapters
```

**Multi-line variant:**
```yaml
description: |
  This package provides HTTP client functionality with support for
  multiple adapters including cURL, sockets, and PSR-18 clients.
```

#### `type` (string, required)
The component type. Determines the composer type and installation behavior.

**Valid values:**
- `library` - Standard PHP composer library. Composer tool may auto-sense horde-library in some cases.
- `horde-library` - Library which needs features of the horde/horde-installer-plugin.
- `application` - Standard PHP composer application. Composer tool may auto-sense to transform to horde-application in some cases.
- `horde-theme` - A Horde Theme package.
- `composer-plugin` - A composer plugin.

**Example:**
```yaml
type: library
```

### Discovery and Metadata Fields

#### `homepage` (string, optional)
URL to the project's homepage or documentation.

**Example:**
```yaml
homepage: https://www.horde.org/libraries/Horde_Http
```

**Default:** `https://www.horde.org` if not specified.

#### `list` (string, optional)
Mailing list identifier. Typically `horde`, `dev`, or vendor-specific.

**Example:**
```yaml
list: horde
```

#### `keywords` (array of strings, optional)
Tags used for package discovery on Packagist and other repositories. Used to improve searchability and categorization.

**Best Practices:**
- Use 3-10 keywords per package
- Use lowercase for consistency
- Include relevant standards (e.g., `psr-7`, `psr-18`)
- Include domain/technology terms (e.g., `http`, `mail`, `database`)
- Avoid redundant keywords (name/vendor already indexed)

**Example:**
```yaml
keywords:
  - http
  - client
  - web
  - psr-7
  - psr-18
  - rest
```

**Empty variant** (explicitly states no keywords):
```yaml
keywords: []
```

**Note:** Omitting the `keywords` field is equivalent to an empty array. Explicit empty key is preferred over omitting.

### Version and State Fields

#### `version` (object, required)
Version information for the component.

**Structure:**
```yaml
version:
  release: string  # Release version (semver or semver-like format)
  api: string      # API version (semver or semver-like format)
```

**Example:**
```yaml
version:
  release: 3.0.1
  api: 3.0.0
```

**Semantic Versioning:** Prefer strict [semver](https://semver.org/) format `MAJOR.MINOR.PATCH[-PRERELEASE]` over semver-like formats.

#### `state` (object, required)
Stability state for release and API.

**Valid states:** `alpha`, `beta`, `stable`

**Structure:**
```yaml
state:
  release: string
  api: string
```

**Example:**
```yaml
state:
  release: stable
  api: stable
```

### Legal and Attribution Fields

#### `license` (object, required)
License information using SPDX identifiers.

**Structure:**
```yaml
license:
  identifier: string  # SPDX license identifier
  uri: string        # URL to license text
```

**Example:**
```yaml
license:
  identifier: LGPL-2.1-only
  uri: http://www.horde.org/licenses/lgpl21
```

**Common SPDX identifiers:**
- `LGPL-2.1-only` - Most Horde libraries
- `GPL-2.0-only` - Some applications
- `BSD-2-Clause` - BSD-licensed components
- `MIT` - MIT-licensed components

#### `authors` (array of objects, required)
List of component authors/maintainers.

**Structure:**
```yaml
authors:
  - name: string     # Full name
    user: string     # Username/handle (optional)
    email: string    # Email address (optional)
    active: boolean  # Currently active maintainer (optional)
    role: string     # Role: lead, developer, contributor (optional)
```

**Example:**
```yaml
authors:
  - name: Jan Schneider
    user: jan
    email: jan@horde.org
    active: true
    role: lead
  - name: Chuck Hagenbuch
    user: chuck
    email: chuck@horde.org
    active: false
    role: lead
```

**Minimal example:**
```yaml
authors:
  - name: Developer Name
    role: lead
```

### Dependency Management

#### `dependencies` (object, optional)
Declares all component dependencies.

**Structure:**
```yaml
dependencies:
  uses: string  # Special dependency mode (optional)
  required:     # Production dependencies
    php: string
    ext: object
    pear: object
    composer: object
  optional:     # Optional/suggested dependencies
    ext: object
    pear: object
    composer: object
  dev:          # Development-only dependencies
    composer: object
```

#### `dependencies.uses` (string, optional)
Special dependency handling mode.

**Valid values:**
- `readonly-parameters` - Use readonly constructor parameters (PHP 8.1+)

**Example:**
```yaml
dependencies:
  uses: readonly-parameters
```

#### `dependencies.required` (object, optional)
Required production dependencies.

**PHP version:**
```yaml
dependencies:
  required:
    php: ^8.2
```

**PHP extensions:**
```yaml
dependencies:
  required:
    ext:
      json: '*'
      mbstring: '*'
      xml: ^8.0
```

**Composer packages:**
```yaml
dependencies:
  required:
    composer:
      horde/exception: ^3
      psr/log: ^3.0
```

**PEAR packages** (legacy):
```yaml
dependencies:
  required:
    pear:
      pear.horde.org/Horde_Exception: '*'
```

**Complete example:**
```yaml
dependencies:
  required:
    php: ^8.2
    ext:
      json: '*'
      mbstring: '*'
    composer:
      horde/exception: ^3
      psr/http-message: ^2
```

#### `dependencies.optional` (object, optional)
Optional dependencies that enhance functionality but are not required.
In composer.json these translate to suggests field

**Example:**
```yaml
dependencies:
  optional:
    ext:
      curl: '*'
      imagick: '*'
    composer:
      horde/cache: ^3
```

#### `dependencies.dev` (object, optional)
Development and testing dependencies.
In composer.json these translate to require-dev field

**Example:**
```yaml
dependencies:
  dev:
    composer:
      phpunit/phpunit: ^12
      phpstan/phpstan: ^2
```

**Note:** We prefer not to list global development tools (phpunit, phpstan, php-cs-fixer) as dependencies in libraries.
They are installed globally via phive or composer. It is however valid and possible to do so, i.e. if your code provides its own plugins to these systems and phpstan needs these for analysis.

### Autoloading Configuration

#### `autoload` (object, optional)
Defines how to autoload the component's classes.

**Structure:**
```yaml
autoload:
  psr-0: {namespace: path}
  psr-4: {namespace: path}
  classmap: [path, ...]
  files: [path, ...]
```

**PSR-4 example** (recommended for new code):
```yaml
autoload:
  psr-4:
    Horde\Http\: src/
```

**PSR-0 example** (legacy Horde libraries):
```yaml
autoload:
  psr-0:
    Horde_Http: lib/
```

**Mixed example:**
```yaml
autoload:
  psr-0:
    Horde_Http: lib/
  psr-4:
    Horde\Http\: src/
```

**Classmap example:**
```yaml
autoload:
  classmap:
    - lib/
```

**Files example:**
```yaml
autoload:
  files:
    - lib/functions.php
```

**Auto-detection:** If `autoload` is not specified:
- If `lib/` directory exists → PSR-0 autoloading from `lib/`
- If `src/` directory exists → PSR-4 autoloading from `src/`

#### `autoload-dev` (object, optional)
Defines autoloading for development/test files.

**Example:**
```yaml
autoload-dev:
  psr-4:
    Horde\Http\Test\: test/
```

**Auto-detection:** If `autoload-dev` is not specified and `test/` directory exists, PSR-4 autoloading is configured automatically.

### Virtual Packages

#### `provides` (object, optional)
Declares that this package provides specific interfaces or virtual packages.

**Example:**
```yaml
provides:
  psr/log-implementation: 3.0.0
  psr/http-client-implementation: 1.0.0
```

**Use case:** Allows other packages to depend on an interface rather than specific implementation.

### Composer Configuration

#### `allow-plugins` (object or boolean, optional)
Controls which Composer plugins are allowed to execute.

**Allow all plugins:**
```yaml
allow-plugins: true
```

**Specific plugins:**
```yaml
allow-plugins:
  horde/horde-installer-plugin: true
  composer/installers: true
```

**Auto-detection:** Known plugins (horde-installer-plugin, composer/installers) are auto-allowed if present in dependencies.

#### `config` (object, optional)
Additional Composer configuration options.

**Example:**
```yaml
config:
  platform:
    php: 8.2.0
  optimize-autoloader: true
```

**Note:** Most config options are not needed in library .horde.yml files.

#### `extra` (object, optional)
Arbitrary data for use by scripts or plugins.

**Example:**
```yaml
extra:
  branch-alias:
    dev-FRAMEWORK_6_0: 3.x-dev
```

**Deep merge:** If the composer generator creates `extra` fields, .horde.yml values are deep-merged on top.

### Binary Commands

#### `commands` (array of strings, optional)
Explicit list of executable commands to expose in `vendor/bin/`.

**Example:**
```yaml
commands:
  - bin/horde-components
  - bin/horde-git-tools
```

**Auto-detection:** If not specified, all executable files in `bin/` directory are automatically included.

#### `nocommands` (array of strings, optional)
Exclusion list of files which explicitly should NOT show up in `vendor/bin/` (applied after auto-detection or `commands`).

**Example:**
```yaml
nocommands:
  - bin/dev-helper
  - bin/local-test
```

## Changelog File

**Note:** The changelog format will be documented in a separate section.

<!-- TBD: Document changelog.yml format -->

## Complete Example

```yaml
---
id: Http
name: Http
vendor: horde
full: HTTP Client Library
description: |
  Provides HTTP client functionality with support for multiple
  adapters including cURL, sockets, and PSR-18 clients.
type: library
homepage: https://www.horde.org/libraries/Horde_Http
list: horde
keywords:
  - http
  - client
  - web
  - psr-7
  - psr-18
  - rest
version:
  release: 3.0.1
  api: 3.0.0
state:
  release: stable
  api: stable
license:
  identifier: LGPL-2.1-only
  uri: http://www.horde.org/licenses/lgpl21
authors:
  - name: Jan Schneider
    user: jan
    email: jan@horde.org
    active: true
    role: lead
  - name: Chuck Hagenbuch
    user: chuck
    email: chuck@horde.org
    active: false
    role: lead
dependencies:
  uses: readonly-parameters
  required:
    php: ^8.2
    ext:
      json: '*'
    composer:
      horde/exception: ^3
      horde/support: ^3
      psr/http-message: ^2
      psr/http-factory: ^1.0.2
      psr/http-client: ^1.0.3
  optional:
    ext:
      curl: '*'
    composer:
      horde/url: ^3
  dev:
    composer:
      phpunit/phpunit: ^12
      phpstan/phpstan: ^2
autoload:
  psr-0:
    Horde_Http: lib/
  psr-4:
    Horde\Http\: src/
autoload-dev:
  psr-4:
    Horde\Http\Test\: test/
provides:
  psr/http-client-implementation: 1.0.0
allow-plugins:
  horde/horde-installer-plugin: true
```

## Minimal Example

The absolute minimum required for a valid .horde.yml:

```yaml
---
id: MyComponent
name: MyComponent
vendor: horde
full: My Component
description: A brief description
type: library
version:
  release: 0.0.1
  api: 0.0.1
state:
  release: alpha
  api: alpha
license:
  identifier: LGPL-2.1-only
  uri: http://www.horde.org/licenses/lgpl21
authors:
  - name: Developer Name
    role: lead
```

## Field Processing Rules

### String Normalization
- **vendor** and **name**: Combined and lowercased for composer package name
- **keywords**: Normalized to lowercase, duplicates removed, empty strings filtered

### Default Values
- **vendor**: Defaults to `horde` if not specified
- **name**: Defaults to directory name if not specified
- **homepage**: Defaults to `https://www.horde.org` if not specified
- **state**: Defaults to `alpha` for both release and API if not specified

### Required vs Optional
Fields marked **required** must be present for successful composer.json generation. Optional fields are only included in output if present in .horde.yml.

## Validation

The HordeYmlFile library performs minimal validation:
- File must exist and be readable
- File must be valid YAML
- No schema validation is performed at library level

Component-level validation (horde-components qc command) performs additional checks:
- Required fields presence
- Version format validity
- License identifier validity
- Author structure completeness

## Usage with horde-components

The .horde.yml file is the primary input for horde-components tool:

**Generate composer.json:**
```bash
horde-components composer
```

**Quality check:**
```bash
horde-components qc
```

**Quality check with auto-fix:**
```bash
horde-components qc --fix
```

**Create release:**
```bash
horde-components release h6
```

## Version History

- **1.0** (2026-03-29): Initial comprehensive documentation
  - Documented all current fields
  - Added keywords field specification
  - Added examples and best practices

## References

- [Composer Schema](https://getcomposer.org/doc/04-schema.md)
- [SPDX License List](https://spdx.org/licenses/)
- [Semantic Versioning](https://semver.org/)
- [PSR Standards](https://www.php-fig.org/psr/)
