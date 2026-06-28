<?php

declare(strict_types=1);

namespace Puzl\PzFeature\Tests\Support;

/**
 * Classe-sonda usada para verificar que o `autoload-dev`
 * (`Puzl\PzFeature\Tests\` -> `tests/`) está corretamente configurado.
 */
final class AutoloadProbe
{
    public static function ping(): string
    {
        return 'pzfeature';
    }
}
