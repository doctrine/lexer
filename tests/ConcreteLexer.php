<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;
use Override;

use function in_array;
use function is_numeric;

/** @extends AbstractLexer<string, string|int> */
class ConcreteLexer extends AbstractLexer
{
    final public const string INT = 'int';

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function getCatchablePatterns(): array
    {
        return [
            '=|<|>',
            '[a-z]+',
            '\d+',
        ];
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function getNonCatchablePatterns(): array
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    #[Override]
    protected function getType(string|int|float &$value): string
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return 'int';
        }

        if (in_array($value, ['=', '<', '>'])) {
            return 'operator';
        }

        return 'string';
    }
}
