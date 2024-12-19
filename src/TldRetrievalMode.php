<?php

declare(strict_types=1);

namespace Rincler\Domain;

use MyCLabs\Enum\Enum;

/**
 * @method static self TRUE()
 * @method static self CUSTOM()
 * @method static self MIX()
 *
 * @extends Enum<self::*>
 */
class TldRetrievalMode extends Enum
{
    private const TRUE = 'true'; // @phpstan-ignore classConstant.unused
    private const CUSTOM = 'custom'; // @phpstan-ignore classConstant.unused
    private const MIX = 'mix'; // @phpstan-ignore classConstant.unused
}
