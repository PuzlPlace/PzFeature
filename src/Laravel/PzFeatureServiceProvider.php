<?php

declare(strict_types=1);

namespace Puzl\PzFeature\Laravel;

use Illuminate\Support\ServiceProvider;
use Puzl\PzFeature\Config\PzFeatureConfig;
use Puzl\PzFeature\Console\FeatureCommand;

/**
 * Service Provider Laravel do PzFeature.
 *
 * Mantém o pacote "plug-and-play" via auto-discovery (declarado em
 * `composer.json` `extra.laravel.providers`):
 *
 * - `register()`: mescla `config/pzfeature.php` em `config('pzfeature.*')` e
 *   registra o {@see PzFeatureConfig} como singleton (resolução única por
 *   processo), hidratado a partir da config mesclada.
 * - `boot()`: apenas quando rodando em console (otimização de memória em runtime
 *   web), registra os `publishes` da config (tag `pzfeature-config`) e dos stubs
 *   (tag `pzfeature-stubs`) e o {@see FeatureCommand} (nome dinâmico via config).
 *
 * Segue o padrão dos pacotes Pz* (PzCurr/PzPdf), com `configPath()`/`basePath()`
 * de fallback para funcionar sob `Illuminate\Container\Container` puro nos testes.
 */
final class PzFeatureServiceProvider extends ServiceProvider
{
    public const CONFIG_PATH = __DIR__.'/../../config/pzfeature.php';

    public const STUBS_PATH = __DIR__.'/../../stubs';

    public const CONFIG_TAG = 'pzfeature-config';

    public const STUBS_TAG = 'pzfeature-stubs';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'pzfeature');

        $this->app->singleton(
            PzFeatureConfig::class,
            static fn ($app): PzFeatureConfig => PzFeatureConfig::fromArray(
                (array) $app->make('config')->get('pzfeature', []),
            ),
        );
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            self::CONFIG_PATH => $this->configPath('pzfeature.php'),
        ], self::CONFIG_TAG);

        $this->publishes([
            self::STUBS_PATH => $this->basePath('stubs/pzfeature'),
        ], self::STUBS_TAG);

        $this->commands([FeatureCommand::class]);
    }

    /**
     * Resolve o destino do publish da config. Em ambiente Laravel completo usa o
     * helper `config_path()`; em testes sob `Illuminate\Container\Container`
     * puro, faz fallback para `basePath('config/...')`.
     */
    protected function configPath(string $file): string
    {
        return function_exists('config_path')
            ? config_path($file)
            : $this->app->basePath('config/'.$file);
    }

    /**
     * Resolve um caminho relativo à base do app. Usa o helper `base_path()`
     * quando disponível; caso contrário, recorre ao `basePath()` do container.
     */
    protected function basePath(string $path): string
    {
        return function_exists('base_path')
            ? base_path($path)
            : $this->app->basePath($path);
    }
}
