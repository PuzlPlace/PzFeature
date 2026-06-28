<?php

declare(strict_types=1);

namespace Puzl\PzFeature\Tests\Unit\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzFeature\Config\PzFeatureConfig;
use Puzl\PzFeature\Console\FeatureCommand;
use Puzl\PzFeature\Laravel\PzFeatureServiceProvider;
use Puzl\PzFeature\Tests\Unit\Laravel\Fixtures\AppStub;

/**
 * Exercita o {@see PzFeatureServiceProvider} sob `Illuminate\Container\Container`
 * puro (sem Orchestra Testbench), cobrindo: merge de config + singleton do
 * {@see PzFeatureConfig}, command name dinâmico e as tags de publish
 * (`pzfeature-config` e `pzfeature-stubs`).
 */
final class ServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ServiceProvider::$publishGroups = [];
        ServiceProvider::$publishes = [];
    }

    protected function tearDown(): void
    {
        ServiceProvider::$publishGroups = [];
        ServiceProvider::$publishes = [];
        Container::setInstance(null);
        parent::tearDown();
    }

    private function makeApp(bool $runningInConsole = true): AppStub
    {
        $app = new AppStub($runningInConsole);
        $app->instance('config', new Repository());
        Container::setInstance($app);

        return $app;
    }

    // ── register() — merge de config + singleton ──────────────────────────

    public function test_register_merges_default_config(): void
    {
        $app = $this->makeApp();
        (new PzFeatureServiceProvider($app))->register();

        $config = $app->make('config');
        self::assertSame('App', $config->get('pzfeature.root_namespace'));
        self::assertSame('feature', $config->get('pzfeature.command.name'));
        self::assertSame('Modules', $config->get('pzfeature.segments.modules'));
    }

    public function test_register_binds_pzfeatureconfig_as_singleton(): void
    {
        $app = $this->makeApp();
        (new PzFeatureServiceProvider($app))->register();

        $first = $app->make(PzFeatureConfig::class);
        $second = $app->make(PzFeatureConfig::class);

        self::assertInstanceOf(PzFeatureConfig::class, $first);
        self::assertSame($first, $second);
    }

    public function test_resolved_config_reflects_merged_values(): void
    {
        $app = $this->makeApp();
        (new PzFeatureServiceProvider($app))->register();

        $resolved = $app->make(PzFeatureConfig::class);

        self::assertSame('App', $resolved->rootNamespace());
        self::assertSame('feature', $resolved->commandName());
        self::assertSame('Modules', $resolved->modulesSegment());
    }

    // ── Command name dinâmico ─────────────────────────────────────────────

    public function test_overridden_command_name_propagates_to_command(): void
    {
        $app = $this->makeApp();
        (new PzFeatureServiceProvider($app))->register();

        // Sobrescreve o nome do comando ANTES da primeira resolução do singleton.
        $app->make('config')->set('pzfeature.command.name', 'puzl:scaffold');

        $resolved = $app->make(PzFeatureConfig::class);
        self::assertSame('puzl:scaffold', $resolved->commandName());

        $command = new FeatureCommand($resolved);
        self::assertSame('puzl:scaffold', $command->getName());
    }

    // ── boot() — publishes (apenas em console) ────────────────────────────

    public function test_boot_registers_config_publish_tag(): void
    {
        $app = $this->makeApp(true);
        (new PzFeatureServiceProvider($app))->boot();

        $paths = ServiceProvider::pathsToPublish(PzFeatureServiceProvider::class, PzFeatureServiceProvider::CONFIG_TAG);

        self::assertNotEmpty($paths);
        self::assertTrue(
            $this->publishContainsSource($paths, PzFeatureServiceProvider::CONFIG_PATH),
            'Publish source da config deve apontar para o arquivo de config do pacote.',
        );
    }

    public function test_boot_registers_stubs_publish_tag(): void
    {
        $app = $this->makeApp(true);
        (new PzFeatureServiceProvider($app))->boot();

        $paths = ServiceProvider::pathsToPublish(PzFeatureServiceProvider::class, PzFeatureServiceProvider::STUBS_TAG);

        self::assertNotEmpty($paths);
        self::assertTrue(
            $this->publishContainsSource($paths, PzFeatureServiceProvider::STUBS_PATH),
            'Publish source dos stubs deve apontar para o diretório de stubs do pacote.',
        );
    }

    public function test_boot_does_not_publish_outside_console(): void
    {
        $app = $this->makeApp(false);
        (new PzFeatureServiceProvider($app))->boot();

        self::assertSame([], ServiceProvider::pathsToPublish(PzFeatureServiceProvider::class, PzFeatureServiceProvider::CONFIG_TAG));
        self::assertSame([], ServiceProvider::pathsToPublish(PzFeatureServiceProvider::class, PzFeatureServiceProvider::STUBS_TAG));
    }

    // ── Auto-discovery — composer.json ────────────────────────────────────

    public function test_composer_json_declares_provider_in_auto_discovery(): void
    {
        $composerPath = realpath(__DIR__.'/../../../composer.json');
        self::assertNotFalse($composerPath);

        $manifest = json_decode((string) file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);

        self::assertContains(
            'Puzl\\PzFeature\\Laravel\\PzFeatureServiceProvider',
            $manifest['extra']['laravel']['providers'],
        );
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function publishContainsSource(array $paths, string $expectedSource): bool
    {
        $resolved = realpath($expectedSource);

        foreach (array_keys($paths) as $source) {
            if (realpath($source) === $resolved) {
                return true;
            }
        }

        return false;
    }
}
