<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle\Tests\Command;

use Letkode\ConfigPublisherBundle\Command\PublishCommand;
use Letkode\ConfigPublisherBundle\PublishableDiscovery;
use Letkode\ConfigPublisherBundle\Publisher;
use Letkode\ConfigPublisherBundle\Tests\FixtureTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class PublishCommandTest extends TestCase
{
    use FixtureTrait;

    /** @var array<string, string> */
    private array $packages = [];

    protected function setUp(): void
    {
        $this->setUpFixture();
        $this->packages = [
            'letkode/locale-bundle' => $this->package('letkode/locale-bundle', ['config/packages/letkode_locale.yaml' => 'l.dist'], ['l.dist' => 'locale']),
            'letkode/http-exception-bundle' => $this->package('letkode/http-exception-bundle', ['config/packages/letkode_http_exception.yaml' => 'h.dist'], ['h.dist' => 'http']),
        ];
    }

    protected function tearDown(): void
    {
        $this->tearDownFixture();
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new PublishCommand(
            new PublishableDiscovery($this->packages),
            new Publisher($this->projectDir()),
        ));
    }

    public function testPublishesOnePackageByShortName(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['packages' => ['locale']], ['interactive' => false]));
        self::assertFileExists($this->projectDir() . '/config/packages/letkode_locale.yaml');
        self::assertFileDoesNotExist($this->projectDir() . '/config/packages/letkode_http_exception.yaml');
        self::assertStringContainsString('created', $tester->getDisplay());
    }

    public function testPublishesByFullPackageName(): void
    {
        $this->tester()->execute(['packages' => ['letkode/http-exception-bundle']], ['interactive' => false]);

        self::assertFileExists($this->projectDir() . '/config/packages/letkode_http_exception.yaml');
    }

    public function testAllPublishesEveryPackage(): void
    {
        $this->tester()->execute(['--all' => true], ['interactive' => false]);

        self::assertFileExists($this->projectDir() . '/config/packages/letkode_locale.yaml');
        self::assertFileExists($this->projectDir() . '/config/packages/letkode_http_exception.yaml');
    }

    public function testExistingFileIsKeptAndForceOverwritesIt(): void
    {
        $target = $this->projectDir() . '/config/packages/letkode_locale.yaml';
        mkdir(\dirname($target), 0o777, true);
        file_put_contents($target, 'mine');

        $tester = $this->tester();
        $tester->execute(['packages' => ['locale']], ['interactive' => false]);
        self::assertSame('mine', file_get_contents($target));
        self::assertStringContainsString('--force', $tester->getDisplay());

        $tester->execute(['packages' => ['locale'], '--force' => true], ['interactive' => false]);
        self::assertSame('locale', file_get_contents($target));
    }

    public function testDryRunWritesNothing(): void
    {
        $tester = $this->tester();
        $tester->execute(['--all' => true, '--dry-run' => true], ['interactive' => false]);

        self::assertFileDoesNotExist($this->projectDir() . '/config/packages/letkode_locale.yaml');
        self::assertStringContainsString('would create', $tester->getDisplay());
    }

    public function testUnknownPackageFailsBeforeWritingAnything(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['packages' => ['locale', 'nope']], ['interactive' => false]));
        self::assertFileDoesNotExist($this->projectDir() . '/config/packages/letkode_locale.yaml');
        self::assertStringContainsString('"nope"', $tester->getDisplay());
    }

    public function testNoArgumentsNonInteractiveListsPackagesAndFails(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('http-exception', $tester->getDisplay());
        self::assertStringContainsString('locale', $tester->getDisplay());
    }

    public function testNoArgumentsInteractiveAsksWhichToPublish(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['locale']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertFileExists($this->projectDir() . '/config/packages/letkode_locale.yaml');
        self::assertFileDoesNotExist($this->projectDir() . '/config/packages/letkode_http_exception.yaml');
    }

    public function testNamesAndAllTogetherAreRejected(): void
    {
        self::assertSame(Command::INVALID, $this->tester()->execute(['packages' => ['locale'], '--all' => true], ['interactive' => false]));
    }

    public function testAmbiguousShortNameAsksForTheFullName(): void
    {
        $this->packages['acme/locale-bundle'] = $this->package('acme/locale-bundle', ['config/packages/acme_locale.yaml' => 'a.dist'], ['a.dist' => 'acme']);
        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['packages' => ['locale']], ['interactive' => false]));
        self::assertStringContainsString('ambiguous', $tester->getDisplay());
    }

    public function testWarnsWhenNothingIsPublishable(): void
    {
        $this->packages = ['acme/plain' => $this->package('acme/plain', null)];
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['--all' => true], ['interactive' => false]));
        self::assertStringContainsString('No installed package offers files', $tester->getDisplay());
    }
}
