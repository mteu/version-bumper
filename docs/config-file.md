# Config file

> [!TIP]
> Check out the [schema](schema.md) to learn about all available
> config options.

When using the console command, it is required to configure
the write operations which are to be performed by the version
bumper.

## Formats

The following file formats are supported currently:

* `json`
* `yaml`, `yml`

## Configuration in `composer.json`

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

## Auto-detection

If no config file is explicitly configured, the config reader
tries to auto-detect its location. The following order is taken
into account during auto-detection:

1. `version-bumper.json`
2. `version-bumper.yaml`
3. `version-bumper.yml`
