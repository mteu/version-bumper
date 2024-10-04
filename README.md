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

```bash
$ composer bump-version <range> [-c|--config CONFIG] [-r|--release] [--dry-run] [--strict]
```

Pass the following options to the console command:

* `<range>`: Version range to be bumped, can be one of:
  - `major`/`maj`: Bump version to next major version
    (`1.2.3` -> `2.0.0`)
  - `minor`/`min`: Bump version to next minor version
    (`1.2.3` -> `1.3.0`)
  - `next`/`n`/`patch`/`p`: Bump version to next patch
    version (`1.2.3` -> `1.2.4`)
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

### PHP API

The main entrypoint of the plugin is the
[`Version\VersionBumper`](src/Version/VersionBumper.php) class.

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

> [!TIP]
> You can use the method argument `$dryRun` in both
> `VersionBumper` and `VersionReleaser` classes to skip any
> write operations (dry-run mode).

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
```

> [!TIP]
> Have a look at the shipped [JSON schema](res/version-bumper.schema.json).

#### Files to modify

| Property                          | Type             | Required | Description                                                                                                                                                                                                                                                                       |
|-----------------------------------|------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `filesToModify`                   | Array of Objects | ✅        | List of files that contain versions which are to be bumped.                                                                                                                                                                                                                       |
| `filesToModify.*.path`            | String           | ✅        | Relative or absolute path to the file. Relative paths are calculated from the configured (or calculated) project root.                                                                                                                                                            |
| `filesToModify.*.patterns`        | Array of Strings | ✅        | List of version patterns to be searched and replaced in the configured file. Each pattern must contain a `{%version%}` placeholder that is replaced by the new version. Patterns are internally converted to regular expressions, so feel free to use regex syntax such as `\s+`. |
| `filesToModify.*.reportUnmatched` | Boolean          | –        | Show warning if a configured pattern does not match file contents. Useful in combination with the `--strict` command option.                                                                                                                                                      |

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
