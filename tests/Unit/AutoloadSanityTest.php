<?php

declare(strict_types=1);

namespace Puzl\PzFeature\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Puzl\PzFeature\Laravel\PzFeatureServiceProvider;
use Puzl\PzFeature\Tests\Support\AutoloadProbe;

/**
 * Sanidade do autoload PSR-4 do pacote.
 *
 * Garante que o namespace base (`Puzl\PzFeature\` -> `src/`) e o namespace de
 * testes (`Puzl\PzFeature\Tests\` -> `tests/`) são resolvidos pelo Composer
 * sem `require` manual.
 */
final class AutoloadSanityTest extends TestCase
{
    public function test_resolve_namespace_base_do_pacote(): void
    {
        self::assertTrue(
            class_exists(PzFeatureServiceProvider::class),
            'O namespace base Puzl\\PzFeature\\ deve resolver para src/ via autoload PSR-4.'
        );
    }

    public function test_classe_do_pacote_pertence_ao_namespace_esperado(): void
    {
        self::assertSame(
            'Puzl\\PzFeature\\Laravel',
            (new \ReflectionClass(PzFeatureServiceProvider::class))->getNamespaceName(),
        );
    }

    public function test_resolve_namespace_de_testes_via_autoload_dev(): void
    {
        self::assertTrue(
            class_exists(AutoloadProbe::class),
            'O namespace Puzl\\PzFeature\\Tests\\ deve resolver para tests/ via autoload-dev.'
        );

        self::assertSame('pzfeature', AutoloadProbe::ping());
    }
}
