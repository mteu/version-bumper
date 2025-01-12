<div align="center">

# Version Bumper

[![Coverage](https://img.shields.io/coverallsCoverage/github/eliashaeussler/version-bumper?logo=coveralls)](https://coveralls.io/github/eliashaeussler/version-bumper)
[![Maintainability](https://img.shields.io/codeclimate/maintainability/eliashaeussler/version-bumper?logo=codeclimate)](https://codeclimate.com/github/eliashaeussler/version-bumper/maintainability)
[![CGL](https://img.shields.io/github/actions/workflow/status/eliashaeussler/version-bumper/cgl.yaml?label=cgl&logo=github)](https://github.com/eliashaeussler/version-bumper/actions/workflows/cgl.yaml)
[![Tests](https://img.shields.io/github/actions/workflow/status/eliashaeussler/version-bumper/tests.yaml?label=tests&logo=github)](https://github.com/eliashaeussler/version-bumper/actions/workflows/tests.yaml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/eliashaeussler/version-bumper/php?logo=php)](https://packagist.org/packages/eliashaeussler/version-bumper)

</div>

A Composer plugin to bump project versions during release preparations.
Provides a Composer command `bump-version` and offers an easy-to-use PHP
API for integration in other frameworks.

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/eliashaeussler/version-bumper?label=version&logo=packagist)](https://packagist.org/packages/eliashaeussler/version-bumper)
[![Packagist Downloads](https://img.shields.io/packagist/dt/eliashaeussler/version-bumper?color=brightgreen)](https://packagist.org/packages/eliashaeussler/version-bumper)

```bash
composer require --dev eliashaeussler/version-bumper
```

## ⚡ Usage

### Console command `bump-version`

> [!TIP]
> The `<range>` command option can be omitted if
> [version range auto-detection](#version-range-auto-detection)
> is properly configured.

```bash
$ composer bump-version [<range>] [-c|--config CONFIG] [-r|--release] [--dry-run] [--strict]
```

> [!IMPORTANT]
> Unstable versions (< `1.0.0`) are handled differently.
> Bumping major version increases the second version
> number (`0.1.2` → `0.2.0`) and bumping minor version
> increases the third version number (`0.1.2` → `0.1.3`).

Pass the following options to the console command:

* `<range>`: Version range to be bumped, can be one of:
  - `major`/`maj`: Bump version to next major version
    + stable: `1.2.3` → `2.0.0`
    + unstable: `0.1.2` → `0.2.0`
  - `minor`/`min`: Bump version to next minor version
    + stable: `1.2.3` → `1.3.0`
    + unstable: `0.1.2` → `0.1.3`
  - `next`/`n`/`patch`/`p`: Bump version to next patch version
    + stable: `1.2.3` → `1.2.4`
    + unstable: `0.1.2` → `0.1.3`
  - Explicit version, e.g. `1.3.0`
* `-c`/`--config`: Path to [config file](#-configuration),
  defaults to auto-detection in current working directory,
  can be configured in `composer.json` as well (see
  config section below).
* `-r`/`--release`: Create a new Git tag after versions are
  bumped.
* `--dry-run`: Do not perform any write operations, just
  calculate and display version bumps.
* `--strict`: Fail if any unmatched file pattern is reported.

#### Presets

Config presets can be used to ship preconfigured configuration
for specific project types. When using presets in your config
file, the resulting config will be merged with your main config
object.

Presets are identified by a name and can be customized by preset
options. The available options differ between presets.

##### Available presets

* **Composer package**
  - Identifier: `composer-package`
  - Options:
    + `path` (string, optional): Directory where `composer.json`
      is located, defaults to current directory.
* **NPM package**
  - Identifier: `npm-package`
  - Options:
    + `packageName` (string, required): Name of the package as
      configured in `package.json`.
    + `path` (string, optional): Directory where `package.json`
      is located, defaults to current directory.
* **TYPO3 extension**
  - Identifier: `typo3-extension`
  - Options:
    + `documentation` (boolean or `auto` keyword, optional): Define
      whether or not a ReST documentation is used in the extension.
      By default or if keyword `auto` is used, ReST documentation
      version may be replaced, if existent, but version bumping will
      not fail if a ReST documentation does not exist.

##### Example

```yaml
presets:
  # Short syntax, only identifier is provided
  - composer-package

  # Extended syntax, identifier and options are provided
  - name: npm-package
    options:
      packageName: '@vendor/my-fancy-library'

  # Extended syntax, but without options (not practically relevant, but possible)
  - name: typo3-extension
```

#### Version range auto-detection

Normally, an explicit version range or version is passed to
the `bump-version` command. However, it may become handy if
a version range is auto-detected, based on the Git history.
This sort of auto-detection is automatically triggered if the
`<range>` command option is omitted.

> [!IMPORTANT]
> Auto-detection is only possible if [`versionRangeIndicators`](#version-range-indicators)
> are configured in the config file.

To use the auto-detection feature, make sure to add version
range indicators to your config file:

```yaml
versionRangeIndicators:
  # 1️⃣ Bump major version on breaking changes, determined by commit message
  - range: major
    patterns:
      - type: commitMessage
        pattern: '/^\[!!!]/'

  # 2️⃣ Bump major version if controllers are deleted and API schema changes
  - range: major
    # All configured patterns must match to use this indicator
    strategy: matchAll
    patterns:
      - type: fileDeleted
        pattern: '/^src\/Controller\/.+Controller\.php$/'
      - type: fileModified
        pattern: '/^res\/api\.schema\.json$/'

  # 3️⃣ Bump minor version when new features are added
  - range: minor
    patterns:
      - type: commitMessage
        pattern: '/^\[FEATURE]/'

  # 4️⃣ Bump patch version if maintenance or documentation tasks were performed
  - range: patch
    patterns:
      - type: commitMessage
        pattern: '/^\[TASK]/'
      - type: commitMessage
        pattern: '/^\[BUGFIX]/'
      - type: commitMessage
        pattern: '/^\[DOCS]/'

  # 5️⃣ Bump patch version if no sources have changed
  - range: patch
    # No configured patterns must match to use this indicator
    strategy: matchNone
    patterns:
      - type: fileAdded
        pattern: '/^src\//'
      - type: fileDeleted
        pattern: '/^src\//'
      - type: fileModified
        pattern: '/^src\//'
```

> [!NOTE]
> The matching version range with the highest priority will be
> used as final version range (`major` receives the highest priority).

If no version range indicator matches, the `bump-version`
command will fail.

##### Strategies

The `strategy` config option (see second indicator in the above example)
defines how matching (or non-matching) patterns are treated to
mark the whole indicator as "matching".

By default, an indicator matches if any of the configured
patterns matches (`matchAny`). If all patterns must match,
`matchAll` can be used.

In some cases, it may be useful to define a version range if
no pattern matches. This can be achieved by the `matchNone` strategy.

##### Examples

Using the above example, the following version range would result
if given preconditions are met:

| Commit message                                     | File operations                                                                                  | Matching range                |
|----------------------------------------------------|--------------------------------------------------------------------------------------------------|-------------------------------|
| `[!!!][TASK] Drop support for PHP < 8.3`           | *any*                                                                                            | 1️⃣&nbsp;`major`              |
| *any*                                              | Deleted:&nbsp;`src/Controller/DashboardController.php`<br>Modified:&nbsp;`res/api.schema.json`   | 2️⃣&nbsp;`major`              |
| `[FEATURE] Add support for PHP 8.4`                | *any*                                                                                            | 3️⃣&nbsp;`minor`              |
| `[TASK] Use PHP 8.4 in CI`                         | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| `[BUGFIX] Avoid implicit nullable types`           | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| `[DOCS] Mention PHP 8.4 support in documentation`  | *any*                                                                                            | 4️⃣&nbsp;`patch`              |
| *any*                                              | Modified:&nbsp;`composer.json`<br>Added:`composer.lock`<br>Deleted:&nbsp;`composer.patches.json` | 5️⃣&nbsp;`patch`              |
| `[TASK] Remove deprecated dashboard functionality` | Deleted:&nbsp;`src/Controller/DashboardController.php`<br>Modified:&nbsp;`res/api.schema.json`   | 2️⃣&nbsp;`major`<sup>1)</sup> |
| `[TASK] Remove deprecated dashboard functionality` | Deleted:&nbsp;`src/Controller/DashboardController.php`                                           | 4️⃣&nbsp;`patch`<sup>2)</sup> |
| `[SECURITY] Avoid XSS in dashboard`                | Modified:&nbsp;`src/Controller/DashboardController.php`                                          | –<sup>3)</sup>                |

*Notes:*

<sup>1)</sup> Even if both indicators 2️⃣ and 4️⃣ match, indicator
2️⃣ takes precedence because of the higher version range.

<sup>2)</sup> Indicator 2️⃣ does not match, because only one
pattern matches, and the indicator's strategy is configured
to match all patterns (`matchAll`).

<sup>3)</sup> No indicator contains patterns for either the
commit message or modified file, hence no version range is
detected.


### PHP API

> [!TIP]
> You can use the method argument `$dryRun` in both
> `VersionBumper` and `VersionReleaser` classes to skip any
> write operations (dry-run mode).

#### Bump versions

The main entrypoint of the plugin is the
[`Version\VersionBumper`](src/Version/VersionBumper.php) class:

```php
use EliasHaeussler\VersionBumper;

// Define files and patterns in which to bump new versions
$filesToModify = [
    new VersionBumper\Config\FileToModify(
        'package.json',
        [
            '"version": "{%version%}"',
        ],
    ),
    new VersionBumper\Config\FileToModify(
        'src/Version.php',
        [
            'public const VERSION = \'{%version%}\';',
        ],
    ),
];

// Define package root path and version range
$rootPath = dirname(__DIR__);
$versionRange = VersionBumper\Enum\VersionRange::Minor;

// Bump versions within configured files
$versionBumper = new VersionBumper\Version\VersionBumper();
$results = $versionBumper->bump(
    $filesToModify,
    $rootPath,
    $versionRange,
);

// Display results
foreach ($results as $result) {
    // File: package.json
    echo sprintf('File: %s', $result->file()->path());
    echo PHP_EOL;

    foreach ($result->groupedOperations() as $operations) {
        foreach ($operations as $operation) {
            // Modified: 1.2.3 => 1.3.0
            echo sprintf(
                '%s: %s => %s',
                $operation->state()->name,
                $operation->source(),
                $operation->target(),
            );
            echo PHP_EOL;
        }
    }
}
```

#### Create release

A release can be created by the
[`Version\VersionReleaser`](src/Version/VersionReleaser.php) class:

```php
use EliasHaeussler\VersionBumper;

$options = new VersionBumper\Config\ReleaseOptions(
    tagName: 'v{%version%}', // Create tag with "v" prefix
    signTag: true, // Sign new tags
);

$versionReleaser = new VersionBumper\Version\VersionReleaser();
$result = $versionReleaser->release($results, $rootPath, $options);

echo sprintf(
    'Committed "%s" and tagged "%s" with %d file(s).',
    $result->commitMessage(),
    $result->tagName(),
    count($result->committedFiles()),
);
echo PHP_EOL;
```

#### Auto-detect version range

When bumping files, a respective version range or explicit version
must be provided (see above). The library provides a
[`Version\VersionRangeDetector`](src/Version/VersionRangeDetector.php)
class to automate this step and auto-detect a version range, based
on a set of [`Config\VersionRangeIndicator`](src/Config/VersionRangeIndicator.php)
objects:

```php
use EliasHaeussler\VersionBumper;

$indicators = [
    new VersionBumper\Config\VersionRangeIndicator(
        // Bump major version if any commit contains breaking changes
        // (commit message starts with "[!!!]")
        VersionBumper\Enum\VersionRange::Major,
        [
            new VersionBumper\Config\VersionRangePattern(
                VersionBumper\Enum\VersionRangeIndicatorType::CommitMessage,
                '/^\[!!!]/',
            ),
        ],
    ),
];

$versionRangeDetector = new VersionBumper\Version\VersionRangeDetector();
$versionRange = $versionRangeDetector->detect($rootPath, $indicators);

echo sprintf('Auto-detected version range is "%s".', $versionRange->value);
echo PHP_EOL;
```

## 📝 Configuration

When using the console command, it is required to configure
the write operations which are to be performed by the version
bumper.

### Formats

The following file formats are supported currently:

* `json`
* `yaml`, `yml`

### Schema

The config file must follow a given schema:

```yaml
presets:
  - composer-package
  - name: npm-package
    options:
      packageName: '@vendor/my-fancy-library'

filesToModify:
  - path: relative/or/absolute/path/to/file
    patterns:
      # Each pattern must contain a {%version%} placeholder
      - '"version": "{%version%}"'
    reportUnmatched: true

releaseOptions:
  commitMessage: '[RELEASE] Release of my-fancy-library {%version%}'
  overwriteExistingTag: true
  signTag: true
  tagName: 'v{%version%}'

# Relative (to config file) or absolute path to project root
rootPath: ../

versionRangeIndicators:
  - range: major
    strategy: matchAll
    patterns:
      - type: fileDeleted
        pattern: '/^src\/Controller\/.+Controller\.php$/'
      - type: fileModified
        pattern: '/^res\/api\.schema\.json$/'
      - type: commitMessage
        pattern: '/^\[!!!]/'
```

> [!TIP]
> Have a look at the shipped [JSON schema](res/version-bumper.schema.json).

#### Presets

| Property            | Type                     | Required       | Description                                                                                                                  |
|---------------------|--------------------------|----------------|------------------------------------------------------------------------------------------------------------------------------|
| `presets`           | Array of objects/strings | –              | List of config presets to apply (read more at [Presets](#presets)).                                                          |
| `presets.*`         | String                   | ✅<sup>1)</sup> | Preset identifier, can be used when no additional options are to be configured. Otherwise, take a look at the next property. |
| `presets.*.name`    | String                   | ✅<sup>1)</sup> | Preset identifier, can be used when additional options are to be configured (see next property).                             |
| `presets.*.options` | Object                   | ✅<sup>1)</sup> | Additional preset options. The available options differ from preset to preset.                                               |

<sup>1)</sup> You can either configure presets using string syntax (provide the
preset identifier only) or using object syntax (provide identifier and options).

#### Files to modify

| Property                          | Type             | Required | Description                                                                                                                                                                                                                                                                       |
|-----------------------------------|------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `filesToModify`                   | Array of objects | ✅        | List of files that contain versions which are to be bumped.                                                                                                                                                                                                                       |
| `filesToModify.*.path`            | String           | ✅        | Relative or absolute path to the file. Relative paths are calculated from the configured (or calculated) project root.                                                                                                                                                            |
| `filesToModify.*.patterns`        | Array of strings | ✅        | List of version patterns to be searched and replaced in the configured file. Each pattern must contain a `{%version%}` placeholder that is replaced by the new version. Patterns are internally converted to regular expressions, so feel free to use regex syntax such as `\s+`. |
| `filesToModify.*.reportUnmatched` | Boolean          | –        | Show warning if a configured pattern does not match file contents. Useful in combination with the `--strict` command option.                                                                                                                                                      |
| `filesToModify.*.reportMissing`   | Boolean          | –        | Fail if file to modify does not exist (defaults to `true`).                                                                                                                                                                                                                       |

#### Release options

| Property                              | Type    | Required | Description                                                                                                                         |
|---------------------------------------|---------|----------|-------------------------------------------------------------------------------------------------------------------------------------|
| `releaseOptions`                      | Object  | –        | Set of configuration options to respect when a new release is created (using the `--release` command option).                       |
| `releaseOptions.commitMessage`        | String  | –        | Commit message pattern to use for new releases. May contain a `{%version%}` placeholder that is replaced by the version to release. |
| `releaseOptions.overwriteExistingTag` | Boolean | –        | Overwrite an probably existing tag by deleting it before a new tag is created.                                                      |
| `releaseOptions.signTag`              | Boolean | –        | Use Git's `-s` command option to sign the new tag using the Git-configured signing key.                                             |
| `releaseOptions.tagName`              | String  | –        | Tag name pattern to use for new releases. Must contain a `{%version%}` placeholder that is replaced by the version to release.      |

#### Root path

| Property   | Type   | Required | Description                                                                                                                                                                                                                                         |
|------------|--------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `rootPath` | String | –        | Relative or absolute path to project root. This path will be used to calculate paths to configured files if they are configured as relative paths. If the root path is configured as relative path, it is calculated based on the config file path. |

#### Version range indicators

| Property                                      | Type             | Required | Description                                                                                         |
|-----------------------------------------------|------------------|----------|-----------------------------------------------------------------------------------------------------|
| `versionRangeIndicators`                      | Array of objects | –        | List of indicators to auto-detect a version range to be bumped.                                     |
| `versionRangeIndicators.*.patterns`           | Array of objects | ✅        | List of version range patterns to match for this indicator.                                         |
| `versionRangeIndicators.*.patterns.*.pattern` | String           | ✅        | Regular expression to match a specific version range indicator.                                     |
| `versionRangeIndicators.*.patterns.*.type`    | String (enum)    | ✅        | Type of the pattern to match, can be `commitMessage`, `fileAdded`, `fileDeleted` or `fileModified`. |
| `versionRangeIndicators.*.range`              | String (enum)    | ✅        | Version range to use when patterns match, can be `major`, `minor`, `next` or `patch`.               |
| `versionRangeIndicators.*.strategy`           | String (enum)    | –        | Match strategy for configured patterns, can be `matchAll`, `matchAny` (default) or `matchNone`.     |

### Configuration in `composer.json`

The config file path can be passed as `-c`/`--config` command
option or, alternatively, as configuration in `composer.json`:

```json
{
    "extra": {
        "version-bumper": {
            "config-file": "path/to/version-bumper.json"
        }
    }
}
```

When configured as relative path, the config file path is
calculated based on the location of the `composer.json` file.

### Auto-detection

If no config file is explicitly configured, the config reader
tries to auto-detect its location. The following order is taken
into account during auto-detection:

1. `version-bumper.json`
2. `version-bumper.yaml`
3. `version-bumper.yml`

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
