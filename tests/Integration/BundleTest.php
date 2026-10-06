<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle\Tests\Integration;

use Letkode\ConfigPublisherBundle\LetkodeConfigPublisherBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

final class BundleTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/config-publisher-kernel-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0o777, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->dir);
    }

    public function testCommandIsRegisteredInTheConsole(): void
    {
        $kernel = new class($this->dir) extends Kernel {
            public function __construct(private readonly string $dir)
            {
                parent::__construct('test', true);
            }

            public function registerBundles(): iterable
            {
                return [new FrameworkBundle(), new LetkodeConfigPublisherBundle()];
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                $loader->load(static function (\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
                    $container->loadFromExtension('framework', ['secret' => 'test', 'http_method_override' => false]);
                });
            }

            public function getProjectDir(): string
            {
                return $this->dir;
            }

            public function getCacheDir(): string
            {
                return $this->dir . '/cache';
            }

            public function getLogDir(): string
            {
                return $this->dir . '/log';
            }
        };

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $command = $application->find('letkode:config:publish');
        $tester = new CommandTester($command);
        $tester->execute(['--all' => true, '--dry-run' => true], ['interactive' => false]);

        self::assertSame(0, $tester->getStatusCode());
        // Resolved from this repository's own vendor/: none of the dev packages ships a file to publish.
        self::assertStringContainsString('No installed package offers files', $tester->getDisplay());
    }
}
