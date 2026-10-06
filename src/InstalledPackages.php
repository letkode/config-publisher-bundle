<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

use Composer\InstalledVersions;

/**
 * The project's installed packages, as seen by Composer's runtime.
 */
final class InstalledPackages
{
    /**
     * @return array<string, string> Composer name => install path
     */
    public static function fromComposer(): array
    {
        $packages = [];

        foreach (InstalledVersions::getInstalledPackages() as $name) {
            $path = InstalledVersions::getInstallPath($name);

            if (null !== $path && false !== ($real = realpath($path))) {
                $packages[$name] = $real;
            }
        }

        return $packages;
    }

    public static function projectDir(): string
    {
        $root = InstalledVersions::getRootPackage()['install_path'];

        return realpath($root) ?: $root;
    }
}
