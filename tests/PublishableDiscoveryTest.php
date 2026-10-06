<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisher\Tests;

use Letkode\ConfigPublisher\Exception\InvalidPublishDefinition;
use Letkode\ConfigPublisher\Publishable;
use Letkode\ConfigPublisher\PublishableDiscovery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublishableDiscoveryTest extends TestCase
{
    use FixtureTrait;

    protected function setUp(): void
    {
        $this->setUpFixture();
    }

    protected function tearDown(): void
    {
        $this->tearDownFixture();
    }

    public function testShortNameDropsVendorAndBundleSuffix(): void
    {
        self::assertSame('locale', Publishable::shortName('letkode/locale-bundle'));
        self::assertSame('http-exception', Publishable::shortName('letkode/http-exception-bundle'));
        self::assertSame('config-publisher', Publishable::shortName('letkode/config-publisher'));
    }

    public function testDiscoversDeclaredFilesAndIgnoresOtherPackages(): void
    {
        $locale = $this->package('letkode/locale-bundle', ['config/packages/letkode_locale.yaml' => 'resources/a.dist'], ['resources/a.dist' => 'x']);
        $plain = $this->package('acme/plain', null);
        $missingJson = $this->tmp . '/vendor/acme/nojson';
        mkdir($missingJson, 0o777, true);

        $found = new PublishableDiscovery([
            'letkode/locale-bundle' => $locale,
            'acme/plain' => $plain,
            'acme/nojson' => $missingJson,
        ])->discover();

        self::assertCount(1, $found);
        self::assertSame('letkode/locale-bundle', $found[0]->package);
        self::assertSame('locale', $found[0]->name);
        self::assertSame('config/packages/letkode_locale.yaml', $found[0]->destination);
        self::assertSame(realpath($locale . '/resources/a.dist'), $found[0]->source);
    }

    public function testResultIsSortedByPackageThenDestination(): void
    {
        $b = $this->package('letkode/b-bundle', ['z.yaml' => 's', 'a.yaml' => 's'], ['s' => '']);
        $a = $this->package('letkode/a-bundle', ['m.yaml' => 's'], ['s' => '']);

        $found = new PublishableDiscovery(['letkode/b-bundle' => $b, 'letkode/a-bundle' => $a])->discover();

        self::assertSame(
            [['letkode/a-bundle', 'm.yaml'], ['letkode/b-bundle', 'a.yaml'], ['letkode/b-bundle', 'z.yaml']],
            array_map(static fn (Publishable $p): array => [$p->package, $p->destination], $found),
        );
    }

    /**
     * @param array<string, string>|string $publish
     */
    #[DataProvider('invalidDefinitions')]
    public function testRejectsInvalidDefinitions(array|string $publish): void
    {
        $path = $this->package('letkode/bad-bundle', $publish, ['ok.dist' => 'x']);
        file_put_contents(\dirname($path, 2) . '/outside.dist', 'secret');

        $this->expectException(InvalidPublishDefinition::class);
        $this->expectExceptionMessage('letkode/bad-bundle');

        new PublishableDiscovery(['letkode/bad-bundle' => $path])->discover();
    }

    /**
     * @return iterable<string, array{array<string, string>|string}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'not an object' => ['config.yaml'];
        yield 'absolute destination' => [['/etc/passwd' => 'ok.dist']];
        yield 'parent segment in destination' => [['config/../../x.yaml' => 'ok.dist']];
        yield 'backslash in destination' => [['config\\x.yaml' => 'ok.dist']];
        yield 'windows drive destination' => [['C:/x.yaml' => 'ok.dist']];
        yield 'empty destination' => [['' => 'ok.dist']];
        yield 'missing source' => [['x.yaml' => 'nope.dist']];
        yield 'source outside the package' => [['x.yaml' => '../../outside.dist']];
        yield 'source is a directory' => [['x.yaml' => '.']];
    }
}
