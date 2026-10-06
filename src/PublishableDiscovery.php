<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

use Letkode\ConfigPublisherBundle\Exception\InvalidPublishDefinition;

/**
 * Reads `extra.letkode.publish` ({destination: source}) from the composer.json of every installed package.
 */
final readonly class PublishableDiscovery
{
    /**
     * @param array<string, string> $packages Composer name => install path
     */
    public function __construct(private array $packages)
    {
    }

    public static function fromComposer(): self
    {
        return new self(InstalledPackages::fromComposer());
    }

    /**
     * @return list<Publishable> Sorted by package, then destination
     */
    public function discover(): array
    {
        $found = [];

        foreach ($this->packages as $package => $path) {
            foreach ($this->definitionOf($package, $path) as $destination => $source) {
                $found[] = $this->build($package, $path, $destination, $source);
            }
        }

        usort($found, static fn (Publishable $a, Publishable $b): int => [$a->package, $a->destination] <=> [$b->package, $b->destination]);

        return $found;
    }

    /**
     * @return array<mixed>
     */
    private function definitionOf(string $package, string $path): array
    {
        $file = $path . '/composer.json';

        if (!is_file($file)) {
            return [];
        }

        $composer = json_decode((string) file_get_contents($file), true);
        $extra = \is_array($composer) ? ($composer['extra'] ?? null) : null;
        $letkode = \is_array($extra) ? ($extra['letkode'] ?? null) : null;
        $definition = \is_array($letkode) ? ($letkode['publish'] ?? null) : null;

        if (null === $definition) {
            return [];
        }

        if (!\is_array($definition)) {
            throw InvalidPublishDefinition::forPackage($package, 'expected an object of {destination: source}.');
        }

        return $definition;
    }

    private function build(string $package, string $path, mixed $destination, mixed $source): Publishable
    {
        if (!\is_string($destination) || !\is_string($source)) {
            throw InvalidPublishDefinition::forPackage($package, 'destination and source must be strings.');
        }

        if (!self::isSafeRelativePath($destination)) {
            throw InvalidPublishDefinition::forPackage($package, \sprintf('destination "%s" must be a relative path inside the project.', $destination));
        }

        $absolute = realpath($path . '/' . $source);
        $root = realpath($path);

        if (false === $absolute || false === $root || !is_file($absolute) || !str_starts_with($absolute, $root . \DIRECTORY_SEPARATOR)) {
            throw InvalidPublishDefinition::forPackage($package, \sprintf('source "%s" is not a file inside the package.', $source));
        }

        return new Publishable($package, Publishable::shortName($package), $destination, $absolute);
    }

    private static function isSafeRelativePath(string $path): bool
    {
        if ('' === $path || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || 1 === preg_match('#^[A-Za-z]:#', $path)) {
            return false;
        }

        return !\in_array('..', explode('/', $path), true);
    }
}
