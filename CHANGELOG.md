# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-10-06

### Added
- `bin/console letkode:config:publish`: copies the example files that installed packages declare in `extra.letkode.publish` into the project. Supports package selection by short or full name, `--all`, `--force` and `--dry-run`. Symfony Flex registers the bundle automatically.
- `vendor/bin/letkode-publish`: the same command as a standalone executable, for use without booting the kernel.
