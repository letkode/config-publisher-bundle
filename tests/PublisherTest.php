<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle\Tests;

use Letkode\ConfigPublisherBundle\Publishable;
use Letkode\ConfigPublisherBundle\Publisher;
use Letkode\ConfigPublisherBundle\PublishResult;
use PHPUnit\Framework\TestCase;

final class PublisherTest extends TestCase
{
    use FixtureTrait;

    private Publishable $publishable;

    protected function setUp(): void
    {
        $this->setUpFixture();
        $path = $this->package('letkode/locale-bundle', null, ['a.dist' => "example\n"]);
        $this->publishable = new Publishable('letkode/locale-bundle', 'locale', 'config/packages/letkode_locale.yaml', $path . '/a.dist');
    }

    protected function tearDown(): void
    {
        $this->tearDownFixture();
    }

    public function testCreatesMissingFileAndItsDirectories(): void
    {
        $result = new Publisher($this->projectDir())->publish($this->publishable);

        self::assertSame(PublishResult::Created, $result);
        self::assertSame("example\n", file_get_contents($this->projectDir() . '/config/packages/letkode_locale.yaml'));
    }

    public function testKeepsExistingFileByDefault(): void
    {
        $target = $this->projectDir() . '/config/packages/letkode_locale.yaml';
        mkdir(\dirname($target), 0o777, true);
        file_put_contents($target, 'mine');

        $result = new Publisher($this->projectDir())->publish($this->publishable);

        self::assertSame(PublishResult::Skipped, $result);
        self::assertSame('mine', file_get_contents($target));
    }

    public function testForceOverwritesExistingFile(): void
    {
        $target = $this->projectDir() . '/config/packages/letkode_locale.yaml';
        mkdir(\dirname($target), 0o777, true);
        file_put_contents($target, 'mine');

        $result = new Publisher($this->projectDir())->publish($this->publishable, force: true);

        self::assertSame(PublishResult::Overwritten, $result);
        self::assertSame("example\n", file_get_contents($target));
    }

    public function testDryRunReportsWithoutWriting(): void
    {
        $result = new Publisher($this->projectDir())->publish($this->publishable, dryRun: true);

        self::assertSame(PublishResult::Created, $result);
        self::assertFileDoesNotExist($this->projectDir() . '/config/packages/letkode_locale.yaml');
    }

    public function testRefusesToReplaceADirectory(): void
    {
        mkdir($this->projectDir() . '/config/packages/letkode_locale.yaml', 0o777, true);

        $this->expectException(\RuntimeException::class);

        new Publisher($this->projectDir())->publish($this->publishable, force: true);
    }
}
