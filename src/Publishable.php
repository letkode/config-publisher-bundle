<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

/**
 * One file a package offers to copy into the project.
 */
final readonly class Publishable
{
    /**
     * @param string $package     Composer name, e.g. "letkode/locale-bundle"
     * @param string $name        Short name used on the command line, e.g. "locale"
     * @param string $destination Path relative to the project root
     * @param string $source      Absolute path of the file inside the package
     */
    public function __construct(
        public string $package,
        public string $name,
        public string $destination,
        public string $source,
    ) {
    }

    public static function shortName(string $package): string
    {
        $name = str_contains($package, '/') ? substr($package, (int) strrpos($package, '/') + 1) : $package;

        return str_ends_with($name, '-bundle') ? substr($name, 0, -\strlen('-bundle')) : $name;
    }
}
