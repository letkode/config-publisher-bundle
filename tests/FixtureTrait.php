<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle\Tests;

use Symfony\Component\Filesystem\Filesystem;

trait FixtureTrait
{
    private string $tmp;

    protected function setUpFixture(): void
    {
        $this->tmp = sys_get_temp_dir() . '/config-publisher-' . bin2hex(random_bytes(4));
        mkdir($this->tmp . '/project', 0o777, true);
    }

    protected function tearDownFixture(): void
    {
        new Filesystem()->remove($this->tmp);
    }

    /**
     * Creates an installed-package fixture and returns its path.
     *
     * @param array<string, string>|string|null $publish {destination: source}, a raw invalid value, or null for no definition
     * @param array<string, string>             $files   relative path => content
     */
    protected function package(string $name, array|string|null $publish, array $files = []): string
    {
        $path = $this->tmp . '/vendor/' . $name;
        mkdir($path, 0o777, true);

        $composer = ['name' => $name];
        if (null !== $publish) {
            $composer['extra']['letkode']['publish'] = $publish;
        }
        file_put_contents($path . '/composer.json', json_encode($composer, \JSON_THROW_ON_ERROR));

        foreach ($files as $relative => $content) {
            @mkdir(\dirname($path . '/' . $relative), 0o777, true);
            file_put_contents($path . '/' . $relative, $content);
        }

        return $path;
    }

    protected function projectDir(): string
    {
        return $this->tmp . '/project';
    }
}
