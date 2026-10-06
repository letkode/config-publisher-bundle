# letkode/config-publisher-bundle

Copies the example config files that `letkode/*` packages ship into your project, with one command: `bin/console letkode:config:publish`. Think `vendor:publish` from Laravel.

The `letkode/*` bundles work with their defaults and need no files. When you want to change a value, publish the commented example, edit it, done.

---

## Installation

You normally don't install it: every `letkode/*` bundle that has a config file requires it, and Symfony Flex registers it in `config/bundles.php`. To use it on its own:

```bash
composer require letkode/config-publisher-bundle
```

If not using Flex, add it manually:

```php
// config/bundles.php
return [
    Letkode\ConfigPublisherBundle\LetkodeConfigPublisherBundle::class => ['all' => true],
];
```

---

## Usage

```bash
bin/console letkode:config:publish locale                    # one package
bin/console letkode:config:publish locale http-exception     # several
bin/console letkode:config:publish letkode/locale-bundle     # full package name works too
bin/console letkode:config:publish --all                     # every installed package that offers files
bin/console letkode:config:publish                           # lists the available ones and asks
```

The same command is also available without booting the kernel, for projects that are not Symfony apps or when the bundle is not registered: `vendor/bin/letkode-publish locale`.

The short name is the package name without the vendor and the `-bundle` suffix.

| Option | Effect |
|---|---|
| `--force`, `-f` | Overwrite files that already exist. Without it they are kept. |
| `--dry-run` | Show what would be copied, write nothing. |
| `--all`, `-a` | Publish every installed package that offers files. |

Nothing is written if one of the names is unknown or ambiguous.

---

## Making a package publishable

Declare `{destination: source}` in the package's `composer.json`. The destination is relative to the project root; the source is relative to the package.

```json
{
    "extra": {
        "letkode": {
            "publish": {
                "config/packages/letkode_locale.yaml": "resources/config/letkode_locale.yaml.dist"
            }
        }
    }
}
```

That is the only contract: the package needs no code from this one, only the `require` so the command is installed with it.

A destination must be a relative path inside the project (no `..`, no leading `/`) and a source must be a file inside the package, otherwise the command stops with an error naming the package.

---

## License

MIT — see [LICENSE](LICENSE).
